<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    use HasFactory;

    protected $table = 'invoices';
    protected $primaryKey = 'id';

    protected $fillable = [
        'invoice_number',
        'patient_id',
        'admission_id',
        'patient_name',
        'patient_phone',
        'total_amount',
        'paid_amount',
        'balance',
        'status',
        'notes',
        'created_by',
        'cancelled_by',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'paid_amount'  => 'float',
        'balance'      => 'float',
        'cancelled_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(BillingItem::class, 'invoice_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'invoice_id', 'id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class, 'admission_id', 'admission_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getBillingDateAttribute()
    {
        return $this->created_at;
    }
}
