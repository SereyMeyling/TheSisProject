<?php

namespace App\Services\Billing;

use App\Models\Admission;
use App\Models\Billing\PatientCharge;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\Log;

class RoomChargeService
{
    /**
     * Billing policy: calendar days. The admission day is always charged.
     * When true, the discharge day is charged too (same-day stay = 1 day).
     */
    public const COUNT_DISCHARGE_DAY = true;

    /** Safety net against bad dates creating hundreds of charges. */
    private const MAX_DAYS = 366;

    private $charges;

    public function __construct(PatientChargeService $charges)
    {
        $this->charges = $charges;
    }

    /**
     * Admissions that may still need room charges:
     * everyone currently admitted, plus anyone discharged in the last 7 days.
     */
    public function accruableAdmissions()
    {
        return Admission::with('room')->where(function ($q) {
            $q->where('status', 'admitted')
                ->orWhere(function ($q2) {
                    $q2->where('status', 'discharged')
                        ->where('discharge_date', '>=', now()->subDays(7));
                });
        });
    }

    /**
     * Calendar dates (Y-m-d) that should be billed for this admission.
     */
    public function billableDates(Admission $admission, ?Carbon $asOf = null): array
    {
        $today = ($asOf ?: now())->copy()->startOfDay();

        if (!$admission->admission_date) {
            return [];
        }

        $start = $admission->admission_date->copy()->startOfDay();

        if ($start->gt($today)) {
            return []; // never bill future days
        }

        if ($admission->status === 'admitted') {
            $end = $today;
        } elseif ($admission->status === 'discharged' && $admission->discharge_date) {
            $end = $admission->discharge_date->copy()->startOfDay();

            if (!self::COUNT_DISCHARGE_DAY && $end->gt($start)) {
                $end->subDay();
            }
            if ($end->gt($today)) {
                $end = $today;
            }
            if ($end->lt($start)) {
                $end = $start->copy(); // same-day or odd data: at least 1 day
            }
        } else {
            return []; // transferred / unknown status: not handled yet
        }

        $dates = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dates[] = $d->toDateString();
            if (count($dates) > self::MAX_DAYS) {
                throw new DomainException(
                    'Admission #' . $admission->admission_id . ' would be billed for more than '
                    . self::MAX_DAYS . ' days. Check its admission date.'
                );
            }
        }

        return $dates;
    }

    /**
     * Billable dates that do not have a charge yet (used by --dry-run).
     */
    public function missingDates(Admission $admission, ?Carbon $asOf = null): array
    {
        $dates = $this->billableDates($admission, $asOf);
        if (empty($dates)) {
            return [];
        }

        $keys = [];
        foreach ($dates as $date) {
            $keys[PatientChargeService::roomKey($admission->admission_id, $date)] = $date;
        }

        $existing = PatientCharge::whereIn('source_key', array_keys($keys))->pluck('source_key')->all();

        return array_values(array_diff_key($keys, array_flip($existing)));
    }

    /**
     * Create any missing daily room charges for one admission.
     *
     * @return int number of NEW charges created
     */
    public function accrueForAdmission(Admission $admission, ?Carbon $asOf = null): int
    {
        $admission->loadMissing('room');
        $room = $admission->room;

        if (!$room) {
            Log::warning('Room charge skipped: admission has no room', [
                'admission_id' => $admission->admission_id,
            ]);
            return 0;
        }

        $created = 0;

        foreach ($this->billableDates($admission, $asOf) as $date) {
            $charge = $this->charges->create([
                'patient_id' => $admission->patient_id,
                'admission_id' => $admission->admission_id,
                'source_type' => PatientCharge::TYPE_ROOM,
                'source_id' => $admission->admission_id,
                'source_key' => PatientChargeService::roomKey($admission->admission_id, $date),
                'description' => 'បន្ទប់ ' . $room->room_number . ' (' . $room->type_label . ') '
                    . Carbon::parse($date)->format('d/m/Y'),
                'quantity' => 1,
                'unit_price' => $room->price_per_day,
                'charge_date' => $date,
            ]);

            if ($charge->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Top up room charges for every active admission of one patient
     * (Phase 8 calls this when the cashier selects the patient).
     */
    public function accrueForPatient(int $patientId): int
    {
        $created = 0;

        $this->accruableAdmissions()
            ->where('patient_id', $patientId)
            ->get()
            ->each(function (Admission $admission) use (&$created) {
                $created += $this->accrueForAdmission($admission);
            });

        return $created;
    }
}
