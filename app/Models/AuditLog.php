<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    // نام جدول مربوط به این مدل در دیتابیس
    protected $table = 'audit_logs';

    // فیلدهایی که اجازه انتساب انبوه (mass assignment) دارند
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'failure_reason', // اضافه شده: از ساختار دیتابیس شما
        'ip_address',
        'user_agent',
        'session_id',
        'attempt_number', // اضافه شده: از ساختار دیتابیس شما
        'request_source', // اضافه شده: از ساختار دیتابیس شما
        'geo_location', // اضافه شده: از ساختار دیتابیس شما
        'ip_is_blacklisted', // اضافه شده: از ساختار دیتابیس شما
        'device_info', // اضافه شده: از ساختار دیتابیس شما
        'mobile_hash',
        'metadata', // این فیلد به صورت JSON در دیتابیس ذخیره می‌شود
        'model_type',
        'model_id',
        'level',
    ];

    // تبدیل خودکار فیلدها هنگام بازیابی از دیتابیس
    protected $casts = [
        'metadata' => 'array',
        'geo_location' => 'array', // اضافه شده: فرض می‌کنیم این هم JSON است
        'ip_is_blacklisted' => 'boolean', // اضافه شده: تبدیل به boolean
    ];

    /**
     * تعریف رابطه با مدل User
     * یک AuditLog می‌تواند به یک User تعلق داشته باشد.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
