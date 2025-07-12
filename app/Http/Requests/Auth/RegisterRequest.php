<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // Import the Rule class for unique validation

class RegisterRequest extends FormRequest
{
    /**
     * Define common invalid mobile number patterns.
     * These patterns are used to prevent common test or invalid numbers.
     *
     * @var array
     */
    private const INVALID_MOBILE_PATTERNS = [
        '/^(.)\1{10}$/', // جلوگیری از تکرار یک رقم در کل شماره (مثال: 09111111111)
        '/^09(123456789|987654321|012345678|876543210)$/', // الگوهای ترتیبی رایج (مثال: 09123456789)
        '/^09([0-9])\1{9}$/', // جلوگیری از تکرار رقم دوم در کل شماره (مثال: 09111111111) - بهبود یافته برای وضوح بیشتر
    ];

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // فقط کاربران مهمان (غیر وارد شده) می‌توانند ثبت‌نام کنند.
        // برای فعال‌سازی این محدودیت، خط زیر را از حالت کامنت خارج کنید.
        return auth()->guest();

        // در حال حاضر، به همه اجازه دسترسی می‌دهد.
        // return true;
    }

    /**
     * Prepare the data for validation.
     * This method is called before the validation rules are applied.
     * It's a good place to sanitize or modify input data.
     */
    protected function prepareForValidation()
    {
        // Sanitize 'name' and 'lastname' by stripping HTML tags to prevent XSS.
        // This ensures that even if malicious HTML/JS is submitted, it's removed.
        // Trim 'mobile_number' to remove any leading/trailing whitespace.
        // Convert Persian/Arabic digits to English digits before validation.
        $this->merge([
            'name' => strip_tags($this->input('name')),
            'lastname' => strip_tags($this->input('lastname')),
            'mobile_number' => $this->convertPersianDigitsToEnglish(trim($this->input('mobile_number'))),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2', // حداقل 2 کاراکتر
                'max:255',
                // فقط حروف (شامل حروف فارسی)، فاصله و خط تیره را مجاز می‌کند
                'regex:/^[\pL\s\-]+$/u'
            ],
            'lastname' => [
                'nullable',
                'string',
                'max:255',
                // همان regex نام را برای نام خانوادگی نیز اعمال می‌کند
                'regex:/^[\pL\s\-]+$/u'
            ],
            'mobile_number' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/', // اطمینان از فرمت صحیح 09xxxxxxxxx
                'size:11', // اطمینان از اینکه دقیقاً 11 کاراکتر است
                // اطمینان از یکتا بودن شماره موبایل در جدول 'users'
                Rule::unique('users', 'mobile_number'),
                // استفاده از متد کمکی برای بررسی الگوهای نامعتبر
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
     *
     * @param string $input The string containing digits.
     * @return string The string with converted digits.
     */
    private function convertPersianDigitsToEnglish(string $input): string
    {
        $persianDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $englishDigits = ['0','1','2','3','4','5','6','7','8','9'];
        // Also include Arabic digits if necessary, as they are often used interchangeably
        $arabicDigits = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];

        $input = str_replace($persianDigits, $englishDigits, $input);
        $input = str_replace($arabicDigits, $englishDigits, $input);

        return $input;
    }

    /**
     * Get custom error messages for validation rules.
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
