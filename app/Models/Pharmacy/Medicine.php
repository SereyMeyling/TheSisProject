<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    use HasFactory;
  
    protected $primaryKey = 'medicine_id';

    protected $fillable = [
        'medicine_name',
        'ndc_code',
        'category',
        'unit',
        'dosage_unit',
        'strength',
        'unit_price',
        'pieces_per_unit',
        'selling_price',
        'reorder_level',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function batches()
    {
        return $this->hasMany(MedicineBatch::class, 'medicine_id', 'medicine_id');
    }

    public function activeBatches()
    {
        return $this->batches()
            ->where('remaining_quantity', '>', 0)
            ->orderBy('expiry_date', 'asc');
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'medicine_id', 'medicine_id');
    }


    public function getTotalStockAttribute(): int
    {
        return (int) $this->batches()->sum('remaining_quantity');
    }
}
