<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // برای لاگ کردن خطاها

class MelipayamakSmsService
{
    protected $username;
    protected $password;
    protected $fromNumber; // شماره خط فرستنده

    /**
     * سازنده کلاس MelipayamakSmsService.
     * اطلاعات احراز هویت را از فایل config/services.php دریافت می کند.
     */
    public function __construct()
    {
        $this->username = config('services.melipayamak.username');
        $this->password = config('services.melipayamak.password');
        $this->fromNumber = config('services.melipayamak.from_number'); // دریافت شماره خط فرستنده
    }

    /**
     * ارسال پیامک ساده.
     *
     * @param string $to شماره موبایل گیرنده.
     * @param string $text متن پیامک.
     * @return array|bool پاسخ API یا false در صورت بروز خطا.
     */
    public function send(string $to, string $text): array|bool
    {
        // در حالت توسعه و بدون API واقعی، فقط لاگ می کنیم
        if (app()->environment('local') && empty($this->username) && empty($this->password)) {
            Log::info("SIMULATED SMS: To: {$to}, Text: {$text}");
            // می توانید یک کد ثابت برای تست برگردانید، مثلاً 123456
            // یا اجازه دهید OTP تصادفی باشد و از لاگ بخوانید
            return ['status' => 'success', 'message' => 'Simulated SMS sent via log'];
        }

        $url = "https://rest.payamak-panel.com/api/SendSMS/SendSMS";

        try {
            $response = Http::get($url, [
                'username' => $this->username,
                'password' => $this->password,
                'to'       => $to,
                'from'     => $this->fromNumber,
                'text'     => $text,
                'isflash'  => false,
            ]);

            if ($response->successful()) {
                Log::info("Melipayamak SMS sent to {$to}: " . $response->body());
                return $response->json();
            } else {
                Log::error("Melipayamak SMS failed for {$to}. Status: {$response->status()}, Response: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Exception while sending Melipayamak SMS to {$to}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ارسال پیامک از طریق پترن (جهت پیامک های خدماتی/کد تایید).
     *
     * @param string $to شماره موبایل گیرنده.
     * @param string $patternCode کد پترن تعریف شده در پنل ملی پیامک.
     * @param array $parameters آرایه‌ای از پارامترها به شکل ['Name' => 'value'].
     * @return array|bool پاسخ API یا false در صورت بروز خطا.
     */
    public function sendByPattern(string $to, string $patternCode, array $parameters): array|bool
    {
        // در حالت توسعه و بدون API واقعی، فقط لاگ می کنیم
        if (app()->environment('local') && empty($this->username) && empty($this->password)) {
            $otp_value = null;
            foreach ($parameters as $param) {
                if ($param['Parameter'] === 'verification-code') { // فرض می کنیم نام پارامتر کد تایید 'verification-code' است
                    $otp_value = $param['ParameterValue'];
                    break;
                }
            }
            Log::info("SIMULATED PATTERN SMS: To: {$to}, Pattern: {$patternCode}, OTP: {$otp_value}, Params: " . json_encode($parameters));
            return ['status' => 'success', 'message' => 'Simulated Pattern SMS sent via log'];
        }


        $url = "https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber";

        $paramsArray = [];
        foreach ($parameters as $key => $value) {
            $paramsArray[] = ['Parameter' => $key, 'ParameterValue' => $value];
        }

        try {
            $response = Http::post($url, [
                'username' => $this->username,
                'password' => $this->password,
                'to'       => $to,
                'body'     => $patternCode,
                'parameters' => $paramsArray,
            ]);

            if ($response->successful()) {
                Log::info("Melipayamak Pattern SMS sent to {$to} with pattern {$patternCode}: " . $response->body());
                return $response->json();
            } else {
                Log::error("Melipayamak Pattern SMS failed for {$to}. Status: {$response->status()}, Response: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Exception while sending Melipayamak Pattern SMS to {$to}: " . $e->getMessage());
            return false;
        }
    }
}
