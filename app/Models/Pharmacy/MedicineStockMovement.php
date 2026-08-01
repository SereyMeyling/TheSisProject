<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicineStockMovement extends Model
{
    use HasFactory;
    protected $primaryKey = 'movement_id';

    protected $fillable = [
        'batch_id',
        'movement_type',
        'quantity',
        'reference_type',
        'reference_id',
        'movement_date',
    ];

    protected $casts = [
        'movement_date' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(MedicineBatch::class, 'batch_id', 'batch_id');
    }
}
