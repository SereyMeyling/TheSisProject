<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabOrder extends Model
{
    use HasFactory;

    protected $table = 'lab_orders';
    protected $primaryKey = 'lab_order_id';

    protected $fillable = [
        'record_id',
        'order_date',
        'status',
    ];

    protected $casts = [
        'order_date' => 'datetime',
    ];

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class, 'record_id', 'record_id');
    }

    public function results()
    {
        return $this->hasMany(LabResult::class, 'lab_order_id', 'lab_order_id');
    }

    public function getOrderIdAttribute()
    {
        return $this->lab_order_id;
    }
}
