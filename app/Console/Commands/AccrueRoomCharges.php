<?php

namespace App\Console\Commands;

use App\Models\Admission;
use App\Services\Billing\RoomChargeService;
use Illuminate\Console\Command;

class AccrueRoomCharges extends Command
{
    protected $signature = 'billing:accrue-room-charges
                            {--admission= : Only this admission_id}
                            {--dry-run : Show the days that would be charged, create nothing}';

    protected $description = 'Create missing daily room charges for admitted patients';

    public function handle(RoomChargeService $rooms)
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($this->option('admission')) {
            $query = Admission::with('room')->where('admission_id', (int) $this->option('admission'));
        } else {
            $query = $rooms->accruableAdmissions();
        }

        $created = 0;
        $failed = 0;

        $query->orderBy('admission_id')->chunkById(100, function ($admissions) use ($rooms, $dryRun, &$created, &$failed) {
            foreach ($admissions as $admission) {
                try {
                    if ($dryRun) {
                        $missing = $rooms->missingDates($admission);
                        if (!empty($missing)) {
                            $this->line('Admission #' . $admission->admission_id . ' would add: ' . implode(', ', $missing));
                            $created += count($missing);
                        }
                    } else {
                        $created += $rooms->accrueForAdmission($admission);
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error('Admission #' . $admission->admission_id . ' failed: ' . $e->getMessage());
                }
            }
        }, 'admission_id');

        $this->info(($dryRun ? 'Would create ' : 'Created ') . $created . ' room charge(s). Failed admissions: ' . $failed);

        return $failed > 0 ? 1 : 0;
    }
}
