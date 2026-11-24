<?php

namespace Tests\Feature;

use App\Models\Patient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use DatabaseTransactions;

    private function makePatient(array $attrs = []): Patient
    {
        return Patient::create(array_merge([
            'patient_code' => 'T-' . Str::random(10),
            'full_name' => 'Test Patient',
            'id_card' => 'ID' . Str::upper(Str::random(10)),
            'sex' => 'male',
            'date_of_birth' => '1990-01-01',
            'phone' => '012345678',
            'address' => 'Phnom Penh',
        ], $attrs));
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'New Patient ' . Str::random(6),
            'id_card' => 'ID-' . Str::random(6),
            'date_of_birth' => '1985-05-20',
            'sex' => 'Female',
            'phone' => '098765432',
            'address' => 'Siem Reap',
        ], $overrides);
    }

    public function test_index_renders_for_nurse(): void
    {
        $this->actingAsRole('nurse');
        $this->get(route('patients.index'))->assertOk();
    }

    public function test_create_page_renders(): void
    {
        $this->actingAsRole('admin');
        $this->get(route('patients.create'))->assertOk();
    }

    public function test_store_persists_patient_with_generated_code(): void
    {
        $this->actingAsRole('cashier');
        $payload = $this->validPayload();

        $this->post(route('patients.store'), $payload)
            ->assertRedirect(route('patients.index'))
            ->assertSessionHas('success');

        $patient = Patient::where('full_name', $payload['full_name'])->first();
        $this->assertNotNull($patient);
        $this->assertSame('female', $patient->sex);
        $this->assertStringStartsWith('#PT-' . date('Y') . '-', $patient->patient_code);
    }

    public function test_store_rejects_invalid_data(): void
    {
        $this->actingAsRole('admin');

        $this->post(route('patients.store'), $this->validPayload([
            'full_name' => '',
            'sex' => 'unknown',
            'date_of_birth' => now()->addDay()->toDateString(),
        ]))->assertSessionHasErrors(['full_name', 'sex', 'date_of_birth']);

        $this->postJson(route('patients.store'), [])->assertStatus(422);
    }

    /** Production schema: full_name varchar(50), id_card varchar(20) UNIQUE. Must be a validation error, not a crash. */
    public function test_store_rejects_too_long_and_duplicate_values(): void
    {
        $this->actingAsRole('admin');
        $existing = $this->makePatient();

        $this->post(route('patients.store'), $this->validPayload([
            'full_name' => str_repeat('a', 51),
            'id_card' => str_repeat('9', 21),
        ]))->assertSessionHasErrors(['full_name', 'id_card']);

        $this->post(route('patients.store'), $this->validPayload(['id_card' => $existing->id_card]))
            ->assertSessionHasErrors(['id_card']);
    }

    public function test_update_keeps_own_id_card_but_rejects_another_patients(): void
    {
        $this->actingAsRole('admin');
        $a = $this->makePatient();
        $b = $this->makePatient();

        $this->put(route('patients.update', $a->patient_id), $this->validPayload(['id_card' => $a->id_card]))
            ->assertSessionHasNoErrors();
        $this->put(route('patients.update', $a->patient_id), $this->validPayload(['id_card' => $b->id_card]))
            ->assertSessionHasErrors(['id_card']);
    }

    public function test_show_edit_and_print_pages_render(): void
    {
        $this->actingAsRole('doctor');
        $patient = $this->makePatient();

        $this->get(route('patients.show', $patient->patient_id))->assertOk()->assertSee('Test Patient');
        $this->get(route('patients.edit', $patient->patient_id))->assertOk();
        $this->get(route('patients.print', $patient->patient_id))->assertOk();
    }

    public function test_update_changes_patient(): void
    {
        $this->actingAsRole('admin');
        $patient = $this->makePatient();

        $this->put(route('patients.update', $patient->patient_id), $this->validPayload(['full_name' => 'Updated Name']))
            ->assertRedirect(route('patients.index'));

        $this->assertDatabaseHas('patients', ['patient_id' => $patient->patient_id, 'full_name' => 'Updated Name']);
    }

    public function test_destroy_deletes_patient(): void
    {
        $this->actingAsRole('admin');
        $patient = $this->makePatient();

        $this->delete(route('patients.destroy', $patient->patient_id))->assertRedirect(route('patients.index'));

        $this->assertDatabaseMissing('patients', ['patient_id' => $patient->patient_id]);
    }

    public function test_pharmacist_is_forbidden(): void
    {
        $this->actingAsRole('pharmacist');
        $this->get(route('patients.index'))->assertForbidden();
        $this->post(route('patients.store'), $this->validPayload())->assertForbidden();
    }
}
