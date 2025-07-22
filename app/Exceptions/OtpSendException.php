<?php

namespace App\Exceptions; // Changed namespace to App\Exceptions

use Throwable;
use Exception;

/**
 * Custom Exception to carry generated OTP on failure.
 * یک استثنای سفارشی برای حمل OTP تولید شده در صورت شکست.
 */
class OtpSendException extends Exception
{
    public ?string $generatedOtp;

    public function __construct(string $message, ?string $generatedOtp = null, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->generatedOtp = $generatedOtp;
    }

    public function getGeneratedOtp(): ?string
    {
        return $this->generatedOtp;
    }
}
