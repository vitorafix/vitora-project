<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Product; // اضافه شده برای بررسی موجودی و محصول
use App\Models\ProductVariant; // اضافه شده برای بررسی واریانت محصول
use Illuminate\Support\Facades\Log; // اضافه شده برای لاگ‌گیری

class AddToCartRequest extends FormRequest
{
    /**
     * نمونه محصول کش شده برای جلوگیری از کوئری‌های تکراری.
     *
     * @var \App\Models\Product|null
     */
    protected ?Product $product = null;

    /**
     * تعیین می‌کند که آیا کاربر مجاز به انجام این درخواست است یا خیر.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // بررسی لاگین بودن کاربر
        if (!auth()->check()) {
            return false;
        }

        // بررسی‌های اضافی مثل وضعیت کاربر، سطح دسترسی و غیره
        // فرض می‌کنیم کاربر باید فعال باشد
        return auth()->user()->status === 'active';
    }

    /**
     * قوانین اعتبارسنجی را برای درخواست تعریف می‌کند.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'integer',
                'exists:products,id,status,active', // بررسی فعال بودن محصول
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:' . config('cart.max_quantity_per_item', 999), // حداکثر تعداد هر آیتم را از config می‌گیرد
            ],
            'variant_id' => [
                'nullable',
                'integer',
                'exists:product_variants,id', // بررسی وجود واریانت
            ],
            'attributes' => ['nullable', 'array'], // برای ویژگی‌های اضافی
        ];
        // نکته: برای Rate Limiting بهتر است از middleware استفاده کنید:
        // Route::middleware(['throttle:cart,10,1'])->group(function () {
        //     Route::post('/cart/add', [CartController::class, 'add']);
        // });
    }

    /**
     * منطق اعتبارسنجی سفارشی پس از اجرای قوانین اصلی.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // اگر product_id از قبل در قوانین اصلی خطا داشته، نیازی به بررسی‌های بعدی نیست
            if ($validator->errors()->has('product_id')) {
                return;
            }

            // بررسی اینکه محصول انتخاب شده موجودی دارد یا خیر
            if ($this->productOutOfStock()) {
                $validator->errors()->add('product_id', 'محصول مورد نظر موجود نیست.');
            }

            // بررسی اینکه تعداد درخواستی بیش از موجودی فعلی نیست
            if ($this->exceedsStockLimit()) {
                $validator->errors()->add('quantity', 'تعداد درخواستی بیش از موجودی فعلی محصول است.');
            }

            // بررسی variant اگر ارسال شده و معتبر است
            if ($this->variant_id && !$validator->errors()->has('variant_id')) {
                if (!$this->variantBelongsToProduct()) {
                    $validator->errors()->add('variant_id', 'واریانت انتخاب شده متعلق به این محصول نیست.');
                }
            }

            // بررسی حداکثر تعداد این محصول در سبد خرید فعلی (مثال برای edge case)
            // این منطق باید با نحوه مدیریت سبد خرید شما در سمت بک‌اند هماهنگ باشد.
            // اگر سبد خرید از طریق سرویس‌ها مدیریت می‌شود، این بررسی ممکن است در سرویس انجام شود.
            if ($this->exceedsCartLimit()) {
                $validator->errors()->add('quantity', 'مجموع تعداد این محصول در سبد خرید از حد مجاز بیشتر می‌شود.');
            }

            // Log کردن تلاش‌های مشکوک یا خطاهای اعتبارسنجی
            if ($validator->errors()->count() > 0) {
                Log::warning('Cart validation failed', [
                    'user_id' => auth()->id(),
                    'product_id' => $this->product_id,
                    'quantity' => $this->quantity,
                    'errors' => $validator->errors()->toArray()
                ]);
            }
        });
    }

    /**
     * محصول اعتبارسنجی شده را برمی‌گرداند و آن را کش می‌کند.
     *
     * @return \App\Models\Product|null
     */
    private function getProduct(): ?Product
    {
        // Cache کردن محصول تا چندین بار query نشود
        if (!isset($this->product)) {
            $this->product = Product::find($this->product_id);
        }
        return $this->product;
    }

    /**
     * بررسی می‌کند که آیا محصول انتخاب شده موجودی کافی ندارد.
     *
     * @return bool
     */
    private function productOutOfStock(): bool
    {
        $product = $this->getProduct();
        // اگر محصول یافت نشد یا موجودی آن صفر یا کمتر است
        return !$product || $product->stock <= 0;
    }

