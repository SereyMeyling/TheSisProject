<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting\InvoiceSetting;
use App\Models\Setting\QrSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use KHQR\BakongKHQR;
use KHQR\Helpers\KHQRData;
use KHQR\Models\IndividualInfo;
use KHQR\Models\MerchantInfo;

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
            'secondary_currency_symbol' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0.01',
            'tax_percent' => 'required|numeric|min:0|max:100',
            'invoice_footer' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $settings = InvoiceSetting::firstOrCreate(['id' => 1]);

        $settings->update($validator->validated());

        return response()->json([
            'message' => 'រក្សាទុកជោគជ័យ'
        ]);
    }

    public function qrcodeindex(Request $request)
    {
        $qrSetting = QrSetting::first();
        return view('form.settings.qrcode.qrcodePage', compact('qrSetting'));
    }

    public function qrcodeUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mode' => 'required|in:manual,bakong',

            // Manual QR
            'manual_qr_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',

            // Bakong
            'account_type' => 'nullable|in:individual,merchant',
            'bank_name' => 'required|string|max:100',
            'bakong_account_id' => 'nullable|string|max:100',
            'account_name' => 'required|string|max:150',
            'account_number' => 'required|string|max:100',
            'merchant_city' => 'nullable|string|max:100',
            'merchant_id' => 'nullable|string|max:50',
            'mobile_number' => 'nullable|string|max:20',
            'merchant_category_code' => 'nullable|string|max:10',

            // Logo
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ], [
            'account_name.required' => 'សូមបញ្ចូលឈ្មោះគណនី។',
            'account_name.string' => 'ឈ្មោះគណនីត្រូវតែជាអក្សរ។',
            'account_name.max' => 'ឈ្មោះគណនីមិនអាចលើស 150 តួអក្សរ។',

            'account_number.required' => 'សូមបញ្ចូលលេខគណនី។',
            'bank_name.required' => 'សូមជ្រើសរើសធនាគារ។',

            'manual_qr_image.image' => 'QR Code ត្រូវតែជារូបភាព។',
            'manual_qr_image.mimes' => 'QR Code ត្រូវតែជា PNG, JPG, JPEG ឬ WEBP។',
            'manual_qr_image.max' => 'QR Code មិនអាចលើស 2MB។',

            'logo.image' => 'Logo ត្រូវតែជារូបភាព។',
            'logo.mimes' => 'Logo ត្រូវតែជា PNG, JPG, JPEG ឬ WEBP។',
            'logo.max' => 'Logo មិនអាចលើស 2MB។',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $setting = QrSetting::first() ?? new QrSetting();

        /*
        |--------------------------------------------------------------------------
        | Save Manual QR Image as Base64
        |--------------------------------------------------------------------------
        */
        if ($request->hasFile('manual_qr_image')) {
            $file = $request->file('manual_qr_image');

            // Set directly, so $fillable cannot block it
            $setting->manual_qr_data = base64_encode(file_get_contents($file->getRealPath()));
            $setting->manual_qr_mime = $file->getMimeType();
        }

        // Do not save uploaded file paths for these fields.
        unset($data['manual_qr_image'], $data['logo']);

        $setting->fill($data);
        $setting->save();

        return response()->json([
            'message' => 'ការកំណត់ QR Code ត្រូវបានរក្សាទុកដោយជោគជ័យ',
            'had_file' => $request->hasFile('manual_qr_image'),
            'image_saved' => (bool) $setting->fresh()->manual_qr_mime,
            'manual_qr_url' => $setting->manual_qr_mime ? route('payment.qrImage') : null,
        ]);
    }
    public function backupindex(Request $request)
    {
        return view('form.settings.backup.backupPage');
    }

    /**
     * Generate a dynamic KHQR (with amount) for a specific invoice/payment.
     * POST /payment/generate-khqr  { amount, currency, bill_number }
     */

    public function generateKhqr(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:KHR,USD',
            'bill_number' => 'nullable|string|max:25',
        ]);

        $setting = QrSetting::first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'មិនទាន់មានការកំណត់ QR Code',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | MANUAL MODE: Return the saved static QR image
        |--------------------------------------------------------------------------
        */
        if ($setting->mode === 'manual') {
            if (!$setting->manual_qr_mime || !$setting->manual_qr_data) {
                return response()->json([
                    'success' => false,
                    'message' => 'សូមបញ្ចូលរូបភាព QR នៅក្នុងការកំណត់',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'mode' => 'manual',
                'qr_image_url' => route('payment.qrImage')
                    . '?v=' . optional($setting->updated_at)->timestamp,
                'bank_name' => $setting->bank_name,
                'account_name' => $setting->account_name,
                'account_number' => $setting->account_number,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | BAKONG MODE: Generate dynamic KHQR
        |--------------------------------------------------------------------------
        */
        $currency = $request->currency === 'KHR'
            ? KHQRData::CURRENCY_KHR
            : KHQRData::CURRENCY_USD;

        $expiration = strval(
            floor(microtime(true) * 1000) + (5 * 60 * 1000)
        );

        if ($setting->account_type === 'merchant') {
            $info = new MerchantInfo(
                bakongAccountID: $setting->bakong_account_id,
                merchantName: $setting->account_name,
                merchantCity: $setting->merchant_city,
                merchantID: $setting->merchant_id,
                acquiringBank: $setting->bank_name,
                mobileNumber: $setting->mobile_number,
                currency: $currency,
                amount: (float) $request->amount,
                billNumber: $request->bill_number,
                expirationTimestamp: $expiration,
                merchantCategoryCode: $setting->merchant_category_code,
            );

            $result = BakongKHQR::generateMerchant($info);
        } else {
            $info = new IndividualInfo(
                bakongAccountID: $setting->bakong_account_id,
                merchantName: $setting->account_name,
                merchantCity: $setting->merchant_city,
                currency: $currency,
                amount: (float) $request->amount,
                billNumber: $request->bill_number,
                expirationTimestamp: $expiration,
                merchantCategoryCode: $setting->merchant_category_code,
            );

            $result = BakongKHQR::generateIndividual($info);
        }

        if ($result->status['code'] !== 0) {
            return response()->json([
                'success' => false,
                'message' => $result->status['message']
                    ?? 'មិនអាចបង្កើត QR Code',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'mode' => 'bakong',
            'qr' => $result->data['qr'],
            'md5' => $result->data['md5'],
        ]);
    }


    public function qrImage()
    {
        $setting = QrSetting::first();

        abort_unless(
            $setting && $setting->manual_qr_data,
            404
        );

        return response(base64_decode($setting->manual_qr_data))
            ->header(
                'Content-Type',
                $setting->manual_qr_mime ?: 'image/png'
            )
            ->header('Cache-Control', 'private, max-age=3600');
    }

    /**
     * Poll payment status by md5 hash from a Bakong API token.
     * GET /payment/check-status/{md5}
     * Requires BAKONG_API_TOKEN in .env (register at https://api-bakong.nbc.gov.kh/register)
     */
    public function checkPaymentStatus($md5)
    {
        $bakong = new BakongKHQR(config('services.bakong.token'));

        try {
            $response = $bakong->checkTransactionByMD5($md5);
            $paid = $response->responseCode === 0; // 0 = transaction found/successful
            return response()->json(['paid' => $paid, 'raw' => $response]);
        } catch (\Throwable $e) {
            return response()->json(['paid' => false, 'error' => $e->getMessage()]);
        }
    }
}
