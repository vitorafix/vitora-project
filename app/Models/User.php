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
use Tymon\JWTAuth\Contracts\JWTSubject; // اضافه کردن اینترفیس JWTSubject

class User extends Authenticatable implements JWTSubject // پیاده‌سازی اینترفیس JWTSubject
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
        'email',
        'username',
        'password',
        // 'role', // حذف شده: اکنون توسط Spatie مدیریت می‌شود
        'status',
        'profile_completed',
        'national_id',
        'birth_date',
        'fixed_phone', // تغییر به fixed_phone
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'profile_completed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     * دریافت شناسه‌ای که در ادعای موضوع JWT ذخیره خواهد شد.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // معمولاً ID کاربر
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     * یک آرایه کلید-مقدار حاوی هر گونه ادعای سفارشی برای اضافه شدن به JWT را برمی‌گرداند.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return []; // می‌توانید ادعاهای سفارشی مانند نقش کاربر را اینجا اضافه کنید
    }
}