    /**
     * بررسی می‌کند که آیا تعداد درخواستی بیش از موجودی محصول است.
     *
     * @return bool
     */
    private function exceedsStockLimit(): bool
    {
        $product = $this->getProduct();

        if (!$product) {
            return false;
        }

        // اگر variant انتخاب شده، موجودی variant را بررسی کن
        if ($this->variant_id) {
            $variant = ProductVariant::find($this->variant_id);
            return $variant && $this->quantity > $variant->stock;
        }

        // در غیر این صورت موجودی محصول اصلی را بررسی کن
        return $this->quantity > $product->stock;
    }

    /**
     * بررسی می‌کند که آیا واریانت انتخاب شده متعلق به محصول اصلی است.
     *
     * @return bool
     */
    private function variantBelongsToProduct(): bool
    {
        $product = $this->getProduct();
        // اگر محصول یافت شد و واریانت به آن تعلق دارد
        return $product && $product->variants()->where('id', $this->variant_id)->exists();
    }

    /**
     * بررسی می‌کند که آیا مجموع تعداد این محصول در سبد خرید از حد مجاز بیشتر می‌شود.
     *
     * @return bool
     */
    private function exceedsCartLimit(): bool
    {
        // این یک مثال ساده است. منطق واقعی باید با نحوه ذخیره سبد خرید شما هماهنگ باشد.
        // مثلاً اگر سبد خرید در دیتابیس است، باید از CartRepository استفاده کنید.
        // فرض: سبد خرید در session ذخیره می‌شود (فقط برای این مثال)
        $cartKey = config('cart.session_key', 'cart');
        $cartItems = session()->get("{$cartKey}.items", []);

        $currentQuantity = 0;
        foreach ($cartItems as $item) {
            if ($item['product_id'] == $this->product_id) {
                // اگر variant هم مطابقت داشته باشد
                if (($item['variant_id'] ?? null) == $this->variant_id) {
                    $currentQuantity += $item['quantity'];
                }
            }
        }

        $totalQuantity = $currentQuantity + $this->quantity;
        // از config('cart.max_quantity_per_item') استفاده می‌کنیم که برای هر آیتم است.
        // اگر max_items_per_cart دارید، باید آن را هم در نظر بگیرید.
        return $totalQuantity > config('cart.max_quantity_per_item', 999);
    }

    /**
     * محصول اعتبارسنجی شده را برمی‌گرداند.
     *
     * @return \App\Models\Product|null
     */
    public function getValidatedProduct(): ?Product
    {
        return $this->getProduct();
    }

    /**
     * تعداد اعتبارسنجی شده را برمی‌گرداند.
     *
     * @return int
     */
    public function getValidatedQuantity(): int
    {
        return (int) $this->quantity;
    }

    /**
     * شناسه واریانت اعتبارسنجی شده را برمی‌گرداند.
     *
     * @return int|null
     */
    public function getValidatedVariantId(): ?int
    {
        return $this->variant_id ? (int) $this->variant_id : null;
    }

    /**
     * آرایه ویژگی‌های اعتبارسنجی شده را برمی‌گرداند.
     *
     * @return array
     */
    public function getValidatedAttributes(): array
    {
        return $this->attributes ?? [];
    }

    /**
     * پیام‌های خطا را برای قوانین اعتبارسنجی تعریف می‌کند.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'لطفاً یک محصول انتخاب کنید.',
            'product_id.integer' => 'شناسه محصول نامعتبر است.',
            'product_id.exists' => 'محصول انتخاب شده یافت نشد یا غیرفعال است.',
            'quantity.required' => 'لطفاً تعداد محصول را وارد کنید.',
            'quantity.integer' => 'تعداد محصول باید عدد صحیح باشد.',
            'quantity.min' => 'حداقل تعداد قابل سفارش :min عدد است.',
            'quantity.max' => 'حداکثر تعداد قابل سفارش :max عدد است.',
            'variant_id.integer' => 'شناسه واریانت نامعتبر است.',
            'variant_id.exists' => 'واریانت محصول یافت نشد.',
            'attributes.array' => 'ویژگی‌های محصول باید در قالب آرایه باشند.',
            // 'throttle' => 'درخواست‌های بیش از حد مجاز. لطفاً کمی بعد دوباره تلاش کنید.', // پیام برای Rate Limiting (اگر به عنوان Custom Rule تعریف شود)
        ];
    }
}
