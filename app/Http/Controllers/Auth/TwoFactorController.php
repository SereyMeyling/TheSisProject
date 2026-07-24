<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Forced setup page — only reachable if secret is still NULL.
     */
    public function showSetupForm(Request $request)
    {
        $user = Auth::user();

        if (! is_null($user->google2fa_secret)) {
            return redirect()->route('2fa.verify');
        }

        $google2fa = new Google2FA();

        $secret = $request->session()->get('2fa_temp_secret');
        if (! $secret) {
            $secret = $google2fa->generateSecretKey();
            $request->session()->put('2fa_temp_secret', $secret);
        }

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name', 'Hospital System'),
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());
        $writer = new Writer($renderer);
        $qrCodeSvg = preg_replace('/^<\?xml.*?\?>/', '', $writer->writeString($qrCodeUrl));

        return view('auth.2fa.setup', compact('qrCodeSvg', 'secret'));
    }

    /**
     * Confirms OTP against the temp secret, persists it, marks the
     * session as verified, and sends the user straight to the dashboard.
     */
    public function confirmSetup(Request $request)
    {
        $request->validate([
            'one_time_password' => 'required|digits:6',
        ]);

        $secret = $request->session()->get('2fa_temp_secret');
        if (! $secret) {
            return redirect()->route('2fa.setup')->with('error', __('auth2fa.session_expired'));
        }

        $google2fa = new Google2FA();
        if (! $google2fa->verifyKey($secret, $request->one_time_password)) {
            return back()->with('error', __('auth2fa.invalid_code'));
        }

        $user = Auth::user();
        $user->google2fa_secret = $secret;
        $user->google2fa_enabled = true;
        $user->save();

        $request->session()->forget('2fa_temp_secret');
        $request->session()->put('2fa_passed', true);

        $intended = $request->session()->pull('2fa_intended', RouteServiceProvider::HOME);
        return redirect($intended);
    }

    /**
     * Verify page for returning users who already have a secret.
     */
    public function showVerifyForm(Request $request)
    {
        $user = Auth::user();

        if (is_null($user->google2fa_secret)) {
            return redirect()->route('2fa.setup');
        }

        return view('auth.2fa.verify');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'one_time_password' => 'required|digits:6',
        ]);

        $user = Auth::user();
        $google2fa = new Google2FA();

        if (! $google2fa->verifyKey($user->google2fa_secret, $request->one_time_password)) {
            return back()->with('error', __('auth2fa.invalid_code'));
        }

        $request->session()->put('2fa_passed', true);
        $request->session()->regenerate();

        $intended = $request->session()->pull('2fa_intended', RouteServiceProvider::HOME);
        return redirect($intended);
    }
}