<?php

namespace App\Services\Billing;

use App\Models\LabOrder;
use Illuminate\Support\Facades\Log;

class LabChargeService
{
    private $charges;

    public function __construct(PatientChargeService $charges)
    {
        $this->charges = $charges;
    }

    /**
     * Create one pending charge per entered lab result of an order.
     * Safe to call twice: charges are keyed by lab_result id.
     * Call inside the transaction that saved the results.
     *
     * @return int number of NEW charges created
     */
    public function chargeCompletedOrder(LabOrder $order): int
    {
        // Reload: the results were updated through separate model instances.
        $order->load(['results.labTest', 'medicalRecord']);

        $record = $order->medicalRecord;
        if (!$record) {
            throw new \Exception('រកមិនឃើញកំណត់ត្រាវេជ្ជសាស្ត្រនៃការកម្មង់ពិនិត្យនេះទេ');
        }

        $created = 0;

        foreach ($order->results as $result) {
            // A test whose result was never entered is not billable yet.
            if ($result->result_value === 'Pending') {
                continue;
            }

            $test = $result->labTest;
            if (!$test) {
                Log::warning('Lab charge skipped: test missing from catalog', [
                    'lab_order_id' => $order->lab_order_id,
                    'result_id' => $result->result_id,
                    'test_id' => $result->test_id,
                ]);
                continue;
            }

            $charge = $this->charges->create([
                'patient_id' => $record->patient_id,
                'medical_record_id' => $record->record_id,
                'source_type' => 'lab',
                'source_id' => $result->result_id,
                'source_key' => PatientChargeService::labKey($result->result_id),
                'description' => trim($test->test_name . ($test->test_code ? ' (' . $test->test_code . ')' : '')),
                'quantity' => 1,
                'unit_price' => $test->price,
                'charge_date' => now()->toDateString(),
            ]);

            if ($charge->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }
}
