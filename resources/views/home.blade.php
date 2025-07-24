@extends('layouts.app')

@section('title', 'صفحه اصلی - چای ابراهیم')

@section('hero_section')
    <section id="hero-carousel" class="relative overflow-hidden flex flex-col items-center justify-center text-center text-white p-8" style="height: calc(100vh - var(--nav-height, 0px));">
        {{-- Slide 1 --}}
        <div class="hero-slide absolute inset-0 w-full h-full bg-cover bg-center transition-opacity duration-1000 ease-in-out opacity-100" style="background-image: url('{{ asset('uploads/hero-banner.jpg') }}');">
            <div class="absolute inset-0 bg-brown-900 opacity-60"></div>
            <div class="relative z-10 w-full px-4 sm:px-6 lg:px-8 flex flex-col items-center justify-center h-full">
                <h1 class="text-4xl md:text-5xl lg::text-6xl font-extrabold mb-4 leading-tight animate-fade-in-up">
                    عطر و طعم اصیل <br> چای ایرانی
                </h1>
                <p class="text-lg md::text-xl mb-8 animate-fade-in-up animation-delay-300">
                    با چای ابراهیم، لحظات خود را به تجربه‌ای بی‌نظیر تبدیل کنید.
                </p>
                <a href="{{ route('products.index') }}" class="btn-primary text-lg px-8 py-3 animate-fade-in-up animation-delay-600">
                    مشاهده محصولات
                </a>
            </div>
        </div>
        {{-- Slide 2 --}}
        <div class="hero-slide absolute inset-0 w-full h-full bg-cover bg-center transition-opacity duration-1000 ease-in-out opacity-0" style="background-image: url('{{ asset('uploads/hero-banner2.jpg') }}');">
            <div class="absolute inset-0 bg-green-900 opacity-60"></div>
            <div class="relative z-10 w-full px-4 sm:px-6 lg:px-8 flex flex-col items-center justify-center h-full">
                <h1 class="text-4xl md::text-5xl lg::text-6xl font-extrabold mb-4 leading-tight animate-fade-in-up">
                    انتخابی برای <br> هر سلیقه
                </h1>
                <p class="text-lg md::text-xl mb-8 animate-fade-in-up animation-delay-300">
                    از چای سیاه کلاسیک تا دمنوش‌های گیاهی خاص، گشتی در دنیای طعم‌ها.
                </p>
                <a href="{{ route('products.index') }}" class="btn-primary text-lg px-8 py-3 animate-fade-in-up animation-delay-600">
                    کشف طعم‌ها
                </a>
            </div>
        </div>
        {{-- Slide 3 --}}
        <div class="hero-slide absolute inset-0 w-full h-full bg-cover bg-center transition-opacity duration-1000 ease-in-out opacity-0" style="background-image: url('{{ asset('uploads/hero-banner3.jpg') }}');">
            <div class="absolute inset-0 bg-blue-900 opacity-60"></div>
            <div class="relative z-10 w-full px-4 sm:px-6 lg:px-8 flex flex-col items-center justify-center h-full">
                <h1 class="text-4xl md::text-5xl lg::text-6xl font-extrabold mb-4 leading-tight animate-fade-in-up">
                    کیفیت بی‌نظیر <br> از قلب طبیعت
                </h1>
                <p class="text-lg md::text-xl mb-8 animate-fade-in-up animation-delay-300">
                    ما بهترین برگ‌های چای را برای تجربه ای عالی برای شما فراهم می‌کنیم.
                </p>
                <a href="{{ route('about') }}" class="btn-primary text-lg px-8 py-3 animate-fade-in-up animation-delay-600">
                    درباره ما
                </a>
            </div>
        </div>
        {{-- Buttons and indicators --}}
        <button id="hero-prev-btn" class="absolute top-1/2 right-4 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-3 rounded-full z-20 hover:bg-opacity-75 transition-colors duration-300">
            <i class="fas fa-chevron-right text-xl"></i>
        </button>
        <button id="hero-next-btn" class="absolute top-1/2 left-4 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-3 rounded-full z-20 hover:bg-opacity-75 transition-colors duration-300">
            <i class="fas fa-chevron-left text-xl"></i>
        </button>
        <div id="hero-indicators" class="absolute bottom-4 z-20 flex space-x-2"></div>
    </section>
@endsection

