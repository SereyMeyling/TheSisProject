<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use DatabaseTransactions;

    private function makePatient(): Patient
    {
        return Patient::create([
            'patient_code' => 'T-' . Str::random(10),
            'full_name' => 'Appt Patient',
            'id_card' => 'T-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(12)),
            'sex' => 'female',
            'date_of_birth' => '1992-03-04',
        ]);
    }

    private function makeDoctor(): User
    {
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');
        return $doctor;
    }

    private function makeAppointment(array $attrs = []): Appointment
    {
        return Appointment::create(array_merge([
            'patient_id' => $this->makePatient()->patient_id,
            'user_id' => $this->makeDoctor()->id,
            'appointment_date' => now()->addDays(3)->setTime(10, 0),
            'status' => 'scheduled',
            'reason' => 'Checkup',
        ], $attrs));
    }

    public function test_index_renders_for_nurse(): void
    {
        $this->actingAsRole('nurse');
        $this->makeAppointment();
        $this->get(route('appointment.index'))->assertOk();
    }

    public function test_index_ajax_returns_json(): void
    {
        $this->actingAsRole('admin');
        $this->getJson(route('appointment.index', ['status' => 'overdue']))
            ->assertOk()
            ->assertJsonStructure(['html', 'total', 'scheduled', 'completed', 'cancelled', 'overdue']);
    }

    public function test_store_persists_appointment(): void
    {
        $this->actingAsRole('admin');
        $patient = $this->makePatient();
        $doctor = $this->makeDoctor();
        $date = now()->addDays(2)->setTime(9, 0)->format('Y-m-d H:i:s');

        $this->from(route('appointment.index'))->post(route('appointment.store'), [
            'patient_id' => $patient->patient_id,
            'user_id' => $doctor->id,
            'appointment_date' => $date,
            'status' => 'scheduled',
            'reason' => 'Follow up',
        ])->assertRedirect(route('appointment.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->patient_id,
            'user_id' => $doctor->id,
            'appointment_date' => $date,
            'status' => 'scheduled',
        ]);
    }

    public function test_doctor_store_is_assigned_to_self(): void
    {
        $me = $this->actingAsRole('doctor');
        $other = $this->makeDoctor();
        $patient = $this->makePatient();

        $this->post(route('appointment.store'), [
            'patient_id' => $patient->patient_id,
            'user_id' => $other->id,
            'appointment_date' => now()->addDays(2)->setTime(11, 0)->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', ['patient_id' => $patient->patient_id, 'user_id' => $me->id]);
    }

    public function test_store_rejects_invalid_data(): void
    {
        $this->actingAsRole('admin');
        $patient = $this->makePatient();
        $notADoctor = User::factory()->create();
        $notADoctor->assignRole('nurse');

        // past date
        $this->post(route('appointment.store'), [
            'patient_id' => $patient->patient_id,
            'user_id' => $this->makeDoctor()->id,
            'appointment_date' => now()->subDay()->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
        ])->assertSessionHas('error');

        // user is not a doctor
        $this->post(route('appointment.store'), [
            'patient_id' => $patient->patient_id,
            'user_id' => $notADoctor->id,
            'appointment_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
        ])->assertSessionHas('error');

        // bad status
        $this->post(route('appointment.store'), [
            'patient_id' => $patient->patient_id,
            'user_id' => $this->makeDoctor()->id,
            'appointment_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'status' => 'bogus',
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('appointments', ['patient_id' => $patient->patient_id]);
    }

    public function test_store_rejects_double_booking(): void
    {
        $this->actingAsRole('admin');
        $existing = $this->makeAppointment();

        $this->post(route('appointment.store'), [
            'patient_id' => $this->makePatient()->patient_id,
            'user_id' => $existing->user_id,
            'appointment_date' => $existing->appointment_date->copy()->addMinutes(10)->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
        ])->assertSessionHas('error');

        $this->assertSame(1, Appointment::where('user_id', $existing->user_id)->count());
    }

    public function test_show_and_edit_render(): void
    {
        $this->actingAsRole('admin');
        $appt = $this->makeAppointment();

        $this->get(route('appointment.show', $appt->appointment_id))->assertOk()->assertSee('Appt Patient');
        $this->getJson(route('appointment.edit', $appt->appointment_id))
            ->assertOk()
            ->assertJson(['appointment_id' => $appt->appointment_id, 'status' => 'scheduled']);
    }

    public function test_doctor_cannot_edit_another_doctors_appointment(): void
    {
        $this->actingAsRole('doctor');
        $appt = $this->makeAppointment();

        $this->getJson(route('appointment.edit', $appt->appointment_id))->assertForbidden();
    }

    public function test_update_changes_status_and_resets_reminder_on_date_change(): void
    {
        $this->actingAsRole('nurse');
        $appt = $this->makeAppointment();
        $appt->forceFill(['reminder_called_at' => now()])->save();

        $newDate = now()->addDays(4)->setTime(14, 0)->format('Y-m-d H:i:s');
        $this->put(route('appointment.update', $appt->appointment_id), [
            'patient_id' => $appt->patient_id,
            'user_id' => $appt->user_id,
            'appointment_date' => $newDate,
            'status' => 'completed',
            'reason' => 'Done',
        ])->assertSessionHas('success');

        $appt->refresh();
        $this->assertSame('completed', $appt->status);
        $this->assertSame($newDate, $appt->appointment_date->format('Y-m-d H:i:s'));
        $this->assertNull($appt->reminder_called_at);
    }

    public function test_update_to_cancelled(): void
    {
        $this->actingAsRole('admin');
        $appt = $this->makeAppointment();

        $this->put(route('appointment.update', $appt->appointment_id), [
            'patient_id' => $appt->patient_id,
            'user_id' => $appt->user_id,
            'appointment_date' => $appt->appointment_date->format('Y-m-d H:i:s'),
            'status' => 'cancelled',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', ['appointment_id' => $appt->appointment_id, 'status' => 'cancelled']);
    }

    public function test_destroy_deletes_appointment(): void
    {
        $this->actingAsRole('admin');
        $appt = $this->makeAppointment();

        $this->delete(route('appointment.destroy', $appt->appointment_id))->assertSessionHas('success');

        $this->assertDatabaseMissing('appointments', ['appointment_id' => $appt->appointment_id]);
    }

    public function test_cashier_is_forbidden_from_appointments(): void
    {
        $this->actingAsRole('cashier');
        $this->get(route('appointment.index'))->assertForbidden();
    }

    // ---- Reminders (cashier) ----

    public function test_reminders_page_lists_tomorrows_appointments_for_cashier(): void
    {
        $this->actingAsRole('cashier');
        $this->makeAppointment(['appointment_date' => now()->addDay()->setTime(9, 30)]);

        $this->get(route('appointment.reminders'))->assertOk()->assertSee('Appt Patient');
    }

    public function test_mark_called_records_cashier(): void
    {
        $cashier = $this->actingAsRole('cashier');
        $appt = $this->makeAppointment(['appointment_date' => now()->addDay()->setTime(9, 30)]);

        $this->postJson(route('appointment.reminders.called', $appt->appointment_id))
            ->assertOk()
            ->assertJson(['by' => $cashier->name]);

        $appt->refresh();
        $this->assertNotNull($appt->reminder_called_at);
        $this->assertSame($cashier->id, (int) $appt->reminder_called_by);
    }

    public function test_mark_called_rejects_non_scheduled(): void
    {
        $this->actingAsRole('cashier');
        $appt = $this->makeAppointment(['status' => 'cancelled']);

        $this->postJson(route('appointment.reminders.called', $appt->appointment_id))->assertStatus(422);
    }

    public function test_nurse_is_forbidden_from_reminders(): void
    {
        $this->actingAsRole('nurse');
        $this->get(route('appointment.reminders'))->assertForbidden();
    }
}
