<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $table = 'medical_records';
    protected $primaryKey = 'record_id';

    protected $fillable = [
        'patient_id',
        'user_id',
        'visit_date',
        'diagnosis',
        'notes',
        'prescription_notes',
        'status_destination',
        'bp_systolic',
        'bp_diastolic',
        'heart_rate',
        'respiratory_rate',
        'temperature',
        'spo2',
        'weight',
    ];

    protected $casts = [
        'visit_date' => 'datetime',
    ];

    public function getBloodPressureAttribute(): ?string
    {
        if (!is_null($this->bp_systolic) && !is_null($this->bp_diastolic)) {
            return "{$this->bp_systolic}/{$this->bp_diastolic}";
        }
        return null;
    }

    public function getChiefComplaintAttribute(): ?string
    {
        return $this->notes;
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }



    public function doctor()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function prescription()
    {
        return $this->hasOne(Prescription::class, 'record_id', 'record_id');
    }
    public function toModalData(): array
    {
        return [
            'id' => $this->record_id,
            'update_url' => route('medical-records.update', $this->record_id),
            'patient_id' => $this->patient_id,
            'patient_name' => $this->patient->full_name ?? 'N/A',
            'patient_code' => $this->patient->patient_code ?? 'N/A',
            'sex' => $this->patient->sex ?? '-',
            'age' => $this->patient->age ?? '-',
            'doctor' => $this->doctor->name ?? 'N/A',
            'visit_date' => $this->visit_date ? $this->visit_date->format('d/m/Y H:i A') : '-',
            'bp' => ($this->bp_systolic ?? '-') . '/' . ($this->bp_diastolic ?? '-'),
            'bp_systolic' => $this->bp_systolic,
            'bp_diastolic' => $this->bp_diastolic,
            'heart_rate' => $this->heart_rate,
            'respiratory_rate' => $this->respiratory_rate,
            'temperature' => $this->temperature,
            'spo2' => $this->spo2,
            'weight' => $this->weight,
            'diagnosis' => $this->diagnosis,
            'notes' => $this->notes,
        ];
    }
    public function scopeWaiting($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('status_destination')->orWhere('status_destination', '');
        });
    }
}
