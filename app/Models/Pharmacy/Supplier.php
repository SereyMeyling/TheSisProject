<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $primaryKey = 'supplier_id';

    protected $fillable = ['name', 'phone', 'address'];

    public function batches()
    {
        return $this->hasMany(MedicineBatch::class, 'supplier_id', 'supplier_id');
    }
}
