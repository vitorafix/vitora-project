<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Http\Controllers\Auth\MobileAuthController;

class VerifyOtpRequest extends FormRequest
{
    /**
     * بررسی مجاز بودن درخواست.
     */
    public function authorize(): bool
    {
        if (!session()->has(MobileAuthController::SESSION_MOBILE_FOR_OTP)) {
            Log::debug('Authorize failed: Mobile number not found in session.');
            return false;
        }

        try {
            $decryptedSessionMobile = Crypt::decryptString(
                session(MobileAuthController::SESSION_MOBILE_FOR_OTP)
            );

            Log::debug('Authorize check: Decrypted Session Mobile: ' . $decryptedSessionMobile . ', Request Mobile: ' . $this->mobile_number);

            return $decryptedSessionMobile === $this->mobile_number;
        } catch (DecryptException $e) {
            Log::error('Authorize failed: Decryption error: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error('Authorize failed: General error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * پاک‌سازی ورودی‌ها قبل از اعتبارسنجی.
     */
    protected function prepareForValidation()
    {
        $cleanedMobile = cleanMobileNumber($this->input('mobile_number'));
        $cleanedOtp = cleanOtp($this->input('otp'));

        $this->merge([
            'mobile_number' => $cleanedMobile,
            'otp' => $cleanedOtp,
        ]);

        Log::debug('PrepareForValidation: Cleaned Mobile: ' . $cleanedMobile . ', Cleaned OTP: ' . $cleanedOtp);
    }

    /**
     * قوانین اعتبارسنجی.
     */
    public function rules(): array
    {
        return [
            'mobile_number' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/',
                'size:11',
                function ($attribute, $value, $fail) {
                    try {
                        $decrypted = Crypt::decryptString(session(MobileAuthController::SESSION_MOBILE_FOR_OTP));
                        if ($decrypted !== $value) {
                            $fail('شماره موبایل با درخواست قبلی مطابقت ندارد. لطفاً دوباره تلاش کنید.');
                        }
                    } catch (DecryptException $e) {
                        Log::error('Mobile match failed (DecryptException): ' . $e->getMessage());
                        $fail('خطا در اعتبارسنجی شماره موبایل.');
                    } catch (\Exception $e) {
                        Log::error('Mobile match failed (Exception): ' . $e->getMessage());
                        $fail('خطا در اعتبارسنجی شماره موبایل.');
                    }
                },
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
