<?php

namespace App\Contracts\Services;

interface RateLimitServiceInterface
{
    public function checkAndIncrementSendAttempts(string $mobileNumber): bool;
    public function checkAndIncrementVerifyAttempts(string $mobileNumber): bool;
    public function checkAndIncrementIpAttempts(string $ipAddress): bool;
    public function resetVerifyAttempts(string $mobileNumber): void;
    public function resetIpAttempts(string $ipAddress): void;
}
