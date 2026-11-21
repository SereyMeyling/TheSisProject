<?php

namespace App\Models;

use App\Models\Pharmacy\Medicine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescriptionItem extends Model
{
    use HasFactory;

    protected $table = 'prescription_items';
    protected $primaryKey = 'prescription_item_id';

    protected $fillable = [
        'prescription_id',
        'medicine_id',
        'quantity',
        'dosage',
        'frequency',
        'duration_days',
    ];

    /**
     * Relationship to Prescription
     */
    public function prescription()
    {
        return $this->belongsTo(Prescription::class, 'prescription_id', 'prescription_id');
    }

    /**
     * Relationship to Medicine
     */
    public function medicine()
    {
        return $this->belongsTo(Medicine::class, 'medicine_id', 'medicine_id');
    }
    public function dispenses()
    {
        return $this->hasMany(PrescriptionItemDispense::class, 'prescription_item_id', 'prescription_item_id');
    }



    /** Quantity still to dispense (never negative). */
    public function getRemainingToDispenseAttribute(): int
    {
        return max(0, (int) $this->quantity - $this->dispensed_quantity);
    }
    /** Total quantity dispensed so far (sum of all dispense events). */
    public function getDispensedQuantityAttribute(): int
    {
        if ($this->relationLoaded('dispenses')) {
            return (int) $this->dispenses->sum('quantity');
        }

        return (int) $this->dispenses()->sum('quantity');
    }
}
