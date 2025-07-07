<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Permission\Traits\HasRoles; // اضافه کردن این خط برای استفاده از قابلیت های Spatie

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles; // اضافه کردن HasRoles به لیست Trait ها

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'lastname',
        'mobile_number',
        'email', // اگرچه برای ورود استفاده نمی‌شود، ممکن است برای اطلاعات پروفایل لازم باشد
        // 'password', // حذف شده: احراز هویت با OTP است
        'profile_completed',
        'national_id',
        'birth_date',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // 'password', // حذف شده: احراز هویت با OTP است
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        // 'password' => 'hashed', // حذف شده: احراز هویت با OTP است
        'profile_completed' => 'boolean',
    ];

    /**
     * Get the orders for the user.
     * دریافت سفارشات مربوط به این کاربر.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the addresses for the user.
     * دریافت آدرس‌های مربوط به این کاربر.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Get the legal information for the user.
     * دریافت اطلاعات حقوقی مربوط به این کاربر.
     */
    public function legalInfo(): HasOne
    {
        return $this->hasOne(LegalInfo::class);
    }

    /**
     * Check if the user's profile is completed.
     * یک متد کمکی برای بررسی وضعیت تکمیل پروفایل
     */
    public function isProfileCompleted(): bool
    {
        return (bool) $this->profile_completed;
    }
}
