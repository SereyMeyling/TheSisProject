<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrescriptionItemDispense extends Model
{
    protected $table = 'prescription_item_dispenses';
    protected $primaryKey = 'dispense_id';

    protected $fillable = [
        'prescription_item_id',
        'quantity',
        'dispensed_by',
        'dispensed_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'dispensed_at' => 'datetime',
    ];

    public function prescriptionItem()
    {
        return $this->belongsTo(PrescriptionItem::class, 'prescription_item_id', 'prescription_item_id');
    }

    public function dispenser()
    {
        return $this->belongsTo(User::class, 'dispensed_by', 'id');
    }
}
