@extends('layouts.app')

@section('title', 'سبد خرید - چای ابراهیم')

@section('content')
<section class="container mx-auto px-4 py-8 md:py-16">
    <h1 class="text-4xl font-extrabold text-brown-900 mb-12 text-center">
        <i class="fas fa-shopping-cart text-green-700 ml-3"></i>
        سبد خرید شما
    </h1>

    <div id="cart-content" class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 p-6 md:p-8">
        {{-- این بخش توسط JavaScript (renderMainCart) پر می‌شود --}}
        <div id="cart-empty-message" class="text-center py-10 hidden">
            <i class="fas fa-shopping-cart text-gray-400 text-6xl mb-4"></i>
            <p class="text-gray-600 text-xl font-semibold mb-2">سبد خرید شما خالی است.</p>
            <p class="text-gray-500">برای شروع خرید، محصولات مورد علاقه خود را اضافه کنید.</p>
            <a href="{{ url('/products') }}" class="btn-primary mt-6 inline-flex items-center">
                شروع خرید
                <i class="fas fa-arrow-left mr-2"></i>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-lg">
                <thead>
                    <tr class="bg-gray-100 text-right text-gray-700 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-right">محصول</th>
                        <th class="py-3 px-6 text-center">قیمت واحد</th>
                        <th class="py-3 px-6 text-center">تعداد</th>
                        <th class="py-3 px-6 text-left">قیمت کل</th>
                    </tr>
                </thead>
                <tbody id="cart-items-container" class="text-gray-600 text-sm font-light">
                    {{-- Cart items will be rendered here by JavaScript --}}
                </tbody>
                <tfoot id="cart-total-row" class="border-t-2 border-gray-200 font-bold text-lg hidden">
                    <tr>
                        <td colspan="3" class="py-4 px-6 text-right text-brown-900">مجموع کل:</td>
                        <td class="py-4 px-6 text-left text-green-700">
                            <span id="cart-total-price-footer">0 تومان</span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-center mt-8 p-4 bg-gray-50 rounded-lg shadow-inner">
            <div class="flex flex-col sm:flex-row gap-4 mb-4 md:mb-0">
                <button id="clear-cart-btn" class="btn-secondary-outline border-red-500 text-red-500 hover:bg-red-50 hover:text-red-700">
                    <i class="fas fa-trash-alt ml-2"></i>
                    خالی کردن سبد
                </button>
                <a href="{{ url('/products') }}" class="btn-secondary-outline">
                    <i class="fas fa-arrow-right ml-2"></i>
                    ادامه خرید
                </a>
            </div>
            <a href="{{ url('/checkout') }}" class="btn-primary text-white text-lg px-8 py-3 transform transition-transform duration-300 hover:scale-105">
                تکمیل سفارش و پرداخت
                <i class="fas fa-credit-card mr-2"></i>
            </a>
        </div>
    </div>
</section>
@endsection
