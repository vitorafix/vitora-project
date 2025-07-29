{{-- resources/views/layouts/admin.blade.php --}}
{{-- This layout is specifically for the admin panel and does NOT extend app.blade.php --}}

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'پنل مدیریت')</title>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Vazirmatn Font --}}
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    {{-- Font Awesome CDN --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- SheetJS (Excel) CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <!-- jsPDF (PDF) CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- html2canvas (for PDF from HTML) CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- Vite Assets for Admin Panel --}}
    {{-- app.js خودش admin.js را ایمپورت می‌کند، بنابراین admin.js نباید اینجا دوباره اضافه شود --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* این استایل برای جلوگیری از نمایش لحظه‌ای المنت‌های x-cloak قبل از بارگذاری Alpine.js ضروری است */
        [x-cloak] { display: none !important; }

        /* CSS برای مدال سفارشی */
        .custom-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .custom-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .custom-modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            max-width: 90%;
            width: 500px;
            transform: translateY(-20px);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
            position: relative;
        }

        .custom-modal-overlay.active .custom-modal-content {
            transform: translateY(0);
            opacity: 1;
        }

        .custom-modal-close-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #6b7280;
            cursor: pointer;
            transition: color 0.2s;
        }

        .custom-modal-close-btn:hover {
            color: #ef4444;
        }

        /* Sidebar styles */
        .sidebar {
            width: 250px; /* Default expanded width */
            transition: width 0.3s ease;
        }

        .sidebar-collapsed {
            width: 80px; /* Collapsed width */
        }

        .sidebar-collapsed .nav-text {
            display: none;
        }

        .main-content-shifted {
            margin-right: 250px; /* Shift content when sidebar is expanded */
            transition: margin-right 0.3s ease;
        }

        .main-content-full {
            margin-right: 80px; /* Shift content when sidebar is collapsed */
        }

        /* Active section content */
        .section-content {
            display: none;
        }

        .section-content.active {
            display: block;
        }

        /* Custom styles for report actions dropdown */
        .report-actions-container {
            position: absolute;
            top: 100%; /* Position below the button */
            left: 0; /* Align with the left edge of the button */
            width: 200px; /* Adjust width as needed */
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 20;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: opacity 0.2s ease-out, transform 0.2s ease-out, visibility 0.2s ease-out;
            transform-origin: top center; /* For better animation */
            padding: 0.5rem 0; /* Add some padding inside the dropdown */
        }

        .report-actions-container.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .report-actions-container a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #4b5563; /* Tailwind gray-700 */
            text-decoration: none;
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
        }

        .report-actions-container a:hover {
            background-color: #f3f4f6; /* Tailwind gray-100 */
            color: #10b981; /* Tailwind green-500 */
        }

        .report-actions-container a i {
            margin-left: 0.5rem; /* Space between icon and text for RTL */
            color: #9ca3af; /* Tailwind gray-400 */
        }

        .report-actions-container a:hover i {
            color: #10b981; /* Tailwind green-500 on hover */
        }
    </style>
