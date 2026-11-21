<?php

namespace App\Services\Billing;

use App\Models\Billing\PatientCharge;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PatientChargeService
{
    private const TYPES = [
        PatientCharge::TYPE_MEDICINE,
        PatientCharge::TYPE_LAB,
        PatientCharge::TYPE_ROOM,
    ];

    // ---- source_key builders (one place, so keys never drift) ----

    public static function medicineKey(int $dispenseId): string
    {
        return 'medicine:' . $dispenseId;
    }

    public static function labKey(int $resultId): string
    {
        return 'lab:' . $resultId;
    }

    public static function roomKey(int $admissionId, $date): string
    {
        return 'room:' . $admissionId . ':' . Carbon::parse($date)->toDateString();
    }

    /**
     * Create a pending charge, or return the existing one for the same source_key.
     * Call this inside the transaction of the workflow that triggers the charge.
     *
     * Required: patient_id, source_type, source_key, description, quantity, unit_price
     * Optional: source_id, medical_record_id, admission_id, charge_date, created_by, notes
     */
    public function create(array $data): PatientCharge
    {
        foreach (['patient_id', 'source_type', 'source_key', 'description', 'quantity', 'unit_price'] as $required) {
            if (!isset($data[$required]) || $data[$required] === '') {
                throw new InvalidArgumentException("Charge field '{$required}' is required.");
            }
        }

        if (!in_array($data['source_type'], self::TYPES, true)) {
            throw new InvalidArgumentException("Unknown charge source_type '{$data['source_type']}'.");
        }

        $quantity = (int) $data['quantity'];
        if ($quantity < 1) {
            throw new InvalidArgumentException('Charge quantity must be at least 1.');
        }

        $unitPrice = round((float) $data['unit_price'], 2);
        if ($unitPrice < 0) {
            throw new InvalidArgumentException('Charge unit price cannot be negative.');
        }

        $existing = PatientCharge::where('source_key', $data['source_key'])->first();
        if ($existing) {
            return $existing;
        }

        try {
            return PatientCharge::create([
                'patient_id' => $data['patient_id'],
                'medical_record_id' => $data['medical_record_id'] ?? null,
                'admission_id' => $data['admission_id'] ?? null,
                'source_type' => $data['source_type'],
                'source_id' => $data['source_id'] ?? null,
                'source_key' => $data['source_key'],
                'description' => mb_substr($data['description'], 0, 255),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => round($quantity * $unitPrice, 2),
                'charge_date' => isset($data['charge_date'])
                    ? Carbon::parse($data['charge_date'])->toDateString()
                    : now()->toDateString(),
                'status' => PatientCharge::STATUS_PENDING,
                'created_by' => $data['created_by'] ?? auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (QueryException $e) {
            // 1062 = duplicate entry: another request created it first.
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return PatientCharge::where('source_key', $data['source_key'])->firstOrFail();
            }
            throw $e;
        }
    }

    /**
     * Pending (not yet invoiced, not cancelled) charges for one patient.
     */
    public function pendingForPatient(int $patientId): Collection
    {
        return PatientCharge::pending()
            ->where('patient_id', $patientId)
            ->orderBy('charge_date')
            ->orderBy('id')
            ->get();
    }

    public function pendingTotal(int $patientId): float
    {
        return (float) PatientCharge::pending()
            ->where('patient_id', $patientId)
            ->sum('total_price');
    }

    /**
     * Cancel a pending charge. Invoiced charges cannot be cancelled here;
     * they are released when their invoice is cancelled (Phase 10).
     */
    public function cancel(int $chargeId, string $reason, ?int $userId = null): PatientCharge
    {
        return DB::transaction(function () use ($chargeId, $reason, $userId) {
            $charge = PatientCharge::whereKey($chargeId)->lockForUpdate()->firstOrFail();

            if (!$charge->isPending()) {
                throw new DomainException('Only pending charges can be cancelled.');
            }

            $charge->update([
                'status' => PatientCharge::STATUS_CANCELLED,
                'cancelled_by' => $userId ?? auth()->id(),
                'cancelled_at' => now(),
                'cancel_reason' => mb_substr($reason, 0, 255),
            ]);

            return $charge;
        });
    }
}
