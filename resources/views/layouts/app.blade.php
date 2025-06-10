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
    @include('partials.nav')

    {{-- Live search results container (outside nav for better absolute positioning) --}}
    {{-- این div باید در ابتدا کاملاً خالی باشد تا هیچ پیامی به صورت پیش‌فرض نمایش داده نشود. --}}
    <div id="live-search-results-container">
        {{-- محتوا توسط جاوااسکریپت به صورت پویا اضافه می‌شود --}}
    </div>

    {{-- Main content section, to be defined in child views --}}
    <main class="max-w-full sm:max-w-screen-xl mx-auto mt-12 p-6 flex-grow bg-white shadow-xl rounded-3xl px-4 sm:px-6 md:px-8">
        @yield('content')
    </main>

    {{-- Footer Component --}}
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
