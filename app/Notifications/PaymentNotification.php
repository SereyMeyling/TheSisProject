<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentNotification extends Notification
{
    use Queueable;

    protected Payment $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $amount = number_format($this->payment->amount, 2);

        return [
            'type' => 'payment',
            'title' => 'New Payment Received',
            'message' => "A payment of \${$amount} was recorded",
            'icon' => 'fa-money-bill-wave',
            'color' => 'text-success',
            'url' => route('billing.show', $this->payment->id),
        ];
    }
}