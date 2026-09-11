<?php

namespace App\Notifications;

use App\Models\Prescription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PrescriptionNotification extends Notification
{
    use Queueable;

    protected Prescription $prescription;

    public function __construct(Prescription $prescription)
    {
        $this->prescription = $prescription;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $patientName = optional($this->prescription->patient)->name ?? 'a patient';

        return [
            'type' => 'prescription',
            'title' => 'New Prescription',
            'message' => "A new prescription was created for {$patientName}",
            'icon' => 'fa-file-prescription',
            'color' => 'text-primary',
            'url' => route('pharmacy.prescriptions.index'),
        ];
    }
}