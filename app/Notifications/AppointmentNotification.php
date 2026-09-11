<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppointmentNotification extends Notification
{
    use Queueable;

    protected Appointment $appointment;
    protected string $action;

    public function __construct(
        Appointment $appointment,
        string $action = 'created'
    ) {
        $this->appointment = $appointment;
        $this->action = $action;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $patientName = optional($this->appointment->patient)->full_name
            ?? 'អ្នកជំងឺ';

        $messages = [
            'created' => "មានការណាត់ជួបថ្មីជាមួយអ្នកជំងឺ {$patientName}",
            'updated' => "ការណាត់ជួបជាមួយអ្នកជំងឺ {$patientName} ត្រូវបានកែប្រែ",
            'cancelled' => "ការណាត់ជួបជាមួយអ្នកជំងឺ {$patientName} ត្រូវបានលុបចោល",
        ];

        return [
            'type' => 'appointment',

            'title' => match ($this->action) {
                'created' => 'ការណាត់ជួបថ្មី',
                'updated' => 'ការណាត់ជួបត្រូវបានកែប្រែ',
                'cancelled' => 'ការណាត់ជួបត្រូវបានលុបចោល',
                default => 'ការជូនដំណឹង',
            },

            'message' => $messages[$this->action]
                ?? $messages['created'],

            'icon' => 'fa-calendar-check',

            'color' => $this->action === 'cancelled'
                ? 'text-danger'
                : 'text-primary',

            'url' => route(
                'appointment.show',
                $this->appointment->appointment_id
            ),
        ];
    }
}