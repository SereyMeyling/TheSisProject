<?php

namespace Tests\Feature;

use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\LabTest as LabTestModel;
use App\Models\MedicalRecord;
use App\Models\Patient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class LabTest extends TestCase
{
    use DatabaseTransactions;

    private function makeRecord(): MedicalRecord
    {
        $patient = Patient::create([
            'patient_code' => 'T-' . Str::random(10),
            'full_name' => 'Lab Patient',
            'id_card' => 'T-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(12)),
            'sex' => 'male',
            'date_of_birth' => '1975-07-07',
        ]);

        return MedicalRecord::create([
            'patient_id' => $patient->patient_id,
            'visit_date' => now(),
            'diagnosis' => 'Fever',
        ]);
    }

    private function makeTest(string $name = 'CBC'): LabTestModel
    {
        return LabTestModel::create([
            'test_name' => $name . ' ' . Str::random(4),
            'test_code' => strtoupper(Str::random(5)),
            'unit' => 'g/dL',
            'normal_range' => '12-16',
            'price' => 5.50,
        ]);
    }

    private function makeOrder(array $tests): LabOrder
    {
        $order = LabOrder::create([
            'record_id' => $this->makeRecord()->record_id,
            'order_date' => now(),
            'status' => 'pending',
        ]);
        foreach ($tests as $t) {
            LabResult::create([
                'lab_order_id' => $order->lab_order_id,
                'test_id' => $t->test_id,
                'result_value' => 'Pending',
                'normal_range' => $t->normal_range,
            ]);
        }
        return $order->load('results');
    }

    public function test_index_renders_for_lab_technician(): void
    {
        $this->actingAsRole('lab_technician');
        $this->makeOrder([$this->makeTest()]);
        $this->get(route('lab.index'))->assertOk();
    }

    public function test_index_ajax_returns_order_and_test_tables(): void
    {
        $this->actingAsRole('admin');
        $this->getJson(route('lab.index', ['status' => 'pending']))
            ->assertOk()->assertJsonStructure(['html', 'totalOrders', 'pendingOrders', 'completedOrders']);
        $this->getJson(route('lab.index', ['tab' => 'tests']))
            ->assertOk()->assertJsonStructure(['html', 'totalTests']);
    }

    public function test_store_order_creates_order_with_pending_results(): void
    {
        $this->actingAsRole('doctor');
        $record = $this->makeRecord();
        $t1 = $this->makeTest('CBC');
        $t2 = $this->makeTest('Glucose');

        $this->post(route('lab.orders.store'), [
            'record_id' => $record->record_id,
            'order_date' => now()->format('Y-m-d H:i:s'),
            'test_ids' => [$t1->test_id, $t2->test_id],
        ])->assertRedirect()->assertSessionHas('success');

        $order = LabOrder::where('record_id', $record->record_id)->with('results')->first();
        $this->assertNotNull($order);
        $this->assertSame('pending', $order->status);
        $this->assertCount(2, $order->results);
        $this->assertTrue($order->results->every(fn ($r) => $r->result_value === 'Pending'));
    }

    public function test_store_order_rejects_invalid_data(): void
    {
        $this->actingAsRole('nurse');

        $this->postJson(route('lab.orders.store'), [
            'record_id' => 999999999,
            'test_ids' => [],
        ])->assertStatus(422)->assertJsonValidationErrors(['record_id', 'order_date', 'test_ids']);
    }

    public function test_full_flow_order_then_results_then_show(): void
    {
        Notification::fake();
        $this->actingAsRole('lab_technician');
        $t1 = $this->makeTest('CBC');
        $t2 = $this->makeTest('Glucose');
        $order = $this->makeOrder([$t1, $t2]);
        [$r1, $r2] = $order->results->all();

        // Enter only the first result: order stays pending, only one charge.
        $this->post(route('lab.results.store', $order->lab_order_id), [
            'results' => [
                ['result_id' => $r1->result_id, 'result_value' => '13.5', 'remark' => 'Normal'],
                ['result_id' => $r2->result_id, 'result_value' => ''],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertDatabaseHas('lab_results', ['result_id' => $r1->result_id, 'result_value' => '13.5', 'remark' => 'Normal']);
        $this->assertDatabaseHas('lab_results', ['result_id' => $r2->result_id, 'result_value' => 'Pending']);
        $this->assertSame(1, DB::table('patient_charges')->where('source_type', 'lab')->whereIn('source_id', [$r1->result_id, $r2->result_id])->count());

        // Enter the second: order completes; re-saving does not double-bill.
        $this->post(route('lab.results.store', $order->lab_order_id), [
            'results' => [
                ['result_id' => $r1->result_id, 'result_value' => '13.5'],
                ['result_id' => $r2->result_id, 'result_value' => '95'],
            ],
        ])->assertSessionHas('success');

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(2, DB::table('patient_charges')->where('source_type', 'lab')->whereIn('source_id', [$r1->result_id, $r2->result_id])->count());

        $this->get(route('lab-results.show', $r2->result_id))->assertOk()->assertSee('Lab Patient')->assertSee('95');
    }

    public function test_results_from_another_order_are_rejected(): void
    {
        $this->actingAsRole('lab_technician');
        $test = $this->makeTest();
        $orderA = $this->makeOrder([$test]);
        $orderB = $this->makeOrder([$test]);

        $this->post(route('lab.results.store', $orderA->lab_order_id), [
            'results' => [['result_id' => $orderB->results->first()->result_id, 'result_value' => '1']],
        ])->assertSessionHas('error');

        $this->assertDatabaseHas('lab_results', ['result_id' => $orderB->results->first()->result_id, 'result_value' => 'Pending']);
    }

    public function test_store_results_validation(): void
    {
        $this->actingAsRole('lab_technician');
        $order = $this->makeOrder([$this->makeTest()]);

        $this->postJson(route('lab.results.store', $order->lab_order_id), ['results' => 'nope'])
            ->assertStatus(422)->assertJsonValidationErrors(['results']);
    }

    public function test_show_result_missing_redirects(): void
    {
        $this->actingAsRole('admin');
        $this->get(route('lab-results.show', 999999999))->assertRedirect(route('lab.index'));
    }

    // ---- Test catalog (admin only) ----

    public function test_admin_can_manage_catalog(): void
    {
        $this->actingAsRole('admin');
        $name = 'Lipid ' . Str::random(5);

        $this->post(route('lab.tests.store'), ['test_name' => $name, 'price' => 12])->assertSessionHas('success');
        $test = LabTestModel::where('test_name', $name)->firstOrFail();

        $this->put(route('lab.tests.update', $test->test_id), ['test_name' => $name . 'X', 'price' => 15])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('lab_tests', ['test_id' => $test->test_id, 'test_name' => $name . 'X', 'price' => 15]);

        $this->postJson(route('lab.tests.store'), ['test_name' => '', 'price' => -1])
            ->assertStatus(422)->assertJsonValidationErrors(['test_name', 'price']);

        $this->delete(route('lab.tests.destroy', $test->test_id))->assertSessionHas('success');
        $this->assertDatabaseMissing('lab_tests', ['test_id' => $test->test_id]);
    }

    public function test_used_catalog_test_cannot_be_deleted(): void
    {
        $this->actingAsRole('admin');
        $test = $this->makeTest();
        $this->makeOrder([$test]);

        $this->delete(route('lab.tests.destroy', $test->test_id))->assertSessionHas('error');
        $this->assertDatabaseHas('lab_tests', ['test_id' => $test->test_id]);
    }

    public function test_lab_technician_cannot_edit_catalog(): void
    {
        $this->actingAsRole('lab_technician');
        $this->post(route('lab.tests.store'), ['test_name' => 'X', 'price' => 1])->assertForbidden();
    }

    public function test_cashier_is_forbidden(): void
    {
        $this->actingAsRole('cashier');
        $this->get(route('lab.index'))->assertForbidden();
    }
}
