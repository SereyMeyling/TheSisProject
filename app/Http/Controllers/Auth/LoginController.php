<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    // Login throttling (read by the built-in ThrottlesLogins trait)
    protected $maxAttempts = 5;
    protected $decayMinutes = 1;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * The name of the single input field used on the login form.
     * The form posts a field called "login" which may contain
     * either an email address or a username.
     *
     * @return string
     */
    public function username()
    {
        return 'login';
    }

    /**
     * Validate the login request.
     * Only the combined "login" field + password are required —
     * we don't know yet whether "login" is an email or a username,
     * so we can't apply an `email` format rule here.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    /**
     * Build the credentials array used by Auth::attempt().
     * Detects whether the submitted "login" value is an email
     * (contains "@") or a username, and maps it to the correct
     * database column before attempting authentication.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        $login = $request->input($this->username());

        // "@" present -> treat as email, otherwise treat as username.
        $field = str_contains((string) $login, '@') ? 'email' : 'username';

        return [
            $field => $login,
            'password' => $request->input('password'),
        ];
    }

    /**
     * Called by AuthenticatesUsers right after a correct password login.
     * Every user must pass through 2FA setup or verify — no bypass.
     */
    protected function authenticated(Request $request, $user)
    {
        // Never carry a setup secret over from a previous session/login.
        $request->session()->forget('2fa_temp_secret');

        // Stored secret that cannot be decrypted -> account locked until an
        // admin resets 2FA. Do NOT send the user to the setup page.
        if ($user->isTwoFactorLocked()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', User::twoFactorLockedMessage());
        }

        $request->session()->put(
            '2fa_intended',
            $request->session()->get('url.intended', $this->redirectPath())
        );

        if (! $user->hasTwoFactorSecretStored()) {
            // First login ever — force setup, no dashboard access yet
            $request->session()->put('2fa_passed', false);
            return redirect()->route('2fa.setup');
        }

        // Secret already exists — must re-verify this session
        $request->session()->put('2fa_passed', false);
        return redirect()->route('2fa.verify');
    }
}