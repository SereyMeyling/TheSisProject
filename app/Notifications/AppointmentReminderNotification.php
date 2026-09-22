<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentReminderNotification extends Notification
{
    use Queueable;

    protected Appointment $appointment;

    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $a = $this->appointment;

        $patient = $a->patient->full_name ?? 'N/A';
        $doctor = $a->doctor->name ?? 'N/A';

        return [
            // TODO: rename these keys to match LowStockNotification / PaymentNotification
            'type' => 'appointment_reminder',
            'icon' => 'fas fa-phone-volume',
            'title' => 'ទូរស័ព្ទរំលឹកការណាត់ជួប',
            'message' => "សូមទូរស័ព្ទរំលឹកអ្នកជំងឺ {$patient} សម្រាប់ការណាត់ជួបជាមួយវេជ្ជបណ្ឌិត {$doctor} "
                . "នៅថ្ងៃទី " . $a->appointment_date->format('d/m/Y')
                . " ម៉ោង " . $a->appointment_date->format('h:i A') . "។",
            'patient_name' => $patient,
            'patient_phone' => $a->patient->phone ?? null,
            'doctor_name' => $doctor,
            'appointment_id' => $a->appointment_id,
            'url' => route('appointment.reminders'),
        ];
    }
}
