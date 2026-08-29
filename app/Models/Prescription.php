<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;

    protected $table = 'prescriptions';
    protected $primaryKey = 'prescription_id';

    protected $fillable = [
        'record_id',
        'prescribed_date',
    ];

    protected $casts = [
        'prescribed_date' => 'datetime',
    ];

    /**
     * Relationship to MedicalRecord
     */
    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class, 'record_id', 'record_id');
    }

    /**
     * Relationship to PrescriptionItems
     */
    public function items()
    {
        return $this->hasMany(PrescriptionItem::class, 'prescription_id', 'prescription_id');
    }
}
