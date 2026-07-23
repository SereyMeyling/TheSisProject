<?php

namespace App\Http\Middleware;

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

        // Never set up 2FA yet -> force setup
        if (is_null($user->google2fa_secret)) {
            return redirect()->route('2fa.setup');
        }

        // Has 2FA but hasn't verified this session -> force verify
        if (! $request->session()->get('2fa_passed', false)) {
            return redirect()->route('2fa.verify');
        }

        return $next($request);
    }
}