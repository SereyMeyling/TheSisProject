<?php

namespace Tests\Feature;

use App\Models\Department;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use DatabaseTransactions;

    private function makeDepartment(): Department
    {
        return Department::create([
            'department_name' => 'Dept ' . Str::random(6),
            'description' => 'Test department',
        ]);
    }

    public function test_index_renders_for_admin(): void
    {
        $this->actingAsRole('admin');
        $this->get(route('department.index'))->assertOk();
    }

    public function test_index_ajax_returns_json(): void
    {
        $this->actingAsRole('admin');
        $this->getJson(route('department.index'))->assertOk()->assertJsonStructure(['html', 'total']);
    }

    public function test_store_persists_department(): void
    {
        $this->actingAsRole('admin');
        $name = 'Cardiology ' . Str::random(5);

        $this->from(route('department.index'))
            ->post(route('department.store'), ['department_name' => $name, 'description' => 'Heart'])
            ->assertRedirect(route('department.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('departments', ['department_name' => $name, 'description' => 'Heart']);
    }

    public function test_store_rejects_invalid_data(): void
    {
        $this->actingAsRole('admin');
        $before = Department::count();

        $this->post(route('department.store'), [
            'department_name' => str_repeat('x', 101),
            'description' => '',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame($before, Department::count());
    }

    public function test_edit_returns_department_json(): void
    {
        $this->actingAsRole('admin');
        $dept = $this->makeDepartment();

        $this->get(route('department.edit', $dept->department_id))
            ->assertOk()
            ->assertJson(['department_id' => $dept->department_id, 'department_name' => $dept->department_name]);
    }

    public function test_update_changes_department(): void
    {
        $this->actingAsRole('admin');
        $dept = $this->makeDepartment();

        $this->put(route('department.update', $dept->department_id), [
            'department_name' => 'Renamed Dept',
            'description' => 'Changed',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('departments', [
            'department_id' => $dept->department_id,
            'department_name' => 'Renamed Dept',
        ]);
    }

    public function test_destroy_deletes_department(): void
    {
        $this->actingAsRole('admin');
        $dept = $this->makeDepartment();

        $this->delete(route('department.destroy', $dept->department_id))->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseMissing('departments', ['department_id' => $dept->department_id]);
    }

    public function test_doctor_is_forbidden(): void
    {
        $this->actingAsRole('doctor');
        $this->get(route('department.index'))->assertForbidden();
        $this->post(route('department.store'), ['department_name' => 'X', 'description' => 'Y'])->assertForbidden();
    }
}
