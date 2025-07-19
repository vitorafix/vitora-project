<?php

namespace App\Contracts\Services;

interface RateLimitServiceInterface
{
    /**
     * Checks and increments the OTP send attempts for a given mobile number.
     *
     * @param string $mobileNumber
     * @return bool True if allowed, false if rate limit exceeded.
     */
    public function checkAndIncrementSendAttempts(string $mobileNumber): bool;

    /**
     * Gets the current OTP send attempts for a given mobile number.
     *
     * @param string $mobileNumber
     * @return int
     */
    public function getSendAttempts(string $mobileNumber): int;

    /**
     * Checks and increments the OTP send attempts for a given IP address.
     *
     * @param string $ipAddress
     * @return bool True if allowed, false if rate limit exceeded.
     */
    public function checkAndIncrementIpAttempts(string $ipAddress): bool;

    /**
     * Gets the current OTP send attempts for a given IP address.
     *
     * @param string $ipAddress
     * @return int
     */
    public function getIpAttempts(string $ipAddress): int;

    /**
     * Checks and increments the OTP verification attempts for a given mobile number.
     *
     * @param string $mobileNumber
     * @return bool True if allowed, false if rate limit exceeded.
     */
    public function checkAndIncrementVerifyAttempts(string $mobileNumber): bool;

    /**
     * Gets the current OTP verification attempts for a given mobile number.
     *
     * @param string $mobileNumber
     * @return int
     */
    public function getVerifyAttempts(string $mobileNumber): int;

    /**
     * Checks and increments the OTP verification attempts for a given IP address.
     *
     * @param string $ipAddress
     * @return bool True if allowed, false if rate limit exceeded.
     */
    public function checkAndIncrementIpVerifyAttempts(string $ipAddress): bool;

    /**
     * Gets the current OTP verification attempts for a given IP address.
     *
     * @param string $ipAddress
     * @return int
     */
    public function getIpVerifyAttempts(string $ipAddress): int;

    /**
     * Clears all rate limit attempts for a given mobile number.
     *
     * @param string $mobileNumber
     * @return void
     */
    public function clearAttempts(string $mobileNumber): void;

    /**
     * Clears all IP-based rate limit attempts for a given IP address.
     *
     * @param string $ipAddress
     * @return void
     */
    public function clearIpAttempts(string $ipAddress): void;
}
