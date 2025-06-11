<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

    {{-- Live search results container (outside nav for better absolute positioning) --}}
    {{-- این div باید در ابتدا کاملاً خالی باشد تا هیچ پیامی به صورت پیش‌فرض نمایش داده نشود. --}}
    <div id="live-search-results-container">
        {{-- محتوا توسط جاوااسکریپت به صورت پویا اضافه می‌شود --}}
    </div>

    {{-- Hero Section - This section will now be full width --}}
    {{-- نکته: این yield برای سکشن‌های تمام عرض مانند بنر صفحه اصلی استفاده می‌شود --}}
    @yield('hero_section')

    {{-- Main content section, to be defined in child views --}}
    {{-- 'sm:max-w-6xl' و 'mx-auto' حذف شدند تا محتوا تمام عرض شود --}}
    <main class="max-w-full mt-12 p-6 flex-grow bg-off-white shadow-xl rounded-3xl px-4 sm:px-6 md:px-8">
        @yield('content')
    </main>

    {{-- Footer Component --}}
    {{-- باید اطمینان حاصل شود که محتوای فوتر در partials.footer نیز از max-w-6xl یا container mx-auto استفاده کند --}}
    @include('partials.footer')

    {{-- Message box for notifications --}}
    <div id="message-box" class="message-box">
        <i class="fas fa-check-circle ml-3"></i>
        <span id="message-text"></span>
    </div>

    {{-- Authentication Modal Component --}}
    @include('partials.auth-modal')

    {{-- Main JavaScript file, managed by Vite --}}
    @vite('resources/js/app.js')

    {{-- Inline script for initial setup (like nav height) that depends on DOM load --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const navElement = document.querySelector('nav');
            if (navElement) {
                // این متغیر CSS برای جبران ارتفاع ناوبار در مواقعی که نیاز باشد استفاده می شود.
                document.documentElement.style.setProperty('--nav-height', `${navElement.offsetHeight}px`);
            }
        });

        window.addEventListener('resize', () => {
            const navElement = document.querySelector('nav');
            if (navElement) {
                document.documentElement.style.setProperty('--nav-height', `${navElement.offsetHeight}px`);
            }
        });

        // JavaScript for the Hero Carousel (اسلایدشو بنر اصلی)
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.hero-slide'); // انتخاب تمام اسلایدهای بنر
            const prevBtn = document.getElementById('hero-prev'); // دکمه قبلی
            const nextBtn = document.getElementById('hero-next'); // دکمه بعدی
            const indicatorsContainer = document.getElementById('hero-indicators'); // کانتینر نشانگرها
            let currentSlide = 0; // اسلاید فعلی
            let slideInterval; // متغیر برای نگهداری اینتروال اسلایدشو
            const intervalTime = 9000; // زمان تغییر اسلاید (5 ثانیه)

            // تابع نمایش اسلاید
            function showSlide(index) {
                slides.forEach((slide, i) => {
                    if (i === index) {
                        // نمایش اسلاید فعلی با opacity 100
                        slide.classList.remove('opacity-0');
                        slide.classList.add('opacity-100');
                    } else {
                        // پنهان کردن سایر اسلایدها با opacity 0
                        slide.classList.remove('opacity-100');
                        slide.classList.add('opacity-0');
                    }
                });
                updateIndicators(index); // به‌روزرسانی نشانگرها
            }

            // تابع برای رفتن به اسلاید بعدی
            function nextSlide() {
                currentSlide = (currentSlide + 1) % slides.length;
                showSlide(currentSlide);
            }

            // تابع برای رفتن به اسلاید قبلی
            function prevSlide() {
                currentSlide = (currentSlide - 1 + slides.length) % slides.length;
                showSlide(currentSlide);
            }

            // تابع برای شروع اسلایدشو خودکار
            function startSlideShow() {
                stopSlideShow(); // اطمینان از توقف اینتروال قبلی
                slideInterval = setInterval(nextSlide, intervalTime); // شروع اینتروال جدید
            }

            // تابع برای توقف اسلایدشو خودکار
            function stopSlideShow() {
                clearInterval(slideInterval);
            }

            // تابع برای ایجاد نشانگرهای اسلاید
            function createIndicators() {
                slides.forEach((_, i) => {
                    const indicator = document.createElement('div');
                    // اضافه کردن کلاس 'mx-1' برای ایجاد فاصله بین دایره‌ها
                    indicator.classList.add('w-3', 'h-3', 'bg-gray-300', 'rounded-full', 'cursor-pointer', 'transition-all', 'duration-300', 'mx-1');
                    indicator.dataset.slideIndex = i; // ذخیره ایندکس اسلاید در data-attribute
                    indicator.addEventListener('click', () => {
                        stopSlideShow(); // توقف اسلایدشو هنگام کلیک دستی
                        showSlide(i); // نمایش اسلاید مربوطه
                        currentSlide = i; // به‌روزرسانی اسلاید فعلی
                        startSlideShow(); // شروع مجدد اسلایدشو
                    });
                    indicatorsContainer.appendChild(indicator);
                });
            }

            // تابع برای به‌روزرسانی وضعیت نشانگرها
            function updateIndicators(activeIndex) {
                const indicators = indicatorsContainer.querySelectorAll('div');
                indicators.forEach((indicator, i) => {
                    if (i === activeIndex) {
                        // نشانگر فعال: طوسی تیره
                        indicator.classList.remove('bg-gray-300', 'bg-white', 'bg-opacity-50'); // حذف کلاس‌های قبلی
                        indicator.classList.add('bg-gray-500', 'bg-opacity-100');
                    } else {
                        // نشانگر غیرفعال: طوسی روشن با کمی شفافیت
                        indicator.classList.remove('bg-gray-500', 'bg-opacity-100'); // حذف کلاس‌های قبلی
                        indicator.classList.add('bg-gray-300', 'bg-opacity-50');
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
