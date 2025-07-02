<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // اگر از Sanctum استفاده می‌کنید
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'lastname', // اضافه شده: برای هماهنگی با مهاجرت و منطق ثبت‌نام
        'mobile_number',
        'email',
        // 'password', // حذف شده: احراز هویت با OTP است
        'profile_completed',
        // 'address',            // حذف شده: این فیلدها به جدول addresses منتقل شده‌اند
        // 'city',               // حذف شده
        // 'province',           // حذف شده
        // 'postal_code',        // حذف شده
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            // 'password' => 'hashed', // حذف شده: احراز هویت با OTP است
            'profile_completed' => 'boolean',
        ];
    }

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
     * Check if the user's profile is completed.
     * یک متد کمکی برای بررسی وضعیت تکمیل پروفایل
     */
    public function isProfileCompleted(): bool
    {
        return (bool) $this->profile_completed;
    }
}

