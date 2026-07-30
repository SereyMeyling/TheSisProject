<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting\InvoiceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{


    public function bilingindex(Request $request)
    {
        $settings = InvoiceSetting::firstOrCreate(['id' => 1]);
        return view('form.settings.billing.billingPage', compact('settings'));
    }

    public function billingUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency_symbol' => 'required|string|max:10',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
            'invoice_prefix' => 'nullable|string|max:20',
            'invoice_footer' => 'nullable|string|max:255',
            'invoice_auto_number' => 'nullable|boolean',
            'next_invoice_number' => 'nullable|integer|min:1',
            'print_size' => 'required|in:A4,80mm',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $settings = InvoiceSetting::firstOrCreate(['id' => 1]);
        $settings->update($validator->validated());

        return response()->json(['message' => 'រក្សាទុកជោគជ័យ']);
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
