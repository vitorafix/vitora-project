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

        /** @var CartContentsResponse $this */
        $response = [
            'items' => $this->formatItems($this->items),
            'summary' => $this->formatSummary(),
        ];

        // اضافه کردن metadata به صورت شرطی
        if ($this->includeMetadata) {
            $response['metadata'] = $this->formatMetadata($request);
        }

        return $response;
    }

    /**
     * Determine if the current request is for a mobile client.
     * بررسی می‌کند که آیا درخواست فعلی برای کلاینت موبایل است یا خیر.
     *
     * @return bool
     */
    private function isMobileRequest(): bool
    {
        // این فلگ توسط یک Middleware یا Service Provider تنظیم می‌شود.
        // مثال: app()->instance('isMobileRequest', true);
        return app()->bound('isMobileRequest') && app('isMobileRequest') === true;
    }

    /**
     * Format summary based on optimization level.
     *
     * @return array
     */
    private function formatSummary(): array
    {
        $summary = [
            'totalQuantity' => $this->totalQuantity,
            'totalPrice' => $this->totalPrice,
            'isEmpty' => $this->totalQuantity === 0,
        ];

        // برای موبایل، فقط قیمت فرمت شده اضافه می‌کنیم
        if (!$this->isMobileRequest()) {
            $summary['formattedTotalPrice'] = $this->formatPrice($this->totalPrice);
            $summary['currency'] = config('cart.currency.code', 'IRR');
        }
        return $summary;
    }

    /**
     * Format metadata for response.
     *
     * @param Request $request
     * @return array
     */
    private function formatMetadata(Request $request): array
    {
        $metadata = [
            'itemCount' => count($this->items),
            'lastUpdated' => now()->toISOString(),
        ];

        // اضافه کردن اطلاعات تکمیلی برای غیرموبایل
        if (!$this->isMobileRequest()) {
            $metadata['version'] = config('cart.api.version', '1.0');
            $metadata['requestId'] = $request->header('X-Request-ID');
        }
        return $metadata;
    }

    /**
     * Format cart items for JSON response.
     *
     * @param array $items
     * @return array
     */
    private function formatItems(array $items): array
    {
        return collect($items)->map(function ($item) {
            if (!$this->isValidCartItem($item)) {
                Log::warning('Invalid cart item structure detected, skipping item.', ['item' => $item]);
                return null;
            }
            $product = (object) $item['product'];
            $formattedItem = [
                'id' => $item['id'],
                'product' => $this->formatProduct($product),
                'quantity' => $item['quantity'],
                'unitPrice' => $item['price'],
                'totalPrice' => $item['quantity'] * $item['price'],
            ];

            // اضافه کردن اطلاعات تکمیلی برای غیرموبایل
            if (!$this->isMobileRequest()) {
                $formattedItem['formattedUnitPrice'] = $this->formatPrice($item['price']);
                $formattedItem['formattedTotalPrice'] = $this->formatPrice($item['quantity'] * $item['price']);
                $formattedItem['addedAt'] = isset($item['created_at']) ? Carbon::parse($item['created_at'])->toISOString() : null;
                $formattedItem['updatedAt'] = isset($item['updated_at']) ? Carbon::parse($item['updated_at'])->toISOString() : null;
            }
            return $formattedItem;
        })->filter()->values()->toArray();
    }

    /**
     * Format product data based on optimization level.
     *
     * @param object $product
     * @return array
     */
    private function formatProduct(object $product): array
    {
        $formattedProduct = [
            'id' => $product->id,
            'name' => $product->title ?? 'نامشخص',
            'inStock' => ($product->stock ?? 0) > 0,
        ];

        // اضافه کردن اطلاعات تکمیلی برای غیرموبایل
        if (!$this->isMobileRequest()) {
            $formattedProduct['slug'] = $product->slug ?? null;
            $formattedProduct['image'] = $product->image ?? null;
            $formattedProduct['stockQuantity'] = $product->stock ?? 0;
        }
        return $formattedProduct;
    }

    /**
     * Helper method to validate cart item structure.
     *
     * @param array $item
     * @return bool
     */
    private function isValidCartItem(array $item): bool
    {
        return isset($item['product'], $item['quantity'], $item['price']);
    }

    /**
     * Format price with currency.
     *
     * @param float $price
     * @return string
     */
    private function formatPrice(float $price): string
    {
        $currencySymbol = config('cart.currency.symbol', 'تومان');
        return number_format($price, 0, '.', ',') . ' ' . $currencySymbol;
    }

    /**
     * Set whether metadata should be included.
     *
     * @param bool $includeMetadata
     * @return self
     */
    public function withMetadata(bool $includeMetadata = true): self
    {
        $this->includeMetadata = $includeMetadata;
        return $this;
    }

    /**
     * Create a new resource instance optimized for mobile.
     * یک نمونه Resource جدید ایجاد می‌کند که برای موبایل بهینه‌سازی شده است.
     *
     * @param mixed $resource
     * @return self
     */
    public static function forMobile($resource): self
    {
        // در این رویکرد جدید، mobileOptimized از طریق constructor تنظیم نمی‌شود،
        // بلکه از isMobileRequest() در متدها استفاده می‌شود.
        // این متد صرفاً با includeMetadata=false برای سادگی پاسخ موبایل استفاده می‌شود.
        return new static($resource, false);
    }

    /**
     * Create a new lightweight resource instance without metadata.
     * یک نمونه Resource سبک جدید بدون متادیتا ایجاد می‌کند.
     *
     * @param mixed $resource
     * @return self
     */
    public static function withoutMetadata($resource): self
    {
        return new static($resource, false);
    }

    /**
     * Get additional data for the response.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        // برای موبایل، پاسخ ساده‌تر
        if ($this->isMobileRequest()) {
            return [
                'success' => true,
                'timestamp' => now()->toISOString(),
            ];
        }
        return [
            'success' => true,
            'message' => 'سبد خرید با موفقیت دریافت شد',
            'timestamp' => now()->toISOString(),
        ];
    }
}
