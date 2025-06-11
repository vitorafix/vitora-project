<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}"> {{-- این خط جدید برای CSRF Token --}}
    <title>@yield('title', 'چای ابراهیم - عطر و طعم اصیل ایرانی')</title>

    {{-- Tailwind CSS CDN: Recommended to use PostCSS and compile locally in production --}}
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Vazirmatn Font --}}
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    {{-- Font Awesome CDN --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    {{-- Custom CSS for app.css, managed by Vite --}}
    @vite('resources/css/app.css')
</head>
<body class="flex flex-col min-h-screen">

    {{-- Navigation Bar Component --}}
    {{-- باید اطمینان حاصل شود که محتوای ناوبار در partials.nav نیز از max-w-6xl یا container mx-auto استفاده کند --}}
    @include('partials.nav')

    {{-- Live search results will be injected here --}}
    <div id="search-results-container" class="absolute top-16 left-0 right-0 z-40 bg-white shadow-lg rounded-b-lg max-w-2xl mx-auto hidden">
        <div id="search-results" class="py-2">
            {{-- Search results will be loaded here by JavaScript --}}
        </div>
        <div id="search-results-empty" class="text-center p-4 text-gray-500 hidden">
            نتیجه‌ای یافت نشد.
        </div>
    </div>

    {{-- Hero Section (Slideshow) --}}
    @yield('hero_section')

    {{-- Main content --}}
    <main class="flex-grow">
        @yield('content')
    </main>

    {{-- Auth Modal --}}
    @include('partials.auth-modal')

    {{-- Footer Component --}}
    @include('partials.footer')

    {{-- Custom JavaScript for app.js, managed by Vite --}}
    @vite('resources/js/app.js')

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
