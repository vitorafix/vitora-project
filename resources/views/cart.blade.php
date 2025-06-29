@extends('layouts.app')

@section('title', 'سبد خرید - چای ابراهیم')

@section('content')
<section class="container mx-auto px-4 py-8 md:py-16 max-w-6xl">
    <h1 class="text-4xl font-extrabold text-brown-900 mb-12 text-center">
        <i class="fas fa-shopping-cart text-green-700 ml-3"></i>
        سبد خرید شما
    </h1>

    {{-- Progress Bar --}}
    <div class="flex justify-between items-center bg-gray-100 rounded-full p-2 mb-10 shadow-inner text-sm md:text-base lg:text-lg">
        <div class="flex-1 text-center p-2 rounded-full bg-green-700 text-white font-bold flex items-center justify-center transition-all duration-300">
            <i class="fas fa-check-circle ml-2"></i> تکمیل سفارش
        </div>
        <div class="flex-1 text-center p-2 text-gray-600 font-semibold flex items-center justify-center transition-all duration-300">
            <i class="fas fa-credit-card ml-2"></i> انتخاب شیوه پرداخت
        </div>
        <div class="flex-1 text-center p-2 text-gray-600 font-semibold flex items-center justify-center transition-all duration-300">
            <i class="fas fa-truck ml-2"></i> انتخاب شیوه ارسال
        </div>
        <div class="flex-1 text-center p-2 text-gray-600 font-semibold flex items-center justify-center transition-all duration-300">
            <i class="fas fa-clipboard-check ml-2"></i> تایید سفارش
        </div>
    </div>

    {{-- Cart Content --}}
    <div id="cart-content" class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 p-6 md:p-8">

        {{-- Empty Cart Message (initially hidden, shown by JS if cart is empty) --}}
        <div id="cart-empty-message" class="text-center py-10 hidden">
            <i class="fas fa-shopping-cart text-gray-400 text-6xl mb-4"></i>
            <p class="text-gray-600 text-xl font-semibold mb-2">سبد خرید شما خالی است.</p>
            <p class="text-gray-500">برای شروع خرید، محصولات مورد علاقه خود را اضافه کنید.</p>
            <a href="{{ route('products.index') }}" class="btn-primary mt-6 inline-flex items-center">
                شروع خرید
                <i class="fas fa-arrow-left mr-2"></i>
            </a>
        </div>

        {{-- Cart Items Container (this is where JS will render items) --}}
        <div id="cart-items-container" class="hidden">
            {{-- Cart items will be dynamically inserted here by JavaScript --}}
        </div>

        {{-- Cart Summary (Total Price and Checkout Button) --}}
        <div id="cart-summary" class="mt-8 pt-8 border-t-2 border-green-700 hidden">
            {{-- Summary content will be dynamically inserted here by JavaScript --}}
        </div>
    </div>
</section>
@endsection

{{-- No @push('scripts') here, as app.js (which imports cart.js) is already loaded in layouts/app.blade.php --}}

{{-- REMOVED THE INLINE SCRIPT FROM HERE. ALL CART JS LOGIC IS NOW IN resources/js/cart.js --}}
