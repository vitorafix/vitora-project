<?php

namespace App\Contracts\Services;

// use Illuminate\Session\Store as SessionStore; // REMOVED: No longer needed for JWT stateless operations

interface OtpServiceInterface
{
    /**
     * Sends an OTP to the given mobile number.
     * Handles OTP generation, storage, rate limiting, and SMS sending.
     *
     * @param string $mobileNumber The mobile number to send OTP to.
     * @param string $ipAddress The IP address of the request for rate limiting.
     * @param RateLimitServiceInterface $rateLimitService The rate limit service instance.
     * @param callable $auditLogger A callable function for logging audit events.
     * @throws \Exception If OTP sending fails due to rate limits, internal errors, etc.
     */
    public function sendOtpForMobile(string $mobileNumber, string $ipAddress, RateLimitServiceInterface $rateLimitService, callable $auditLogger): void;

    /**
     * Verifies the provided OTP for a given mobile number.
     * Handles OTP validation, clearing, rate limit resets, and user lookup/creation.
     *
     * @param string $mobileNumber The mobile number for verification.
     * @param string $otp The OTP provided by the user.
     * @param string $ipAddress The IP address of the request for rate limiting.
     * @param RateLimitServiceInterface $rateLimitService The rate limit service instance.
     * @param callable $auditLogger A callable function for logging audit events.
     * @return \App\Models\User The authenticated user model.
     * @throws \Exception If OTP verification fails (invalid OTP, rate limit, etc.).
     */
    public function verifyOtpForMobile(string $mobileNumber, string $otp, string $ipAddress, RateLimitServiceInterface $rateLimitService, callable $auditLogger): \App\Models\User;

    /**
     * Generates and stores an OTP for a given mobile number.
     *
     * @param string $mobileNumber
     * @return string The generated OTP.
     */
    public function generateAndStoreOtp(string $mobileNumber): string;

    /**
     * Verifies if the provided OTP matches the stored OTP for a mobile number.
     *
     * @param string $mobileNumber
     * @param string $otp
     * @return bool
     */
    public function verifyOtp(string $mobileNumber, string $otp): bool;

    /**
     * Clears the stored OTP for a given mobile number.
     *
     * @param string $mobileNumber
     * @return void
     */
    public function clearOtp(string $mobileNumber): void;

    /**
     * Checks if there's a pending OTP in cache for the given mobile number.
     * بررسی می‌کند که آیا کد OTP معلق (در انتظار تأیید) برای شماره موبایل مورد نظر در کش وجود دارد یا خیر.
     *
     * @param string $mobileNumber
     * @return bool
     */
    public function hasPendingOtp(string $mobileNumber): bool;
}
