<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class PageController extends Controller
{
    /**
     * صفحه اصلی برنامه را نمایش می‌دهد.
     *
     * @return \Illuminate\View\View
     */
    public function home()
    {
        // محصولات جدیدترین را دریافت کن
        $latestProducts = Product::orderBy('created_at', 'desc')->limit(6)->get();

        // محصولات ویژه (به‌صورت تصادفی)
        $featuredProducts = Product::inRandomOrder()->limit(6)->get();

        return view('home', compact('latestProducts', 'featuredProducts'));
    }

    /**
     * صفحه درباره ما را نمایش می‌دهد.
     *
     * @return \Illuminate\View\View
     */
    public function about()
    {
        return view('about');
    }

    // اگر صفحات دیگری نیاز بود، به همین شکل اضافه کنید:
    // public function contact() { return view('contact'); }
    // public function blog() { return view('blog'); }
    // public function cart() { return view('cart'); }
    // public function faq() { return view('faq'); }
}
