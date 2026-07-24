<?php

use App\Models\Setting\GeneralSettings;



if (!function_exists('setting')) {
    function setting($key, $default = null)
    {
        $settings = GeneralSettings::first();

        return $settings ? ($settings->$key ?? $default) : $default;
    }
}
