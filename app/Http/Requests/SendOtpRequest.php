<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log; // اضافه شده برای لاگ کردن خطا در exceedsRateLimit

class SendOtpRequest extends FormRequest
{
    /**
     * تعیین کنید آیا کاربر مجاز به انجام این درخواست است یا خیر.
     */
    public function authorize(): bool
    {
        // اگر نیاز به محدودیت rate limiting دارید، می‌توانید آن را اینجا بررسی کنید.
        // در غیر این صورت، true برگردانید.
        return !$this->exceedsRateLimit();
    }

    /**
     * منطق بررسی محدودیت نرخ (rate limiting) برای جلوگیری از اسپم.
     * این یک پیاده‌سازی ساده است و باید با سرویس RateLimitService واقعی شما هماهنگ شود.
     */
    private function exceedsRateLimit(): bool
    {
        try {
            $mobileNumber = $this->input('mobile_number');
            $ipAddress = $this->ip();

            // در اینجا می‌توانید منطق واقعی استفاده از RateLimitService را اضافه کنید.
            // مثلا:
            // $rateLimitService = app(\App\Contracts\Services\RateLimitServiceInterface::class);
            // $isMobileRateLimited = !$rateLimitService->checkAndIncrementSendAttempts($mobileNumber);
            // $isIpRateLimited = !$rateLimitService->checkAndIncrementIpAttempts($ipAddress);
            // return $isMobileRateLimited || $isIpRateLimited;

            // برای سادگی فعلاً false برگردانده می‌شود تا درخواست‌ها رد نشوند.
            return false;
        } catch (\Exception $e) {
            // خطا را لاگ کنید و اجازه دهید درخواست ادامه یابد (یا رد شود، بسته به سیاست امنیتی)
            Log::warning('Rate limit check failed: ' . $e->getMessage(), [
                'mobile_number' => $this->input('mobile_number'),
                'ip_address' => $this->ip(),
                'user_agent' => $this->userAgent(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString() // اضافه شده برای ردیابی کامل خطا
            ]);
            return false; // یا true اگر می‌خواهید در صورت خطا هم رد شود
        }
    }

    /**
     * داده‌ها را برای اعتبارسنجی آماده کنید.
     */
    protected function prepareForValidation()
    {
        // آدرس IP کاربر، User Agent و timestamp را به داده‌های درخواست اضافه کنید
        // تا بتوانید آن‌ها را اعتبارسنجی، لاگ یا برای throttling استفاده کنید.
        $this->merge([
            'ip_address' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'timestamp' => now()->timestamp,
        ]);
    }

    /**
     * قوانین اعتبارسنجی را که برای درخواست اعمال می‌شود، دریافت کنید.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'mobile_number' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/', // اطمینان از فرمت صحیح 09xxxxxxxxx
                'size:11', // اطمینان از اینکه دقیقاً 11 کاراکتر است
                'not_regex:/^(.)\1{10}$/', // جلوگیری از تکرار 11 کاراکتر یکسان (مثال: 09111111111)
                // جلوگیری از پترن‌های معمول و ساده (مثال: 09123456789 یا 09987654321)
                'not_regex:/^09(123456789|987654321|012345678|876543210)$/', // الگوهای متوالی و معکوس
                'not_regex:/^09(000000000|111111111|222222222|333333333|444444444|555555555|666666666|777777777|888888888|999999999)$/', // الگوهای تکراری صفر تا نه
            ],
            'ip_address' => ['required', 'ip'], // اعتبارسنجی آدرس IP
            'user_agent' => ['nullable', 'string', 'max:255'], // User Agent اختیاری است
            'timestamp' => ['required', 'integer'], // timestamp برای لاگ و throttling
            // می‌توانید قوانین اعتبارسنجی دیگری را نیز اینجا اضافه کنید.
        ];
    }

    /**
     * پیام‌های خطای سفارشی را برای قوانین اعتبارسنجی دریافت کنید.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'mobile_number.required' => 'شماره موبایل الزامی است.',
            'mobile_number.string' => 'شماره موبایل باید از نوع متن باشد.',
            'mobile_number.regex' => 'فرمت شماره موبایل نامعتبر است. شماره باید با 09 شروع شده و شامل ۱۱ رقم باشد.',
            'mobile_number.size' => 'شماره موبایل باید دقیقاً ۱۱ رقم باشد.',
            'mobile_number.not_regex' => 'شماره موبایل وارد شده معتبر نیست. لطفاً شماره موبایل واقعی خود را وارد کنید.', // بهبود یافته
            'ip_address.required' => 'آدرس IP الزامی است.',
            'ip_address.ip' => 'فرمت آدرس IP نامعتبر است.',
            'user_agent.string' => 'User Agent باید از نوع متن باشد.',
            'user_agent.max' => 'User Agent نباید بیشتر از ۲۵۵ کاراکتر باشد.',
            'timestamp.required' => 'زمان ارسال درخواست الزامی است.',
            'timestamp.integer' => 'فرمت زمان ارسال درخواست نامعتبر است.',
        ];
    }

    /**
     * نام‌های سفارشی برای ویژگی‌ها را دریافت کنید.
     * این نام‌ها در پیام‌های خطای اعتبارسنجی نمایش داده می‌شوند.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'mobile_number' => 'شماره موبایل',
            'ip_address' => 'آدرس IP',
            'user_agent' => 'User Agent',
            'timestamp' => 'زمان ارسال',
        ];
    }
}