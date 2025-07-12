<?php

namespace App\Http\Requests\Auth; // Changed: Updated namespace to match the expected folder structure (app/Http/Requests/Auth)

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // Import the Rule class for unique validation

class RegisterRequest extends FormRequest
{
    /**
     * Define common invalid mobile number patterns.
     * These patterns are used to prevent common test or invalid numbers.
     * الگوهای رایج شماره موبایل نامعتبر را تعریف می‌کند.
     * این الگوها برای جلوگیری از شماره‌های تستی یا نامعتبر رایج استفاده می‌شوند.
     *
     * @var array
     */
    private const INVALID_MOBILE_PATTERNS = [
        '/^(.)\1{10}$/', // Prevents repetition of a single digit throughout the number (e.g., 09111111111)
        // جلوگیری از تکرار یک رقم در کل شماره (مثال: 09111111111)
        '/^09(123456789|987654321|012345678|876543210)$/', // Common sequential patterns (e.g., 09123456789)
        // الگوهای ترتیبی رایج (مثال: 09123456789)
        '/^09([0-9])\1{9}$/', // Prevents repetition of the second digit throughout the number (e.g., 09111111111) - improved for clarity
        // جلوگیری از تکرار رقم دوم در کل شماره (مثال: 09111111111) - بهبود یافته برای وضوح بیشتر
    ];

