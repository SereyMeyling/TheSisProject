<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabTest extends Model
{
    use HasFactory;

    protected $table = 'lab_tests';
    protected $primaryKey = 'test_id';

    protected $fillable = [
        'test_name',
        'test_code',
        'unit',
        'normal_range',
        'price',
    ];

    protected $casts = [
        'price' => 'float',
    ];

    public function results()
    {
        return $this->hasMany(LabResult::class, 'test_id', 'test_id');
    }
}
