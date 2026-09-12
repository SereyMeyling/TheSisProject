<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $table = 'appointments';
    protected $primaryKey = 'appointment_id';

    protected $fillable = [
        'patient_id',
        'user_id',
        'appointment_date',
        'status',
        'reason',
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
    ];

    /**
     * Relationship to Patient
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    /**
     * Relationship to user (Doctor)
     */
    public function doctor()
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id'
        );
    }
}
