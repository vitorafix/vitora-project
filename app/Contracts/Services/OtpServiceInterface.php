<?php

namespace App\Contracts\Services;

interface OtpServiceInterface
{
    /**
     * Generates a new OTP, stores it in cache, and returns it.
     * نام متد به generateAndStoreOtp تغییر یافت تا با OtpService هماهنگ باشد.
     *
     * @param string $mobileNumber
     * @return string The generated OTP
     */
    public function generateAndStoreOtp(string $mobileNumber): string;

    /**
     * Verifies the provided OTP against the stored one for the given mobile number.
     * نام متد به verifyOtp تغییر یافت تا با OtpService هماهنگ باشد.
     *
     * @param string $mobileNumber
     * @param string $otp The OTP provided by the user
     * @return bool True if OTP is valid, false otherwise
     */
    public function verifyOtp(string $mobileNumber, string $otp): bool;

    /**
     * Clears the stored OTP for a given mobile number after successful verification.
     * این متد اضافه شد تا با OtpService هماهنگ باشد.
     *
     * @param string $mobileNumber
     * @return void
     */
    public function clearOtp(string $mobileNumber): void;
}
