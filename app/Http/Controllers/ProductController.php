<?php

namespace App\Http\Controllers;

use App\Models\Product; // مدل Product را ایمپورت کنید
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // نمایش همه محصولات (فعلا بدون صفحه بندی، بعدا اضافه میشه)
        $products = Product::orderBy('created_at', 'desc')->get(); // همه محصولات
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
        // نمایش جزئیات یک محصول خاص
        return view('products.show', compact('product'));
    }
}
