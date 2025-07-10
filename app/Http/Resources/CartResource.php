<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\Responses\CartContentsResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CartResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * Indicates if metadata should be included in the response.
     * نشان می‌دهد که آیا متادیتا باید در پاسخ گنجانده شود.
     *
     * @var bool
     */
    protected bool $includeMetadata = true;

    /**
     * Create a new resource instance.
     *
     * @param mixed $resource
     * @param bool $includeMetadata
     */
    public function __construct($resource, bool $includeMetadata = true)
    {
        parent::__construct($resource);
        $this->includeMetadata = $includeMetadata;
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!$this->resource instanceof CartContentsResponse) {
            throw new \InvalidArgumentException('Resource must be an instance of CartContentsResponse');
        }

        // اگر درخواست از نوع موبایل باشد، پاسخ ساده‌تر خواهد بود
        $isMobile = self::isMobileRequest();

        return [
            'items' => $this->formatItems($isMobile),
            'summary' => $this->formatSummary($isMobile),
            // 'cartTotals' => $this->resource->cartTotals->toArray(), // cartTotals در summary گنجانده شده است
            // metadata فقط در صورتی اضافه می‌شود که includeMetadata true باشد
            'metadata' => $this->when($this->includeMetadata && !$isMobile, $this->formatMetadata()),
        ];
    }

    /**
     * Check if the current request is from a mobile device.
     * بررسی می‌کند که آیا درخواست فعلی از دستگاه موبایل است.
     *
     * @return bool
     */
    protected static function isMobileRequest(): bool
    {
        // این یک پیاده‌سازی ساده است. در محیط واقعی، ممکن است نیاز به بررسی User-Agent دقیق‌تر باشد.
        return request()->hasHeader('X-Mobile-Optimized') && request()->header('X-Mobile-Optimized') === 'true';
    }

    /**
     * Format cart items for the response.
     *
     * @param bool $isMobile
     * @return array
     */
    protected function formatItems(bool $isMobile): array
    {
        return collect($this->resource->getItems())->map(function ($item) use ($isMobile) {
            $product = $item['product']; // فرض می‌کنیم که جزئیات محصول در 'product' وجود دارد
            return [
                'id' => $item['id'],
                'product' => $this->formatProduct($product, $isMobile),
                'quantity' => (int) $item['quantity'],
                'unitPrice' => (float) $item['price'],
                'totalPrice' => (float) $item['subtotal'], // فرض می‌کنیم subtotal آیتم همان totalPrice است
                $this->mergeWhen(!$isMobile, [
                    'formattedUnitPrice' => $this->formatPrice($item['price']),
                    'formattedTotalPrice' => $this->formatPrice($item['subtotal']),
                    'addedAt' => Carbon::parse($item['created_at'])->toDateTimeString(),
                    'updatedAt' => Carbon::parse($item['updated_at'])->toDateTimeString(),
                ]),
            ];
        })->toArray();
    }

    /**
     * Format product details for the response.
     *
     * @param array $product
     * @param bool $isMobile
     * @return array
     */
    protected function formatProduct(array $product, bool $isMobile): array
    {
        return [
            'id' => $product['id'],
            'name' => $product['title'], // فرض می‌کنیم 'title' نام محصول است
            'inStock' => $product['stock'] > 0, // فرض می‌کنیم 'stock' موجودی کالا است
            $this->mergeWhen(!$isMobile, [
                'slug' => $product['slug'],
                'image' => $product['image'] ? asset('storage/' . $product['image']) : null, // مسیر کامل تصویر
                'stockQuantity' => (int) $product['stock'],
            ]),
        ];
    }

    /**
     * Format summary details for the response.
     *
     * @param bool $isMobile
     * @return array
     */
    protected function formatSummary(bool $isMobile): array
    {
        $cartTotals = $this->resource->getCartTotals(); // دریافت DTO از CartContentsResponse
        return [
            'totalQuantity' => $this->resource->getTotalQuantity(),
            'totalPrice' => $cartTotals->total, // استفاده از total نهایی از CartTotalsDTO
            'isEmpty' => empty($this->resource->getItems()),
            $this->mergeWhen(!$isMobile, [
                'formattedTotalPrice' => $this->formatPrice($cartTotals->total),
                'currency' => 'IRR', // یا هر واحد پول دیگر
                'subtotal' => $cartTotals->subtotal,
                'formattedSubtotal' => $this->formatPrice($cartTotals->subtotal),
                'discount' => $cartTotals->discount,
                'formattedDiscount' => $this->formatPrice($cartTotals->discount),
                'shipping' => $cartTotals->shipping,
                'formattedShipping' => $this->formatPrice($cartTotals->shipping),
                'tax' => $cartTotals->tax,
                'formattedTax' => $this->formatPrice($cartTotals->tax),
            ]),
        ];
    }

    /**
     * Format metadata for the response.
     *
     * @return array
     */
    protected function formatMetadata(): array
    {
        return [
            'itemCount' => count($this->resource->getItems()),
            'lastUpdated' => now()->toISOString(), // زمان فعلی سرور
            'version' => '1.0', // نسخه API
            'requestId' => request()->header('X-Request-ID'), // اگر از X-Request-ID استفاده می‌کنید
        ];
    }

    /**
     * Format price to a localized string.
     *
     * @param float $price
     * @return string
     */
    protected function formatPrice(float $price): string
    {
        return number_format($price, 0, '.', ',') . ' تومان';
    }

    /**
     * Get additional data for the response.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        // این متد اکنون فقط برای افزودن داده‌های اضافی که در toArray() نیستند استفاده می‌شود.
        // فیلدهای 'success', 'message', 'timestamp' از اینجا حذف شدند
        // و باید از طریق ->additional() در کنترلر اضافه شوند.
        return [];
    }
}
