<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Show the application's home page.
     *
     * @return \Illuminate\View\View
     */
    public function home()
    {
        return view('home');
    }

    // You can add methods for other pages here as needed
    // public function products() { return view('products'); }
    // public function about() { return view('about'); }
    // public function contact() { return view('contact'); }
    // public function blog() { return view('blog'); }
    // public function cart() { return view('cart'); }
    // public function faq() { return view('faq'); }
}