    /**
     * Determine if the user is authorized to make this request.
     * کاربر مجاز به انجام این درخواست است یا خیر.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only guest users (not logged in) can register.
        // Uncomment the line below to enable this restriction.
        // فقط کاربران مهمان (غیر وارد شده) می‌توانند ثبت‌نام کنند.
        // برای فعال‌سازی این محدودیت، خط زیر را از حالت کامنت خارج کنید.
        return auth()->guest();

        // Currently, it allows all access.
        // در حال حاضر، به همه اجازه دسترسی می‌دهد.
        // return true;
    }

    /**
     * Prepare the data for validation.
     * This method is called before the validation rules are applied.
     * It's a good place to sanitize or modify input data.
     * این متد قبل از اعمال قوانین اعتبارسنجی فراخوانی می‌شود.
     * مکان مناسبی برای پاکسازی یا اصلاح داده‌های ورودی است.
     */
    protected function prepareForValidation()
    {
        // Sanitize 'name' and 'lastname' by stripping HTML tags to prevent XSS.
        // This ensures that even if malicious HTML/JS is submitted, it's removed.
        // Trim 'mobile_number' to remove any leading/trailing whitespace.
        // Convert Persian/Arabic digits to English digits before validation.
        // نام و نام خانوادگی را با حذف تگ‌های HTML برای جلوگیری از XSS پاکسازی می‌کند.
        // این تضمین می‌کند که حتی اگر HTML/JS مخرب ارسال شود، حذف می‌شود.
        // 'mobile_number' را برای حذف هرگونه فضای خالی ابتدایی/انتهایی Trim می‌کند.
        // ارقام فارسی/عربی را قبل از اعتبارسنجی به ارقام انگلیسی تبدیل می‌کند.
        $this->merge([
            'name' => strip_tags($this->input('name')),
            'lastname' => strip_tags($this->input('lastname')),
            'mobile_number' => $this->convertPersianDigitsToEnglish(trim($this->input('mobile_number'))),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     * قوانین اعتبارسنجی که برای این درخواست اعمال می‌شوند را برمی‌گرداند.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2', // Minimum 2 characters
                // حداقل 2 کاراکتر
                'max:255',
                // Allows only letters (including Persian letters), spaces, and hyphens
                // فقط حروف (شامل حروف فارسی)، فاصله و خط تیره را مجاز می‌کند
                'regex:/^[\pL\s\-]+$/u'
            ],
            'lastname' => [
                'nullable',
                'string',
                'max:255',
                // Applies the same regex as name for lastname
                // همان regex نام را برای نام خانوادگی نیز اعمال می‌کند
                'regex:/^[\pL\s\-]+$/u'
            ],
            'mobile_number' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/', // Ensures correct format 09xxxxxxxxx
                // اطمینان از فرمت صحیح 09xxxxxxxxx
                'size:11', // Ensures it's exactly 11 characters
                // اطمینان از اینکه دقیقاً 11 کاراکتر است
                // Ensures uniqueness of mobile number in the 'users' table
                // اطمینان از یکتا بودن شماره موبایل در جدول 'users'
                Rule::unique('users', 'mobile_number'),
                // Uses a helper method to check for invalid patterns
                // استفاده از متد کمکی برای بررسی الگوهای نامعتبر
                function ($attribute, $value, $fail) {
                    if (!$this->isValidMobilePattern($value)) {
                        $fail('شماره موبایل وارد شده معتبر نیست. لطفاً شماره موبایل واقعی خود را وارد کنید.');
                    }
                },
            ],
            // Add other validation rules as needed (e.g., password if used)
            // سایر قوانین اعتبارسنجی را در صورت نیاز اضافه کنید (مثلاً رمز عبور در صورت استفاده)
        ];
    }

    /**
     * Check if mobile number passes all validation patterns.
     * بررسی می‌کند که آیا شماره موبایل از تمام الگوهای اعتبارسنجی عبور می‌کند یا خیر.
     *
     * @param string $mobile
     * @return bool
     */
    private function isValidMobilePattern(string $mobile): bool
    {
        foreach (self::INVALID_MOBILE_PATTERNS as $pattern) {
            if (preg_match($pattern, $mobile)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Converts Persian/Arabic digits in a string to English digits.
     * ارقام فارسی/عربی را در یک رشته به ارقام انگلیسی تبدیل می‌کند.
     *
     * @param string $input The string containing digits.
     * @return string The string with converted digits.
     */
    private function convertPersianDigitsToEnglish(string $input): string
    {
        $persianDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $englishDigits = ['0','1','2','3','4','5','6','7','8','9'];
        // Also include Arabic digits if necessary, as they are often used interchangeably
        // در صورت لزوم، ارقام عربی را نیز اضافه کنید، زیرا اغلب به جای یکدیگر استفاده می‌شوند.
        $arabicDigits = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];

        $input = str_replace($persianDigits, $englishDigits, $input);
        $input = str_replace($arabicDigits, $englishDigits, $input);

        return $input;
    }

    /**
     * Get custom error messages for validation rules.
     * پیام‌های خطای سفارشی برای قوانین اعتبارسنجی را برمی‌گرداند.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'لطفاً اطلاعات صحیح را وارد نمایید.',
            'name.string' => 'لطفاً اطلاعات صحیح را وارد نمایید.',
            'name.min' => 'لطفاً اطلاعات صحیح را وارد نمایید.',
            'name.max' => 'لطفاً اطلاعات صحیح را وارد نمایید.',
            'name.regex' => 'لطفاً اطلاعات صحیح را وارد نمایید.',

            'lastname.string' => 'لطفاً اطلاعات صحیح را وارد نمایید.',
            'lastname.max' => 'لطفاً اطلاعات صحیح را وارد نمایید.',
            'lastname.regex' => 'لطفاً اطلاعات صحیح را وارد نمایید.',

            'mobile_number.required' => 'شماره موبایل الزامی است.',
            'mobile_number.string' => 'لطفاً اطلاعات صحیح را وارد نمایید.',
            'mobile_number.regex' => 'فرمت شماره موبایل نامعتبر است.',
            'mobile_number.size' => 'شماره موبایل باید دقیقاً ۱۱ رقم باشد.',
            'mobile_number.unique' => 'این شماره موبایل قبلاً ثبت‌نام شده است.',
        ];
    }

    /**
     * Get custom names for attributes.
     * These names are displayed in validation error messages.
     * نام‌های سفارشی برای ویژگی‌ها را برمی‌گرداند.
     * این نام‌ها در پیام‌های خطای اعتبارسنجی نمایش داده می‌شوند.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => 'نام',
            'lastname' => 'نام خانوادگی',
            'mobile_number' => 'شماره موبایل',
        ];
    }
}
