<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;
        protected $primaryKey = 'sale_item_id';

    protected $fillable = [
        'sale_id', 'medicine_id', 'batch_id', 'quantity', 'unit_price', 'subtotal',
    ];

    public function medicine()
    {
        return $this->belongsTo(Medicine::class, 'medicine_id', 'medicine_id');
    }

    public function batch()
    {
        return $this->belongsTo(MedicineBatch::class, 'batch_id', 'batch_id');
    }
}
