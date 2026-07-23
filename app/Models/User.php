<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'google2fa_secret' => 'encrypted', // encrypted at rest automatically
        'google2fa_enabled' => 'boolean',
    ];

    /**
     * Clear this user's 2FA enrollment so they are forced back through
     * the /2fa/setup flow on next login. Does NOT touch name, email,
     * username, or password. Only columns that actually exist on the
     * users table are written to, so this stays safe across schema
     * variations (e.g. if two_factor_enabled / two_factor_confirmed_at
     * are added later).
     *
     * @return void
     */
    public function resetTwoFactorAuthentication(): void
    {
        $table = $this->getTable();

        $this->google2fa_secret = null;

        if (Schema::hasColumn($table, 'google2fa_enabled')) {
            $this->google2fa_enabled = false;
        }

        if (Schema::hasColumn($table, 'two_factor_enabled')) {
            $this->two_factor_enabled = false;
        }

        if (Schema::hasColumn($table, 'two_factor_confirmed_at')) {
            $this->two_factor_confirmed_at = null;
        }

        $this->save();
    }
}
