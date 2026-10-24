<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting\GeneralSettings;
use Illuminate\Http\Request;

class GeneralSettingsController extends Controller
{
    //
    public function index()
    {
        $setting = GeneralSettings::first() ?? new GeneralSettings();

        return view('form.settings.general.generalPage', compact('setting'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'system_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'facebook' => 'nullable|string|max:255',
            'telegram' => 'nullable|string|max:255',
            'working_hours' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'favicon' => 'nullable|file|mimes:jpg,jpeg,png,ico|max:1024',
        ]);

        $data = collect($validated)->except(['logo', 'favicon'])->all();

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $data['logo_data'] = base64_encode(file_get_contents($file->getRealPath()));
            $data['logo_mime'] = $file->getMimeType();
        }

        if ($request->hasFile('favicon')) {
            $file = $request->file('favicon');
            $data['favicon_data'] = base64_encode(file_get_contents($file->getRealPath()));
            $data['favicon_mime'] = $file->getMimeType();
        }

        $setting = GeneralSettings::first() ?? new GeneralSettings();
        $setting->fill($data)->save();

        return back()->with('success', 'Setting updated successfully');
    }

    public function logo()
    {
        $setting = GeneralSettings::select('logo_data', 'logo_mime')->first();
        abort_unless($setting && $setting->logo_data, 404);

        return response(base64_decode($setting->logo_data))
            ->header('Content-Type', $setting->logo_mime ?: 'image/png')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    public function favicon()
    {
        $setting = GeneralSettings::select('favicon_data', 'favicon_mime')->first();
        abort_unless($setting && $setting->favicon_data, 404);

        return response(base64_decode($setting->favicon_data))
            ->header('Content-Type', $setting->favicon_mime ?: 'image/png')
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
