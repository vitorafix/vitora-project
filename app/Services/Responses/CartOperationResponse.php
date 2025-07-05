<?php
// File: app/Services/Responses/CartOperationResponse.php
namespace App\Services\Responses;

use JsonSerializable; // ایمپورت کردن اینترفیس JsonSerializable برای قابلیت تبدیل به JSON

/**
 * کلاس CartOperationResponse برای استانداردسازی پاسخ‌های عملیات سبد خرید.
 * این کلاس یک DTO (Data Transfer Object) است که نتیجه یک عملیات (موفقیت‌آمیز یا ناموفق)
 * را به همراه پیام، داده‌های مرتبط و کد وضعیت HTTP کپسوله می‌کند.
 */
class CartOperationResponse implements JsonSerializable
{
    /**
     * سازنده کلاس CartOperationResponse.
     *
     * @param bool $success وضعیت موفقیت‌آمیز بودن عملیات (true برای موفقیت، false برای شکست).
     * @param string $message پیامی که نتیجه عملیات را توضیح می‌دهد (مثلاً "محصول با موفقیت اضافه شد").
     * @param mixed|null $data داده‌های اختیاری مرتبط با عملیات (مثلاً جزئیات آیتم سبد خرید).
     * @param int $statusCode کد وضعیت HTTP برای پاسخ (مثلاً 200 برای OK، 400 برای Bad Request).
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly mixed $data = null,
        public readonly int $statusCode = 200
    ) {}

    /**
     * متد jsonSerialize برای قابلیت تبدیل به JSON.
     * این متد توسط تابع json_encode() فراخوانی می‌شود تا مشخص کند کدام داده‌ها
     * باید در خروجی JSON قرار گیرند.
     *
     * @return array آرایه‌ای از داده‌ها که به JSON تبدیل خواهند شد.
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
     * متد کارخانه‌ای (Factory Method) برای ایجاد یک پاسخ موفقیت‌آمیز.
     * این متد یک راه تمیز و خوانا برای ساخت نمونه‌های موفقیت‌آمیز فراهم می‌کند.
     *
     * @param string $message پیام موفقیت.
     * @param mixed|null $data داده‌های اختیاری برای شامل شدن در پاسخ.
     * @param int $statusCode کد وضعیت HTTP (پیش‌فرض 200 OK).
     * @return static یک نمونه جدید از CartOperationResponse.
     */
    public static function success(string $message, mixed $data = null, int $statusCode = 200): self
    {
        return new self(true, $message, $data, $statusCode);
    }

    /**
     * متد کارخانه‌ای (Factory Method) برای ایجاد یک پاسخ ناموفق.
     * این متد یک راه تمیز و خوانا برای ساخت نمونه‌های ناموفق فراهم می‌کند.
     *
     * @param string $message پیام خطا.
     * @param int $statusCode کد وضعیت HTTP (پیش‌فرض 400 Bad Request).
     * @param mixed|null $data داده‌های اختیاری برای شامل شدن در پاسخ (می‌تواند شامل جزئیات خطا باشد).
     * @return static یک نمونه جدید از CartOperationResponse.
     */
    public static function fail(string $message, int $statusCode = 400, mixed $data = null): self
    {
        return new self(false, $message, $data, $statusCode);
    }
}
