<?php

namespace Tests\Feature;

use App\Models\LabOrder;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class MedicalRecordTest extends TestCase
{
    use DatabaseTransactions;

    private function makePatient(): Patient
    {
        return Patient::create([
            'patient_code' => 'T-' . Str::random(10),
            'full_name' => 'Record Patient',
            'id_card' => 'T-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(12)),
            'sex' => 'male',
            'date_of_birth' => '1980-01-01',
        ]);
    }

    private function makeRecord(?int $userId, array $attrs = []): MedicalRecord
    {
        return MedicalRecord::create(array_merge([
            'patient_id' => $this->makePatient()->patient_id,
            'user_id' => $userId,
            'visit_date' => now(),
            'diagnosis' => 'Flu',
            'notes' => 'Cough and fever',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 37.5,
        ], $attrs));
    }

    public function test_index_renders_for_doctor(): void
    {
        $doctor = $this->actingAsRole('doctor');
        $this->makeRecord($doctor->id);
        $this->get(route('medical-records.index'))->assertOk();
    }

    public function test_create_redirects_to_index_modal(): void
    {
        $this->actingAsRole('admin');
        $this->get(route('medical-records.create', ['patient_id' => 5]))
            ->assertRedirect(route('medical-records.index', ['create' => 1, 'patient_id' => 5]));
    }

    public function test_patient_search_returns_json(): void
    {
        $this->actingAsRole('doctor');
        $patient = $this->makePatient();

        $this->getJson(route('medical-records.index', ['patient_q' => $patient->patient_code]))
            ->assertOk()
            ->assertJsonFragment(['patient_id' => $patient->patient_id]);
    }

    public function test_store_persists_record_as_author(): void
    {
        $doctor = $this->actingAsRole('doctor');
        $patient = $this->makePatient();

        $this->post(route('medical-records.store'), [
            'patient_id' => $patient->patient_id,
            'visit_date' => now()->format('Y-m-d H:i:s'),
            'diagnosis' => 'Hypertension',
            'bp_systolic' => 150,
            'bp_diastolic' => 95,
            'heart_rate' => 80,
        ])->assertRedirect(route('medical-records.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('medical_records', [
            'patient_id' => $patient->patient_id,
            'user_id' => $doctor->id,
            'diagnosis' => 'Hypertension',
        ]);
    }

    public function test_store_via_ajax_returns_row_html(): void
    {
        $this->actingAsRole('admin');
        $patient = $this->makePatient();

        $this->postJson(route('medical-records.store'), [
            'patient_id' => $patient->patient_id,
            'visit_date' => now()->format('Y-m-d H:i:s'),
            'diagnosis' => 'DMII',
        ])->assertOk()->assertJsonStructure(['message', 'row']);
    }

    public function test_store_rejects_invalid_data(): void
    {
        $this->actingAsRole('doctor');

        $this->postJson(route('medical-records.store'), [
            'patient_id' => 999999999,
            'visit_date' => 'not-a-date',
            'temperature' => 99,
            'heart_rate' => 'fast',
        ])->assertStatus(422)->assertJsonValidationErrors(['patient_id', 'visit_date', 'temperature', 'heart_rate']);
    }

    public function test_show_and_edit_pages_render_for_author(): void
    {
        $doctor = $this->actingAsRole('doctor');
        $record = $this->makeRecord($doctor->id);

        $this->get(route('medical-records.show', $record->record_id))->assertOk()->assertSee('Record Patient');
        $this->get(route('medical-records.edit', $record->record_id))->assertOk();
    }

    public function test_show_renders_with_lab_orders(): void
    {
        $this->actingAsRole('admin');
        $record = $this->makeRecord(null);
        LabOrder::create(['record_id' => $record->record_id, 'order_date' => now(), 'status' => 'pending']);

        $this->get(route('medical-records.show', $record->record_id))->assertOk();
        $this->get(route('medical-records.edit', $record->record_id))->assertOk();
    }

    public function test_other_doctor_cannot_edit_or_update(): void
    {
        $author = User::factory()->create();
        $author->assignRole('doctor');
        $record = $this->makeRecord($author->id);

        $this->actingAsRole('doctor');

        $this->get(route('medical-records.edit', $record->record_id))
            ->assertRedirect(route('medical-records.show', $record->record_id));

        $this->putJson(route('medical-records.update', $record->record_id), ['diagnosis' => 'Hacked'])
            ->assertForbidden();

        $this->assertDatabaseHas('medical_records', ['record_id' => $record->record_id, 'diagnosis' => 'Flu']);
    }

    public function test_update_by_author(): void
    {
        $doctor = $this->actingAsRole('doctor');
        $record = $this->makeRecord($doctor->id);

        $this->put(route('medical-records.update', $record->record_id), [
            'diagnosis' => 'Dengue',
            'temperature' => 39.2,
        ])->assertRedirect(route('medical-records.show', $record->record_id));

        $this->assertDatabaseHas('medical_records', [
            'record_id' => $record->record_id,
            'diagnosis' => 'Dengue',
            'temperature' => 39.2,
            'patient_id' => $record->patient_id,
        ]);
    }

    public function test_update_rejects_invalid_vitals(): void
    {
        $doctor = $this->actingAsRole('doctor');
        $record = $this->makeRecord($doctor->id);

        $this->putJson(route('medical-records.update', $record->record_id), ['spo2' => 150])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['spo2']);
    }

    public function test_destroy_deletes_record_without_children(): void
    {
        $this->actingAsRole('admin');
        $record = $this->makeRecord(null);

        $this->delete(route('medical-records.destroy', $record->record_id))
            ->assertRedirect(route('medical-records.index'));

        $this->assertDatabaseMissing('medical_records', ['record_id' => $record->record_id]);
    }

    public function test_destroy_blocked_when_record_has_lab_orders(): void
    {
        $this->actingAsRole('admin');
        $record = $this->makeRecord(null);
        LabOrder::create(['record_id' => $record->record_id, 'order_date' => now(), 'status' => 'pending']);

        $this->delete(route('medical-records.destroy', $record->record_id))
            ->assertRedirect(route('medical-records.show', $record->record_id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('medical_records', ['record_id' => $record->record_id]);
    }

    public function test_nurse_is_forbidden(): void
    {
        $this->actingAsRole('nurse');
        $record = $this->makeRecord(null);

        $this->get(route('medical-records.index'))->assertForbidden();
        $this->get(route('medical-records.show', $record->record_id))->assertForbidden();
    }
}
