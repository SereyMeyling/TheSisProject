<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admission extends Model
{
    use HasFactory;

    protected $primaryKey = 'admission_id';

    protected $fillable = [
        'patient_id',
        'room_id',
        'admission_date',
        'discharge_date',
        'status',
    ];

    protected $casts = [
        'admission_date' => 'datetime',
        'discharge_date' => 'datetime',
    ];

    /**
     * Patient who is admitted.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    /**
     * Assigned room.
     */
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    /**
     * Billing/Invoices for this admission.
     * (Requires admission_id in invoices table.)
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'admission_id', 'admission_id');
    }
}