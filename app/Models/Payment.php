<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'invoice_payments';
    protected $primaryKey = 'id';

    protected $fillable = [
        'invoice_id',
        'payment_date',
        'amount_paid',
        'payment_method',
        'status',
        'received_by',
        'notes',
    ];

    protected $casts = [
        'amount_paid'  => 'float',
        'payment_date' => 'datetime',
    ];

    public function billing()
    {
        return $this->belongsTo(Billing::class, 'invoice_id', 'id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
