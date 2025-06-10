@extends('layouts.app')

@section('title', 'صفحه اصلی - چای ابراهیم')

{{-- این سکشن 'hero_section' حالا به yield جدید در app.blade.php فرستاده می‌شود --}}
@section('hero_section')
    <section id="hero-section" class="relative overflow-hidden flex flex-col items-center justify-center text-center text-white p-8" style="height: calc(100vh - var(--nav-height, 0px));">
        {{-- Background Image Overlay (full width) --}}
        <div class="absolute inset-0 w-full h-full bg-cover bg-center" style="background-image: url('{{ asset('uploads/hero-banner.jpg') }}');">
            <div class="absolute inset-0 bg-brown-900 opacity-60"></div> {{-- Dark overlay --}}
        </div>

        {{-- Content - Centered and Max-width controlled (now full width within padding) --}}
        <div class="relative z-10 w-full px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold mb-4 leading-tight animate-fade-in-up">
                عطر و طعم اصیل <br> چای ابراهیم
            </h1>
            <p class="text-lg md:text-xl lg:text-2xl mb-8 leading-relaxed animate-fade-in-up delay-100">
                از مزارع سرسبز لاهیجان تا فنجان شما، شمیم ناب چای ایرانی
            </p>
            
        </div>
    </section>
@endsection

