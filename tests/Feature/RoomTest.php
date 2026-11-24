<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\Billing\PatientCharge;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Room;
use App\Services\Billing\RoomChargeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoomTest extends TestCase
{
    use DatabaseTransactions;

    // ---------------------------------------------------------------- helpers

    private function uid(): string
    {
        return strtoupper(Str::random(8));
    }

    private function makeRoom(array $attrs = []): Room
    {
        return Room::create(array_merge([
            'room_number' => 'T-' . $this->uid(),
            'room_type' => 'private',
            'status' => 'available',
            'price_per_day' => 25.50,
        ], $attrs));
    }

    private function makePatient(): Patient
    {
        return Patient::create([
            'patient_code' => 'PT-' . $this->uid(),
            'full_name' => 'Room Patient',
            'id_card' => 'T-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(12)),
            'sex' => 'male',
            'date_of_birth' => '1970-01-01',
        ]);
    }

    /** Admission row created directly (admission_date in the past). */
    private function makeAdmission(Patient $patient, Room $room, $admittedAt, string $status = 'admitted'): Admission
    {
        $a = new Admission([
            'patient_id' => $patient->patient_id,
            'room_id' => $room->room_id,
            'admission_date' => $admittedAt,
            'status' => $status,
        ]);
        $a->admission_number = 'ADM-T-' . $this->uid();
        $a->save();
        $room->update(['status' => 'occupied']);

        return $a;
    }

    private function roomCharges(Admission $a)
    {
        return PatientCharge::where('admission_id', $a->admission_id)->where('source_type', 'room')->orderBy('charge_date')->get();
    }

    // ------------------------------------------------------------ pages/auth

    public function test_room_index_renders_for_allowed_roles(): void
    {
        $this->makeRoom();
        foreach (['admin', 'doctor', 'nurse'] as $role) {
            $this->actingAsRole($role);
            $this->get('/room')->assertOk();
        }
        $this->getJson('/room', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonStructure(['html', 'stats']);
    }

    public function test_disallowed_roles_get_403_on_rooms(): void
    {
        $room = $this->makeRoom();
        foreach (['pharmacist', 'cashier', 'lab_technician'] as $role) {
            $this->actingAsRole($role);
            $this->get('/room')->assertForbidden();
            $this->postJson('/room/store', [])->assertForbidden();
            $this->postJson('/room/admissions/1/discharge')->assertForbidden();
        }
        $this->assertNotNull($room->fresh());
    }

    // ------------------------------------------------------------- room CRUD

    public function test_create_room(): void
    {
        $this->actingAsRole('admin');
        $number = 'R-' . $this->uid();

        $this->postJson('/room/store', [
            'room_number' => $number,
            'room_type' => 'icu',
            'status' => 'available',
            'price_per_day' => 120.75,
        ], ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $room = Room::where('room_number', $number)->firstOrFail();
        $this->assertSame('icu', $room->room_type);
        $this->assertEquals(120.75, (float) $room->price_per_day);

        // Duplicate number / bad type / negative price are rejected.
        $this->postJson('/room/store', [
            'room_number' => $number,
            'room_type' => 'suite',
            'status' => 'available',
            'price_per_day' => -1,
        ])->assertStatus(422)->assertJsonValidationErrors(['room_number', 'room_type', 'price_per_day']);
    }

    public function test_update_room_and_cannot_free_room_with_active_admission(): void
    {
        $this->actingAsRole('admin');
        $room = $this->makeRoom();

        $this->putJson("/room/update/{$room->room_id}", [
            'room_number' => $room->room_number,
            'room_type' => 'general',
            'status' => 'maintenance',
            'price_per_day' => 10,
        ])->assertOk();
        $this->assertSame('maintenance', $room->fresh()->status);

        $room->update(['status' => 'available']);
        $this->makeAdmission($this->makePatient(), $room, now());

        $this->putJson("/room/update/{$room->room_id}", [
            'room_number' => $room->room_number,
            'room_type' => 'general',
            'status' => 'available',
            'price_per_day' => 10,
        ])->assertStatus(422);
        $this->assertSame('occupied', $room->fresh()->status);
    }

    public function test_cannot_delete_room_with_admission_history(): void
    {
        $this->actingAsRole('admin');
        $room = $this->makeRoom();
        $this->makeAdmission($this->makePatient(), $room, now());

        $this->deleteJson("/room/delete/{$room->room_id}")->assertStatus(422);
        $this->assertNotNull($room->fresh());

        $empty = $this->makeRoom();
        $this->deleteJson("/room/delete/{$empty->room_id}")->assertOk();
        $this->assertNull(Room::find($empty->room_id));
    }

    // ----------------------------------------------------- admission + charges

    public function test_admit_patient_occupies_room_and_charges_first_day(): void
    {
        $this->actingAsRole('doctor');
        $room = $this->makeRoom(['price_per_day' => 25.50]);
        $patient = $this->makePatient();

        $this->from('/room')->post('/doctor/admit', [
            'patient_id' => $patient->patient_id,
            'room_id' => $room->room_id,
        ])->assertSessionHas('success');

        $admission = Admission::where('patient_id', $patient->patient_id)->firstOrFail();
        $this->assertSame('admitted', $admission->status);
        $this->assertSame('occupied', $room->fresh()->status);

        $charges = $this->roomCharges($admission);
        $this->assertCount(1, $charges);
        $this->assertEquals(25.50, (float) $charges[0]->total_price);
        $this->assertSame(now()->toDateString(), $charges[0]->charge_date->toDateString());

        // Room is now taken: a second patient cannot be admitted to it.
        $other = $this->makePatient();
        $this->from('/room')->post('/doctor/admit', [
            'patient_id' => $other->patient_id,
            'room_id' => $room->room_id,
        ])->assertSessionHas('error');
        $this->assertFalse(Admission::where('patient_id', $other->patient_id)->exists());
    }

    public function test_room_charge_service_accrues_one_charge_per_day_idempotently(): void
    {
        $this->actingAsRole('admin');
        $room = $this->makeRoom(['price_per_day' => 30]);
        $admission = $this->makeAdmission($this->makePatient(), $room, now()->subDays(3)->setTime(22, 0));

        $svc = app(RoomChargeService::class);
        $this->assertSame(4, $svc->accrueForAdmission($admission));
        $this->assertSame(0, $svc->accrueForAdmission($admission), 'second run creates nothing');

        $charges = $this->roomCharges($admission);
        $this->assertCount(4, $charges);
        $this->assertEquals(120.0, (float) $charges->sum('total_price'));
        $this->assertSame(now()->subDays(3)->toDateString(), $charges->first()->charge_date->toDateString());
        $this->assertSame(now()->toDateString(), $charges->last()->charge_date->toDateString());
    }

    public function test_price_change_does_not_alter_existing_room_charges(): void
    {
        $this->actingAsRole('admin');
        $room = $this->makeRoom(['price_per_day' => 30]);
        $admission = $this->makeAdmission($this->makePatient(), $room, now()->subDay());
        $svc = app(RoomChargeService::class);
        $svc->accrueForAdmission($admission, now()->subDay());

        $room->update(['price_per_day' => 50]);
        $svc->accrueForAdmission($admission->fresh());

        $charges = $this->roomCharges($admission);
        $this->assertEquals([30.0, 50.0], $charges->pluck('total_price')->map(fn ($v) => (float) $v)->all());
    }

    // ------------------------------------------------------------------ discharge

    public function test_discharge_frees_room_and_stops_charges(): void
    {
        $this->actingAsRole('nurse');
        $room = $this->makeRoom(['price_per_day' => 20]);
        $patient = $this->makePatient();
        $admission = $this->makeAdmission($patient, $room, now()->subDays(2));

        $this->postJson("/room/admissions/{$admission->admission_id}/discharge")
            ->assertOk()->assertJsonPath('success', true);

        $admission->refresh();
        $this->assertSame('discharged', $admission->status);
        $this->assertNotNull($admission->discharge_date);
        $this->assertSame('available', $room->fresh()->status);

        // Stay = 3 calendar days (admission day .. discharge day), never more.
        $svc = app(RoomChargeService::class);
        $svc->accrueForAdmission($admission);
        $svc->accrueForAdmission($admission, now()->addDays(10));
        $this->assertCount(3, $this->roomCharges($admission));
        $this->assertEquals(60.0, (float) $this->roomCharges($admission)->sum('total_price'));

        // Second discharge is rejected.
        $this->postJson("/room/admissions/{$admission->admission_id}/discharge")->assertStatus(422);
        $this->postJson('/room/admissions/999999999/discharge')->assertNotFound();
    }

    public function test_discharged_patients_missing_room_days_are_billed_at_checkout(): void
    {
        $this->actingAsRole('admin');
        $room = $this->makeRoom(['price_per_day' => 15.25]);
        $patient = $this->makePatient();
        $admission = $this->makeAdmission($patient, $room, now()->subDays(2));

        // Only the admission day was charged (e.g. cron never ran), then discharge.
        app(RoomChargeService::class)->accrueForAdmission($admission, now()->subDays(2));
        $this->postJson("/room/admissions/{$admission->admission_id}/discharge")->assertOk();
        $this->assertCount(1, $this->roomCharges($admission));

        // Cashier opens the patient: missing days are topped up.
        $this->actingAsRole('cashier');
        $res = $this->getJson("/billing/pending-charges/{$patient->patient_id}")->assertOk();
        $this->assertCount(3, $res->json('items'));
        $this->assertEquals(45.75, $res->json('total'));

        // Invoice from those charges is IPD and totals correctly.
        $ids = collect($res->json('items'))->pluck('charge_id')->all();
        $inv = $this->postJson('/billing/store', [
            'patient_id' => $patient->patient_id,
            'patient_name' => $patient->full_name,
            'charge_ids' => $ids,
        ])->assertStatus(201);

        $invoice = Invoice::findOrFail($inv->json('data.id'));
        $this->assertSame($admission->admission_id, (int) $invoice->admission_id);
        $this->assertEquals(45.75, $invoice->total_amount);

        // Nothing left pending, nothing re-created on the next look.
        $this->getJson("/billing/pending-charges/{$patient->patient_id}")->assertOk()->assertJsonCount(0, 'items');
    }

    public function test_room_can_be_reused_after_discharge(): void
    {
        $this->actingAsRole('admin');
        $room = $this->makeRoom();
        $first = $this->makeAdmission($this->makePatient(), $room, now()->subDay());
        $this->postJson("/room/admissions/{$first->admission_id}/discharge")->assertOk();

        $next = $this->makePatient();
        $this->from('/room')->post('/doctor/admit', [
            'patient_id' => $next->patient_id,
            'room_id' => $room->room_id,
        ])->assertSessionHas('success');

        $this->assertSame('occupied', $room->fresh()->status);
        $this->assertTrue(Admission::where('patient_id', $next->patient_id)->where('status', 'admitted')->exists());
    }

    public function test_discharge_leaves_maintenance_room_untouched(): void
    {
        $this->actingAsRole('admin');
        $room = $this->makeRoom();
        $admission = $this->makeAdmission($this->makePatient(), $room, now());
        $room->update(['status' => 'maintenance']);

        $this->postJson("/room/admissions/{$admission->admission_id}/discharge")->assertOk();
        $this->assertSame('maintenance', $room->fresh()->status);
    }
}
