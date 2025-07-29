<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'چای ابراهیم')</title>

    {{-- REQUIRED FOR AJAX REQUESTS: CSRF Token --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Vazirmatn Font -->
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    {{-- Vite CSS and JS --}}
    {{-- app.js مسئول ایمپورت کردن سایر ماژول‌ها (مانند cart.js, search.js, auth.js, navbar_new.js) است --}}
    {{-- تغییر: مسیر app.js به core/app.js اصلاح شد --}}
    @vite(['resources/css/app.css', 'resources/js/core/app.js'])

    <style>
        /* تنظیم فونت Vazirmatn برای کل بدنه */
        body {
            font-family: 'Vazirmatn', sans-serif;
        }

        /* استایل برای جلوگیری از نمایش لحظه‌ای المنت‌های x-cloak قبل از بارگذاری Alpine.js */
        [x-cloak] { display: none !important; }

        /* استایل‌های سفارشی برای فیلدهای ورودی (از register.blade.php) */
        .input-field {
            display: block;
            width: 100%;
            padding: 0.5rem 1rem; /* px-4 py-2 */
            border-radius: 0.5rem; /* rounded-lg */
            border: 1px solid #d1d5db; /* border-gray-300 */
            background-color: #ffffff; /* bg-white */
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* shadow-sm */
            transition: all 0.2s ease-in-out;
            font-size: 1rem; /* text-base */
            color: #1f2937; /* text-gray-900 */
        }
        .dark .input-field {
            border-color: #4b5563; /* dark:border-gray-600 */
            background-color: #374151; /* dark:bg-gray-700 */
            color: #ffffff; /* dark:text-white */
        }
        .input-field:focus {
            border-color: #10b981; /* focus:border-green-500 */
            outline: none;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.5); /* focus:ring-green-500 */
        }
        .input-field::placeholder {
            color: #9ca3af; /* placeholder-gray-400 */
        }

        /* استایل برای پیام‌های خطا (شبیه‌سازی x-input-error) */
        .error-message {
            margin-top: 0.5rem; /* mt-2 */
            font-size: 0.875rem; /* text-sm */
            color: #ef4444; /* text-red-500 */
        }

        /* استایل برای پیام‌های وضعیت (شبیه‌سازی x-auth-session-status) */
        .session-status {
            background-color: #dbeafe; /* bg-blue-100 */
            border: 1px solid #93c5fd; /* border-blue-400 */
            color: #1d4ed8; /* text-blue-700 */
            padding: 0.75rem 1rem; /* px-4 py-3 */
            border-radius: 0.25rem; /* rounded */
            position: relative;
            margin-bottom: 1rem; /* mb-4 */
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center">

    {{-- محتوای اصلی صفحه در اینجا قرار می‌گیرد --}}
    @yield('content')

    {{-- اسکریپت‌های سفارشی که از ویوهای فرزند push می‌شوند (اگر نیازی باشد) --}}
    @stack('scripts')

    {{-- Confirmation Modal structure (اگر قبلاً در app.blade.php تعریف نشده است) --}}
    {{-- این مدال برای نمایش پیام‌های تایید به کاربر استفاده می‌شود --}}
    <div id="confirm-modal-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-xl w-full max-w-sm text-center">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4"></h3>
            <p id="confirm-message" class="text-gray-600 dark:text-gray-400 mb-6"></p>
            <div class="flex justify-center space-x-4 rtl:space-x-reverse">
                <button id="confirm-yes" class="btn-primary px-6 py-2">بله</button>
                <button id="confirm-no" class="btn-secondary px-6 py-2">خیر</button>
            </div>
        </div>
    </div>
</body>
</html>
