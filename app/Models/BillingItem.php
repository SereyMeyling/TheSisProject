<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingItem extends Model
{
    use HasFactory;

    protected $table = 'invoice_items';
    protected $primaryKey = 'id';

    protected $fillable = [
        'invoice_id',
        'item_type',
        'description',
        'quantity',
        'unit_price',
        'amount',
    ];

    protected $casts = [
        'unit_price' => 'float',
        'amount'     => 'float',
        'quantity'   => 'integer',
    ];

    public function billing()
    {
        return $this->belongsTo(Billing::class, 'invoice_id', 'id');
    }

    public function getTotalPriceAttribute()
    {
        return $this->amount;
    }
}
