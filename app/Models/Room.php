<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

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


    public function getRouteKeyName()
    {
        return 'room_id';
    }
    public static function typeLabels(): array
    {
        return [
            'general' => 'ទូទៅ',
            'private' => 'ឯកជន',
            'icu' => 'ICU',
            'isolation' => 'គ្រែឯកោ',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            'available' => 'ទំនេរ',
            'occupied' => 'បានប្រើប្រាស់',
            'maintenance' => 'ថែទាំ',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return self::typeLabels()[$this->room_type] ?? $this->room_type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }
}
