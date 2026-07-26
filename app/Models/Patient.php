<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $primaryKey = 'patient_id';

    protected $fillable = [
        'patient_code',
        'full_name',
        'id_card',
        'sex',
        'date_of_birth',
        'phone',
        'address',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'patient_id', 'patient_id');
    }
}