{{-- بقیه محتوای صفحه اصلی که نیاز به محدودیت عرض دارد، در سکشن 'content' می‌ماند --}}
@section('content')
    {{-- Why choose us section (Top version) - Centered Container --}}
    <section class="my-16 p-8 bg-off-white rounded-xl shadow-lg container mx-auto">
        <h2 class="text-3xl font-bold text-center text-brown-900 mb-10 section-heading">چرا چای ابراهیم را انتخاب کنید؟</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
            <div class="flex flex-col items-center p-6 bg-green-50 rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300">
                <i class="fas fa-leaf text-5xl text-green-800 mb-4"></i>
                <h3 class="text-xl font-semibold text-brown-900 mb-2">عطر و طعم ماندگار</h3>
                <p class="text-gray-700">با استفاده از برگ‌های تازه و دست‌چین شده از بهترین مزارع چای، تجربه‌ای بی‌نظیر از عطر و طعم اصیل را به ارمغان می‌آوریم.</p>
            </div>
            <div class="flex flex-col items-center p-6 bg-green-50 rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300">
                <i class="fas fa-award text-5xl text-green-800 mb-4"></i>
                <h3 class="text-xl font-semibold text-brown-900 mb-2">اصالت ایرانی</h3>
                <p class="text-gray-700">محصولات چای ابراهیم، نماینده‌ای از سنت دیرینه کشت و فرآوری چای در ایران است که کیفیت بی‌رقیب را تضمین می‌کند.</p>
            </div>
            <div class="flex flex-col items-center p-6 bg-green-50 rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300">
                <i class="fas fa-hand-holding-heart text-5xl text-green-800 mb-4"></i>
                <h3 class="text-xl font-semibold text-brown-900 mb-2">کیفیت بی‌نظیر</h3>
                <p class="text-gray-700">هر دانه چای با دقت انتخاب می‌شود تا بهترین کیفیت و سلامت را برای شما و خانواده‌تان به ارمغان آورد.</p>
            </div>
        </div>
    </section>

    {{-- Product Categories Carousel Section ("محصولات") - Centered Container --}}
    <section class="my-16 p-8 relative bg-gray-50 rounded-xl shadow-lg container mx-auto">
        <h2 class="text-3xl font-bold text-center text-brown-900 mb-10 section-heading">محصولات</h2>

        {{-- Carousel Navigation Buttons --}}
        <button id="carousel-prev" class="absolute top-1/2 -translate-y-1/2 left-4 bg-green-800 text-white p-3 rounded-full shadow-lg hover:bg-green-700 transition-all duration-300 z-20">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button id="carousel-next" class="absolute top-1/2 -translate-y-1/2 right-4 bg-green-800 text-white p-3 rounded-full shadow-lg hover:bg-green-700 transition-all duration-300 z-20">
            <i class="fas fa-chevron-right"></i>
        </button>

        {{-- Product Carousel Items (scrollable) --}}
        {{-- Added 'scroll-smooth' for better user experience when navigating with buttons. --}}
        <div id="product-carousel" dir="ltr" class="flex overflow-x-scroll scrollbar-hide carousel-container pb-4 gap-6 snap-x snap-mandatory px-4 scroll-smooth">
            <a href="{{ url('/products') }}" class="carousel-item flex-shrink-0 snap-center w-[85%] sm:w-[48%] md:w-[32%] lg:w-[25%] bg-off-white p-8 rounded-2xl shadow-xl card-hover-effect border border-gray-100 group">
                <div class="w-full h-56 rounded-xl mb-6 overflow-hidden placeholder-image">
                    {{-- Added 'max-w-full' for better image responsiveness --}}
                    <img src="https://placehold.co/500x350/a77a62/fcf8f5?text=چای+سیاه" alt="چای سیاه" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22500%22%20height%3D%22350%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ccc%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2225%22%20fill%3D%22%23666%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%3E%D8%AA%D8%B5%D9%88%DB%8C%D8%B1%3C%2Ftext%3E%3C%2Fsvg%3E';">
                </div>
                <h3 class="text-3xl font-bold text-brown-900 mb-3 text-center group-hover:text-green-800 transition-colors duration-300">چای سیاه</h3>
                <p class="text-gray-600 text-base text-center">عطر و طعم بی‌نظیر، مناسب برای هر لحظه روز.</p>
            </a>
            <a href="{{ url('/products') }}" class="carousel-item flex-shrink-0 snap-center w-[85%] sm:w-[48%] md:w-[32%] lg:w-[25%] bg-off-white p-8 rounded-2xl shadow-xl card-hover-effect border border-gray-100 group">
                <div class="w-full h-56 rounded-xl mb-6 overflow-hidden placeholder-image">
                    {{-- Added 'max-w-full' for better image responsiveness --}}
                    <img src="https://placehold.co/500x350/789a7f/fcf8f5?text=چای+سبز" alt="چای سبز" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22500%22%20height%3D%22350%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ccc%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2225%22%22%20fill%3D%22%23666%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%3E%D8%AA%D8%B5%D9%88%DB%8C%D8%B1%3C%2Ftext%3E%3C%2Fsvg%3E';">
                </div>
                <h3 class="text-3xl font-bold text-brown-900 mb-3 text-center group-hover:text-green-800 transition-colors duration-300">چای سبز</h3>
                <p class="text-gray-600 text-base text-center">سرشار از خواص طبیعی، طراوت و انرژی.</p>
            </a>
            <a href="{{ url('/products') }}" class="carousel-item flex-shrink-0 snap-center w-[85%] sm:w-[48%] md:w-[32%] lg:w-[25%] bg-off-white p-8 rounded-2xl shadow-xl card-hover-effect border border-gray-100 group">
                <div class="w-full h-56 rounded-xl mb-6 overflow-hidden placeholder-image">
                    {{-- Added 'max-w-full' for better image responsiveness --}}
                    <img src="https://placehold.co/500x350/b08f83/fcf8f5?text=دمنوش‌ها" alt="دمنوش‌ها" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22500%22%20height%3D%22350%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ccc%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2225%22%22%20fill%3D%22%23666%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%3E%D8%AA%D8%B5%D9%88%DB%8C%D8%B1%3C%2Ftext%3E%3C%2Fsvg%3E';">
                </div>
                <h3 class="text-3xl font-bold text-brown-900 mb-3 text-center group-hover:text-green-800 transition-colors duration-300">چای دمنوش‌ها</h3>
                <p class="text-gray-600 text-base text-center">آرامش‌بخش و مفید، از دل طبیعت.</p>
            </a>
            <a href="{{ url('/products') }}" class="carousel-item flex-shrink-0 snap-center w-[85%] sm:w-[48%] md:w-[32%] lg:w-[25%] bg-off-white p-8 rounded-2xl shadow-xl card-hover-effect border border-gray-100 group">
                <div class="w-full h-56 rounded-xl mb-6 overflow-hidden placeholder-image">
                    {{-- Added 'max-w-full' for better image responsiveness --}}
                    <img src="https://placehold.co/500x350/EFEFEF/666666?text=چای+سفید" alt="چای سفید" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22500%22%20height%3D%22350%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ccc%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2225%22%22%20fill%3D%22%23666%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%3E%D8%AA%D8%B5%D9%88%DB%8C%D8%B1%3C%2Ftext%3E%3C%2Fsvg%3E';">
                </div>
                <h3 class="text-3xl font-bold text-brown-900 mb-3 text-center group-hover:text-green-800 transition-colors duration-300">چای سفید</h3>
                <p class="text-gray-600 text-base text-center">ظریف و کمیاب، تجربه‌ای لوکس و خاص.</p>
            </a>
            <a href="{{ url('/products') }}" class="carousel-item flex-shrink-0 snap-center w-[85%] sm:w-[48%] md:w-[32%] lg:w-[25%] bg-off-white p-8 rounded-2xl shadow-xl card-hover-effect border border-gray-100 group">
                <div class="w-full h-56 rounded-xl mb-6 overflow-hidden placeholder-image">
                    {{-- Added 'max-w-full' for better image responsiveness --}}
                    <img src="https://placehold.co/500x350/EFEFEF/666666?text=چای+اولانگ" alt="چای اولانگ" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22500%22%20height%3D%22350%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ccc%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2225%22%22%20fill%3D%22%23666%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%3E%D8%AA%D8%B5%D9%88%DB%8C%D8%B1%3C%2Ftext%3E%3C%2Fsvg%3E';">
                </div>
                <h3 class="text-3xl font-bold text-brown-900 mb-3 text-center group-hover:text-green-800 transition-colors duration-300">چای اولانگ</h3>
                <p class="text-gray-600 text-base text-center">ترکیبی بی‌نظیر از طعم‌های چای سبز و سیاه.</p>
            </a>
            <a href="{{ url('/products') }}" class="carousel-item flex-shrink-0 snap-center w-[85%] sm:w-[48%] md:w-[32%] lg:w-[25%] bg-off-white p-8 rounded-2xl shadow-xl card-hover-effect border border-gray-100 group">
                <div class="w-full h-56 rounded-xl mb-6 overflow-hidden placeholder-image">
                    {{-- Added 'max-w-full' for better image responsiveness --}}
                    <img src="https://placehold.co/500x350/EFEFEF/666666?text=چای+میوه‌ای" alt="چای میوه‌ای" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22500%22%20height%3D%22350%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ccc%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2225%22%22%20fill%3D%22%23666%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%3E%D8%AA%D8%B5%D9%88%DB%8C%D8%B1%3C%2Ftext%3E%3C%2Fsvg%3E';">
                </div>
                <h3 class="text-3xl font-bold text-brown-900 mb-3 text-center group-hover:text-green-800 transition-colors duration-300">چای عطری</h3>
                <p class="text-gray-600 text-base text-center">با رایحه‌های دلنشین و آرام‌بخش طبیعی.</p>
            </a>
        </div>
    </section>

    {{-- Why choose us section (Bottom version - with icons and gradient background) --}}
    <section class="bg-gradient-to-r from-green-800 to-green-700 py-16 rounded-3xl mx-auto container px-8 shadow-2xl">
        <h2 class="text-4xl font-bold text-center text-white mb-10 section-heading after:bg-white">چرا چای ابراهیم را انتخاب کنید؟</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 text-white text-center">
            <div class="p-6 rounded-xl bg-white bg-opacity-10 backdrop-filter backdrop-blur-sm card-hover-effect">
                <span class="text-5xl mb-4 text-amber-300">⭐</span>
                <h3 class="text-2xl font-semibold mb-3">کیفیت بی‌نظیر</h3>
                <p class="text-gray-200 text-base">برگ‌های دستچین شده، فرآوری اصولی و کنترل کیفیت دقیق.</p>
            </div>
            <div class="p-6 rounded-xl bg-white bg-opacity-10 backdrop-filter backdrop-blur-sm card-hover-effect">
                <span class="text-5xl mb-4 text-green-300">🌱</span>
                <h3 class="text-2xl font-semibold mb-3">اصالت ایرانی</h3>
                <p class="text-gray-200 text-base">از مزارع سرسبز لاهیجان با سابقه طولانی در کشت چای.</p>
            </div>
            <div class="p-6 rounded-xl bg-white bg-opacity-10 backdrop-filter backdrop-blur-sm card-hover-effect">
                <span class="text-5xl mb-4 text-red-300">❤️</span>
                <h3 class="text-2xl font-semibold mb-3">عطر و طعم ماندگار</h3>
                <p class="text-gray-200 text-base">چای تازه با عطر طبیعی و طعمی که در خاطر می‌ماند.</p>
            </div>
        </div>
    </section>

    {{-- Latest Products Section --}}
    {{-- Changed bg-white to bg-off-white for a softer background --}}
    <section class="my-16 p-8 bg-off-white shadow-2xl rounded-3xl">
        <h2 class="text-4xl font-bold text-center text-brown-900 mb-10 section-heading">جدیدترین محصولات</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <div class="bg-off-white p-6 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-200">
                <div class="w-full h-48 rounded-lg mb-4 overflow-hidden flex items-center justify-center border border-gray-300">
                    {{-- Added 'max-w-full' for better image responsiveness --}}
                    <img src="https://placehold.co/400x300/F0F4C3/212121?text=محصول+۱" alt="چای دبش" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22400%22%20height%3D%22300%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ccc%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2225%22%20fill%3D%22%23666%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%3E%D8%AA%D8%B5%D9%88%DB%8C%D8%B1%3C%2Ftext%3E%3C%2Fsvg%3E';">
                </div>
                <h3 class="text-2xl font-semibold text-brown-900 mb-2">چای دبش ممتاز</h3>
                <p class="text-gray-600 text-sm mb-4 leading-normal">چای سیاه قلم ممتاز با طعم و رنگ بی‌نظیر.</p>
                <div class="flex justify-between items-center mt-4">
                    <span class="text-green-800 text-xl font-bold">۱۱۰,۰۰۰ تومان</span>
                    <button class="bg-green-800 text-white px-5 py-2 rounded-full hover:bg-green-700 transition-colors duration-300 shadow-md flex items-center add-to-cart-btn" data-product-id="dabesh" data-product-name="چای دبش ممتاز" data-product-price="110000" data-product-image="https://placehold.co/400x300/F0F4C3/212121?text=محصول+۱">
                        <i class="fas fa-plus-circle ml-2"></i> افزودن
                    </button>
                </div>
            </div>
            <div class="bg-off-white p-6 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-200">
                <div class="w-full h-48 rounded-lg mb-4 overflow-hidden flex items-center justify-center border border-gray-300">
                    {{-- Added 'max-w-full' for better image responsiveness --}}
                    <img src="https://placehold.co/400x300/F0F4C3/212121?text=محصول+۲" alt="چای سیاه ارل گری" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22400%22%20height%3D%22300%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ccc%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2225%22%20fill%3D%22%23666%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%3E%D8%AA%D8%B5%D9%88%DB%8C%D8%B1%3C%2Ftext%3E%3C%2Fsvg%3E';">
                </div>
                <h3 class="text-2xl font-semibold text-brown-900 mb-2">چای ارل گری</h3>
                <p class="text-gray-600 text-sm mb-4 leading-normal">چای سیاه معطر با اسانس طبیعی برگاموت.</p>
                <div class="flex justify-between items-center mt-4">
                    <span class="text-green-800 text-xl font-bold">۱۳۵,۰۰۰ تومان</span>
                    <button class="bg-green-800 text-white px-5 py-2 rounded-full hover:bg-green-700 transition-colors duration-300 shadow-md flex items-center add-to-cart-btn" data-product-id="earl-grey" data-product-name="چای ارل گری" data-product-price="135000" data-product-image="https://placehold.co/400x300/F0F4C3/212121?text=محصول+۲">
                        <i class="fas fa-plus-circle ml-2"></i> افزودن
                    </button>
                </div>
            </div>
            <div class="bg-off-white p-6 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-200">
                <div class="w-full h-48 rounded-lg mb-4 overflow-hidden flex items-center justify-center border border-gray-300">
                    {{-- Added 'max-w-full' for better image responsiveness --}}
                    <img src="https://placehold.co/400x300/F0F4C3/212121?text=محصول+۳" alt="دمنوش به لیمو" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22400%22%20height%3D%22300%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ccc%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2225%22%20fill%3D%22%23666%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%3E%D8%AA%D8%B5%D9%88%DB%8C%D8%B1%3C%2Ftext%3E%3C%2Fsvg%3E';">
                </div>
                <h3 class="text-2xl font-semibold text-brown-900 mb-2">دمنوش به لیمو</h3>
                <p class="text-gray-600 text-sm mb-4 leading-normal">دمنوش آرام‌بخش با طعم دلپذیر به لیمو.</p>
                <div class="flex justify-between items-center mt-4">
                    <span class="text-green-800 text-xl font-bold">۷۰,۰۰۰ تومان</span>
                    <button class="bg-green-800 text-white px-5 py-2 rounded-full hover:bg-green-700 transition-colors duration-300 shadow-md flex items-center add-to-cart-btn" data-product-id="lemon-balm" data-product-name="دمنوش به لیمو" data-product-price="70000" data-product-image="https://placehold.co/400x300/F0F4C3/212121?text=محصول+۳">
                        <i class="fas fa-plus-circle ml-2"></i> افزودن
                    </button>
                </div>
            </div>
            {{-- You can add more latest products here --}}
        </div>
    </section>

    {{-- Call to Action: Shop Now - Centered Container --}}
    {{-- Changed bg-white to bg-off-white for a softer background --}}
    <section class="my-16 p-8 text-center bg-off-white shadow-2xl rounded-3xl container mx-auto">
        <h2 class="text-4xl font-bold text-brown-900 mb-6 section-heading">همین حالا چای خود را انتخاب کنید</h2>
        <p class="text-gray-700 leading-loose max-w-3xl mx-auto mb-10 text-lg">
            دنیایی از طعم‌ها و عطرها در انتظار شماست. با کلیک بر روی دکمه زیر، وارد فروشگاه شوید و محصول مورد علاقه خود را بیابید.
        </p>
        <a href="{{ url('/products') }}" class="bg-brown-900 text-white px-10 py-4 rounded-full text-lg font-semibold hover:bg-brown-800 transition-all duration-300 shadow-xl transform hover:scale-105">
            ورود به فروشگاه
        </a>
    </section>

    {{-- Our Story Section - Centered Container --}}
    {{-- Changed bg-white to bg-off-white for a softer background --}}
    <section class="my-16 p-8 bg-off-white shadow-2xl rounded-3xl container mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div class="text-center md:text-right">
                <h2 class="text-4xl font-bold text-brown-900 mb-6 section-heading">داستان ما</h2>
                <p class="text-gray-700 leading-loose text-lg mb-6">
                    در چای ابراهیم، ما بیش از یک نوشیدنی را ارائه می‌دهیم؛ ما یک سنت دیرینه و فرهنگ غنی را به خانه شما می‌آوریم. با بیش از نیم قرن تجربه در کشت و فرآوری چای، هر برگ با عشق و دقت انتخاب می‌شود تا لحظات آرامش‌بخشی را برای شما رقم بزنیم. تعهد ما به کیفیت، از دل مزارع سرسبز لاهیجان آغاز می‌شود و با فرآوری اصولی و بسته‌بندی نوین، به دست شما می‌رسد. اصالت و طعم بی‌نظیر، رمز ماندگاری چای ابراهیم است.
                </p>
                <a href="{{ url('/about') }}" class="mt-8 bg-green-800 text-white px-8 py-3 rounded-full text-base font-semibold hover:bg-green-700 transition-colors duration-300 shadow-lg card-hover-effect">
                    بیشتر بدانید
                </a>
            </div>
            <div class="relative h-96 rounded-2xl overflow-hidden shadow-xl transform rotate-3 scale-95 origin-bottom-right">
                {{-- Added 'max-w-full' for better image responsiveness --}}
                <img src="https://placehold.co/600x400/e6d3c0/8c7161?text=مزارع+چای" alt="مزارع چای ابراهیم" class="w-full h-full object-cover rounded-2xl" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22600%22%20height%3D%22400%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ccc%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2230%22%20fill%3D%22%23666%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%3E%D8%AA%D8%B5%D9%88%DB%8C%D8%B1%3C%2Ftext%3E%3C%2Fsvg%3E';">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent opacity-30 rounded-2xl"></div>
            </div>
        </div>
    </section>

    {{-- Testimonials Section - Centered Container --}}
    <section class="my-16 p-8 bg-green-800 text-white rounded-xl shadow-lg container mx-auto">
        <h2 class="text-3xl font-bold text-center mb-10 section-heading text-white">نظرات مشتریان</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-white bg-opacity-10 p-6 rounded-lg shadow-md border border-white border-opacity-20">
                <p class="mb-4 text-lg italic">"چای ابراهیم واقعاً فوق‌العاده است! عطری دلنشین و طعمی بی‌نظیر که هر روز صبح من را سرحال می‌کند."</p>
                <p class="font-semibold text-right">- سارا احمدی</p>
            </div>
            <div class="bg-white bg-opacity-10 p-6 rounded-lg shadow-md border border-white border-opacity-20">
                <p class="mb-4 text-lg italic">"سال‌هاست که از چای ابراهیم استفاده می‌کنم و همیشه از کیفیت آن راضی بوده‌ام. اصالت طعم ایرانی را واقعاً حس می‌کنید."</p>
                <p class="font-semibold text-right">- علی کریمی</p>
            </div>
        </div>
    </section>

    {{-- Blog/News Section - Centered Container --}}
    {{-- Changed bg-white to bg-off-white for a softer background --}}
    <section class="my-16 p-8 bg-off-white rounded-xl shadow-lg container mx-auto">
        <h2 class="text-3xl font-bold text-center text-brown-900 mb-10 section-heading">جدیدترین مقالات</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            {{-- Example Blog Post 1 --}}
            <div class="bg-gray-50 rounded-xl shadow-md overflow-hidden card-hover-effect border border-gray-100">
                <img src="https://placehold.co/400x250/F3F4F6/6B7280?text=برداشت+چای" alt="روش‌های نوین برداشت چای" class="w-full h-48 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-brown-900 mb-2">روش‌های نوین برداشت چای</h3>
                    <p class="text-gray-600 text-sm mb-4">آشنایی با پیشرفته‌ترین روش‌های برداشت چای که کیفیت و عطر آن را حفظ می‌کند.</p>
                    <a href="{{ url('/blog/1') }}" class="text-green-800 hover:underline font-semibold">بیشتر بخوانید <i class="fas fa-arrow-left text-sm ml-1"></i></a>
                </div>
            </div>
            {{-- Example Blog Post 2 --}}
            <div class="bg-gray-50 rounded-xl shadow-md overflow-hidden card-hover-effect border border-gray-100">
                <img src="https://placehold.co/400x250/F3F4F6/6B7280?text=فواید+چای+سبز" alt="فواید شگفت‌انگیز چای سبز" class="w-full h-48 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-brown-900 mb-2">فواید شگفت‌انگیز چای سبز</h3>
                    <p class="text-gray-600 text-sm mb-4">مروری بر خواص بی‌شمار چای سبز برای سلامتی و شادابی شما.</p>
                    <a href="{{ url('/blog/2') }}" class="text-green-800 hover:underline font-semibold">بیشتر بخوانید <i class="fas fa-arrow-left text-sm ml-1"></i></a>
                </div>
            </div>
            {{-- Example Blog Post 3 --}}
            <div class="bg-gray-50 rounded-xl shadow-md overflow-hidden card-hover-effect border border-gray-100">
                <img src="https://placehold.co/400x250/F3F4F6/6B7280?text=آداب+چای" alt="آداب و رسوم سرو چای" class="w-full h-48 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-brown-900 mb-2">آداب و رسوم سرو چای در ایران</h3>
                    <p class="text-gray-600 text-sm mb-4">سفری به تاریخ و فرهنگ غنی چای‌نوشی در سرزمین ایران.</p>
                    <a href="{{ url('/blog/3') }}" class="text-green-800 hover:underline font-semibold">بیشتر بخوانید <i class="fas fa-arrow-left text-sm ml-1"></i></a>
                </div>
            </div>
        </div>
        <div class="text-center mt-10">
            <a href="{{ url('/blog') }}" class="btn-secondary">مشاهده همه مقالات <i class="fas fa-arrow-left mr-2"></i></a>
        </div>
    </section>

    {{-- Contact Us CTA - Centered Container --}}
    <section class="my-16 p-8 bg-brown-900 text-white rounded-xl shadow-lg container mx-auto">
        <h2 class="text-3xl font-bold mb-4">سوالی دارید؟</h2>
        <p class="text-lg mb-8">تیم پشتیبانی چای ابراهیم آماده پاسخگویی به شماست.</p>
        <a href="{{ url('/contact') }}" class="btn-primary bg-white text-brown-900 hover:bg-gray-200">تماس با ما <i class="fas fa-phone-alt ml-2"></i></a>
    </section>
@endsection
