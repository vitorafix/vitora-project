<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Repositories\Eloquent\ProductRepository; // فرض می‌کنیم از این ریپازیتوری استفاده می‌کنید
use App\Services\ProductService; // فرض می‌کنیم ProductService را تست می‌کنید
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB; // اضافه شده: برای استفاده از DB Facade
use App\Exceptions\ProductNotFoundException; // اضافه شده: برای استفاده از Exception

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductService $productService;
    protected ProductRepository $productRepository;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock کردن ProductRepository
        $this->productRepository = $this->mock(ProductRepository::class);
        // نمونه‌سازی ProductService با ریپازیتوری Mock شده
        $this->productService = new ProductService($this->productRepository);
    }

    /** @test */
    public function test_create_product(): void
    {
        $productData = [
            'title' => 'چای سبز', // 'name' به 'title' تغییر یافت
            'description' => 'یک چای سبز عالی',
            'price' => 50000,
            'stock' => 100,
            'status' => 'active',
            'image' => null,
            'category_id' => 1, // یا یک category_id معتبر
        ];

        $this->productRepository->shouldReceive('create')->once()->andReturn(new Product($productData));

        $product = $this->productService->createProduct($productData);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('چای سبز', $product->title);
    }

    /** @test */
    public function test_update_product(): void
    {
        $product = Product::factory()->create(['title' => 'چای سیاه', 'price' => 50000]); // 'name' به 'title' تغییر یافت
        $updatedData = ['title' => 'چای سیاه جدید', 'price' => 55000]; // 'name' به 'title' تغییر یافت

        $this->productRepository->shouldReceive('find')->once()->andReturn($product);
        $this->productRepository->shouldReceive('update')->once()->andReturnUsing(function ($p, $data) {
            $p->fill($data);
            return $p;
        });

        $updatedProduct = $this->productService->updateProduct($product->id, $updatedData);

        $this->assertEquals('چای سیاه جدید', $updatedProduct->title);
        $this->assertEquals(55000, $updatedProduct->price);
    }

    /** @test */
    public function test_delete_product(): void
    {
        $product = Product::factory()->create();

        $this->productRepository->shouldReceive('find')->once()->andReturn($product);
        $this->productRepository->shouldReceive('delete')->once()->andReturn(true);

        $result = $this->productService->deleteProduct($product->id);

        $this->assertTrue($result);
    }

    /** @test */
    public function test_get_product(): void
    {
        $product = Product::factory()->create(['title' => 'چای نمونه']); // 'name' به 'title' تغییر یافت

        $this->productRepository->shouldReceive('find')->once()->andReturn($product);

        $foundProduct = $this->productService->getProduct($product->id);

        $this->assertInstanceOf(Product::class, $foundProduct);
        $this->assertEquals('چای نمونه', $foundProduct->title);
    }

    /** @test */
    public function test_get_product_not_found(): void
    {
        $this->productRepository->shouldReceive('find')->once()->andReturn(null);

        $this->expectException(ProductNotFoundException::class);
        $this->productService->getProduct(999);
    }
}
