<?php

namespace App\Services\Responses;

use JsonSerializable; // برای اطمینان از اینکه کلاس به درستی به JSON تبدیل شود

class CartOperationResponse implements JsonSerializable
{
    public bool $success;
    public string $message;
    public mixed $data;
    public int $code;

    /**
     * سازنده کلاس CartOperationResponse.
     *
     * @param bool $success آیا عملیات موفقیت‌آمیز بوده است؟
     * @param string $message پیام مربوط به نتیجه عملیات.
     * @param mixed $data داده‌های اضافی که باید برگردانده شوند (اختیاری).
     * @param int $code کد وضعیت HTTP (اختیاری، پیش‌فرض 200).
     */
    public function __construct(bool $success, string $message, mixed $data = null, int $code = 200)
    {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
        $this->code = $code;
    }

    /**
     * ایجاد یک پاسخ موفقیت‌آمیز.
     *
     * @param string $message پیام موفقیت.
     * @param mixed $data داده‌های اضافی.
     * @param int $code کد وضعیت HTTP.
     * @return self
     */
    public static function success(string $message, mixed $data = null, int $code = 200): self
    {
        return new self(true, $message, $data, $code);
    }

    /**
     * ایجاد یک پاسخ ناموفق.
     *
     * @param string $message پیام خطا.
     * @param int $code کد وضعیت HTTP.
     * @param mixed $data داده‌های اضافی.
     * @return self
     */
    public static function fail(string $message, int $code = 400, mixed $data = null): self
    {
        return new self(false, $message, $data, $code);
    }

    /**
     * بررسی می‌کند که آیا عملیات موفقیت‌آمیز بوده است.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * دریافت پیام عملیات.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * دریافت داده‌های عملیات.
     *
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * دریافت کد وضعیت HTTP.
     *
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * متد toArray() برای تبدیل شیء به آرایه.
     * این متد برای زمانی که می‌خواهیم داده‌ها را به صورت آرایه به کنترلر برگردانیم مفید است.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'code' => $this->code, // این 'code' مربوط به HTTP status نیست، بلکه کد داخلی پاسخ است.
                                   // در فرانت‌اند از HTTP status اصلی پاسخ استفاده می‌شود.
        ];
    }

    /**
     * متد jsonSerialize() برای سریالایز کردن شیء به JSON.
     * این متد به طور خودکار توسط تابع json_encode() یا متد response()->json() لاراول فراخوانی می‌شود.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
