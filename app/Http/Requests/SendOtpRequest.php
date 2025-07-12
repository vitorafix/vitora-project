<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * کاربر مجاز به انجام این درخواست است یا خیر.
     */
    public function authorize(): bool
    {
        // در این درخواست، هر کاربری مجاز به ارسال درخواست OTP است.
        // اگر نیاز به بررسی احراز هویت یا مجوز خاصی باشد، اینجا اضافه کنید.
        return true;
    }

    /**
     * Prepare the data for validation.
     * این متد قبل از اعمال قوانین اعتبارسنجی فراخوانی می‌شود.
     * مکان مناسبی برای پاکسازی یا اصلاح داده‌های ورودی است.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'mobile_number' => $this->cleanMobileNumber($this->input('mobile_number')),
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
            'mobile_number' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/', // اطمینان از فرمت صحیح 09xxxxxxxxx
                'size:11', // اطمینان از اینکه دقیقاً 11 کاراکتر است
            ],
        ];
    }

    /**
     * Converts Persian/Arabic digits in a string to English digits and removes non-numeric characters.
     * این متد ارقام فارسی/عربی را به انگلیسی تبدیل کرده و کاراکترهای غیرعددی را حذف می‌کند.
     *
     * @param string $mobile The mobile number string.
     * @return string The cleaned mobile number.
     */
    private function cleanMobileNumber($mobile): string
    {
        $persianDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $englishDigits = ['0','1','2','3','4','5','6','7','8','9'];
        $arabicDigits = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];

        $mobile = str_replace($persianDigits, $englishDigits, $mobile);
        $mobile = str_replace($arabicDigits, $englishDigits, $mobile);

        // حذف کاراکترهای اضافی (غیر عددی)
        return preg_replace('/[^0-9]/', '', $mobile);
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
            'mobile_number.required' => 'شماره موبایل الزامی است.',
            'mobile_number.string' => 'لطفاً اطلاعات صحیح را وارد کنید.',
            'mobile_number.regex' => 'فرمت شماره موبایل صحیح نیست. (مثال: 09123456789)',
            'mobile_number.size' => 'شماره موبایل باید دقیقاً ۱۱ رقم باشد.',
        ];
    }

    /**
     * Get custom names for attributes.
     * نام‌های سفارشی برای ویژگی‌ها را برمی‌گرداند.
     * این نام‌ها در پیام‌های خطای اعتبارسنجی نمایش داده می‌شوند.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'mobile_number' => 'شماره موبایل',
        ];
    }
}
