@extends('layouts.app')

@section('title', 'سبد خرید - چای ابراهیم')

@section('content')
    <section class="my-16 p-8">
        <h1 class="text-4xl font-bold text-center text-brown-900 mb-10 section-heading">سبد خرید شما</h1>

        <div id="cart-empty-message" class="text-center text-gray-700 text-xl py-12 hidden">
            <i class="fas fa-shopping-basket text-6xl text-gray-400 mb-4"></i>
            <p>سبد خرید شما خالی است. همین حالا به <a href="{{ url('/products') }}" class="text-green-800 hover:underline">محصولات</a> ما سر بزنید!</p>
        </div>

        <div id="cart-content" class="hidden">
            <div class="overflow-x-auto bg-white rounded-xl shadow-lg border border-gray-100">
                <table class="w-full cart-table text-gray-700">
                    <thead>
                        <tr>
                            <th class="py-4 px-3 text-right rounded-tr-xl">محصول</th>
                            <th class="py-4 px-3 text-right">قیمت واحد</th>
                            <th class="py-4 px-3 text-center">تعداد</th>
                            <th class="py-4 px-3 text-right">جمع جزء</th>
                            <th class="py-4 px-3 text-center rounded-tl-xl">حذف</th>
                        </tr>
                    </thead>
                    <tbody id="cart-items-container">
                        <!-- Cart items will be loaded here by JavaScript -->
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col md:flex-row justify-between items-center mt-8 p-6 bg-green-50 rounded-xl shadow-md border border-green-100">
                <div class="text-brown-900 font-bold text-2xl mb-4 md:mb-0">
                    جمع کل سبد خرید: <span id="cart-total-price" class="text-green-800 text-3xl">۰ تومان</span>
                </div>
                <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto">
                    <a href="{{ url('/products') }}" class="btn-secondary w-full sm:w-auto text-center">
                        ادامه خرید
                    </a>
                    {{-- Changed button to anchor tag to link to checkout page --}}
                    <a href="{{ url('/checkout') }}" class="btn-primary w-full sm:w-auto text-center">
                        تکمیل خرید
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
