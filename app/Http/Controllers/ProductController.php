<?php

namespace App\Http\Controllers;

use App\Models\Product; // مدل Product را ایمپورت کنید
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     * محصولات را لیست می‌کند (صفحه همه محصولات).
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $products = Product::latest()->paginate(12); // مثلاً 12 محصول در هر صفحه
        return view('products', compact('products'));
    }

    /**
     * Display the specified product.
     * محصول مشخص شده را نمایش می‌دهد (صفحه جزئیات محصول).
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\View\View
     */
    public function show(Product $product)
    {
        // 1. واکشی محصولات مرتبط
        // محصولاتی که در همان دسته محصول فعلی هستند اما خود محصول فعلی نیستند.
        // تا 4 محصول مرتبط را می‌آوریم.
        $relatedProducts = Product::where('category_id', $product->category_id)
                                ->where('id', '!=', $product->id) // مطمئن شوید محصول فعلی شامل نمی‌شود
                                ->inRandomOrder() // به صورت تصادفی مرتب کند
                                ->limit(4) // حداکثر 4 محصول
                                ->get();

        // 2. ارسال محصول اصلی و محصولات مرتبط به ویو
        return view('product-single', compact('product', 'relatedProducts'));
    }
}

