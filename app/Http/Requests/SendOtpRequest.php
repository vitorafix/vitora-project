<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use App\Contracts\Services\RateLimitServiceInterface; // Import the RateLimitServiceInterface

class SendOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check rate limiting here.
        return !$this->exceedsRateLimit();
    }

    /**
     * Logic to check rate limiting to prevent spam.
     * This implementation should be coordinated with your actual RateLimitService.
     */
    private function exceedsRateLimit(): bool
    {
        try {
            $mobileNumber = $this->input('mobile_number');
            $ipAddress = $this->ip();

            // Instantiate the RateLimitService using Laravel's service container
            $rateLimitService = app(RateLimitServiceInterface::class);

            // Check and increment attempts for both mobile number and IP address
            $isMobileRateLimited = !$rateLimitService->checkAndIncrementSendAttempts($mobileNumber);
            $isIpRateLimited = !$rateLimitService->checkAndIncrementIpAttempts($ipAddress);

            // If either is rate limited, return true (exceeds rate limit)
            return $isMobileRateLimited || $isIpRateLimited;

        } catch (\Exception $e) {
            // Log the error and allow the request to proceed (or deny, depending on security policy)
            Log::warning('Rate limit check failed: ' . $e->getMessage(), [
                'mobile_number' => $this->input('mobile_number'),
                'ip_address' => $this->ip(),
                'user_agent' => $this->userAgent(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false; // Return false to not block the request if the rate limit check itself fails
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Add user's IP address, User Agent, and timestamp to the request data
        // so you can validate them, log them, or use them for throttling.
        $this->merge([
            'ip_address' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'timestamp' => now()->timestamp,
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
            'mobile_number' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/', // Ensure correct 09xxxxxxxxx format
                'size:11', // Ensure it is exactly 11 characters
                'not_regex:/^(.)\1{10}$/', // Prevent 11 identical repeating characters (e.g., 09111111111)
                // Prevent common and simple patterns (e.g., 09123456789 or 09987654321)
                'not_regex:/^09(123456789|987654321|012345678|876543210)$/', // Sequential and reverse patterns
                'not_regex:/^09(000000000|111111111|222222222|333333333|444444444|555555555|666666666|777777777|888888888|999999999)$/', // Repeating patterns from zero to nine
            ],
            'ip_address' => ['required', 'ip'], // IP address validation
            'user_agent' => ['nullable', 'string', 'max:255'], // User Agent is optional
            'timestamp' => ['required', 'integer'], // timestamp for logging and throttling
            // You can add other validation rules here.
        ];
    }

    /**
     * Get custom error messages for validation rules.
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
            'mobile_number.not_regex' => 'شماره موبایل وارد شده معتبر نیست. لطفاً شماره موبایل واقعی خود را وارد کنید.', // Improved
            'ip_address.required' => 'آدرس IP الزامی است.',
            'ip_address.ip' => 'فرمت آدرس IP نامعتبر است.',
            'user_agent.string' => 'User Agent باید از نوع متن باشد.',
            'user_agent.max' => 'User Agent نباید بیشتر از ۲۵۵ کاراکتر باشد.',
            'timestamp.required' => 'زمان ارسال درخواست الزامی است.',
            'timestamp.integer' => 'فرمت زمان ارسال درخواست نامعتبر است.',
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
            'mobile_number' => 'شماره موبایل',
            'ip_address' => 'آدرس IP',
            'user_agent' => 'User Agent',
            'timestamp' => 'زمان ارسال',
        ];
    }
}
