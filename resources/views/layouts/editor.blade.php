<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'پنل ویرایشگر')</title>

    {{-- Vazirmatn Font --}}
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    {{-- Font Awesome CDN --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    {{-- Vite به طور خودکار <link rel="stylesheet"> و <script> را برای فایل‌های ورودی تولید می‌کند --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* این استایل برای جلوگیری از نمایش لحظه‌ای المنت‌های x-cloak قبل از بارگذاری Alpine.js ضروری است */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="font-sans antialiased flex bg-gray-100 min-h-screen">

    {{-- Sidebar برای پنل ویرایشگر --}}
    @include('editor.partials.sidebar')

    <div class="flex-1 flex flex-col">
        {{-- Header برای پنل ویرایشگر --}}
        @include('editor.partials.header')

        {{-- Main content area --}}
        <main class="flex-1 p-6">
            @yield('content')
        </main>

        {{-- Footer Component (اختیاری: می‌توانید یک فوتر مخصوص پنل ادمین داشته باشید یا از فوتر اصلی استفاده کنید) --}}
        {{-- @include('partials.footer') --}}
    </div>

    {{-- Auth Modal (اگر مدال احراز هویت سفارشی دارید) --}}
    @include('partials.auth-modal')

    {{-- Custom Confirmation Modal --}}
    <div id="confirmation-modal-overlay" class="custom-modal-overlay">
        <div class="custom-modal-content">
            <button id="confirmation-modal-close-btn" class="custom-modal-close-btn">
                <i class="fas fa-times"></i>
            </button>
            <i class="fas fa-question-circle text-orange-500 text-5xl mb-6"></i>
            <h3 class="text-2xl font-bold text-brown-900 mb-4" id="confirmation-modal-title">تایید عملیات</h3>
            <p class="text-gray-700 text-lg mb-8" id="confirmation-modal-message">آیا از انجام این عملیات مطمئن هستید؟</p>
            <div class="flex justify-center gap-6">
                <button id="confirmation-modal-confirm-btn" class="btn-primary flex items-center justify-center min-w-[120px]">
                    <i class="fas fa-check ml-2"></i> بله، مطمئنم
                </button>
                <button id="confirmation-modal-cancel-btn" class="btn-secondary flex items-center justify-center min-w-[120px]">
                    <i class="fas fa-times ml-2"></i> لغو
                </button>
            </div>
        </div>
    </div>

    {{-- Stack for custom scripts pushed from child views --}}
    @stack('scripts')

    {{-- این اسکریپت‌ها از app.js لود می‌شوند --}}
    {{-- منطق اسلایدشو و سایر موارد مربوط به فرانت اصلی سایت در این Layout نیاز نیست مگر اینکه در پنل ادمین هم استفاده شوند. --}}
</body>
</html>
