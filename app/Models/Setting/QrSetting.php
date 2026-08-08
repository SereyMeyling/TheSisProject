<?php

namespace App\Models\Setting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QrSetting extends Model
{
    use HasFactory;
    protected $fillable = [
        'mode', 'manual_qr_image',
        'account_type', 'bank_name', 'bakong_account_id', 'account_name',
        'account_number', 'merchant_city', 'merchant_id', 'mobile_number',
        'is_active', 'merchant_category_code',
    ];
}