</head>
<body class="font-sans antialiased flex h-screen overflow-hidden">

    {{-- Sidebar --}}
    <aside id="sidebar" class="sidebar bg-white shadow-lg fixed top-0 right-0 h-full overflow-y-auto z-50 flex flex-col sidebar-expanded">
        <div class="p-4 flex items-center justify-between border-b border-gray-200">
            <h1 class="text-2xl font-bold text-brown-900 nav-text">پنل مدیریت</h1>
            <button id="sidebar-toggle" class="text-gray-600 hover:text-green-800 focus:outline-none">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
        <nav class="mt-5 flex-grow space-y-1">
            <!-- Dashboard -->
            <a href="#" onclick="showSection('dashboard')" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 group">
                <i class="fas fa-tachometer-alt fa-fw text-gray-500 group-hover:text-green-700"></i>
                <span class="ms-3 nav-text">داشبورد</span>
            </a>
            <!-- Products -->
            <a href="#" onclick="showSection('products')" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 group">
                <i class="fas fa-box-open fa-fw text-gray-500 group-hover:text-green-700"></i>
                <span class="ms-3 nav-text">محصولات</span>
            </a>
            <!-- Orders -->
            <a href="#" onclick="showSection('orders')" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 group">
                <i class="fas fa-shopping-cart fa-fw text-gray-500 group-hover:text-green-700"></i>
                <span class="ms-3 nav-text">سفارشات</span>
            </a>
            <!-- Customer Management -->
            <a href="#" onclick="showSection('customers')" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 group">
                <i class="fas fa-users fa-fw text-gray-500 group-hover:text-green-700"></i>
                <span class="ms-3 nav-text">مشتریان</span>
            </a>
            <!-- Reports -->
            <a href="#" onclick="showSection('reports')" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 group">
                <i class="fas fa-chart-bar fa-fw text-gray-500 group-hover:text-green-700"></i>
                <span class="ms-3 nav-text">گزارشات</span>
            </a>
            <!-- Marketing -->
            <a href="#" onclick="showSection('marketing')" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 group">
                <i class="fas fa-bullhorn fa-fw text-gray-500 group-hover:text-green-700"></i>
                <span class="ms-3 nav-text">بازاریابی</span>
            </a>
            <!-- Discounts -->
            <a href="#" onclick="showSection('discounts')" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 group">
                <i class="fas fa-tags fa-fw text-gray-500 group-hover:text-green-700"></i>
                <span class="ms-3 nav-text">تخفیفات</span>
            </a>
            <!-- Content Management -->
            <a href="#" onclick="showSection('content-management')" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 group">
                <i class="fas fa-file-alt fa-fw text-gray-500 group-hover:text-green-700"></i>
                <span class="ms-3 nav-text">مدیریت محتوا</span>
            </a>
            <!-- Comments -->
            <a href="#" onclick="showSection('comments')" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 group">
                <i class="fas fa-comments fa-fw text-gray-500 group-hover:text-green-700"></i>
                <span class="ms-3 nav-text">نظرات</span>
            </a>
            <!-- Support -->
            <a href="#" onclick="showSection('support')" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 group">
                <i class="fas fa-life-ring fa-fw text-gray-500 group-hover:text-green-700"></i>
                <span class="ms-3 nav-text">پشتیبانی</span>
            </a>
            <!-- Shipping -->
            <a href="#" onclick="showSection('shipping')" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 group">
                <i class="fas fa-truck fa-fw text-gray-500 group-hover:text-green-700"></i>
                <span class="ms-3 nav-text">حمل و نقل</span>
            </a>
            <!-- Payments -->
            <a href="#" onclick="showSection('payments')" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 group">
                <i class="fas fa-credit-card fa-fw text-gray-500 group-hover:text-green-700"></i>
                <span class="ms-3 nav-text">پرداخت‌ها</span>
            </a>
            <!-- Analytics -->
            <a href="#" onclick="showSection('analytics')" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 group">
                <i class="fas fa-chart-line fa-fw text-gray-500 group-hover:text-green-700"></i>
                <span class="ms-3 nav-text">تحلیل‌ها</span>
            </a>
            <!-- Settings -->
            <a href="#" onclick="showSection('settings')" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 group">
                <i class="fas fa-cogs fa-fw text-gray-500 group-hover:text-green-700"></i>
                <span class="ms-3 nav-text">تنظیمات</span>
            </a>
            <!-- User Management -->
            <a href="#" onclick="showSection('user-management')" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 group">
                <i class="fas fa-user-cog fa-fw text-gray-500 group-hover:text-green-700"></i>
                <span class="ms-3 nav-text">مدیریت کاربران</span>
            </a>
            <!-- Backup -->
            <a href="#" onclick="showSection('backup')" class="flex items-center p-2 text-gray-700 rounded-lg hover:bg-green-50 hover:text-green-700 group">
                <i class="fas fa-database fa-fw text-gray-500 group-hover:text-green-700"></i>
                <span class="ms-3 nav-text">پشتیبان‌گیری</span>
            </a>
        </nav>
        <!-- User Profile/Logout at bottom -->
        <div class="p-4 border-t border-gray-200 mt-auto">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img class="w-8 h-8 rounded-full" src="https://placehold.co/32x32/FF6F61/FFFFFF?text=AD" alt="User avatar">
                    <span class="ms-2 font-medium text-brown-900 nav-text">مدیر سیستم</span>
                </div>
                <button onclick="logoutUser()" class="text-gray-600 hover:text-red-600 focus:outline-none">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </div>
        </div>
    </aside>

    <!-- Main Content Area Wrapper -->
    <div id="main-content-wrapper" class="flex-grow p-4 main-content-shifted overflow-auto h-screen">
        {{-- Header for Admin Panel Sections --}}
        <header class="bg-white shadow rounded-lg p-4 mb-4 flex items-center justify-between">
            <h2 id="current-section-title" class="text-xl font-semibold text-brown-900">داشبورد</h2>
            <div class="flex items-center space-x-4 space-x-reverse">
                <span class="text-gray-600 text-sm" id="current-time"></span>
                <div class="relative">
                    <button id="notification-button" class="text-gray-600 hover:text-green-800 focus:outline-none">
                        <i class="fas fa-bell text-lg"></i>
                        <span id="notification-count" class="absolute top-0 right-0 -mt-1 -mr-1 bg-red-600 text-white text-xs rounded-full px-1 py-0.5 hidden">0</span>
                    </button>
                    <div id="notification-dropdown" class="absolute left-0 mt-2 w-64 bg-white rounded-lg shadow-lg z-10 hidden">
                        <div class="p-4 border-b border-gray-200 text-brown-900">اعلانات</div>
                        <ul id="notification-list" class="divide-y divide-gray-200">
                            <!-- Notifications will be loaded here -->
                            <li class="p-3 text-gray-500">موردی برای نمایش نیست.</li>
                        </ul>
                        <div class="p-3 text-center text-green-700 hover:text-green-800 cursor-pointer">مشاهده همه</div>
                    </div>
                </div>
            </div>
        </header>

        {{-- Content Slot / Section - for admin panel sections --}}
        <main class="flex-grow">
            @yield('admin_content') {{-- This will hold dashboard, user management etc. --}}
        </main>

        {{-- Custom Confirmation Modal --}}
        <div id="confirm-modal-overlay" class="custom-modal-overlay hidden">
            <div class="custom-modal-content max-w-md text-center">
                <h3 class="text-2xl font-bold text-brown-900 mb-4">تایید عملیات</h3>
                <p id="confirm-message" class="text-gray-700 text-lg mb-8"></p>
                <div class="flex justify-center gap-6">
                    <button id="confirm-yes" class="btn-danger flex items-center justify-center min-w-[100px]">
                        <i class="fas fa-check ml-2"></i> بله
                    </button>
                    <button id="confirm-no" class="btn-secondary flex items-center justify-center min-w-[100px]">
                        <i class="fas fa-times ml-2"></i> خیر
                    </button>
                </div>
            </div>
        </div>

        {{-- User Edit/Add Modal (Hidden by default) --}}
        <div id="user-modal-overlay" class="custom-modal-overlay hidden">
            <div class="custom-modal-content">
                <button id="user-modal-close-btn" class="custom-modal-close-btn">
                    <i class="fas fa-times"></i>
                </button>
                <h3 class="text-2xl font-bold text-brown-900 mb-4" id="user-modal-title">ویرایش کاربر</h3>
                
                <!-- Form Error Messages -->
                <div id="form-errors" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul id="error-list" class="list-disc list-inside"></ul>
                </div>

                <form id="user-form" class="space-y-4">
                    @csrf {{-- CSRF Token for Laravel forms --}}
                    <input type="hidden" id="user-form-method" name="_method" value="POST"> {{-- For PUT/PATCH requests --}}
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="user-id" class="block text-right text-sm font-medium text-gray-700">شناسه کاربر:</label>
                            <input type="text" id="user-id" name="id" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-100 cursor-not-allowed" 
                                   readonly>
                        </div>
                        <div>
                            <label for="username" class="block text-right text-sm font-medium text-gray-700">
                                نام کاربری: <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="username" name="username" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-2 focus:ring-brown-500 focus:border-brown-500" 
                                   required>
                            <div class="invalid-feedback text-red-500 text-sm mt-1 hidden"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-right text-sm font-medium text-gray-700">
                                ایمیل: <span class="text-red-500">*</span>
                            </label>
                            <input type="email" id="email" name="email" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-2 focus:ring-brown-500 focus:border-brown-500" 
                                   required>
                            <div class="invalid-feedback text-red-500 text-sm mt-1 hidden"></div>
                        </div>
                        <div>
                            <label for="role" class="block text-right text-sm font-medium text-gray-700">
                                نقش: <span class="text-red-500">*</span>
                            </label>
                            <select id="role" name="role" 
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-2 focus:ring-brown-500 focus:border-brown-500" 
                                    required>
                                <option value="">انتخاب کنید</option>
                                <option value="admin">مدیر</option>
                                <option value="user">کاربر</option>
                                <option value="editor">ویرایشگر</option>
                                <option value="moderator">مدیر محتوا</option>
                            </select>
                            <div class="invalid-feedback text-red-500 text-sm mt-1 hidden"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div id="password-field">
                            <label for="password" class="block text-right text-sm font-medium text-gray-700">
                                رمز عبور: <span class="text-red-500" id="password-required">*</span>
                            </label>
                            <input type="password" id="password" name="password" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-2 focus:ring-brown-500 focus:border-brown-500">
                            <div class="invalid-feedback text-red-500 text-sm mt-1 hidden"></div>
                            <small class="text-gray-500">حداقل 8 کاراکتر</small>
                        </div>
                        <div id="password-confirm-field">
                            <label for="password_confirmation" class="block text-right text-sm font-medium text-gray-700">
                                تکرار رمز عبور: <span class="text-red-500" id="password-confirm-required">*</span>
                            </label>
                            <input type="password" id="password_confirmation" name="password_confirmation" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-2 focus:ring-brown-500 focus:border-brown-500">
                            <div class="invalid-feedback text-red-500 text-sm mt-1 hidden"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="status" class="block text-right text-sm font-medium text-gray-700">وضعیت:</label>
                            <select id="status" name="status" 
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-2 focus:ring-brown-500 focus:border-brown-500">
                                <option value="active">فعال</option>
                                <option value="inactive">غیرفعال</option>
                                <option value="suspended">معلق</option>
                            </select>
                        </div>
                        <div>
                            <label for="phone" class="block text-right text-sm font-medium text-gray-700">شماره تلفن:</label>
                            <input type="tel" id="phone" name="phone" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-2 focus:ring-brown-500 focus:border-brown-500"
                                   placeholder="09123456789">
                            <div class="invalid-feedback text-red-500 text-sm mt-1 hidden"></div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-4 mt-6">
                        <button type="submit" class="btn-primary flex items-center" id="submit-btn">
                            <i class="fas fa-save ml-2"></i>
                            <span>ذخیره تغییرات</span>
                        </button>
                        <button type="button" id="user-form-cancel-btn" class="btn-secondary flex items-center">
                            <i class="fas fa-times ml-2"></i>
                            لغو
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
