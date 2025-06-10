@extends('layouts.app')

@section('title', 'محصولات - چای ابراهیم')

@section('content')
    <section class="my-16 p-8">
        <h1 class="text-4xl font-bold text-center text-brown-900 mb-10 section-heading">محصولات چای ابراهیم</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <!-- Product Card 1 -->
            <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-200">
                <div class="w-full h-48 rounded-lg mb-4 overflow-hidden flex items-center justify-center border border-gray-300">
                    <img src="https://placehold.co/400x300/F0F4C3/212121?text=چای+سیاه+دبش" alt="چای سیاه دبش ممتاز" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22400%22%20height%3D%22300%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ccc%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2225%22%20fill%3D%22%23666%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%3Eتصویر%3C%2Ftext%3E%3C%2Fsvg%3E';">
                </div>
                <h3 class="text-2xl font-semibold text-brown-900 mb-2">چای سیاه دبش ممتاز</h3>
                <p class="text-gray-600 text-sm mb-4 leading-normal">چای سیاه قلم ممتاز با طعم و رنگ بی‌نظیر.</p>
                <div class="flex justify-between items-center mt-4">
                    <span class="text-green-800 text-xl font-bold product-price" data-price="110000">۱۱۰,۰۰۰ تومان</span>
                    <button class="bg-green-800 text-white px-5 py-2 rounded-full hover:bg-green-700 transition-colors duration-300 shadow-md flex items-center add-to-cart-btn"
                            data-product-id="ebrahim-tea-black-1"
                            data-product-name="چای سیاه دبش ممتاز"
                            data-product-price="110000"
                            data-product-image="https://placehold.co/400x300/F0F4C3/212121?text=چای+سیاه+دبش">
                        <i class="fas fa-plus-circle ml-2"></i> افزودن
                    </button>
                </div>
            </div>

            <!-- Product Card 2 -->
            <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-200">
                <div class="w-full h-48 rounded-lg mb-4 overflow-hidden flex items-center justify-center border border-gray-300">
                    <img src="https://placehold.co/400x300/F0F4C3/212121?text=چای+سبز+اصیل" alt="چای سبز اصیل" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22400%22%20height%3D%22300%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ccc%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2225%22%20fill%3D%22%23666%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%3Eتصویر%3C%2Ftext%3E%3C%2Fsvg%3E';">
                </div>
                <h3 class="text-2xl font-semibold text-brown-900 mb-2">چای سبز اصیل لاهیجان</h3>
                <p class="text-gray-600 text-sm mb-4 leading-normal">سرشار از آنتی‌اکسیدان‌ها، طعمی تازه و دلنشین.</p>
                <div class="flex justify-between items-center mt-4">
                    <span class="text-green-800 text-xl font-bold product-price" data-price="95000">۹۵,۰۰۰ تومان</span>
                    <button class="bg-green-800 text-white px-5 py-2 rounded-full hover:bg-green-700 transition-colors duration-300 shadow-md flex items-center add-to-cart-btn"
                            data-product-id="ebrahim-tea-green-1"
                            data-product-name="چای سبز اصیل لاهیجان"
                            data-product-price="95000"
                            data-product-image="https://placehold.co/400x300/F0F4C3/212121?text=چای+سبز+اصیل">
                        <i class="fas fa-plus-circle ml-2"></i> افزودن
                    </button>
                </div>
            </div>

            <!-- Product Card 3 -->
            <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-200">
                <div class="w-full h-48 rounded-lg mb-4 overflow-hidden flex items-center justify-center border border-gray-300">
                    <img src="https://placehold.co/400x300/F0F4C3/212121?text=دمنوش+بابونه" alt="دمنوش آرامش بخش" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22400%22%20height%3D%22300%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ccc%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2225%22%20fill%3D%22%23666%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%3Eتصویر%3C%2Ftext%3E%3C%2Fsvg%3E';">
                </div>
                <h3 class="text-2xl font-semibold text-brown-900 mb-2">دمنوش بابونه آرامش‌بخش</h3>
                <p class="text-gray-600 text-sm mb-4 leading-normal">برای لحظات آرامش شبانه و کاهش استرس.</p>
                <div class="flex justify-between items-center mt-4">
                    <span class="text-green-800 text-xl font-bold product-price" data-price="75000">۷۵,۰۰۰ تومان</span>
                    <button class="bg-green-800 text-white px-5 py-2 rounded-full hover:bg-green-700 transition-colors duration-300 shadow-md flex items-center add-to-cart-btn"
                            data-product-id="ebrahim-damnoosh-babouneh-1"
                            data-product-name="دمنوش بابونه آرامش‌بخش"
                            data-product-price="75000"
                            data-product-image="https://placehold.co/400x300/F0F4C3/212121?text=دمنوش+بابونه">
                        <i class="fas fa-plus-circle ml-2"></i> افزودن
                    </button>
                </div>
            </div>

            <!-- Product Card 4 -->
            <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-200">
                <div class="w-full h-48 rounded-lg mb-4 overflow-hidden flex items-center justify-center border border-gray-300">
                    <img src="https://placehold.co/400x300/F0F4C3/212121?text=چای+سفید+خالص" alt="چای سفید خالص" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22400%22%20height%3D%22300%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ccc%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2225%22%20fill%3D%22%23666%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%3Eتصویر%3C%2Ftext%3E%3C%2Fsvg%3E';">
                </div>
                <h3 class="text-2xl font-semibold text-brown-900 mb-2">چای سفید خالص</h3>
                <p class="text-gray-600 text-sm mb-4 leading-normal">کمیاب و ظریف، تجربه‌ای لوکس با خواص بی‌نظیر.</p>
                <div class="flex justify-between items-center mt-4">
                    <span class="text-green-800 text-xl font-bold product-price" data-price="220000">۲۲۰,۰۰۰ تومان</span>
                    <button class="bg-green-800 text-white px-5 py-2 rounded-full hover:bg-green-700 transition-colors duration-300 shadow-md flex items-center add-to-cart-btn"
                            data-product-id="ebrahim-tea-white-1"
                            data-product-name="چای سفید خالص"
                            data-product-price="220000"
                            data-product-image="https://placehold.co/400x300/F0F4C3/212121?text=چای+سفید+خالص">
                        <i class="fas fa-plus-circle ml-2"></i> افزودن
                    </button>
                </div>
            </div>

            <!-- Product Card 5 -->
            <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-200">
                <div class="w-full h-48 rounded-lg mb-4 overflow-hidden flex items-center justify-center border border-gray-300">
                    <img src="https://placehold.co/400x300/F0F4C3/212121?text=چای+اولانگ" alt="چای اولانگ" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22400%22%20height%3D%22300%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ccc%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2225%22%20fill%3D%22%23666%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%3Eتصویر%3C%2Ftext%3E%3C%2Fsvg%3E';">
                </div>
                <h3 class="text-2xl font-semibold text-brown-900 mb-2">چای اولانگ کوهستان</h3>
                <p class="text-gray-600 text-sm mb-4 leading-normal">ترکیبی متعادل از چای سیاه و سبز با طعمی پیچیده.</p>
                <div class="flex justify-between items-center mt-4">
                    <span class="text-green-800 text-xl font-bold product-price" data-price="170000">۱۷۰,۰۰۰ تومان</span>
                    <button class="bg-green-800 text-white px-5 py-2 rounded-full hover:bg-green-700 transition-colors duration-300 shadow-md flex items-center add-to-cart-btn"
                            data-product-id="ebrahim-tea-oolong-1"
                            data-product-name="چای اولانگ کوهستان"
                            data-product-price="170000"
                            data-product-image="https://placehold.co/400x300/F0F4C3/212121?text=چای+اولانگ">
                        <i class="fas fa-plus-circle ml-2"></i> افزودن
                    </button>
                </div>
            </div>

            <!-- Product Card 6 -->
            <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-200">
                <div class="w-full h-48 rounded-lg mb-4 overflow-hidden flex items-center justify-center border border-gray-300">
                    <img src="https://placehold.co/400x300/F0F4C3/212121?text=چای+زعفرانی" alt="چای زعفرانی" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22400%22%20height%3D%22300%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ccc%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2225%22%20fill%3D%22%23666%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%3Eتصویر%3C%2Ftext%3E%3C%2Fsvg%3E';">
                </div>
                <h3 class="text-2xl font-semibold text-brown-900 mb-2">چای سیاه زعفرانی</h3>
                <p class="text-gray-600 text-sm mb-4 leading-normal">ترکیب چای اصیل با عطر دلنشین زعفران ایرانی.</p>
                <div class="flex justify-between items-center mt-4">
                    <span class="text-green-800 text-xl font-bold product-price" data-price="145000">۱۴۵,۰۰۰ تومان</span>
                    <button class="bg-green-800 text-white px-5 py-2 rounded-full hover:bg-green-700 transition-colors duration-300 shadow-md flex items-center add-to-cart-btn"
                            data-product-id="ebrahim-tea-saffron-1"
                            data-product-name="چای سیاه زعفرانی"
                            data-product-price="145000"
                            data-product-image="https://placehold.co/400x300/F0F4C3/212121?text=چای+زعفرانی">
                        <i class="fas fa-plus-circle ml-2"></i> افزودن
                    </button>
                </div>
            </div>

            <!-- Add more product cards here if needed -->

        </div>
    </section>
@endsection
