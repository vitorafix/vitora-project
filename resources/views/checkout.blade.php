@extends('layouts.app')

@section('title', 'تکمیل خرید - چای ابراهیم')

@section('content')
    <section class="my-16 p-8">
        <h1 class="text-4xl font-bold text-center text-brown-900 mb-10 section-heading">تکمیل خرید و پرداخت</h1>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
            <!-- Order Summary Section -->
            <div class="bg-gray-50 p-8 rounded-xl shadow-lg border border-gray-100 card-hover-effect">
                <h3 class="text-3xl font-semibold text-brown-900 mb-6 text-center">خلاصه سفارش</h3>
                <div id="order-summary-content">
                    <!-- Order items will be loaded here by JavaScript -->
                    <div id="order-summary-empty-message" class="text-center text-gray-700 text-lg py-4 hidden">
                        <i class="fas fa-box-open text-5xl text-gray-400 mb-4"></i>
                        <p>سبد خرید شما خالی است!</p>
                        <a href="{{ url('/products') }}" class="text-green-800 hover:underline mt-2 inline-block">بازگشت به فروشگاه</a>
                    </div>
                </div>
                
                <div id="order-summary-details" class="mt-8 text-lg hidden">
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span>جمع جزء:</span>
                        <span id="subtotal-price" class="font-semibold text-gray-700">۰ تومان</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span>هزینه ارسال:</span>
                        <span id="shipping-cost" class="font-semibold text-gray-700">۰ تومان</span>
                    </div>
                    <div class="flex justify-between py-2 total-row rounded-b-lg">
                        <span class="text-brown-900 text-xl">مجموع کل:</span>
                        <span id="total-final-price" class="text-green-800 text-2xl">۰ تومان</span>
                    </div>
                </div>
            </div>

            <!-- Shipping Information & Payment Method Section -->
            <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-100 card-hover-effect">
                <h3 class="text-3xl font-semibold text-brown-900 mb-6 text-center">اطلاعات ارسال</h3>
                <form id="checkout-form" class="space-y-6">
                    <div>
                        <label for="full-name" class="block text-gray-700 text-lg font-medium mb-2">نام و نام خانوادگی:</label>
                        <input type="text" id="full-name" name="full-name" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-800 transition-all duration-300 text-gray-800" placeholder="نام کامل شما" required>
                    </div>
                    <div>
                        <label for="phone" class="block text-gray-700 text-lg font-medium mb-2">شماره تلفن:</label>
                        <input type="tel" id="phone" name="phone" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-800 transition-all duration-300 text-gray-800" placeholder="مثال: 09123456789" required>
                    </div>
                    <div>
                        <label for="address" class="block text-gray-700 text-lg font-medium mb-2">آدرس کامل:</label>
                        <textarea id="address" name="address" rows="3" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-800 transition-all duration-300 text-gray-800 resize-y" placeholder="خیابان، کوچه، پلاک، واحد" required></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="city" class="block text-gray-700 text-lg font-medium mb-2">شهر:</label>
                            <input type="text" id="city" name="city" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-800 transition-all duration-300 text-gray-800" placeholder="شهر شما" required>
                        </div>
                        <div>
                            <label for="postal-code" class="block text-gray-700 text-lg font-medium mb-2">کد پستی:</label>
                            <input type="text" id="postal-code" name="postal-code" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-800 transition-all duration-300 text-gray-800" placeholder="مثال: ۱۲۳۴۵۶۷۸۹۰" required>
                        </div>
                    </div>

                    <h3 class="text-3xl font-semibold text-brown-900 mt-10 mb-6 text-center">روش پرداخت</h3>
                    <div class="bg-green-50 p-6 rounded-xl border border-green-200 text-center text-gray-700">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="payment-method" value="online" class="form-radio text-green-800 h-5 w-5 ml-2" checked>
                            <span class="text-lg font-medium">پرداخت آنلاین (درگاه بانکی)</span>
                        </label>
                        <p class="text-sm text-gray-600 mt-2">به درگاه امن بانکی منتقل خواهید شد.</p>
                    </div>

                    <button type="submit" id="pay-button" class="btn-primary w-full mt-8">
                        <i class="fas fa-credit-card ml-2"></i> پرداخت و تکمیل سفارش
                        <span id="loader" class="loader hidden"></span>
                    </button>
                </form>
            </div>
        </div>
    </section>
@endsection
