<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';
    protected $description = 'Notify cashiers to phone patients whose appointment is tomorrow';

    public function handle()
    {
        $appointments = Appointment::with(['patient', 'doctor'])
            ->where('status', 'scheduled')
            ->whereDate('appointment_date', Carbon::tomorrow()->toDateString())
            ->whereNull('reminder_sent_at')
            ->orderBy('appointment_date')
            ->get();

        if ($appointments->isEmpty()) {
            $this->info('No reminders to send.');
            return 0;
        }

        // cashier only: admin, doctor, nurse, pharmacist are never included
        $cashiers = User::role('cashier')->get();

        if ($cashiers->isEmpty()) {
            // reminder_sent_at is left empty, so it is sent once a cashier exists
            Log::warning('appointments:send-reminders found appointments but no users with the cashier role.');
            $this->warn('No users with the cashier role.');
            return 0;
        }

        $sent = 0;

        foreach ($appointments as $appointment) {
            try {
                Notification::send($cashiers, new AppointmentReminderNotification($appointment));
                $appointment->forceFill(['reminder_sent_at' => now()])->save();
                $sent++;
            } catch (\Throwable $e) {
                Log::error('Appointment reminder failed', [
                    'appointment_id' => $appointment->appointment_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("appointments:send-reminders sent {$sent} reminder(s) to {$cashiers->count()} cashier(s).");
        $this->info("{$sent} reminder(s) sent.");

        return 0;
    }
}
