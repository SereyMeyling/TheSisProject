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
        $setting = GeneralSettings::first();

        return view('form.settings.general.generalPage', compact('setting'));
    }


    public function update(Request $request)
    {

        $setting = GeneralSettings::first();


        if ($request->hasFile('logo')) {

            $logo = $request->file('logo')
                ->store('settings', 'public');

            $setting->logo = $logo;
        }


        if ($request->hasFile('favicon')) {

            $favicon = $request->file('favicon')
                ->store('settings', 'public');

            $setting->favicon = $favicon;
        }


        $setting->update([

            'system_name' => $request->system_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'facebook' => $request->facebook,
            'telegram' => $request->telegram,
            'working_hours' => $request->working_hours,

        ]);
        $request->validate([
            'system_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,svg,webp|max:2048',
            'favicon' => 'nullable|image|mimes:jpg,jpeg,png,ico|max:1024',
        ]);

        return back()->with(
            'success',
            'Setting updated successfully'
        );
    }
}
