<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabResult extends Model
{
    use HasFactory;

    protected $table = 'lab_results';
    protected $primaryKey = 'result_id';

    protected $fillable = [
        'lab_order_id',
        'test_id',
        'result_value',
        'normal_range',
        'remark',
    ];

    public function labOrder()
    {
        return $this->belongsTo(LabOrder::class, 'lab_order_id', 'lab_order_id');
    }

    public function labTest()
    {
        return $this->belongsTo(LabTest::class, 'test_id', 'test_id');
    }
}
