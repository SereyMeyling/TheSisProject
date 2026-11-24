<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorController extends Controller
{
    /**
     * How long the last accepted OTP timestep is remembered (seconds).
     * Must be longer than the verification window (default ±1 step of 30s).
     */
    private const LAST_TS_TTL = 600;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Forced setup page — only reachable if no secret is stored.
     */
    public function showSetupForm(Request $request)
    {
        $user = Auth::user();

        if ($user->isTwoFactorLocked()) {
            return $this->lockedResponse($request);
        }

        if ($user->hasTwoFactorSecretStored()) {
            $request->session()->forget('2fa_temp_secret');
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

        $user = Auth::user();

        if ($user->isTwoFactorLocked()) {
            return $this->lockedResponse($request);
        }

        // Already enrolled: never let a (possibly stale) temp secret
        // overwrite the existing one.
        if ($user->hasTwoFactorSecretStored()) {
            $request->session()->forget('2fa_temp_secret');
            return redirect()->route('2fa.verify');
        }

        $secret = $request->session()->get('2fa_temp_secret');
        if (! $secret) {
            return redirect()->route('2fa.setup')->with('error', __('auth2fa.session_expired'));
        }

        $google2fa = new Google2FA();
        $timestep = $google2fa->verifyKeyNewer($secret, $request->one_time_password, 0);
        if ($timestep === false) {
            return back()->with('error', __('auth2fa.invalid_code'));
        }

        $user->google2fa_secret = $secret;
        $user->google2fa_enabled = true;
        $user->save();

        Cache::put($this->lastTimestepKey($user), $timestep, self::LAST_TS_TTL);

        $request->session()->forget('2fa_temp_secret');
        $request->session()->put('2fa_passed', true);
        $request->session()->regenerate();

        $intended = $request->session()->pull('2fa_intended', RouteServiceProvider::HOME);
        return redirect($intended);
    }

    /**
     * Verify page for returning users who already have a secret.
     */
    public function showVerifyForm(Request $request)
    {
        $user = Auth::user();

        if ($user->isTwoFactorLocked()) {
            return $this->lockedResponse($request);
        }

        if (! $user->hasTwoFactorSecretStored()) {
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

        if ($user->isTwoFactorLocked()) {
            return $this->lockedResponse($request);
        }

        if (! $user->hasTwoFactorSecretStored()) {
            return redirect()->route('2fa.setup');
        }

        $google2fa = new Google2FA();

        // Reject replay of an OTP (or an older one) that was already accepted:
        // verifyKeyNewer only accepts timesteps strictly newer than the last one.
        $cacheKey = $this->lastTimestepKey($user);
        $lastTimestep = (int) Cache::get($cacheKey, 0);

        $timestep = $google2fa->verifyKeyNewer(
            $user->google2fa_secret,
            $request->one_time_password,
            $lastTimestep
        );

        if ($timestep === false) {
            return back()->with('error', __('auth2fa.invalid_code'));
        }

        Cache::put($cacheKey, $timestep, self::LAST_TS_TTL);

        $request->session()->put('2fa_passed', true);
        $request->session()->regenerate();

        $intended = $request->session()->pull('2fa_intended', RouteServiceProvider::HOME);
        return redirect($intended);
    }

    private function lastTimestepKey($user): string
    {
        return "2fa_last_ts:{$user->getKey()}";
    }

    /**
     * Account has a stored secret that cannot be decrypted: log out and
     * tell the user an admin must reset 2FA (never offer re-enrollment).
     */
    private function lockedResponse(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('error', User::twoFactorLockedMessage());
    }
}
