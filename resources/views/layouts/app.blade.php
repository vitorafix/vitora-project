<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'چای ابراهیم - عطر و طعم اصیل ایرانی'))</title>

    {{-- Vazirmatn Font --}}
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    {{-- Font Awesome CDN --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    {{-- === اصلاح شده: اطمینان از بارگذاری صحیح CSS و JS توسط Vite === --}}
    {{-- Vite به طور خودکار <link rel="stylesheet"> و <script> را برای فایل‌های ورودی تولید می‌کند --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* CSS برای مدال سفارشی */
        .custom-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6); /* لایه تیره */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000; /* مطمئن شوید روی همه چیز باشد */
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .custom-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .custom-modal-content {
            background-color: #fff;
            padding: 2.5rem;
            border-radius: 1.5rem; /* rounded-2xl */
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2); /* shadow-xl */
            max-width: 480px; /* max-w-md */
            width: 90%;
            text-align: center;
            transform: translateY(-20px); /* برای انیمیشن ورودی */
            transition: transform 0.3s ease;
            position: relative;
        }
        .custom-modal-overlay.active .custom-modal-content {
            transform: translateY(0);
        }
        .custom-modal-close-btn {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6B7280; /* gray-400 */
            transition: color 0.2s ease;
        }
        .custom-modal-close-btn:hover {
            color: #EF4444; /* red-500 */
        }
    </style>
</head>
<body class="font-sans antialiased flex flex-col min-h-screen bg-gray-100">

    {{-- Navigation Bar Component (بهبود یافته برای سازگاری با Breeze و فروشگاه) --}}
    @include('layouts.navigation') {{-- از ناوبار Breeze استفاده می‌کنیم --}}

    {{-- Page Heading (برای صفحات داشبورد و پروفایل Breeze) --}}
    @isset($header)
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
    @endisset

    {{-- Main content area --}}
    <main class="flex-grow">
        {{-- Hero Section (Slideshow) - فقط برای صفحه اصلی --}}
        @yield('hero_section')

        {{-- Content Slot / Section - برای صفحات فروشگاه و همچنین صفحات Breeze --}}
        @if (isset($slot))
            {{ $slot }} {{-- این برای صفحات Breeze (مانند dashboard, login, register) است --}}
        @else
            @yield('content') {{-- این برای صفحات فروشگاه شما (مانند home, products, cart, checkout) است --}}
        @endif
    </main>

    {{-- Auth Modal (اگر مدال احراز هویت سفارشی دارید) --}}
    @include('partials.auth-modal') {{-- این خط را فعال نگه می‌داریم، فرض می‌کنیم فایل آن موجود است --}}

    {{-- Custom Confirmation Modal --}}
    <div id="confirmation-modal-overlay" class="custom-modal-overlay">
        <div class="custom-modal-content">
            <button id="confirmation-modal-close-btn" class="custom-modal-close-btn">
                <i class="fas fa-times"></i>
            </button>
            <i class="fas fa-question-circle text-orange-500 text-5xl mb-6"></i>
            <h3 class="text-2xl font-bold text-brown-900 mb-4" id="confirmation-modal-title">تایید عملیات</h3>
            <p class="text-gray-700 text-lg mb-8" id="confirmation-modal-message">آیا از انجام این عملیات مطمئن هستید؟</p>
            <div class="flex justify-center gap-6"> {{-- Increased gap from gap-4 to gap-6 for more space --}}
                <button id="confirmation-modal-confirm-btn" class="btn-primary flex items-center justify-center min-w-[120px]"> {{-- Added min-w to ensure consistent button size --}}
                    <i class="fas fa-check ml-2"></i> بله، مطمئنم
                </button>
                <button id="confirmation-modal-cancel-btn" class="btn-secondary flex items-center justify-center min-w-[120px]"> {{-- Added min-w to ensure consistent button size --}}
                    <i class="fas fa-times ml-2"></i> لغو
                </button>
            </div>
        </div>
    </div>

    {{-- Footer Component --}}
    @include('partials.footer') {{-- مسیر به partials.footer تغییر یافت تا با نام فایل شما همخوانی داشته باشد --}}

    {{-- Stack for custom scripts pushed from child views (مانند checkout.blade.php) --}}
    @stack('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // محاسبه و تنظیم ارتفاع navbar برای استفاده در CSS (اگر لازم باشد)
            const navBar = document.querySelector('nav');
            if (navBar) {
                const navHeight = navBar.offsetHeight;
                document.documentElement.style.setProperty('--nav-height', `${navHeight}px`);
            }

            // منطق اسلایدشو (Hero Carousel) - این کد از فایل اصلی شما حفظ شده است.
            const slides = document.querySelectorAll('.hero-slide');
            const prevBtn = document.getElementById('hero-prev-btn');
            const nextBtn = document.getElementById('hero-next-btn');
            const indicatorsContainer = document.getElementById('hero-indicators');
            let currentSlide = 0;
            let slideInterval;

            function showSlide(index) {
                slides.forEach((slide, i) => {
                    if (i === index) {
                        slide.classList.remove('opacity-0');
                        slide.classList.add('opacity-100');
                    } else {
                        slide.classList.remove('opacity-100');
                        slide.classList.add('opacity-0');
                    }
                });
                updateIndicators(index);
            }

            function nextSlide() {
                currentSlide = (currentSlide + 1) % slides.length;
                showSlide(currentSlide);
            }

            function prevSlide() {
                currentSlide = (currentSlide - 1 + slides.length) % slides.length;
                showSlide(currentSlide);
            }

            function startSlideShow() {
                stopSlideShow(); // اطمینان از عدم وجود چندین اینتروال
                slideInterval = setInterval(nextSlide, 5000); // هر 5 ثانیه اسلاید بعدی
            }

            function stopSlideShow() {
                    clearInterval(slideInterval);
            }

            function createIndicators() {
                // بررسی وجود indicatorsContainer قبل از دستکاری آن
                if (!indicatorsContainer) {
                    console.warn("Indicators container not found. Skipping indicator creation.");
                    return;
                }
                indicatorsContainer.innerHTML = ''; // پاک کردن نشانگرهای قبلی
                slides.forEach((_, i) => {
                    const indicator = document.createElement('div');
                    indicator.classList.add('w-3', 'h-3', 'rounded-full', 'bg-gray-300', 'bg-opacity-50', 'cursor-pointer', 'transition-all', 'duration-300');
                    indicator.addEventListener('click', () => {
                        stopSlideShow();
                        showSlide(i);
                        // بروزرسانی اسلاید فعلی پس از کلیک روی نشانگر
                        currentSlide = i;
                        startSlideShow();
                    });
                    indicatorsContainer.appendChild(indicator);
                });
                updateIndicators(currentSlide);
            }

            function updateIndicators(activeIndex) {
                if (!indicatorsContainer) { // اضافه کردن چک null
                    return;
                }
                const indicators = indicatorsContainer.querySelectorAll('div');
                indicators.forEach((indicator, i) => {
                    indicator.classList.remove('bg-gray-500', 'bg-opacity-100'); // حذف کلاس‌های قبلی
                    indicator.classList.add('bg-gray-300', 'bg-opacity-50');
                    if (i === activeIndex) {
                        indicator.classList.remove('bg-gray-300', 'bg-opacity-50'); // حذف کلاس‌های قبلی
                        indicator.classList.add('bg-gray-500', 'bg-opacity-100'); // افزودن کلاس‌های فعال
                    }
                });
            }

            // مقداردهی اولیه اسلایدشو
            // اضافه کردن چک slides.length > 0 برای جلوگیری از خطا اگر اسلایدی نیست
            if (slides.length > 0) {
                createIndicators(); // ایجاد نشانگرها
                showSlide(currentSlide); // نمایش اولین اسلاید
                startSlideShow(); // شروع اسلایدشو خودکار

                // افزودن Event Listener برای دکمه‌های ناوبری
                if (prevBtn) {
                    prevBtn.addEventListener('click', () => {
                        stopSlideShow();
                        prevSlide();
                        startSlideShow();
                    });
                }
                if (nextBtn) {
                    nextBtn.addEventListener('click', () => {
                        stopSlideShow();
                        nextSlide();
                        startSlideShow();
                    });
                }

                // اختیاری: مکث اسلایدشو هنگام قرار گرفتن ماوس روی آن
                const heroCarousel = document.getElementById('hero-carousel');
                if (heroCarousel) {
                    heroCarousel.addEventListener('mouseenter', stopSlideShow);
                    heroCarousel.addEventListener('mouseleave', startSlideShow);
                }
            } else {
                console.warn("No hero slides found. Hero carousel will not be initialized.");
            }
        });
    </script>
    {{-- REMOVED THIS LINE: <script src="{{ asset('js/search.js') }}"></script> --}}
</body>
</html>
