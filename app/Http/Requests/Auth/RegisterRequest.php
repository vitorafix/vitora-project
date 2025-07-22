<?php

namespace App\Http\Requests\Auth; 

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; 
// Removed: use function App\Helpers\convertPersianDigitsToEnglish; // Helper functions are global

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
        '/^(.)\1{10}$/', 
        '/^09(123456789|987654321|012345678|876543210)$/', 
        '/^09([0-9])\1{9}$/', 
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
        return auth()->guest();

        // Currently, it allows all access.
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
        $this->merge([
            'name' => strip_tags($this->input('name')),
            'lastname' => strip_tags($this->input('lastname')), // Changed back to 'lastname'
            'mobile_number' => convertPersianDigitsToEnglish(trim($this->input('mobile_number'))), // Use the global helper function
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
                'min:2', 
                'max:255',
                'regex:/^[\pL\s\-]+$/u'
            ],
            'lastname' => [ // Changed back to 'lastname'
                'nullable',
                'string',
                'max:255',
                'regex:/^[\pL\s\-]+$/u'
            ],
            'mobile_number' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/', 
                'size:11', 
                Rule::unique('users', 'mobile_number'),
                function ($attribute, $value, $fail) {
                    if (!$this->isValidMobilePattern($value)) {
                        $fail('شماره موبایل وارد شده معتبر نیست. لطفاً شماره موبایل واقعی خود را وارد کنید.');
                    }
                },
            ],
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

            'lastname.string' => 'لطفاً اطلاعات صحیح را وارد نمایید.', // Changed back to 'lastname'
            'lastname.max' => 'لطفاً اطلاعات صحیح را وارد نمایید.', // Changed back to 'lastname'
            'lastname.regex' => 'لطفاً اطلاعات صحیح را وارد نمایید.', // Changed back to 'lastname'

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
            'lastname' => 'نام خانوادگی', // Changed back to 'lastname'
            'mobile_number' => 'شماره موبایل',
        ];
    }
}
