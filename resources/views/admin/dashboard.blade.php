<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل مدیریت چای ابراهیم</title>
    <!-- CSRF Token (Placeholder for backend integration) -->
    <meta name="csrf-token" content="{{ csrf_token() }}"> {{-- Laravel CSRF token --}}
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Vazirmatn Font -->
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- SheetJS (Excel) CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <!-- jsPDF (PDF) CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- html2canvas (for PDF from HTML) CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: 'Vazirmatn', sans-serif;
            background-color: #f3f4f6;
            color: #333;
        }
        .sidebar {
            background-color: #1a202c;
            color: #cbd5e0;
        }
        .sidebar a {
            color: #cbd5e0;
            padding: 0.75rem 1rem;
            display: block;
            border-radius: 0.375rem;
        }
        .sidebar a:hover {
            background-color: #2d3748;
        }
        .header {
            background-color: #fff;
            border-bottom: 1px solid #e2e8f0;
        }
        .card {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        .btn-primary {
            background-color: #4c51bf;
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
        }
        .btn-primary:hover {
            background-color: #434190;
        }
        .notification-bell {
            position: relative;
            cursor: pointer;
        }
        .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.75rem;
        }
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
            box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2), 0 6px 20px 0 rgba(0,0,0,0.19);
            position: relative;
        }
        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close-button:hover,
        .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        /* Custom styles for the new monthly sales chart */
        .chart-container {
            position: relative;
            height: 300px; /* Adjust height as needed */
            width: 100%;
        }
        #monthlySalesChart {
            direction: ltr; /* Ensure Chart.js renders correctly for data interpretation */
        }

        /* Custom styles for sidebar */
        .sidebar {
            transition: width 0.3s ease-in-out;
            direction: rtl; /* For RTL text */
        }
        .sidebar-expanded {
            width: 256px; /* w-64 */
        }
        .sidebar-collapsed {
            width: 64px; /* w-16 */
        }
        /* Hide text when collapsed */
        .sidebar-collapsed .nav-text {
            display: none;
        }
        /* Ensure content shifts */
        #main-content {
            transition: margin-right 0.3s ease-in-out;
        }
        .main-content-shifted {
            margin-right: 256px; /* For expanded sidebar */
        }
        .main-content-full {
            margin-right: 64px; /* For collapsed sidebar */
        }
        .section-content {
            display: none;
        }
        .section-content.active {
            display: block;
        }
        /* Custom scrollbar for activity log */
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar bg-white shadow-lg fixed top-0 right-0 h-full overflow-y-auto z-50 flex flex-col sidebar-expanded">
        <div class="p-4 flex items-center justify-between border-b">
            <h1 class="text-2xl font-bold text-gray-800 nav-text">پنل مدیریت</h1>
            <button id="sidebar-toggle" class="text-gray-600 hover:text-gray-900 focus:outline-none">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
        <nav class="mt-5 flex-grow">
            <!-- Dashboard -->
            <a href="#" onclick="showSection('dashboard')" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
                <i class="fas fa-tachometer-alt fa-fw text-gray-500 group-hover:text-gray-900"></i>
                <span class="ms-3 nav-text">داشبورد</span>
            </a>
            <!-- Products -->
            <a href="#" onclick="showSection('products')" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group mt-1">
                <i class="fas fa-box-open fa-fw text-gray-500 group-hover:text-gray-900"></i>
                <span class="ms-3 nav-text">محصولات</span>
            </a>
            <!-- Orders -->
            <a href="#" onclick="showSection('orders')" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group mt-1">
                <i class="fas fa-shopping-cart fa-fw text-gray-500 group-hover:text-gray-900"></i>
                <span class="ms-3 nav-text">سفارشات</span>
            </a>
            <!-- Customer Management -->
            <a href="#" onclick="showSection('customers')" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group mt-1">
                <i class="fas fa-users fa-fw text-gray-500 group-hover:text-gray-900"></i>
                <span class="ms-3 nav-text">مشتریان</span>
            </a>
            <!-- Reports -->
            <a href="#" onclick="showSection('reports')" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group mt-1">
                <i class="fas fa-chart-bar fa-fw text-gray-500 group-hover:text-gray-900"></i>
                <span class="ms-3 nav-text">گزارشات</span>
            </a>
            <!-- Marketing -->
            <a href="#" onclick="showSection('marketing')" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group mt-1">
                <i class="fas fa-bullhorn fa-fw text-gray-500 group-hover:text-gray-900"></i>
                <span class="ms-3 nav-text">بازاریابی</span>
            </a>
            <!-- Discounts -->
            <a href="#" onclick="showSection('discounts')" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group mt-1">
                <i class="fas fa-tags fa-fw text-gray-500 group-hover:text-gray-900"></i>
                <span class="ms-3 nav-text">تخفیفات</span>
            </a>
            <!-- Content Management -->
            <a href="#" onclick="showSection('content-management')" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group mt-1">
                <i class="fas fa-file-alt fa-fw text-gray-500 group-hover:text-gray-900"></i>
                <span class="ms-3 nav-text">مدیریت محتوا</span>
            </a>
            <!-- Comments -->
            <a href="#" onclick="showSection('comments')" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group mt-1">
                <i class="fas fa-comments fa-fw text-gray-500 group-hover:text-gray-900"></i>
                <span class="ms-3 nav-text">نظرات</span>
            </a>
            <!-- Support -->
            <a href="#" onclick="showSection('support')" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group mt-1">
                <i class="fas fa-life-ring fa-fw text-gray-500 group-hover:text-gray-900"></i>
                <span class="ms-3 nav-text">پشتیبانی</span>
            </a>
            <!-- Shipping -->
            <a href="#" onclick="showSection('shipping')" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group mt-1">
                <i class="fas fa-truck fa-fw text-gray-500 group-hover:text-gray-900"></i>
                <span class="ms-3 nav-text">حمل و نقل</span>
            </a>
            <!-- Payments -->
            <a href="#" onclick="showSection('payments')" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group mt-1">
                <i class="fas fa-credit-card fa-fw text-gray-500 group-hover:text-gray-900"></i>
                <span class="ms-3 nav-text">پرداخت‌ها</span>
            </a>
            <!-- Analytics -->
            <a href="#" onclick="showSection('analytics')" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group mt-1">
                <i class="fas fa-chart-line fa-fw text-gray-500 group-hover:text-gray-900"></i>
                <span class="ms-3 nav-text">تحلیل‌ها</span>
            </a>
            <!-- Settings -->
            <a href="#" onclick="showSection('settings')" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group mt-1">
                <i class="fas fa-cogs fa-fw text-gray-500 group-hover:text-gray-900"></i>
                <span class="ms-3 nav-text">تنظیمات</span>
            </a>
            <!-- User Management (assuming this is already there or was "کاربران") -->
            <a href="#" onclick="showSection('user-management')" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group mt-1">
                <i class="fas fa-user-cog fa-fw text-gray-500 group-hover:text-gray-900"></i>
                <span class="ms-3 nav-text">مدیریت کاربران</span>
            </a>
            <!-- Backup -->
            <a href="#" onclick="showSection('backup')" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group mt-1">
                <i class="fas fa-database fa-fw text-gray-500 group-hover:text-gray-900"></i>
                <span class="ms-3 nav-text">پشتیبان‌گیری</span>
            </a>
        </nav>
        <!-- User Profile/Logout at bottom -->
        <div class="p-4 border-t mt-auto">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img class="w-8 h-8 rounded-full" src="https://placehold.co/32x32/FF6F61/FFFFFF?text=AD" alt="User avatar">
                    <span class="ms-2 font-medium text-gray-900 nav-text">مدیر سیستم</span>
                </div>
                <button onclick="logoutUser()" class="text-gray-600 hover:text-red-500 focus:outline-none">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div id="main-content" class="flex-grow p-4 main-content-shifted overflow-auto h-screen">
        <!-- Header -->
        <header class="bg-white shadow rounded-lg p-4 mb-4 flex items-center justify-between">
            <h2 id="current-section-title" class="text-xl font-semibold text-gray-800">داشبورد</h2>
            <div class="flex items-center space-x-4 space-x-reverse">
                <span class="text-gray-600 text-sm" id="current-time"></span>
                <div class="relative">
                    <button id="notification-button" class="text-gray-600 hover:text-gray-900 focus:outline-none">
                        <i class="fas fa-bell text-lg"></i>
                        <span id="notification-count" class="absolute top-0 right-0 -mt-1 -mr-1 bg-red-500 text-white text-xs rounded-full px-1 py-0.5 hidden">0</span>
                    </button>
                    <div id="notification-dropdown" class="absolute left-0 mt-2 w-64 bg-white rounded-lg shadow-lg z-10 hidden">
                        <div class="p-4 border-b text-gray-700">اعلانات</div>
                        <ul id="notification-list" class="divide-y divide-gray-200">
                            <!-- Notifications will be loaded here -->
                            <li class="p-3 text-gray-500">موردی برای نمایش نیست.</li>
                        </ul>
                        <div class="p-3 text-center text-blue-500 hover:text-blue-700 cursor-pointer">مشاهده همه</div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Section Content -->
        <section id="dashboard-content" class="section-content active">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <!-- Cards for Dashboard -->
                <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">محصولات موجود</p>
                        <p class="text-2xl font-bold text-gray-900">2,156</p>
                    </div>
                    <i class="fas fa-boxes text-4xl text-blue-400"></i>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">مشتریان جدید</p>
                        <p class="text-2xl font-bold text-gray-900">8,642</p>
                    </div>
                    <i class="fas fa-user-plus text-4xl text-green-400"></i>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">فروش امروز (میلیون تومان)</p>
                        <p class="text-2xl font-bold text-gray-900">45,320</p>
                    </div>
                    <i class="fas fa-money-bill-wave text-4xl text-yellow-400"></i>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">سفارشات جدید</p>
                        <p class="text-2xl font-bold text-gray-900">1,284</p>
                    </div>
                    <i class="fas fa-clipboard-list text-4xl text-red-400"></i>
                </div>
            </div>

            <!-- Charts and Activity Log -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800">نمودار فروش ماهانه</h3>
                    <div class="chart-container">
                        <canvas id="monthlySalesChart"></canvas> <!-- This is the chart from the previous file -->
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800">فعالیت‌های اخیر ادمین</h3>
                    <div id="admin-activity-log" class="space-y-3 h-64 overflow-y-auto custom-scrollbar">
                        <!-- Log entries will be added here by JS -->
                    </div>
                </div>
            </div>
        </section>

        <!-- Dynamic Sections for other menu items -->
        <section id="products-content" class="section-content">
            <h2 class="text-2xl font-semibold mb-4">مدیریت محصولات</h2>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p>محتوای بخش مدیریت محصولات در اینجا قرار می‌گیرد.</p>
            </div>
        </section>

        <section id="orders-content" class="section-content">
            <h2 class="text-2xl font-semibold mb-4">مدیریت سفارشات</h2>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p>محتوای بخش مدیریت سفارشات در اینجا قرار می‌گیرد.</p>
            </div>
        </section>

        <section id="customers-content" class="section-content">
            <h2 class="text-2xl font-semibold mb-4">مدیریت مشتریان</h2>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p>محتوای بخش مدیریت مشتریان در اینجا قرار می‌گیرد.</p>
            </div>
        </section>

        <section id="reports-content" class="section-content">
            <h2 class="text-2xl font-semibold mb-4">گزارشات</h2>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p>این بخش برای نمایش گزارشات مختلف است.</p>
                <div class="mt-4 flex space-x-2 space-x-reverse">
                    <button id="export-excel" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 focus:outline-none">
                        خروجی اکسل
                    </button>
                    <button id="export-pdf" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 focus:outline-none">
                        خروجی PDF
                    </button>
                    <button id="toggle-report-actions" aria-expanded="false" aria-controls="report-actions-container"
                            class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 focus:outline-none relative">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div id="report-actions-container" class="absolute bg-white shadow-lg rounded-lg py-2 right-40 mt-12 w-48 hidden">
                        <a href="#" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">گزارش فروش</a>
                        <a href="#" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">گزارش مشتریان</a>
                        <a href="#" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">گزارش محصولات</a>
                    </div>
                </div>
            </div>
        </section>

        <section id="marketing-content" class="section-content">
            <h2 class="text-2xl font-semibold mb-4">بازاریابی</h2>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p>محتوای بخش بازاریابی و کمپین‌ها در اینجا قرار می‌گیرد.</p>
            </div>
        </section>

        <section id="discounts-content" class="section-content">
            <h2 class="text-2xl font-semibold mb-4">تخفیفات</h2>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p>محتوای بخش مدیریت تخفیفات و کدهای تخفیف در اینجا قرار می‌گیرد.</p>
            </div>
        </section>

        <section id="content-management-content" class="section-content">
            <h2 class="text-2xl font-semibold mb-4">مدیریت محتوا</h2>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p>محتوای بخش مدیریت محتوا (وبلاگ، صفحات) در اینجا قرار می‌گیرد.</p>
            </div>
        </section>

        <section id="comments-content" class="section-content">
            <h2 class="text-2xl font-semibold mb-4">نظرات</h2>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p>محتوای بخش مدیریت نظرات کاربران در اینجا قرار می‌گیرد.</p>
            </div>
        </section>

        <section id="support-content" class="section-content">
            <h2 class="text-2xl font-semibold mb-4">پشتیبانی</h2>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p>محتوای بخش پشتیبانی و تیکت‌ها در اینجا قرار می‌گیرد.</p>
            </div>
        </section>

        <section id="shipping-content" class="section-content">
            <h2 class="text-2xl font-semibold mb-4">حمل و نقل</h2>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p>محتوای بخش مدیریت حمل و نقل و ارسال در اینجا قرار می‌گیرد.</p>
            </div>
        </section>

        <section id="payments-content" class="section-content">
            <h2 class="text-2xl font-semibold mb-4">پرداخت‌ها</h2>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p>محتوای بخش مدیریت پرداخت‌ها و تراکنش‌ها در اینجا قرار می‌گیرد.</p>
            </div>
        </section>

        <section id="analytics-content" class="section-content">
            <h2 class="text-2xl font-semibold mb-4">تحلیل‌ها</h2>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p>محتوای بخش تحلیل‌ها و آمار وب‌سایت در اینجا قرار می‌گیرد.</p>
            </div>
        </section>

        <section id="settings-content" class="section-content">
            <h2 class="text-2xl font-semibold mb-4">تنظیمات</h2>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p>محتوای بخش تنظیمات کلی سیستم در اینجا قرار می‌گیرد.</p>
            </div>
        </section>

        <section id="user-management-content" class="section-content">
            <h2 class="text-2xl font-semibold mb-4">مدیریت کاربران</h2>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p>محتوای بخش مدیریت کاربران (افزودن، ویرایش، حذف) در اینجا قرار می‌گیرد.</p>
            </div>
        </section>

        <section id="backup-content" class="section-content">
            <h2 class="text-2xl font-semibold mb-4">پشتیبان‌گیری</h2>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p>محتوای بخش پشتیبان‌گیری و بازیابی اطلاعات در اینجا قرار می‌گیرد.</p>
            </div>
        </section>

        <!-- Message Box -->
        <div id="message-box" class="fixed bottom-4 left-1/2 -translate-x-1/2 p-4 rounded-lg shadow-lg text-white z-[1000] hidden">
            <p id="message-text"></p>
        </div>

    </div>

    <script>
        // Mock Data
        const users = [
            { id: 1, username: 'admin', role: 'مدیر', lastLocation: '192.168.1.100' },
            { id: 2, username: 'ali.ahmadi', role: 'کاربر', lastLocation: '172.20.10.2' },
            { id: 3, username: 'reza.karimi', role: 'کاربر', lastLocation: '192.168.1.101' },
            { id: 4, username: 'sara.naseri', role: 'کاربر', lastLocation: '10.0.0.5' }
        ];

        let currentUser = { id: 1, username: 'admin', role: 'مدیر', lastLocation: '192.168.1.100' }; // Simulating logged-in admin

        const adminActivityLog = [
            { timestamp: new Date(), username: 'سیستم', action: 'راه‌اندازی پنل', details: 'سیستم آماده کار است.' }
        ];

        // Chart.js instance for monthly sales chart
        let monthlySalesChartInstance;

        // Data for the monthly sales chart (from super-dashboard-v4_Version3.html)
        const monthlySalesData = {
            labels: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
            datasets: [{
                label: 'میزان فروش',
                data: [120, 190, 150, 220, 180, 250, 200, 280, 230, 300, 270, 350], // Sample data in millions
                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1,
                fill: true,
                tension: 0.3
            }]
        };

        const monthlySalesConfig = {
            type: 'line',
            data: monthlySalesData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                        labels: {
                            font: {
                                family: 'Vazirmatn' // Set font for legend
                            }
                        }
                    },
                    tooltip: {
                        rtl: true, // Enable RTL for tooltips
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y.toLocaleString('fa-IR') + ' میلیون تومان';
                                }
                                return label;
                            }
                        },
                        titleFont: {
                            family: 'Vazirmatn'
                        },
                        bodyFont: {
                            family: 'Vazirmatn'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'میزان فروش (میلیون تومان)',
                            font: {
                                family: 'Vazirmatn'
                            }
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('fa-IR');
                            },
                            font: {
                                family: 'Vazirmatn'
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'ماه',
                            font: {
                                family: 'Vazirmatn'
                            }
                        },
                        ticks: {
                            font: {
                                family: 'Vazirmatn'
                            }
                        }
                    }
                },
                layout: {
                    padding: {
                        left: 10,
                        right: 10,
                        top: 10,
                        bottom: 10
                    }
                }
            }
        };

        // Functions from previous file
        function showMessage(message, type = 'info') {
            const msgBox = document.getElementById('message-box');
            const msgText = document.getElementById('message-text');
            msgText.textContent = message;
            msgBox.className = `fixed bottom-4 left-1/2 -translate-x-1/2 p-4 rounded-lg shadow-lg text-white z-[1000] ${type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'} block`;
            setTimeout(() => {
                msgBox.classList.add('hidden');
            }, 3000);
        }

        function logAdminAction(username, action, details) {
            const timestamp = new Date();
            adminActivityLog.push({ timestamp, username, action, details });
            renderActivityLog();
        }

        function renderActivityLog() {
            const logContainer = document.getElementById('admin-activity-log');
            logContainer.innerHTML = '';
            adminActivityLog.slice().reverse().forEach(log => { // Show most recent first
                const logEntry = document.createElement('div');
                logEntry.className = 'p-2 bg-gray-50 rounded-md text-sm text-gray-700';
                logEntry.innerHTML = `
                    <p><span class="font-semibold">${log.username}</span>: ${log.action} - <span class="text-gray-500">${new Date(log.timestamp).toLocaleString('fa-IR')}</span></p>
                    <span class="text-xs text-gray-400">${log.details}</span>
                `;
                logContainer.appendChild(logEntry);
            });
        }

        // Section switching logic
        function showSection(sectionId) {
            const sections = document.querySelectorAll('.section-content');
            sections.forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById(`${sectionId}-content`).classList.add('active');
            document.getElementById('current-section-title').textContent = document.querySelector(`[onclick="showSection('${sectionId}')"] .nav-text`).textContent;
            // Close dropdowns if any are open
            document.getElementById('notification-dropdown').classList.add('hidden');
            const floatingReportToggle = document.getElementById('toggle-report-actions');
            const reportActionsContainer = document.getElementById('report-actions-container');
            if (floatingReportToggle && reportActionsContainer) {
                reportActionsContainer.classList.add('hidden');
                floatingReportToggle.setAttribute('aria-expanded', 'false');
            }

            // If navigating to dashboard, ensure chart is rendered
            if (sectionId === 'dashboard' && !monthlySalesChartInstance) {
                const monthlySalesCtx = document.getElementById('monthlySalesChart').getContext('2d');
                monthlySalesChartInstance = new Chart(monthlySalesCtx, monthlySalesConfig);
            } else if (sectionId === 'dashboard' && monthlySalesChartInstance) {
                 // If already initialized, just resize in case of sidebar toggle
                 monthlySalesChartInstance.resize();
            }
        }

        function logoutUser() {
            showMessage('شما از سیستم خارج شدید.', 'info');
            // In a real application, you would redirect to a login page or clear session.
            console.log('User logged out.');
        }

        // Event Listeners
        document.addEventListener('DOMContentLoaded', () => {
            // Sidebar toggle
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const navTexts = document.querySelectorAll('.nav-text');

            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('sidebar-expanded');
                sidebar.classList.toggle('sidebar-collapsed');
                mainContent.classList.toggle('main-content-shifted');
                mainContent.classList.toggle('main-content-full');

                navTexts.forEach(text => {
                    text.classList.toggle('hidden');
                });

                // Adjust chart size on sidebar toggle
                setTimeout(() => {
                    if (monthlySalesChartInstance) {
                        monthlySalesChartInstance.resize();
                    }
                }, 300); // Small delay to allow CSS transition
            });

            // Notification dropdown
            const notificationButton = document.getElementById('notification-button');
            const notificationDropdown = document.getElementById('notification-dropdown');

            notificationButton.addEventListener('click', (event) => {
                notificationDropdown.classList.toggle('hidden');
                event.stopPropagation(); // Prevent document click from immediately closing
            });

            document.addEventListener('click', (event) => {
                if (!notificationDropdown.contains(event.target) && !notificationButton.contains(event.target)) {
                    notificationDropdown.classList.add('hidden');
                }
            });

            // Report actions dropdown (if exists)
            const floatingReportToggle = document.getElementById('toggle-report-actions');
            const reportActionsContainer = document.getElementById('report-actions-container');

            if (floatingReportToggle && reportActionsContainer) {
                floatingReportToggle.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const isExpanded = floatingReportToggle.getAttribute('aria-expanded') === 'true';
                    floatingReportToggle.setAttribute('aria-expanded', String(!isExpanded));
                    reportActionsContainer.classList.toggle('hidden');
                    reportActionsContainer.classList.toggle('active');
                });

                document.addEventListener('click', (event) => {
                    if (!reportActionsContainer.contains(event.target) && !floatingReportToggle.contains(event.target)) {
                        reportActionsContainer.classList.add('hidden');
                        reportActionsContainer.classList.remove('active');
                        floatingReportToggle.setAttribute('aria-expanded', 'false');
                    }
                });

                // Close report actions when an action is clicked
                reportActionsContainer.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', () => {
                        reportActionsContainer.classList.add('hidden');
                        reportActionsContainer.classList.remove('active'); // Close menu after action
                        floatingReportToggle.setAttribute('aria-expanded', 'false');
                    });
                });
            }

            // Live clock update
            function updateClock() {
                const now = new Date();
                const options = {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false,
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                };
                document.getElementById('current-time').textContent = now.toLocaleString('fa-IR', options).replace(',', ' -');
            }
            setInterval(updateClock, 1000);
            updateClock(); // Initial call

            // Initialize the monthly sales chart on DOMContentLoaded
            const monthlySalesCtx = document.getElementById('monthlySalesChart').getContext('2d');
            monthlySalesChartInstance = new Chart(monthlySalesCtx, monthlySalesConfig);
        });


        // مقداردهی اولیه هنگام بارگذاری صفحه
        window.onload = () => {
            showSection('dashboard'); // نمایش داشبورد در ابتدا
            renderActivityLog(); // رندر اولیه لاگ فعالیت‌ها


            // شبیه‌سازی ورود ادمین و بررسی فعالیت مشکوک هنگام بارگذاری
            // در یک سیستم واقعی، این منطق پس از احراز هویت موفق کاربر اجرا می‌شود.
            const storedAdmin = users.find(u => u.username === currentUser.username);
            if (storedAdmin) {
                // شبیه‌سازی لاگین و بررسی فعالیت مشکوک
                // checkSuspiciousActivity(storedAdmin); // uncomment to test suspicious activity
                logAdminAction(currentUser.username, 'ورود به پنل', 'ورود موفق به سیستم');
            } else {
                // اگر کاربر شبیه‌سازی شده یافت نشد (مثلاً برای نقش‌های دیگر)
                showMessage('کاربر ادمین شبیه‌سازی شده یافت نشد. به عنوان کاربر پیش‌فرض عمل می‌کنیم.', 'info');
                // اگر می‌خواهید دسترسی‌ها را بر اساس نقش کاربر عادی محدود کنید، می‌توانید currentUser را به یک کاربر عادی تغییر دهید
                // currentUser = { id: 1, username: 'ali.ahmadi', role: 'کاربر', lastLocation: '172.20.10.2' };
            }
        };

        // Export to Excel (using SheetJS)
        document.getElementById('export-excel').addEventListener('click', () => {
            const data = [
                ['نام محصول', 'قیمت', 'موجودی'],
                ['چای سیاه', 50000, 1000],
                ['چای سبز', 60000, 500],
                ['چای ارل گری', 75000, 300]
            ];
            const ws = XLSX.utils.aoa_to_sheet(data);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "گزارش محصولات");
            XLSX.writeFile(wb, "گزارش_محصولات.xlsx");
            showMessage('فایل اکسل با موفقیت صادر شد.', 'success');
        });

        // Export to PDF (using jsPDF and html2canvas)
        document.getElementById('export-pdf').addEventListener('click', async () => {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Select the content you want to export (e.g., the reports section)
            const content = document.getElementById('reports-content'); // Or any other section

            if (content) {
                // Temporarily show the report content if it's hidden
                const isHidden = content.classList.contains('hidden') || !content.classList.contains('active');
                if (isHidden) {
                    content.style.display = 'block'; // Make it visible for capture
                    content.classList.add('temp-visible-for-pdf'); // Mark for removal later
                }

                doc.setFont('Vazirmatn', 'normal'); // Set font for PDF

                await html2canvas(content, {
                    scale: 2, // Increase scale for better quality
                    useCORS: true, // If images/assets are from different origin
                    windowWidth: document.body.scrollWidth,
                    windowHeight: document.body.scrollHeight,
                }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const imgWidth = 210; // A4 width in mm
                    const pageHeight = 297; // A4 height in mm
                    const imgHeight = canvas.height * imgWidth / canvas.width;
                    let heightLeft = imgHeight;
                    let position = 0;

                    doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;

                    while (heightLeft >= 0) {
                        position = heightLeft - imgHeight;
                        doc.addPage();
                        doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                        heightLeft -= pageHeight;
                    }
                    doc.save("گزارش.pdf");
                    showMessage('فایل PDF با موفقیت صادر شد.', 'success');

                    // Revert display style if temporarily changed
                    if (isHidden) {
                        content.style.display = ''; // Revert to original display
                        content.classList.remove('temp-visible-for-pdf');
                    }
                }).catch(error => {
                    console.error("Error generating PDF:", error);
                    showMessage('خطا در تولید PDF: ' + error.message, 'error');
                    // Ensure display style is reverted even on error
                    if (content.classList.contains('temp-visible-for-pdf')) {
                        content.style.display = '';
                        content.classList.remove('temp-visible-for-pdf');
                    }
                });
            } else {
                showMessage('محتوای گزارش برای تولید PDF یافت نشد.', 'error');
            }
        });
    </script>
</body>
</html>
