<?php

namespace App\Services\Responses;

use JsonSerializable;

class CartOperationResponse implements JsonSerializable
{
    public bool $success;
    public string $message;
    public mixed $data;
    public int $code;

    public function __construct(bool $success, string $message, mixed $data = null, int $code = 200)
    {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
        $this->code = $code;
    }

    public static function success(string $message, mixed $data = null, int $code = 200): self
    {
        return new self(true, $message, $data, $code);
    }

    public static function fail(string $message, int $code = 400, mixed $data = null): self
    {
        return new self(false, $message, $data, $code);
    }

    /**
     * Static method to create an error response.
     * متد استاتیک برای ایجاد پاسخ خطا.
     *
     * @param string $message The error message.
     * @param int $code The HTTP status code.
     * @param mixed $data Optional additional data.
     * @return self
     */
    public static function error(string $message, int $code = 500, mixed $data = null): self
    {
        return new self(false, $message, $data, $code);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'code' => $this->code,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Helper method to get the cart from the data.
     * Assumes if data is an array or object and has a 'cart' key, it returns it.
     * Otherwise, it returns the data itself or null.
     * متد کمکی برای گرفتن سبد خرید از داده‌ها.
     * فرض می‌کند اگر داده‌ها آرایه یا شیء هستند و کلید 'cart' دارند، آن را برمی‌گرداند.
     * در غیر اینصورت خود داده را برمی‌گرداند یا null.
     */
    public function getCart(): mixed
    {
        if (is_array($this->data) && array_key_exists('cart', $this->data)) {
            return $this->data['cart'];
        }

        if (is_object($this->data) && property_exists($this->data, 'cart')) {
            return $this->data->cart;
        }

        // اگر داده‌ها به صورت مستقیم خود سبد خرید هستند، برگردانده شود
        return $this->data ?? null;
    }
}
