<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicineBatch extends Model
{
    use HasFactory;
    protected $primaryKey = 'batch_id';

    protected $fillable = [
        'medicine_id',
        'supplier_id',
        'batch_number',
        'expiry_date',
        'quantity_initial',
        'remaining_quantity',
        'purchase_price',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function medicine()
    {
        return $this->belongsTo(Medicine::class, 'medicine_id', 'medicine_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function movements()
    {
        return $this->hasMany(MedicineStockMovement::class, 'batch_id', 'batch_id');
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('remaining_quantity', '>', 0)
            ->whereDate('expiry_date', '<=', now()->addDays($days))
            ->whereDate('expiry_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('remaining_quantity', '>', 0)
            ->whereDate('expiry_date', '<', now());
    }
}
