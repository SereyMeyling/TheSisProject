<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $table = 'rooms';
    protected $primaryKey = 'room_id';

    protected $fillable = [
        'room_number',
        'room_type',
        'status',
        'price_per_day',
    ];

    protected $casts = [
        'price_per_day' => 'decimal:2',
    ];

    /**
     * Relationship to Admissions
     */
    public function admissions()
    {
        return $this->hasMany(Admission::class, 'room_id', 'room_id');
    }
}
