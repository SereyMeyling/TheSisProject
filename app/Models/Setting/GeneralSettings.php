<?php

namespace App\Models\Setting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_name',
        'phone',
        'email',
        'address',
        'facebook',
        'telegram',
        'working_hours',
        'logo',
        'favicon'
    ];

}

