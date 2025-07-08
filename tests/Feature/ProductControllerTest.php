<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test; // استفاده از Attributes برای PHPUnit 10+

class ProductControllerTest extends TestCase
{
    use RefreshDatabase; // برای بازنشانی دیتابیس قبل از هر تست

    /**
     * تست می‌کند که صفحه لیست محصولات به درستی نمایش داده می‌شود و محصولات موجود را نشان می‌دهد.
     */
    #[Test]
    public function index_displays_products_when_products_exist(): void
    {
        // ایجاد یک دسته و چند محصول فعال برای تست
        $category = Category::factory()->create(['name' => 'دسته تست']);
        Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'status' => 'active', // مطمئن شوید که محصولات فعال هستند
            'stock' => 10, // مطمئن شوید که موجودی دارند
        ]);
        // یک محصول خاص برای اطمینان از وجود آن در خروجی
        $specificProduct = Product::factory()->create([
            'title' => 'محصول تستی ویژه',
            'category_id' => $category->id,
            'status' => 'active',
            'stock' => 5,
            'slug' => 'test-product-special',
        ]);

        // ارسال درخواست GET به مسیر محصولات
        $response = $this->get(route('products.index'));

        // بررسی وضعیت پاسخ
        $response->assertStatus(200);

        // بررسی اینکه ویو 'products' رندر شده باشد
        $response->assertViewIs('products');

        // بررسی اینکه متغیر 'products' به ویو پاس داده شده باشد
        $response->assertViewHas('products');

        // بررسی اینکه نام محصول خاص و دسته‌بندی آن در خروجی HTML وجود داشته باشد
        // از assertSee استفاده می‌کنیم که به وجود رشته در پاسخ نگاه می‌کند.
        // این روش کمتر شکننده است زیرا به ساختار دقیق HTML وابسته نیست.
        $response->assertSee($specificProduct->title);
        $response->assertSee($category->name);

        // همچنین می‌توانید بررسی کنید که تعداد محصولات صحیح در صفحه نمایش داده شده باشد
        // (اگرچه این تست پیچیده‌تر است و نیاز به پارس کردن HTML دارد)
        // $response->assertSee('<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">');
        // $response->assertElementCount('.card-hover-effect', 4); // 3 + 1 محصول خاص
    }

    /**
     * تست می‌کند که صفحه لیست محصولات پیام مناسب را در صورت عدم وجود محصول نمایش می‌دهد.
     */
    #[Test]
    public function index_shows_message_when_no_products_exist(): void
    {
        // اطمینان از عدم وجود هیچ محصولی در دیتابیس
        Product::query()->delete();
        Category::query()->delete(); // پاک کردن دسته‌ها هم برای اطمینان

        // ارسال درخواست GET به مسیر محصولات
        $response = $this->get(route('products.index'));

        // بررسی وضعیت پاسخ
        $response->assertStatus(200);

        // بررسی اینکه ویو 'products' رندر شده باشد
        $response->assertViewIs('products');

        // بررسی اینکه متغیر 'products' به ویو پاس داده شده باشد (حتی اگر خالی باشد)
        $response->assertViewHas('products');

        // بررسی اینکه پیام "هیچ محصولی یافت نشد" در خروجی وجود داشته باشد
        // این متن در بلوک @empty در products.blade.php قرار دارد.
        $response->assertSeeText('متاسفانه هیچ محصولی برای نمایش یافت نشد.');
    }

    // می‌توانید تست‌های بیشتری برای متدهای دیگر ProductController اضافه کنید
}
