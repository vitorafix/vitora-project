<?php
// File: app/Services/Responses/CartContentsResponse.php
namespace App\Services\Responses;

use JsonSerializable; // ایمپورت کردن اینترفیس JsonSerializable برای قابلیت تبدیل به JSON
use InvalidArgumentException; // ایمپورت کردن InvalidArgumentException برای اعتبارسنجی
use Illuminate\Support\Collection; // برای استفاده از Collection در متد hasItem

/**
 * کلاس CartContentsResponse برای کپسوله کردن محتویات کامل سبد خرید.
 * این کلاس به عنوان یک DTO (Data Transfer Object) عمل می‌کند تا داده‌های سبد خرید
 * (آیتم‌ها، تعداد کل، و قیمت کل) را به صورت استاندارد برای مصرف توسط فرانت‌اند یا
 * سایر بخش‌های برنامه ارائه دهد.
 */
class CartContentsResponse implements JsonSerializable
{
    /**
     * سازنده کلاس CartContentsResponse.
     *
     * @param array $items آرایه‌ای از آیتم‌های سبد خرید، هر کدام شامل جزئیات محصول.
     * @param int $totalQuantity تعداد کل محصولات.
     * @param float $totalPrice قیمت کل محصولات.
     * @param \App\Models\CartItem[] $items (Type Hint دقیق‌تر: آرایه‌ای از آبجکت‌های CartItem)
     */
    public function __construct(
        public readonly array $items,
        public readonly int $totalQuantity,
        public readonly float $totalPrice
    ) {
        // 1. اعتبارسنجی داده‌ها: اطمینان از عدم منفی بودن مقادیر
        if ($totalQuantity < 0) {
            throw new InvalidArgumentException('Total quantity cannot be negative.'); // تعداد کل نمی‌تواند منفی باشد.
        }
        if ($totalPrice < 0) {
            throw new InvalidArgumentException('Total price cannot be negative.'); // قیمت کل نمی‌تواند منفی باشد.
        }
    }

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
            'items' => $this->items,
            'total_quantity' => $this->totalQuantity,
            // 3. فرمت‌بندی قیمت: اضافه کردن قیمت فرمت شده
            'total_price' => number_format($this->totalPrice, 2, '.', ''), // قیمت کل به صورت رشته با دو رقم اعشار
            'formatted_total_price_usd' => $this->getFormattedPrice('USD', '$'), // قیمت کل با نماد دلار
            // می‌توانید در اینجا فیلدهای اضافی مانند 'cart_id' یا 'user_id' را نیز اضافه کنید
            // اگر این اطلاعات برای مصرف‌کننده پاسخ مفید هستند.
        ];
    }

    /**
     * بررسی می‌کند که آیا سبد خرید خالی است یا خیر.
     *
     * @return bool اگر سبد خرید خالی باشد (بدون آیتم یا با تعداد کل صفر)، true برمی‌گرداند.
     */
    public function isEmpty(): bool
    {
        // 4. متدهای کمکی: بررسی خالی بودن سبد خرید
        return empty($this->items) || $this->totalQuantity === 0;
    }

    /**
     * تعداد آیتم‌های منحصر به فرد در سبد خرید را برمی‌گرداند.
     *
     * @return int تعداد آیتم‌های منحصر به فرد.
     */
    public function getItemCount(): int
    {
        // 4. متدهای کمکی: دریافت تعداد آیتم‌های منحصر به فرد
        return count($this->items);
    }

    /**
     * محاسبه متوسط قیمت هر آیتم در سبد خرید.
     *
     * @return float متوسط قیمت هر آیتم. اگر تعداد کل صفر باشد، 0 برمی‌گرداند.
     */
    public function getAveragePrice(): float
    {
        // 2. متد اضافی برای محاسبه متوسط قیمت
        return $this->totalQuantity > 0 ? $this->totalPrice / $this->totalQuantity : 0;
    }

    /**
     * قیمت کل را با فرمت ارز مشخص شده برمی‌گرداند.
     *
     * @param string $currency کد ارز (مثلاً 'USD', 'EUR').
     * @param string $symbol نماد ارز (مثلاً '$', '€').
     * @return string قیمت فرمت شده.
     */
    public function getFormattedPrice(string $currency = 'USD', string $symbol = '$'): string
    {
        // 3. پشتیبانی از چندین ارز: متد کمکی برای فرمت‌بندی قیمت
        // در یک سناریوی واقعی، منطق تبدیل ارز و فرمت‌بندی پیچیده‌تر خواهد بود.
        return $symbol . number_format($this->totalPrice, 2);
    }

    /**
     * بررسی می‌کند که آیا آیتمی با شناسه محصول مشخص در سبد خرید وجود دارد یا خیر.
     *
     * @param int $productId شناسه محصول مورد جستجو.
     * @return bool اگر آیتم یافت شود، true و در غیر این صورت false.
     */
    public function hasItem(int $productId): bool
    {
        // 4. متد برای جستجوی آیتم
        // از Collection لاراول برای جستجوی آسان‌تر استفاده می‌شود.
        // فرض بر این است که هر آیتم در آرایه $items دارای کلید 'product_id' است.
        return collect($this->items)->contains('product_id', $productId);
    }

    /**
     * محاسبه قیمت کل با اعمال تخفیف درصدی.
     *
     * @param float $discountPercentage درصد تخفیف (مثلاً 10 برای 10%).
     * @return float قیمت کل پس از اعمال تخفیف.
     * @throws InvalidArgumentException اگر درصد تخفیف منفی یا بیشتر از 100 باشد.
     */
    public function getPriceWithDiscount(float $discountPercentage): float
    {
        if ($discountPercentage < 0 || $discountPercentage > 100) {
            throw new InvalidArgumentException('Discount percentage must be between 0 and 100.'); // درصد تخفیف باید بین 0 تا 100 باشد.
        }
        return $this->totalPrice * (1 - $discountPercentage / 100);
    }

    /**
     * گروه‌بندی آیتم‌های سبد خرید بر اساس دسته‌بندی.
     *
     * @return \Illuminate\Support\Collection گروه‌بندی آیتم‌ها بر اساس دسته‌بندی.
     * فرض بر این است که هر آیتم در آرایه $items دارای کلید 'category' است.
     */
    public function getItemsByCategory(): Collection
    {
        // فرض بر این است که هر آیتم در آرایه $items دارای کلید 'category' است.
        // اگر این کلید وجود ندارد، باید ساختار داده آیتم‌ها را بررسی کنید.
        return collect($this->items)->groupBy('category');
    }

    /**
     * صادرات داده‌های سبد خرید به فرمت CSV.
     *
     * @return string رشته CSV حاوی اطلاعات محصولات، تعداد و قیمت.
     * فرض بر این است که هر آیتم در آرایه $items دارای کلیدهای 'product_name', 'quantity', 'product_price' است.
     */
    public function toCsv(): string
    {
        $csv = "Product,Quantity,Price\n"; // سربرگ CSV
        foreach ($this->items as $item) {
            // فرض بر این است که هر آیتم دارای کلیدهای 'product_name', 'quantity', 'product_price' است.
            // اگر نام کلیدها متفاوت است، آنها را بر اساس ساختار واقعی آیتم‌های خود تنظیم کنید.
            $productName = $item['product_name'] ?? 'N/A';
            $quantity = $item['quantity'] ?? 0;
            $price = $item['product_price'] ?? 0.0;
            $csv .= "{$productName},{$quantity},{$price}\n";
        }
        return $csv;
    }
}
