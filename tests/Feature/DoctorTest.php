<?php

namespace Tests\Feature;

use App\Models\LabOrder;
use App\Models\LabTest;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DoctorTest extends TestCase
{
    use DatabaseTransactions;

    private function makeRecord(?int $userId = null, array $attrs = []): MedicalRecord
    {
        $patient = Patient::create([
            'patient_code' => 'T-' . Str::random(10),
            'full_name' => 'Consult Patient',
            'id_card' => 'T-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(12)),
            'sex' => 'female',
            'date_of_birth' => '1970-02-02',
        ]);

        return MedicalRecord::create(array_merge([
            'patient_id' => $patient->patient_id,
            'user_id' => $userId,
            'visit_date' => now(),
            'notes' => 'Headache',
        ], $attrs));
    }

    private function makeMedicineWithStock(): int
    {
        $id = DB::table('medicines')->insertGetId([
            'medicine_name' => 'Paracetamol ' . Str::random(4),
            'unit' => 'tablet',
            'unit_price' => 0.1,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('medicine_batches')->insert([
            'medicine_id' => $id,
            'batch_number' => 'B-' . Str::random(10),
            'expiry_date' => now()->addYear()->toDateString(),
            'quantity_initial' => 100,
            'remaining_quantity' => 100,
            'purchase_price' => 0.05,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return $id;
    }

    public function test_index_renders_for_each_clinical_role(): void
    {
        $this->makeRecord();

        $this->actingAsRole('admin');
        $this->get(route('doctor.index'))->assertOk();

        $this->actingAsRole('nurse');
        $this->get(route('doctor.index'))->assertOk();

        $this->actingAsRole('doctor');
        $this->get(route('doctor.index'))->assertOk();
    }

    public function test_doctor_index_opens_own_record(): void
    {
        $doctor = $this->actingAsRole('doctor');
        $record = $this->makeRecord($doctor->id);

        $this->get(route('doctor.index', ['record_id' => $record->record_id]))
            ->assertOk()->assertSee('Consult Patient');
    }

    public function test_doctor_cannot_open_another_doctors_record(): void
    {
        $other = User::factory()->create();
        $other->assignRole('doctor');
        $record = $this->makeRecord($other->id);

        $this->actingAsRole('doctor');
        $this->get(route('doctor.index', ['record_id' => $record->record_id]))
            ->assertRedirect(route('doctor.index'))->assertSessionHas('error');
    }

    public function test_consultation_redirects_to_workspace(): void
    {
        $this->actingAsRole('doctor');
        $record = $this->makeRecord();

        $this->get(route('doctor.consultation', $record->record_id))
            ->assertRedirect(route('doctor.index', ['record_id' => $record->record_id]));
    }

    public function test_update_saves_diagnosis_and_prescription(): void
    {
        $doctor = $this->actingAsRole('doctor');
        $record = $this->makeRecord();
        $medicineId = $this->makeMedicineWithStock();

        $this->put(route('doctor.update', $record->record_id), [
            'diagnosis' => 'Migraine',
            'status_destination' => 'pharmacy',
            'blood_pressure' => '130/85',
            'items' => [[
                'medicine_id' => $medicineId,
                'quantity' => 10,
                'dosage' => '1 tablet',
                'frequency' => '3x/day',
                'duration_days' => 3,
            ]],
        ])->assertRedirect(route('doctor.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('medical_records', [
            'record_id' => $record->record_id,
            'user_id' => $doctor->id,
            'diagnosis' => 'Migraine',
            'status_destination' => 'pharmacy',
            'bp_systolic' => 130,
            'bp_diastolic' => 85,
        ]);
        $prescription = Prescription::where('record_id', $record->record_id)->first();
        $this->assertNotNull($prescription);
        $this->assertDatabaseHas('prescription_items', [
            'prescription_id' => $prescription->prescription_id,
            'medicine_id' => $medicineId,
            'quantity' => 10,
        ]);
    }

    public function test_update_rejects_invalid_data(): void
    {
        $this->actingAsRole('doctor');
        $record = $this->makeRecord();

        $this->put(route('doctor.update', $record->record_id), [
            'diagnosis' => '',
            'status_destination' => 'nowhere',
            'temperature' => 1000,
            'items' => [['medicine_id' => 999999999, 'quantity' => 0]],
        ])->assertSessionHasErrors(['diagnosis', 'status_destination', 'temperature', 'items.0.medicine_id', 'items.0.quantity']);
    }

    public function test_update_blocked_on_finalized_record(): void
    {
        $doctor = $this->actingAsRole('doctor');
        $record = $this->makeRecord($doctor->id, ['status_destination' => 'done', 'diagnosis' => 'Original']);

        $this->put(route('doctor.update', $record->record_id), [
            'diagnosis' => 'Changed',
            'status_destination' => 'done',
        ])->assertSessionHas('error');

        $this->assertDatabaseHas('medical_records', ['record_id' => $record->record_id, 'diagnosis' => 'Original']);
    }

    public function test_admin_and_nurse_cannot_use_consultation_routes(): void
    {
        $record = $this->makeRecord();

        $this->actingAsRole('admin');
        $this->get(route('doctor.consultation', $record->record_id))->assertForbidden();

        $this->actingAsRole('nurse');
        $this->put(route('doctor.update', $record->record_id), [
            'diagnosis' => 'X', 'status_destination' => 'done',
        ])->assertForbidden();
    }

    public function test_store_lab_order_from_workspace(): void
    {
        $this->actingAsRole('doctor');
        $record = $this->makeRecord();
        $test = LabTest::create(['test_name' => 'CBC ' . Str::random(4), 'price' => 5, 'normal_range' => '1-2']);

        $this->post(route('doctor.lab-order.store'), [
            'record_id' => $record->record_id,
            'test_ids' => [$test->test_id],
        ])->assertSessionHas('success');

        $order = LabOrder::where('record_id', $record->record_id)->with('results')->first();
        $this->assertNotNull($order);
        $this->assertSame($test->test_id, (int) $order->results->first()->test_id);
    }

    public function test_store_lab_order_rejects_unknown_test_with_validation_error(): void
    {
        $this->actingAsRole('doctor');
        $record = $this->makeRecord();

        $this->post(route('doctor.lab-order.store'), [
            'record_id' => $record->record_id,
            'test_ids' => [999999999],
        ])->assertSessionHasErrors(['test_ids.0']);

        $this->assertDatabaseMissing('lab_orders', ['record_id' => $record->record_id]);
    }

    public function test_update_vitals_by_nurse(): void
    {
        $this->actingAsRole('nurse');
        $record = $this->makeRecord();

        $this->post(route('doctor.vitals.update'), [
            'record_id' => $record->record_id,
            'blood_pressure' => '110/70',
            'heart_rate' => 72,
            'temperature' => 36.8,
            'weight' => 60,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('medical_records', [
            'record_id' => $record->record_id,
            'bp_systolic' => 110,
            'bp_diastolic' => 70,
            'heart_rate' => 72,
        ]);

        $this->post(route('doctor.vitals.update'), ['record_id' => $record->record_id, 'heart_rate' => 'abc'])
            ->assertSessionHasErrors(['heart_rate']);

        // Out-of-range values must be rejected, not overflow the DECIMAL(5,2) column.
        $this->post(route('doctor.vitals.update'), ['record_id' => $record->record_id, 'temperature' => 1000])
            ->assertSessionHasErrors(['temperature']);
    }

    public function test_admit_patient_occupies_room(): void
    {
        $this->actingAsRole('doctor');
        $record = $this->makeRecord();
        $room = Room::create([
            'room_number' => 'T' . Str::random(6),
            'room_type' => 'general',
            'status' => 'available',
            'price_per_day' => 20,
        ]);

        $this->post(route('doctor.admit.store'), [
            'patient_id' => $record->patient_id,
            'room_id' => $room->room_id,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('admissions', [
            'patient_id' => $record->patient_id,
            'room_id' => $room->room_id,
            'status' => 'admitted',
        ]);
        $this->assertSame('occupied', $room->fresh()->status);

        // Room no longer available for another patient.
        $other = $this->makeRecord();
        $this->post(route('doctor.admit.store'), [
            'patient_id' => $other->patient_id,
            'room_id' => $room->room_id,
        ])->assertSessionHas('error');
    }

    public function test_medicine_search_returns_in_stock_medicines(): void
    {
        $this->actingAsRole('doctor');
        $id = $this->makeMedicineWithStock();
        $name = DB::table('medicines')->where('medicine_id', $id)->value('medicine_name');

        $this->getJson(route('doctor.medicines.search', ['q' => $name]))
            ->assertOk()->assertJsonFragment(['medicine_id' => $id]);
    }

    public function test_cashier_is_forbidden(): void
    {
        $this->actingAsRole('cashier');
        $this->get(route('doctor.index'))->assertForbidden();
    }
}
