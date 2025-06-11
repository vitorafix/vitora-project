<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product; // مدل Product را اضافه کنید

class PageController extends Controller
{
    /**
     * Show the application's home page.
     *
     * @return \Illuminate\View\View
     */
    public function home()
    {
        // محصولات جدیدترین را بر اساس created_at دریافت کنید
        $latestProducts = Product::orderBy('created_at', 'desc')->limit(6)->get();
        // محصولات پرفروش (مثلاً بر اساس یک فیلد فروش یا به صورت تصادفی در ابتدا)
        // در یک پروژه واقعی، این منطق پیچیده‌تر خواهد بود.
        $featuredProducts = Product::inRandomOrder()->limit(6)->get();

        return view('home', compact('latestProducts', 'featuredProducts'));
    }

    // شما می‌توانید متدهای دیگری برای صفحات دیگر اینجا اضافه کنید
    // public function products() { return view('products'); }
    // public function about() { return view('about'); }
    // public function contact() { return view('contact'); }
    // public function blog() { return view('blog'); }
    // public function cart() { return view('cart'); }
    // public function faq() { return view('faq'); }
}
