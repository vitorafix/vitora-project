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
    {{-- @include('partials.auth-modal') --}} {{-- اگر این فایل وجود ندارد، باید آن را بسازید یا کامنت کنید --}}

    {{-- Footer Component --}}
    @include('partials.footer')

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

            // منطق اسلایدشو (Hero Carousel)
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
                indicatorsContainer.innerHTML = ''; // پاک کردن نشانگرهای قبلی
                slides.forEach((_, i) => {
                    const indicator = document.createElement('div');
                    indicator.classList.add('w-3', 'h-3', 'rounded-full', 'bg-gray-300', 'bg-opacity-50', 'cursor-pointer', 'transition-all', 'duration-300');
                    indicator.addEventListener('click', () => {
                        stopSlideShow();
                        showSlide(i);
                        startSlideShow();
                    });
                    indicatorsContainer.appendChild(indicator);
                });
                updateIndicators(currentSlide);
            }

            function updateIndicators(activeIndex) {
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
            }
        });
    </script>
</body>
</html>
