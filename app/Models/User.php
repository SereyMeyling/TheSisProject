<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

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
        'phone',
        'avatar',
        'avatar_mime',
        'department_id',
        'specialization',
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
        'google2fa_enabled' => 'boolean',
    ];

    /**
     * Get the google2fa_secret attribute with fallback for invalid MAC / old APP_KEY.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getGoogle2faSecretAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Set the google2fa_secret attribute.
     *
     * @param  string|null  $value
     * @return void
     */
    public function setGoogle2faSecretAttribute($value)
    {
        $this->attributes['google2fa_secret'] = $value
            ? \Illuminate\Support\Facades\Crypt::encryptString($value)
            : null;
    }

    /**
     * Clear this user's 2FA enrollment so they are forced back through
     * the /2fa/setup flow on next login.
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

    public function adminlte_image()
    {
        return $this->avatar
            ? route('profile.avatar', $this->id) . '?v=' . $this->updated_at->timestamp
            : asset('vendor/adminlte/dist/img/user2-160x160.jpg');
    }

    public function adminlte_desc()
    {
        return $this->department->department_name ?? ($this->getRoleNames()->first() ?? '');
    }

    public function adminlte_profile_url()
    {
        return route('profile.edit');
    }

    public function department()
    {
        return $this->belongsTo(
            Department::class,
            'department_id',
            'department_id'
        );
    }
    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id', 'id');
    }
}
