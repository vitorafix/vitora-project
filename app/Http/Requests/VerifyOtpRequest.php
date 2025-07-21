<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Crypt; // REMOVED: No longer decrypting from session
// use Illuminate\Contracts\Encryption\DecryptException; // REMOVED: No longer needed
// use App\Http\Controllers\Auth\MobileAuthController; // Keep if other constants are used, but SESSION_MOBILE_FOR_OTP is removed

class VerifyOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * بررسی مجاز بودن درخواست.
     *
     * In a stateless JWT flow, the mobile number is passed directly in the request.
     * The actual OTP verification logic happens in the OtpService, which checks against cache.
     * This authorize method can be simplified or removed if no other specific authorization
     * logic is needed at the FormRequest level beyond basic validation rules.
     *
     * در یک جریان JWT بدون حالت، شماره موبایل مستقیماً در درخواست ارسال می‌شود.
     * منطق واقعی تأیید OTP در OtpService اتفاق می‌افتد که در برابر کش بررسی می‌کند.
     * این متد authorize را می‌توان ساده کرد یا حذف کرد اگر هیچ منطق مجوز خاص دیگری
     * در سطح FormRequest فراتر از قوانین اعتبارسنجی اساسی مورد نیاز نباشد.
     */
    public function authorize(): bool
    {
        // Since we are moving away from session-based mobile number storage for OTP verification,
        // this method no longer needs to check session or decrypt a mobile number from it.
        // The mobile_number field itself will be validated by the rules() method.
        // If there's no other authorization logic needed here, it can simply return true.
        // از آنجایی که ما از ذخیره‌سازی شماره موبایل مبتنی بر سشن برای تأیید OTP فاصله می‌گیریم،
        // این متد دیگر نیازی به بررسی سشن یا رمزگشایی شماره موبایل از آن ندارد.
        // فیلد mobile_number خود توسط متد rules() اعتبارسنجی خواهد شد.
        // اگر منطق مجوز دیگری در اینجا مورد نیاز نیست، می‌تواند به سادگی true را برگرداند.
        Log::debug('VerifyOtpRequest: authorize method called. Returning true as session check is removed.');
        return true;
    }

    /**
     * Clean and prepare data for validation.
     * پاک‌سازی ورودی‌ها قبل از اعتبارسنجی.
     */
    protected function prepareForValidation()
    {
        // Assuming cleanMobileNumber and cleanOtp helper functions are available globally
        // or properly imported/defined.
        // فرض بر این است که توابع کمکی cleanMobileNumber و cleanOtp به صورت سراسری در دسترس هستند
        // یا به درستی وارد/تعریف شده‌اند.
        $cleanedMobile = cleanMobileNumber($this->input('mobile_number'));
        $cleanedOtp = cleanOtp($this->input('otp'));

        $this->merge([
            'mobile_number' => $cleanedMobile,
            'otp' => $cleanedOtp,
        ]);

        Log::debug('PrepareForValidation: Cleaned Mobile: ' . $cleanedMobile . ', Cleaned OTP: ' . $cleanedOtp);
    }

    /**
     * Get the validation rules that apply to the request.
     * قوانین اعتبارسنجی.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'mobile_number' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/',
                'size:11',
                // REMOVED: Custom validation rule that checked session-based mobile number.
                // This check is now handled by OtpService against the cached OTP data.
            ],
            'otp' => [
                'required',
                'string',
                'numeric',
                'digits:6',
            ],
        ];
    }

    /**
     * Custom validation messages.
     * پیام‌های خطای سفارشی.
     */
    public function messages(): array
    {
        return [
            'mobile_number.required' => 'شماره موبایل الزامی است.',
            'mobile_number.string'   => 'فرمت شماره موبایل صحیح نیست.',
            'mobile_number.regex'    => 'فرمت شماره موبایل صحیح نیست.',
            'mobile_number.size'     => 'شماره موبایل باید دقیقاً ۱۱ رقم باشد.',

            'otp.required'  => 'کد تأیید الزامی است.',
            'otp.string'    => 'فرمت کد تأیید صحیح نیست.',
            'otp.numeric'   => 'کد تأیید باید فقط شامل اعداد باشد.',
            'otp.digits'    => 'کد تأیید باید دقیقاً ۶ رقم باشد.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     * برگرداندن نام‌های سفارشی برای فیلدها.
     */
    public function attributes(): array
    {
        return [
            'mobile_number' => 'شماره موبایل',
            'otp' => 'کد تأیید',
        ];
    }
}
