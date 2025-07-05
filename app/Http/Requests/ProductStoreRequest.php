<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\ProductStoreRequest; // Import the Form Request
use App\Services\ProductService; // Import the Product Service
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // Import Storage facade

class ProductController extends Controller
{
    protected $productService;

    /**
     * Constructor to inject the ProductService.
     *
     * @param ProductService $productService
     */
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the products.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Fetch products using the service or directly from the model if service only handles CRUD
        $products = Product::latest()->paginate(12);
        return view('products', compact('products'));
    }

    /**
     * Display the specified product.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\View\View
     */
    public function show(Product $product)
    {
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return view('product-single', compact('product', 'relatedProducts'));
    }

    /**
     * Show the form for creating a new product.
     * (اگر فرم ساخت محصول داری)
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('product-create');
    }

    /**
     * Store a newly created product in storage.
     *
     * @param  \App\Http\Requests\ProductStoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ProductStoreRequest $request)
    {
        // Validation is handled by ProductStoreRequest
        $validatedData = $request->validated();

        try {
            // Use the service to create the product, passing validated data and the image file
            $this->productService->createProduct($validatedData, $request->file('image'));

            return redirect()->route('products.index')->with('success', 'محصول با موفقیت اضافه شد.');
        } catch (\Exception $e) {
            // Handle any exceptions during product creation (e.g., image upload error)
            return back()->withInput()->with('error', 'خطا در ایجاد محصول: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified product.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\View\View
     */
    public function edit(Product $product)
    {
        return view('product-edit', compact('product'));
    }

    /**
     * Update the specified product in storage.
     *
     * @param  \App\Http\Requests\ProductStoreRequest  $request // Reusing the same request for validation
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ProductStoreRequest $request, Product $product)
    {
        // Validation is handled by ProductStoreRequest
        $validatedData = $request->validated();

        try {
            // Use the service to update the product
            $this->productService->updateProduct($product, $validatedData, $request->file('image'), $request->boolean('remove_image'));

            return redirect()->route('products.index')->with('success', 'محصول با موفقیت به‌روزرسانی شد.');
        } catch (\Exception $e) {
            // Handle any exceptions during product update
            return back()->withInput()->with('error', 'خطا در به‌روزرسانی محصول: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified product from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Product $product)
    {
        try {
            // Use the service to delete the product
            $this->productService->deleteProduct($product);

            return redirect()->route('products.index')->with('success', 'محصول با موفقیت حذف شد.');
        } catch (\Exception $e) {
            // Handle any exceptions during product deletion
            return back()->with('error', 'خطا در حذف محصول: ' . $e->getMessage());
        }
    }
}

