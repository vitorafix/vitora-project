<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\ProductStoreRequest; // Import the Form Request
use App\Contracts\ProductServiceInterface; // Use the interface for dependency injection
use App\Exceptions\ImageProcessingException; // Use the custom exception
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // Import Storage facade
use Illuminate\View\View; // Use for type hinting view return type
use App\Models\Category; // Import Category model to pass to create/edit views
use Illuminate\Support\Str; // این خط برای استفاده از Str::slug اضافه شده است

class ProductController extends Controller
{
    /**
     * Constructor to inject the ProductService interface.
     *
     * @param ProductServiceInterface $productService
     */
    public function __construct(
        private ProductServiceInterface $productService // Use constructor property promotion
    ) {}

    /**
     * Display a listing of the products.
     * Includes eager loading of category and images.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        // Eager load the 'category' and 'images' relationships to avoid N+1 query problem.
        // The 'images' relationship is defined in the Product model.
        // We are chaining 'with' to your existing query.
        $products = Product::with(['category', 'images'])->latest()->paginate(12);
        // Pass the productService instance to the view
        return view('products', compact('products'))->with('productService', $this->productService);
    }

    /**
     * Display the specified product.
     * Includes eager loading of category and images.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\View\View
     */
    public function show(Product $product): View
    {
        // Eager load the 'category' and 'images' relationships for a single product.
        // This ensures that when you access $product->images, they are already loaded.
        $product->load(['category', 'images']);

        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->inRandomOrder()
            ->limit(4)
            ->get();

        // Pass the productService instance to the view for single product page as well
        return view('product-single', compact('product', 'relatedProducts'))->with('productService', $this->productService);
    }

    /**
     * Show the form for creating a new product.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        $categories = Category::all(); // Fetch all categories to populate the dropdown
        return view('product-create', compact('categories'));
    }

    /**
     * Store a newly created product in storage.
     * Handles main image and multiple gallery images.
     *
     * @param  \App\Http\Requests\ProductStoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ProductStoreRequest $request): \Illuminate\Http\RedirectResponse
    {
        // Validation is handled by ProductStoreRequest
        $validatedData = $request->validated();

        try {
            // Use the service to create the product, passing validated data, main image, and gallery images
            $this->productService->createProduct(
                $validatedData,
                $request->file('image'), // Main image
                $request->file('gallery_images') ?? [] // Array of gallery images, default to empty array if none
            );

            return redirect()->route('products.index')->with('success', 'محصول با موفقیت اضافه شد.');
        } catch (ImageProcessingException $e) {
            // Catch the specific ImageProcessingException for user-friendly messages
            return back()->withInput()->with('error', 'خطا در پردازش تصویر: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Catch any other general exceptions
            return back()->withInput()->with('error', 'خطای ناشناخته در ایجاد محصول: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified product.
     * Eager loads images for the editing form.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\View\View
     */
    public function edit(Product $product): View
    {
        // Eager load images for the editing form to display existing images.
        $product->load('images');
        $categories = Category::all(); // Fetch all categories to populate the dropdown
        return view('product-edit', compact('product', 'categories'));
    }

    /**
     * Update the specified product in storage.
     * Handles main image, multiple gallery images, and deletion of existing images.
     *
     * @param  \App\Http\Requests\ProductStoreRequest  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ProductStoreRequest $request, Product $product): \Illuminate\Http\RedirectResponse
    {
        // Validation is handled by ProductStoreRequest
        $validatedData = $request->validated();

        try {
            // Use the service to update the product
            $this->productService->updateProduct(
                $product,
                $validatedData,
                $request->file('image'), // New main image
                $request->boolean('remove_image'), // Flag to remove existing main image
                $request->file('gallery_images') ?? [] // Array of new gallery images
                // $request->input('remove_gallery_images') ?? [] // Array of IDs of gallery images to remove - این خط در ProductController اصلی شما نبود
            );

            return redirect()->route('products.index')->with('success', 'محصول با موفقیت به‌روزرسانی شد.');
        } catch (ImageProcessingException $e) {
            // Catch the specific ImageProcessingException
            return back()->withInput()->with('error', 'خطا در پردازش تصویر: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Catch any other general exceptions
            return back()->withInput()->with('error', 'خطای ناشناخته در به‌روزرسانی محصول: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified product from storage.
     * Image deletion is now handled by the Product model's 'deleting' event and ProductImage Observer.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Product $product): \Illuminate\Http\RedirectResponse
    {
        try {
            // Use the service to delete the product
            // The Product model's 'deleting' event will handle the deletion of associated images.
            $this->productService->deleteProduct($product);

            return redirect()->route('products.index')->with('success', 'محصول با موفقیت حذف شد.');
        } catch (ImageProcessingException $e) {
            // Catch the specific ImageProcessingException
            return back()->with('error', 'خطا در حذف تصویر: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Catch any other general exceptions
            return back()->with('error', 'خطای ناشناخته در حذف محصول: ' . $e->getMessage());
        }
    }
}
