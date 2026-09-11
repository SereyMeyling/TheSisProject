<?php

namespace App\Notifications;

use App\Models\LabResult;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LabResultNotification extends Notification
{
    use Queueable;

    protected LabResult $labResult;

    public function __construct(LabResult $labResult)
    {
        $this->labResult = $labResult;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        // Adjust this relation chain (LabResult -> LabOrder -> Patient) to your schema
        $patientName = optional(optional($this->labResult->labOrder)->patient)->name ?? 'a patient';

        return [
            'type'    => 'lab_result',
            'title'   => 'Lab Result Ready',
            'message' => "Lab result for {$patientName} is ready for review",
            'icon'    => 'fa-flask',
            'color'   => 'text-info',
            'url'     => route('lab-results.show', $this->labResult->id),
        ];
    }
}