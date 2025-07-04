<?php

namespace App\Contracts\Services;

interface OtpServiceInterface
{
    public function generateAndStore(string $mobileNumber): string;
    public function verify(string $mobileNumber, string $enteredOtp): bool;
}
