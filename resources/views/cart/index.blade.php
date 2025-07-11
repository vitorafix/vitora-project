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
            <i class="fas fa-box-open ml-2"></i> تایید نهایی
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 md:p-8 border border-gray-200">
        {{-- Cart Items Container (this is where JS will render items) --}}
        <div id="cart-items-container" class="space-y-6">
            {{-- Cart items will be dynamically inserted here by JavaScript --}}
        </div>

        {{-- Empty Cart Message (initially hidden, shown by JS if cart is empty) --}}
        <div id="cart-empty-message" class="text-center py-10 hidden">
            <i class="fas fa-shopping-cart text-gray-400 text-6xl mb-4"></i>
            <p class="text-gray-600 text-xl font-semibold">سبد خرید شما در حال حاضر خالی است.</p>
            <a href="{{ route('products.index') }}" class="mt-6 inline-block btn-primary">شروع خرید</a>
        </div>

        {{-- Cart Summary (Total Price and Checkout Button) --}}
        <div id="cart-summary" class="mt-8 pt-8 border-t-2 border-green-700 hidden">
            {{-- Summary content will be dynamically inserted here by JavaScript --}}
            <div class="flex justify-between items-center text-lg text-gray-700 mb-2">
                <span>جمع فرعی:</span>
                <span id="cart-subtotal-price" class="font-semibold">0 تومان</span>
            </div>
            <div class="flex justify-between items-center text-lg text-red-600 mb-2">
                <span>تخفیف:</span>
                <span id="cart-discount-price" class="font-semibold">0 تومان</span>
            </div>
            <div class="flex justify-between items-center text-lg text-gray-700 mb-2">
                <span>هزینه ارسال:</span>
                <span id="cart-shipping-price" class="font-semibold">0 تومان</span>
            </div>
            <div class="flex justify-between items-center text-lg text-gray-700 mb-4">
                <span>مالیات:</span>
                <span id="cart-tax-price" class="font-semibold">0 تومان</span>
            </div>
            <div class="flex justify-between items-center text-xl font-bold text-brown-900 mb-4">
                <span>جمع کل سبد خرید:</span>
                <span id="cart-total-price">0 تومان</span>
            </div>
            <a href="{{ route('checkout.index') }}" class="btn-primary w-full text-center">تکمیل سفارش</a>
        </div>
    </div>
</section>
@endsection

{{-- No @push('scripts') here... scripts are loaded via Vite or similar in layouts/app.blade.php --}}
