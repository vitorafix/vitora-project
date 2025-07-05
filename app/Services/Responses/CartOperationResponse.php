<?php
// File: app/Services/Responses/CartOperationResponse.php
namespace App\Services\Responses;

use JsonSerializable;

class CartOperationResponse implements JsonSerializable
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly mixed $data = null, // بهبود: تغییر ?array به mixed برای انعطاف‌پذیری بیشتر
        public readonly int $statusCode = 200
    ) {}

    /**
     * Specify data which should be serialized to JSON
     * داده‌هایی که باید به JSON تبدیل شوند را مشخص می‌کند.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'status_code' => $this->statusCode,
        ];
    }

    /**
     * Create a successful operation response.
     * یک پاسخ عملیات موفقیت‌آمیز ایجاد می‌کند.
     *
     * @param string $message The success message.
     * @param mixed|null $data Optional data to include in the response.
     * @param int $statusCode The HTTP status code.
     * @return static
     */
    public static function success(string $message, mixed $data = null, int $statusCode = 200): self
    {
        return new self(true, $message, $data, $statusCode);
    }

    /**
     * Create a failed operation response.
     * یک پاسخ عملیات ناموفق ایجاد می‌کند.
     *
     * @param string $message The error message.
     * @param int $statusCode The HTTP status code.
     * @param mixed|null $data Optional data to include in the response.
     * @return static
     */
    public static function fail(string $message, int $statusCode = 400, mixed $data = null): self
    {
        return new self(false, $message, $data, $statusCode);
    }
}
