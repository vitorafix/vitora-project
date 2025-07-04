<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    /**
     * تعیین کنید آیا کاربر مجاز به انجام این درخواست است یا خیر.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // اجازه دادن به همه کاربران (احراز هویت شده یا مهمان) برای تأیید OTP.
        // اگر نیاز به منطق مجوز خاصی دارید، آن را اینجا پیاده‌سازی کنید.
        return true;
    }

    /**
     * قوانین اعتبارسنجی را که برای درخواست اعمال می‌شود، دریافت کنید.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'mobile_number' => ['required', 'string', 'regex:/^09[0-9]{9}$/', 'size:11'],
            'otp' => ['required', 'string', 'digits:6', 'numeric'],
            'website' => ['prohibited'], // تغییر یافته: برای Honeypot (جلوگیری از ربات)
        ];
    }

    /**
     * پیام‌های خطای سفارشی را برای قوانین اعتبارسنجی دریافت کنید.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mobile_number.regex' => 'فرمت شماره موبایل اشتباه است.',
            'mobile_number.required' => 'شماره موبایل الزامی است.',
            'mobile_number.string' => 'شماره موبایل باید یک رشته باشد.',
            'mobile_number.size' => 'شماره موبایل باید ۱۱ رقم باشد.',
            'otp.required' => 'کد تایید الزامی است.',
            'otp.string' => 'کد تایید باید یک رشته باشد.',
            'otp.digits' => 'کد تایید باید ۶ رقم باشد.',
            'otp.numeric' => 'کد تایید باید عددی باشد.',
            'website.prohibited' => 'درخواست نامعتبر است. ', // پیام خطا برای prohibited
        ];
    }
}
