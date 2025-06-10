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
    {{-- 'sm:max-w-6xl' برای اطمینان از عرض ثابت محتوا در اندازه های بزرگتر --}}
    <main class="max-w-full sm:max-w-6xl mx-auto mt-12 p-6 flex-grow bg-off-white shadow-xl rounded-3xl px-4 sm:px-6 md:px-8">
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
    </script>
</body>
</html>
