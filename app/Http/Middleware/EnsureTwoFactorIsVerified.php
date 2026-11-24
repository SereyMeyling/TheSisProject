<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class EnsureTwoFactorIsVerified
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        // Secret stored but cannot be decrypted -> locked until an admin
        // resets 2FA. Never fall through to the setup flow here, otherwise
        // anyone holding the password could enroll their own authenticator.
        if ($user->isTwoFactorLocked()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => User::twoFactorLockedMessage()], 423);
            }

            return redirect()->route('login')->with('error', User::twoFactorLockedMessage());
        }

        // Never set up 2FA yet -> force setup
        if (! $user->hasTwoFactorSecretStored()) {
            return redirect()->route('2fa.setup');
        }

        // Has 2FA but hasn't verified this session -> force verify
        if (! $request->session()->get('2fa_passed', false)) {
            return redirect()->route('2fa.verify');
        }

        return $next($request);
    }
}
