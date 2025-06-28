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
        // شما می‌توانید محصولات مرتبط یا نظرات را نیز در اینجا لود کنید
        return view('product-single', compact('product'));
    }
}
