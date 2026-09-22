<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentNotification;
use Illuminate\Support\Facades\Notification;

class AppointmentObserver
{
    public function created(Appointment $appointment): void
    {
        $this->notify($appointment, 'created');
    }

    public function updated(Appointment $appointment): void
    {
        if ($appointment->wasChanged('status') && $appointment->status === 'cancelled') {
            $this->notify($appointment, 'cancelled');
            return;
        }

        if ($appointment->wasChanged(['appointment_date', 'appointment_time', 'status'])) {
            $this->notify($appointment, 'updated');
        }
    }

    protected function notify(Appointment $appointment, string $action): void
    {
        // Appointment notifications -> cashier only
        $recipients = User::role('cashier')->get();

        if ($recipients->isNotEmpty()) {
            Notification::send(
                $recipients,
                new AppointmentNotification($appointment, $action)
            );
        }
    }
}
