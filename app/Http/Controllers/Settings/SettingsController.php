<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    
    public function bilingindex(Request $request)
    {
        return view('form.settings.billing.billingPage');
    }
    public function qrcodeindex(Request $request)
    {
        return view('form.settings.qrcode.qrcodePage');
    }
    public function backupindex(Request $request)
    {
        return view('form.settings.backup.backupPage');
    }
}