@section('content')
    {{-- Latest Products --}}
    <section class="container mx-auto px-4 py-8 md:py-16">
        <h2 class="text-4xl font-extrabold text-brown-900 mb-10 text-center">
            <i class="fas fa-star text-yellow-500 ml-3"></i>
            جدیدترین محصولات
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 gap-8">
            @forelse ($latestProducts as $product)
                <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 transform transition-transform duration-300 hover:scale-105 hover:shadow-xl group">
                    <div class="relative overflow-hidden">
                        <img src="{{ $product->image_url }}" alt="{{ $product->title }}" class="w-full h-64 object-cover transition-transform duration-300 group-hover:scale-110"
                             onerror="this.onerror=null;this.src='https://placehold.co/400x400/E5E7EB/4B5563?text=Product';">
                        <div class="absolute inset-0 bg-black bg-opacity-20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <a href="{{ route('products.show', $product->id) }}" class="btn-primary-outline text-white border-white">
                                مشاهده جزئیات
                            </a>
                        </div>
                    </div>
                    <div class="p-6 text-right">
                        <h3 class="text-xl font-semibold text-brown-900 mb-2">{{ $product->title }}</h3>
                        <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ $product->description }}</p>
                        <div class="flex justify-between items-center mt-4">
                            {{-- قیمت محصول --}}
                            <span class="text-green-700 text-2xl font-bold">{{ number_format($product->price) }} تومان</span>
                            {{-- نقطه اتصال برای دکمه افزودن به سبد خرید React --}}
                            <div id="add-to-cart-root-{{ $product->id }}"></div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-600 col-span-full">هیچ محصول جدیدی برای نمایش وجود ندارد.</p>
            @endforelse
        </div>
        <div class="text-center mt-10">
            <a href="{{ route('products.index') }}" class="btn-secondary">مشاهده همه محصولات <i class="fas fa-arrow-left mr-2"></i></a>
        </div>
    </section>

    {{-- Bestselling Products --}}
    <section class="container mx-auto px-4 py-16 text-center">
        <h2 class="text-4xl font-extrabold text-brown-900 mb-12">
            <i class="fas fa-fire text-orange-500 ml-3"></i>
            محصولات پرفروش
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 gap-8">
            @foreach ($featuredProducts as $product)
                <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 transform transition-transform duration-300 hover:scale-105 hover:shadow-xl group">
                    <div class="relative overflow-hidden">
                        <img src="{{ $product->image_url }}" alt="{{ $product->title }}" class="w-full h-64 object-cover transition-transform duration-300 group-hover:scale-110"
                             onerror="this.onerror=null;this.src='https://placehold.co/400x400/E5E7EB/4B5563?text=Product';">
                        <div class="absolute inset-0 bg-black bg-opacity-20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <a href="{{ route('products.show', $product->id) }}" class="btn-primary-outline text-white border-white">
                                مشاهده جزئیات
                            </a>
                        </div>
                    </div>
                    <div class="p-6 text-right">
                        <h3 class="text-xl font-semibold text-brown-900 mb-2">{{ $product->title }}</h3>
                        <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ $product->description }}</p>
                        <div class="flex justify-between items-center mt-4">
                            {{-- قیمت محصول --}}
                            <span class="text-green-700 text-2xl font-bold">{{ number_format($product->price) }} تومان</span>
                            {{-- نقطه اتصال برای دکمه افزودن به سبد خرید React --}}
                            <div id="add-to-cart-root-{{ $product->id }}"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="text-center mt-10">
            <a href="{{ route('products.index') }}" class="btn-secondary">مشاهده همه محصولات <i class="fas fa-arrow-left mr-2"></i></a>
        </div>
    </section>

    {{-- Customer Reviews --}}
    <section class="bg-green-100 py-16 px-4">
        <div class="container mx-auto text-center">
            <h2 class="text-4xl font-extrabold text-brown-900 mb-12">
                <i class="fas fa-comments text-green-700 ml-3"></i>
                نظرات مشتریان
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-xl shadow-md border border-gray-100 text-right">
                    <p class="text-gray-700 italic mb-6">"چای ابراهیم واقعا عطر و طعم بی‌نظیری داره. از وقتی از این چای استفاده می‌کنم، حس شادابی بیشتری دارم."</p>
                    <div class="flex items-center justify-end">
                        <span class="font-semibold text-brown-900 mr-4">سارا احمدی</span>
                        <img src="https://placehold.co/60x60/F3F4F6/6B7280?text=SA" alt="سارا احمدی" class="w-12 h-12 rounded-full object-cover">
                    </div>
                </div>
                <div class="bg-white p-8 rounded-xl shadow-md border border-gray-100 text-right">
                    <p class="text-gray-700 italic mb-6">"من عاشق دمنوش‌های میوه‌ای چای ابراهیم شدم. هر فنجانش یه دنیا آرامش میده."</p>
                    <div class="flex items-center justify-end">
                        <span class="font-semibold text-brown-900 mr-4">علی قاسمی</span>
                        <img src="https://placehold.co/60x60/F3F4F6/6B7280?text=AG" alt="علی قاسمی" class="w-12 h-12 rounded-full object-cover">
                    </div>
                </div>
                <div class="bg-white p-8 rounded-xl shadow-md border border-gray-100 text-right">
                    <p class="text-gray-700 italic mb-6">"کیفیت چای سیاهشون بی‌نظیره. همیشه از این برند خرید می‌کنم و راضی‌ام."</p>
                    <div class="flex items-center justify-end">
                        <span class="font-semibold text-brown-900 mr-4">زهرا کریمی</span>
                        <img src="https://placehold.co/60x60/F3F4F6/6B7280?text=ZK" alt="زهرا کریمی" class="w-12 h-12 rounded-full object-cover">
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Recent Articles --}}
    <section class="container mx-auto px-4 py-16 text-center">
        <h2 class="text-4xl font-extrabold text-brown-900 mb-12">
            <i class="fas fa-newspaper text-red-500 ml-3"></i>
            آخرین مقالات وبلاگ
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="bg-gray-50 rounded-xl shadow-md overflow-hidden card-hover-effect border border-gray-100">
                <img src="https://placehold.co/400x250/F3F4F6/6B7280?text=تاریخچه+چای" alt="تاریخچه چای" class="w-full h-48 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-brown-900 mb-2">تاریخچه شگفت‌انگیز چای در ایران</h3>
                    <p class="text-gray-600 text-sm mb-4">کشف کنید چای چگونه وارد فرهنگ و زندگی مردم ایران شد.</p>
                    <a href="{{ route('blog.show', 1) }}" class="text-green-800 hover:underline font-semibold">بیشتر بخوانید <i class="fas fa-arrow-left text-sm ml-1"></i></a>
                </div>
            </div>
            <div class="bg-gray-50 rounded-xl shadow-md overflow-hidden card-hover-effect border border-gray-100">
                <img src="https://placehold.co/400x250/F3F4F6/6B7280?text=فواید+چای+سبز" alt="فواید چای سبز" class="w-full h-48 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-brown-900 mb-2">فواید بی‌شمار چای سبز برای سلامتی</h3>
                    <p class="text-gray-600 text-sm mb-4">آشنایی با خواص آنتی‌اکسیدانی و درمانی چای سبز.</p>
                    <a href="{{ route('blog.show', 2) }}" class="text-green-800 hover:underline font-semibold">بیشتر بخوانید <i class="fas fa-arrow-left text-sm ml-1"></i></a>
                </div>
            </div>
            <div class="bg-gray-50 rounded-xl shadow-md overflow-hidden card-hover-effect border border-gray-100">
                <img src="{{ asset('uploads/blog-image-3.jpg') }}" alt="آداب و رسوم سرو چای" class="w-full h-48 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-brown-900 mb-2">آداب و رسوم سرو چای در ایران</h3>
                    <p class="text-gray-600 text-sm mb-4">سفری به تاریخ و فرهنگ غنی چای‌نوشی در سرزمین ایران.</p>
                    <a href="{{ route('blog.show', 3) }}" class="text-green-800 hover:underline font-semibold">بیشتر بخوانید <i class="fas fa-arrow-left text-sm ml-1"></i></a>
                </div>
            </div>
        </div>
        <div class="text-center mt-10">
            <a href="{{ route('blog.index') }}" class="btn-secondary">مشاهده همه مقالات <i class="fas fa-arrow-left mr-2"></i></a>
        </div>
    </section>

    {{-- Contact Us --}}
    <section class="my-16 p-8 bg-brown-900 text-white rounded-2xl shadow-lg mx-auto max-w-6xl text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-6">همین امروز با ما تماس بگیرید!</h2>
        <p class="text-lg mb-8 max-w-2xl mx-auto">
            سوال، پیشنهاد یا نیاز به مشاوره دارید؟ تیم پشتیبانی چای ابراهیم آماده پاسخگویی به شماست.
        </p>
        <a href="{{ route('contact') }}" class="btn-secondary bg-white text-brown-900 hover:bg-gray-100">
            <i class="fas fa-phone-alt ml-2"></i>
            تماس با ما
        </a>
    </section>
@endsection
