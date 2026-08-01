<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $primaryKey = 'sale_id';

    protected $fillable = [
        'patient_id',
        'employee_id',
        'sale_date',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class, 'sale_id', 'sale_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
