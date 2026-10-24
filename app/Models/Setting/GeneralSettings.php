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
        'logo_data',
        'logo_mime',
        'favicon_data',
        'favicon_mime',
    ];

    public function getLogoUrlAttribute()
    {
        return $this->logo_mime
            ? route('settingsgeneral.logo') . '?v=' . optional($this->updated_at)->timestamp
            : null;
    }

    public function getFaviconUrlAttribute()
    {
        return $this->favicon_mime
            ? route('settingsgeneral.favicon') . '?v=' . optional($this->updated_at)->timestamp
            : null;
    }
}

