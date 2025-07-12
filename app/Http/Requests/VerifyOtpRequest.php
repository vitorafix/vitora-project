<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * کاربر مجاز به انجام این درخواست است یا خیر.
     */
    public function authorize(): bool
    {
        // بررسی وجود شماره موبایل در سشن و تطابق آن با شماره موبایل ارسالی در درخواست
        // این یک لایه امنیتی است تا اطمینان حاصل شود که کاربر در حال تایید کد برای همان شماره‌ای است
        // که قبلاً کد برایش ارسال شده است.
        return session()->has('mobile_number') &&
               session('mobile_number') === $this->mobile_number;
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
            'otp' => $this->cleanOtp($this->input('otp')),
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
                // قانون سفارشی برای بررسی تطابق شماره موبایل با سشن
                function ($attribute, $value, $fail) {
                    if (session('mobile_number') !== $value) {
                        $fail('شماره موبایل با شماره‌ای که کد برای آن ارسال شده، تطابق ندارد.');
                    }
                },
            ],
            'otp' => [
                'required',
                'string',
                'numeric', // اطمینان از اینکه فقط شامل اعداد است
                'digits:6', // اطمینان از اینکه دقیقاً 6 رقم است
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
     * Removes non-numeric characters from the OTP string.
     * این متد کاراکترهای غیرعددی را از رشته OTP حذف می‌کند.
     *
     * @param string $otp The OTP string.
     * @return string The cleaned OTP.
     */
    private function cleanOtp($otp): string
    {
        // حذف فاصله‌ها و کاراکترهای غیرضروری
        return preg_replace('/[^0-9]/', '', $otp);
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
            'mobile_number.string' => 'فرمت شماره موبایل صحیح نیست.',
            'mobile_number.regex' => 'فرمت شماره موبایل صحیح نیست. ',
            'mobile_number.size' => 'شماره موبایل باید دقیقاً ۱۱ رقم باشد.',
            // پیام برای قانون سفارشی تطابق شماره موبایل
            'mobile_number.custom_match' => 'شماره موبایل با شماره‌ای که کد برای آن ارسال شده، تطابق ندارد.',
            'otp.required' => 'کد تأیید الزامی است.',
            'otp.string' => 'فرمت کد تأیید صحیح نیست.',
            'otp.numeric' => 'کد تأیید باید فقط شامل اعداد باشد.',
            'otp.digits' => 'کد تأیید باید دقیقاً ۶ رقم باشد.',
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
            'otp' => 'کد تأیید',
        ];
    }
}
