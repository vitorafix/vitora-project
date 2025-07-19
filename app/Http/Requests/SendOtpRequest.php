<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    /**
     * تعیین اینکه آیا کاربر مجاز به ارسال این درخواست است یا خیر.
     */
    public function authorize(): bool
    {
        // در این درخواست، هر کاربری مجاز به ارسال OTP است.
        return true;
    }

    /**
     * آماده‌سازی داده‌ها قبل از اعتبارسنجی.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'mobile_number' => cleanMobileNumber($this->input('mobile_number')),
        ]);
    }

    /**
     * قوانین اعتبارسنجی درخواست.
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
            ],
        ];
    }

    /**
     * پیام‌های خطای سفارشی برای قوانین اعتبارسنجی.
     */
    public function messages(): array
    {
        return [
            'mobile_number.required' => 'شماره موبایل الزامی است.',
            'mobile_number.string'   => 'فرمت شماره موبایل صحیح نیست.',
            'mobile_number.regex'    => 'فرمت شماره موبایل نامعتبر است. مثال: 09123456789',
            'mobile_number.size'     => 'شماره موبایل باید دقیقاً ۱۱ رقم باشد.',
        ];
    }

    /**
     * نام‌های سفارشی برای ویژگی‌ها.
     */
    public function attributes(): array
    {
        return [
            'mobile_number' => 'شماره موبایل',
        ];
    }
}
