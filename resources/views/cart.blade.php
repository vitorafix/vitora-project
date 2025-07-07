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
            {{-- مثال ساختار آیتم‌ها که توسط JS رندر می‌شود: --}}
            {{--
            <div class="flex justify-between items-center border-b pb-4 last:border-b-0 last:pb-0"
                data-item-id="[cart_item_id]"
                data-product-id="[product_id]"
                data-item-price="[product_price]">
                <div class="flex items-center">
                    <img src="https://placehold.co/64x64/E0F2F1/004D40?text=Product" alt="[product_name]" class="w-16 h-16 object-cover rounded-lg ml-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">[product_name]</h3>
                        <div class="flex items-center mt-1">
                            <button type="button" class="quantity-btn minus-btn bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-full w-6 h-6 flex items-center justify-center text-lg font-bold transition-colors duration-200" aria-label="کاهش تعداد">
                                -
                            </button>
                            <span class="item-quantity mx-2 text-gray-700 text-base font-medium" data-quantity="[quantity]">
                                [quantity]
                            </span>
                            <button type="button" class="quantity-btn plus-btn bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-full w-6 h-6 flex items-center justify-center text-lg font-bold transition-colors duration-200" aria-label="افزایش تعداد">
                                +
                            </button>
                            <span class="mr-2 text-gray-600 text-sm">عدد</span>
                        </div>
                    </div>
                </div>
                <span class="item-subtotal text-green-700 font-bold text-lg" data-subtotal="[subtotal]">
                    [subtotal_formatted] تومان
                </span>
            </div>
            --}}
        </div>

        {{-- Cart Summary (Total Price and Checkout Button) --}}
        <div id="cart-summary" class="mt-8 pt-8 border-t-2 border-green-700 hidden">
            {{-- Summary content will be dynamically inserted here by JavaScript --}}
            <div class="flex justify-between items-center text-xl font-bold text-brown-900 mb-4">
                <span>جمع کل سبد خرید:</span>
                <span id="cart-total-price">0 تومان</span> {{-- این عنصر اضافه شد --}}
            </div>
            <a href="{{ route('checkout.index') }}" class="btn-primary w-full text-center">تکمیل سفارش</a>
        </div>
    </div>
</section>
@endsection

{{-- No @push('scripts') here, as app.js (which imports cart.js) is already loaded in layouts/app.blade.php --}}

{{-- REMOVED THE INLINE SCRIPT FROM HERE. ALL CART JS LOGIC IS NOW IN resources/js/cart.js --}}
