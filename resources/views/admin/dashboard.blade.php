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
    <!-- Chart.js CDN (برای نمودارها) - به head منتقل شد تا قبل از کد شما بارگذاری شود -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* CSS سفارشی برای هماهنگی با طرح کلی سایت و بهبود ظاهر پنل */
        body {
            font-family: 'Vazirmatn', sans-serif;
            background-color: #f5f7fa; /* پس زمینه روشن‌تر برای پنل */
            color: #3a251c; /* رنگ متن اصلی (قهوه‌ای تیره) */
            line-height: 1.6;
        }
        /* رنگ‌های اصلی سایت */
        .text-brown-900 { color: #3a251c; }
        .bg-brown-900 { background-color: #3a251c; }
        .hover\:bg-brown-800:hover { background-color: #553e2e; }

        .text-green-800 { color: #1e5a20; }
        .bg-green-800 { background-color: #1e5a20; }
        .border-green-800 { border-color: #1e5a20; }
        .hover\:bg-green-700:hover { background-color: #164317; }
        .bg-blue-600 { background-color: #2563eb; } /* برای اعلان‌های اطلاعاتی */
        .bg-yellow-500 { background-color: #eab308; } /* برای اعلان‌های هشدار */
        .bg-red-600 { background-color: #dc2626; } /* برای اعلان‌های خطا */
        .bg-purple-600 { background-color: #9333ea; } /* برای اعلان‌های خطا */


        /* استایل‌های سایدبار */
        .sidebar {
            width: 250px;
            min-width: 250px; /* حداقل عرض برای جلوگیری از بهم‌ریختگی */
            background-color: #3a251c; /* قهوه‌ای تیره سایت */
            color: white;
            padding: 1.5rem 0;
            transition: all 0.3s ease-in-out;
            transform: translateX(0); /* در حالت عادی قابل مشاهده */
            z-index: 50; /* بالاتر از محتوا اما پایین‌تر از نوتیفیکیشن‌ها */
            height: 100vh; /* ارتفاع کامل صفحه */
            position: fixed; /* ثابت بماند هنگام اسکرول */
            top: 0;
            right: 0;
            overflow-y: auto; /* برای اسکرول در صورت نیاز */
            box-shadow: 2px 0 10px rgba(0,0,0,0.2);
        }
        .sidebar.collapsed {
            transform: translateX(calc(100% - 64px)); /* فقط آیکون‌ها و کمی از متن visible */
        }
        .sidebar-header {
            padding: 0 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .sidebar-header .text-3xl {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            opacity: 1;
            transition: opacity 0.3s ease-in-out;
        }
        .sidebar.collapsed .sidebar-header .text-3xl {
            opacity: 0;
            width: 0;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: #d1d5db; /* gray-300 */
            transition: all 0.2s ease-in-out;
            font-weight: 500;
            white-space: nowrap; /* جلوگیری از شکستن خط */
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar-nav a:hover, .sidebar-nav a.active {
            background-color: #553e2e; /* قهوه‌ای روشن‌تر */
            color: white;
            border-right: 4px solid #1e5a20; /* خط سبز کنار لینک فعال */
        }
        .sidebar-nav a i {
            margin-left: 1rem; /* فاصله آیکون از متن در RTL */
            font-size: 1.1rem;
        }
        .sidebar.collapsed .sidebar-nav a span {
            opacity: 0;
            width: 0;
            transition: opacity 0.1s ease-in-out, width 0.1s ease-in-out;
        }
        .sidebar.collapsed .sidebar-nav a {
            padding: 0.75rem 0.5rem; /* پدینگ کمتر برای حالت جمع شده */
            justify-content: center; /* آیکون در مرکز */
        }

        /* ناحیه اصلی محتوا */
        .main-content {
            margin-right: 250px; /* فضای سایدبار را جبران می‌کند */
            flex-grow: 1;
            padding: 1.5rem;
            transition: margin-right 0.3s ease-in-out;
        }
        .sidebar.collapsed + .main-content {
            margin-right: 64px; /* فضای کمتر در حالت جمع شده */
        }

        /* نوار بالای پنل */
        .topbar {
            background-color: white;
            padding: 1rem 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 40;
            position: sticky;
            top: 0;
        }
        .topbar-right, .topbar-left {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .profile-dropdown {
            position: relative;
        }
        .profile-dropdown-content {
            position: absolute;
            top: 100%;
            left: 0;
            background-color: white;
            border: 1px solid #e0e0e0;
            border-radius: 0.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 0.5rem 0;
            min-width: 150px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.2s ease-out;
            z-index: 60;
        }
        .profile-dropdown:hover .profile-dropdown-content {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .profile-dropdown-content a {
            display: block;
            padding: 0.75rem 1rem;
            color: #3a251c;
            font-size: 0.95rem;
            transition: background-color 0.2s;
        }
        .profile-dropdown-content a:hover {
            background-color: #f5f7fa;
        }

        /* استایل کارت‌های داشبورد */
        .dashboard-card {
            background-color: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }

        /* دکمه‌های عمومی */
        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.2s ease-in-out;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-primary {
            @apply bg-green-800 text-white hover:bg-green-700;
        }
        .btn-secondary {
            @apply bg-gray-200 text-gray-700 hover:bg-gray-300;
        }
        .btn-danger {
            @apply bg-red-600 text-white hover:bg-red-700;
        }

        /* استایل‌های جدول */
        .table-container {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            overflow-x: auto; /* برای اسکرول افقی در موبایل */
            padding: 1rem;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
        }
        .data-table th, .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
            white-space: nowrap;
        }
        .data-table th {
            background-color: #f8f8f8;
            font-weight: 600;
            color: #3a251c;
            text-align: right;
            cursor: pointer; /* برای ستون‌های قابل مرتب‌سازی */
        }
        .data-table tr:last-child td {
            border-bottom: none;
        }
        .data-table tbody tr:hover {
            background-color: #fafafa;
        }

        /* استایل‌های فرم */
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #3a251c;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            color: #3a251c;
            background-color: #f9fafb;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #1e5a20;
            box-shadow: 0 0 0 3px rgba(30, 90, 32, 0.2);
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        /* Media Queries برای ریسپانسیو بودن */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(100%); /* در موبایل به صورت پیش‌فرض مخفی */
            }
            .sidebar.active {
                transform: translateX(0);
                box-shadow: -5px 0 15px rgba(0,0,0,0.3);
            }
            .main-content {
                margin-right: 0; /* در موبایل بدون مارجین */
                padding: 1rem;
            }
            .topbar {
                padding: 0.75rem 1rem;
            }
            .topbar-right {
                gap: 1rem;
            }
            .topbar-left .text-xl {
                font-size: 1.2rem;
            }
            .dashboard-grid {
                grid-template-columns: 1fr; /* کارت‌های داشبورد در یک ستون */
            }
            .data-table th, .data-table td {
                padding: 0.75rem;
            }
            .form-group input, .form-group select, .form-group textarea {
                padding: 0.6rem 0.8rem;
                font-size: 0.9rem;
            }
            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
            /* دکمه باز کردن سایدبار در موبایل */
            .sidebar-toggle-btn {
                display: block; /* نمایش دکمه در موبایل */
            }
            .sidebar.collapsed {
                transform: translateX(100%); /* در موبایل هم به صورت کامل مخفی باشد */
            }
        }
        /* دکمه باز و بسته کردن سایدبار که فقط در موبایل نمایش داده می‌شود */
        .sidebar-toggle-btn {
            display: none; /* به صورت پیش‌فرض مخفی است */
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #3a251c;
            cursor: pointer;
            margin-left: 1rem;
        }

        /* Styles for Floating Report Menu */
        .report-actions {
            position: fixed;
            bottom: 2rem;
            left: 2rem; /* Changed to left for RTL layout */
            z-index: 50;
        }
        .report-actions button {
            transition: transform 0.3s ease-in-out;
        }
        .report-actions:hover button {
            transform: rotate(45deg);
        }
        .report-menu {
            position: absolute;
            bottom: 100%;
            left: 0; /* Align with the button */
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 0.75rem;
            min-width: 180px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease-out;
            z-index: 50;
            direction: rtl; /* Ensure menu content is RTL */
            margin-bottom: 0.5rem; /* Space between button and menu */
        }
        .report-actions.active .report-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .report-menu button.export-option {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 0.6rem 0.75rem;
            background: none;
            border: none;
            color: #3a251c;
            font-size: 0.95rem;
            text-align: right;
            cursor: pointer;
            transition: background-color 0.2s;
            border-radius: 0.5rem;
        }
        .report-menu button.export-option:hover {
            background-color: #f5f7fa;
        }
        .report-menu button.export-option i {
            margin-left: 0.75rem; /* Space icon from text */
            font-size: 1.1rem;
        }
        .report-menu h4 {
            font-size: 1rem;
            color: #3a251c;
            margin-bottom: 0.5rem;
        }

        /* Report Preview Modal */
        .report-preview-modal {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
        }
        .report-preview-modal.show {
            opacity: 1;
            visibility: visible;
        }
        .report-preview-content {
            background-color: white;
            width: 95%;
            max-width: 800px;
            height: 90%;
            border-radius: 1rem;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transform: translateY(-20px);
            opacity: 0;
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
        }
        .report-preview-modal.show .report-preview-content {
            transform: translateY(0);
            opacity: 1;
        }
        .report-preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e0e0e0;
            background-color: #f8f8f8;
            flex-shrink: 0;
        }
        .report-preview-header h3 {
            font-size: 1.25rem;
            font-weight: bold;
            color: #3a251c;
        }
        .report-preview-header button.close-preview {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #6b7280;
            cursor: pointer;
            transition: color 0.2s;
        }
        .report-preview-header button.close-preview:hover {
            color: #3a251c;
        }
        #preview-content-area {
            flex-grow: 1;
            padding: 1.5rem;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Loading Overlay Styles */
        .loading-overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
        }
        .loading-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left-color: #1e5a20; /* Green spinner */
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .loading-text {
            margin-top: 1rem;
            font-size: 1.1rem;
            color: #3a251c;
        }

        /* Pagination Styles */
        .pagination-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 1.5rem;
            gap: 0.5rem;
        }
        .pagination-button {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            background-color: #e5e7eb;
            color: #374151;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .pagination-button:hover:not(:disabled) {
            background-color: #d1d5db;
        }
        .pagination-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .pagination-button.active {
            background-color: #1e5a20;
            color: white;
        }
    </style>
</head>
<body class="flex">
    <!-- سایدبار پنل مدیریت -->
    <aside id="sidebar" class="sidebar flex-col justify-between" role="navigation" aria-label="منوی اصلی پنل مدیریت">
        <div>
            <div class="sidebar-header">
                <a href="{{ url('/admin/dashboard') }}" class="flex items-center text-white" aria-label="لوگوی چای ابراهیم و بازگشت به داشبورد">
                    <i class="fas fa-leaf text-green-800 ml-2 text-2xl"></i>
                    <span class="text-2xl font-bold">چای ابراهیم</span>
                </a>
            </div>
            <nav class="sidebar-nav">
                <a href="#" data-section="dashboard" class="active" role="menuitem" aria-label="داشبورد">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>داشبورد</span>
                </a>
                <a href="#" data-section="products" role="menuitem" aria-label="مدیریت محصولات">
                    <i class="fas fa-boxes"></i>
                    <span>مدیریت محصولات</span>
                </a>
                <a href="#" data-section="categories" role="menuitem" aria-label="مدیریت دسته‌بندی‌ها">
                    <i class="fas fa-tags"></i>
                    <span>مدیریت دسته‌بندی‌ها</span>
                </a>
                <a href="#" data-section="orders" role="menuitem" aria-label="مدیریت سفارش‌ها">
                    <i class="fas fa-clipboard-list"></i>
                    <span>مدیریت سفارش‌ها</span>
                </a>
                <a href="#" data-section="users" role="menuitem" aria-label="مدیریت کاربران">
                    <i class="fas fa-users"></i>
                    <span>مدیریت کاربران</span>
                </a>
                <a href="#" data-section="notifications" role="menuitem" aria-label="اعلان‌ها">
                    <i class="fas fa-bell"></i>
                    <span>اعلان‌ها</span>
                </a>
                <a href="#" data-section="reports" role="menuitem" aria-label="گزارش‌گیری">
                    <i class="fas fa-chart-line"></i>
                    <span>گزارش‌گیری</span>
                </a>
                <a href="#" data-section="audit-logs" role="menuitem" aria-label="لاگ امنیتی">
                    <i class="fas fa-shield-alt"></i>
                    <span>لاگ امنیتی</span>
                </a>
            </nav>
        </div>
        <div class="p-6 text-center text-sm text-gray-400">
            &copy; 2023 چای ابراهیم <br> تمامی حقوق محفوظ است.
        </div>
    </aside>

    <!-- محتوای اصلی پنل -->
    <div class="main-content flex flex-col flex-grow" role="main">
        <!-- نوار بالایی پنل -->
        <header class="topbar">
            <div class="topbar-right">
                <button id="sidebar-toggle" class="sidebar-toggle-btn" aria-label="نمایش/پنهان کردن سایدبار">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="text-xl font-semibold text-brown-900" id="current-section-title">داشبورد</span>
            </div>
            <div class="topbar-left">
                <div class="relative profile-dropdown">
                    <button class="flex items-center gap-2 text-gray-700 hover:text-green-800" aria-haspopup="true" aria-expanded="false" aria-label="پروفایل کاربر">
                        <img src="https://placehold.co/40x40/e0e0e0/888888?text=ME" alt="پروفایل" class="rounded-full border border-gray-300">
                        <span class="font-medium hidden md:inline">مدیر سیستم</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div class="profile-dropdown-content" role="menu">
                        <a href="#" role="menuitem"><i class="fas fa-user-circle ml-2"></i>پروفایل من</a>
                        <a href="#" role="menuitem"><i class="fas fa-cog ml-2"></i>تنظیمات</a>
                        <div class="border-t border-gray-100 my-1"></div>
                        <a href="#" id="logout-btn" role="menuitem"><i class="fas fa-sign-out-alt ml-2"></i>خروج</a>
                    </div>
                </div>
                <button class="relative text-gray-700 hover:text-green-800 text-lg" aria-label="اعلان‌ها">
                    <i class="fas fa-bell"></i>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center" aria-label="سه اعلان جدید">3</span>
                </button>
            </div>
        </header>

        <!-- بخش‌های مختلف پنل (فقط یک بخش در لحظه نمایش داده می‌شود) -->
        <div id="content-area" class="mt-8">
            <!-- داشبورد -->
            <section id="dashboard-section" class="active-section space-y-6" role="region" aria-labelledby="dashboard-title">
                <h2 class="text-3xl font-bold text-brown-900 mb-6" id="dashboard-title">داشبورد</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 dashboard-grid">
                    <div class="dashboard-card bg-green-100 border-l-4 border-green-800 flex items-center" role="status" aria-label="آمار سفارشات جدید">
                        <i class="fas fa-shopping-cart text-green-800 text-3xl ml-4" aria-hidden="true"></i>
                        <div>
                            <p class="text-gray-600">سفارشات جدید</p>
                            <h3 class="text-3xl font-bold text-green-800">150</h3>
                        </div>
                    </div>
                    <div class="dashboard-card bg-blue-100 border-l-4 border-blue-600 flex items-center" role="status" aria-label="آمار کل محصولات">
                        <i class="fas fa-boxes text-blue-600 text-3xl ml-4" aria-hidden="true"></i>
                        <div>
                            <p class="text-gray-600">کل محصولات</p>
                            <h3 class="text-3xl font-bold text-blue-600">250</h3>
                        </div>
                    </div>
                    <div class="dashboard-card bg-yellow-100 border-l-4 border-yellow-500 flex items-center" role="status" aria-label="آمار کاربران ثبت‌نام شده">
                        <i class="fas fa-users text-yellow-500 text-3xl ml-4" aria-hidden="true"></i>
                        <div>
                            <p class="text-gray-600">کاربران ثبت‌نام شده</p>
                            <h3 class="text-3xl font-bold text-yellow-500">1200</h3>
                        </div>
                    </div>
                    <div class="dashboard-card bg-purple-100 border-l-4 border-purple-600 flex items-center" role="status" aria-label="آمار درآمد کل">
                        <i class="fas fa-dollar-sign text-purple-600 text-3xl ml-4" aria-hidden="true"></i>
                        <div>
                            <p class="text-gray-600">درآمد کل</p>
                            <h3 class="text-3xl font-bold text-purple-600">345,000,000</h3>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
                    <div class="dashboard-card" role="region" aria-labelledby="sales-chart-dashboard-title">
                        <h4 class="text-xl font-bold text-brown-900 mb-4" id="sales-chart-dashboard-title">نمودار فروش (ماهیانه)</h4>
                        <canvas id="salesChart" class="w-full h-64" role="img" aria-label="نمودار میله‌ای فروش ماهیانه"></canvas>
                    </div>
                    <div class="dashboard-card" role="region" aria-labelledby="bestsellers-title">
                        <h4 class="text-xl font-bold text-brown-900 mb-4" id="bestsellers-title">پرفروش‌ترین محصولات</h4>
                        <ul class="list-disc pr-6 space-y-2 text-gray-700">
                            <li>چای سیاه ممتاز (۱۲۰ عدد)</li>
                            <li>دمنوش به لیمو (۹۵ عدد)</li>
                            <li>چای سبز چینی (۷۰ عدد)</li>
                            <li>چای ارل گری (۶۵ عدد)</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- مدیریت محصولات -->
            <section id="products-section" class="hidden space-y-6" role="region" aria-labelledby="products-title">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-bold text-brown-900" id="products-title">مدیریت محصولات</h2>
                    <button class="btn btn-primary" id="add-product-btn" aria-label="افزودن محصول جدید">
                        <i class="fas fa-plus ml-2" aria-hidden="true"></i>افزودن محصول جدید
                    </button>
                </div>

                <!-- فرم افزودن/ویرایش محصول (مخفی به صورت پیش فرض) -->
                <div id="product-form-container" class="hidden bg-white p-6 rounded-lg shadow-md mb-8" role="form" aria-labelledby="product-form-title">
                    <h3 class="text-2xl font-bold text-brown-900 mb-4" id="product-form-title">افزودن محصول جدید</h3>
                    <form id="product-form" class="space-y-4">
                        <input type="hidden" id="product-id">
                        <div class="form-group">
                            <label for="product-name">نام محصول:</label>
                            <input type="text" id="product-name" required aria-required="true">
                        </div>
                        <div class="form-group">
                            <label for="product-category">دسته‌بندی:</label>
                            <select id="product-category" required aria-required="true">
                                <option value="">انتخاب دسته‌بندی</option>
                                <option value="black-tea">چای سیاه</option>
                                <option value="green-tea">چای سبز</option>
                                <option value="herbal-infusion">دمنوش</option>
                                <option value="white-tea">چای سفید</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="product-price">قیمت (تومان):</label>
                            <input type="number" id="product-price" required min="0" aria-required="true">
                        </div>
                        <div class="form-group">
                            <label for="product-stock">موجودی:</label>
                            <input type="number" id="product-stock" required min="0" aria-required="true">
                        </div>
                        <div class="form-group">
                            <label for="product-image">تصویر محصول:</label>
                            <input type="file" id="product-image" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label for="product-description">توضیحات:</label>
                            <textarea id="product-description"></textarea>
                        </div>
                        <div class="flex justify-end gap-4">
                            <button type="submit" class="btn btn-primary" id="submit-product-btn">ذخیره</button>
                            <button type="button" class="btn btn-secondary" id="cancel-product-btn">لغو</button>
                        </div>
                    </form>
                </div>

                <!-- کنترل‌های جدول محصولات -->
                <div class="flex justify-between items-center mb-4">
                    <div class="w-1/3">
                        <input type="text" id="product-search" placeholder="جستجو در محصولات..." class="w-full p-2 border border-gray-300 rounded-md">
                    </div>
                    <div class="flex items-center gap-2">
                        <label for="products-per-page" class="text-sm">نمایش:</label>
                        <select id="products-per-page" class="p-2 border rounded-md text-sm">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="20">20</option>
                        </select>
                    </div>
                </div>

                <!-- لیست محصولات -->
                <div class="table-container" role="table" aria-label="لیست محصولات">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">تصویر</th>
                                <th scope="col" data-sort="name">نام محصول <i class="fas fa-sort text-xs ml-1"></i></th>
                                <th scope="col">دسته‌بندی</th>
                                <th scope="col" data-sort="price">قیمت <i class="fas fa-sort text-xs ml-1"></i></th>
                                <th scope="col" data-sort="stock">موجودی <i class="fas fa-sort text-xs ml-1"></i></th>
                                <th scope="col">عملیات</th>
                            </tr>
                        </thead>
                        <tbody id="products-table-body">
                            <!-- داده‌های محصولات اینجا توسط جاوااسکریپت بارگذاری می‌شوند -->
                        </tbody>
                    </table>
                </div>
                <!-- Pagination controls -->
                <div id="products-pagination" class="pagination-controls" role="navigation" aria-label="صفحه‌بندی محصولات"></div>
            </section>

            <!-- مدیریت دسته‌بندی‌ها -->
            <section id="categories-section" class="hidden space-y-6" role="region" aria-labelledby="categories-title">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-bold text-brown-900" id="categories-title">مدیریت دسته‌بندی‌ها</h2>
                    <button class="btn btn-primary" id="add-category-btn" aria-label="افزودن دسته‌بندی جدید">
                        <i class="fas fa-plus ml-2" aria-hidden="true"></i>افزودن دسته‌بندی جدید
                    </button>
                </div>

                <!-- فرم افزودن/ویرایش دسته‌بندی (مخفی به صورت پیش فرض) -->
                <div id="category-form-container" class="hidden bg-white p-6 rounded-lg shadow-md mb-8" role="form" aria-labelledby="category-form-title">
                    <h3 class="text-2xl font-bold text-brown-900 mb-4" id="category-form-title">افزودن دسته‌بندی جدید</h3>
                    <form id="category-form" class="space-y-4">
                        <input type="hidden" id="category-id">
                        <div class="form-group">
                            <label for="category-name">نام دسته‌بندی:</label>
                            <input type="text" id="category-name" required aria-required="true">
                        </div>
                        <div class="flex justify-end gap-4">
                            <button type="submit" class="btn btn-primary" id="submit-category-btn">ذخیره</button>
                            <button type="button" class="btn btn-secondary" id="cancel-category-btn">لغو</button>
                        </div>
                    </form>
                </div>

                <!-- کنترل‌های جدول دسته‌بندی‌ها -->
                <div class="flex justify-end items-center mb-4">
                    <div class="flex items-center gap-2">
                        <label for="categories-per-page" class="text-sm">نمایش:</label>
                        <select id="categories-per-page" class="p-2 border rounded-md text-sm">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="20">20</option>
                        </select>
                    </div>
                </div>

                <!-- لیست دسته‌بندی‌ها -->
                <div class="table-container" role="table" aria-label="لیست دسته‌بندی‌ها">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">نام دسته‌بندی</th>
                                <th scope="col">تعداد محصولات</th>
                                <th scope="col">عملیات</th>
                            </tr>
                        </thead>
                        <tbody id="categories-table-body">
                            <!-- داده‌های دسته‌بندی‌ها اینجا توسط جاوااسکریپت بارگذاری می‌شوند -->
                        </tbody>
                    </table>
                </div>
                <!-- Pagination controls -->
                <div id="categories-pagination" class="pagination-controls" role="navigation" aria-label="صفحه‌بندی دسته‌بندی‌ها"></div>
            </section>


            <!-- مدیریت سفارش‌ها -->
            <section id="orders-section" class="hidden space-y-6" role="region" aria-labelledby="orders-title">
                <h2 class="text-3xl font-bold text-brown-900 mb-6" id="orders-title">مدیریت سفارش‌ها</h2>
                <!-- فیلترهای سفارش -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h3 class="text-xl font-bold text-brown-900 mb-4">فیلتر سفارشات</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label for="order-search-id" class="block text-sm font-medium text-gray-700">شناسه سفارش:</label>
                            <input type="text" id="order-search-id" placeholder="جستجو بر اساس شناسه" class="w-full p-2 border rounded-md">
                        </div>
                        <div>
                            <label for="order-search-user" class="block text-sm font-medium text-gray-700">کاربر:</label>
                            <input type="text" id="order-search-user" placeholder="جستجو بر اساس نام کاربر" class="w-full p-2 border rounded-md">
                        </div>
                        <div>
                            <label for="order-filter-status" class="block text-sm font-medium text-gray-700">وضعیت:</label>
                            <select id="order-filter-status" class="w-full p-2 border rounded-md">
                                <option value="">همه</option>
                                <option value="پرداخت شده">پرداخت شده</option>
                                <option value="در حال آماده‌سازی">در حال آماده‌سازی</option>
                                <option value="ارسال شده">ارسال شده</option>
                                <option value="لغو شده">لغو شده</option>
                            </select>
                        </div>
                        <div>
                            <label for="order-filter-date-from" class="block text-sm font-medium text-gray-700">تاریخ از:</label>
                            <input type="date" id="order-filter-date-from" class="w-full p-2 border rounded-md">
                        </div>
                        <div>
                            <label for="order-filter-date-to" class="block text-sm font-medium text-gray-700">تاریخ تا:</label>
                            <input type="date" id="order-filter-date-to" class="w-full p-2 border rounded-md">
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button class="btn btn-primary" id="apply-order-filters" aria-label="اعمال فیلترهای سفارشات">
                            <i class="fas fa-filter ml-2" aria-hidden="true"></i>اعمال فیلتر
                        </button>
                        <button class="btn btn-secondary" id="clear-order-filters" aria-label="پاک کردن فیلترهای سفارشات">
                            <i class="fas fa-times-circle ml-2" aria-hidden="true"></i>پاک کردن فیلترها
                        </button>
                    </div>
                </div>

                <!-- کنترل‌های جدول سفارش‌ها و دکمه خروجی -->
                <div class="flex justify-between items-center mb-4">
                    <button class="btn bg-green-700 text-white" id="export-orders-excel-btn" aria-label="خروجی اکسل سفارشات">
                        <i class="fas fa-file-excel ml-2" aria-hidden="true"></i>خروجی Excel سفارشات
                    </button>
                    <div class="flex items-center gap-2">
                        <label for="orders-per-page" class="text-sm">نمایش:</label>
                        <select id="orders-per-page" class="p-2 border rounded-md text-sm">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="20">20</option>
                        </select>
                    </div>
                </div>
                <div class="table-container" role="table" aria-label="لیست سفارشات">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col" data-sort="id">شناسه سفارش <i class="fas fa-sort text-xs ml-1"></i></th>
                                <th scope="col" data-sort="user">کاربر <i class="fas fa-sort text-xs ml-1"></i></th>
                                <th scope="col" data-sort="date">تاریخ <i class="fas fa-sort text-xs ml-1"></i></th>
                                <th scope="col" data-sort="time">زمان <i class="fas fa-sort text-xs ml-1"></i></th>
                                <th scope="col" data-sort="total">مبلغ کل <i class="fas fa-sort text-xs ml-1"></i></th>
                                <th scope="col" data-sort="status">وضعیت <i class="fas fa-sort text-xs ml-1"></i></th>
                                <th scope="col">عملیات</th>
                            </tr>
                        </thead>
                        <tbody id="orders-table-body">
                            <!-- داده‌های سفارش‌ها اینجا توسط جاوااسکریپت بارگذاری می‌شوند -->
                        </tbody>
                    </table>
                </div>
                <!-- Pagination controls -->
                <div id="orders-pagination" class="pagination-controls" role="navigation" aria-label="صفحه‌بندی سفارشات"></div>
            </section>

            <!-- مدیریت کاربران -->
            <section id="users-section" class="hidden space-y-6" role="region" aria-labelledby="users-title">
                <h2 class="text-3xl font-bold text-brown-900 mb-6" id="users-title">مدیریت کاربران</h2>
                <!-- کنترل‌های جدول کاربران -->
                <div class="flex justify-end items-center mb-4">
                    <div class="flex items-center gap-2">
                        <label for="users-per-page" class="text-sm">نمایش:</label>
                        <select id="users-per-page" class="p-2 border rounded-md text-sm">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="20">20</option>
                        </select>
                    </div>
                </div>
                <div class="table-container" role="table" aria-label="لیست کاربران">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">نام کاربری</th>
                                <th scope="col">ایمیل</th>
                                <th scope="col">شماره تلفن</th>
                                <th scope="col">تاریخ ثبت‌نام</th>
                                <th scope="col">نقش</th>
                                <th scope="col">عملیات</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body">
                            <!-- داده‌های کاربران اینجا توسط جاوااسکریپت بارگذاری می‌شوند -->
                        </tbody>
                    </table>
                </div>
                <!-- Pagination controls -->
                <div id="users-pagination" class="pagination-controls" role="navigation" aria-label="صفحه‌بندی کاربران"></div>
            </section>

            <!-- اعلان‌ها -->
            <section id="notifications-section" class="hidden space-y-6" role="region" aria-labelledby="notifications-title">
                <h2 class="text-3xl font-bold text-brown-900 mb-6" id="notifications-title">اعلان‌ها</h2>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <ul class="space-y-4">
                        <li class="flex items-center gap-4 p-4 bg-blue-50 border border-blue-200 rounded-lg" role="listitem">
                            <i class="fas fa-info-circle text-blue-600 text-xl" aria-hidden="true"></i>
                            <p class="text-gray-700">سفارش جدید با شناسه <span class="font-bold">#1001</span> توسط <span class="font-bold">علی احمدی</span> ثبت شد.</p>
                            <span class="text-gray-500 text-sm mr-auto">5 دقیقه پیش</span>
                        </li>
                        <li class="flex items-center gap-4 p-4 bg-green-50 border border-green-200 rounded-lg" role="listitem">
                            <i class="fas fa-check-circle text-green-600 text-xl" aria-hidden="true"></i>
                            <p class="text-gray-700">محصول <span class="font-bold">چای سیاه ممتاز</span> به اتمام رسید.</p>
                            <span class="text-gray-500 text-sm mr-auto">1 ساعت پیش</span>
                        </li>
                        <li class="flex items-center gap-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg" role="listitem">
                            <i class="fas fa-exclamation-triangle text-yellow-600 text-xl" aria-hidden="true"></i>
                            <p class="text-gray-700">یک کاربر جدید به نام <span class="font-bold">فاطمه حسینی</span> ثبت نام کرد.</p>
                            <span class="text-gray-500 text-sm mr-auto">2 ساعت پیش</span>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- گزارش‌گیری -->
            <section id="reports-section" class="hidden space-y-6" role="region" aria-labelledby="reports-title">
                <h2 class="text-3xl font-bold text-brown-900 mb-6" id="reports-title">گزارش‌گیری</h2>

                <!-- فیلترهای پیشرفته -->
                <div class="report-filters bg-white p-6 rounded-lg shadow mb-6" role="form" aria-labelledby="filters-title">
                    <h4 class="font-bold text-brown-900 mb-3" id="filters-title">فیلترهای پیشرفته</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="filter-date-from" class="block text-sm font-medium text-gray-700 mb-1">تاریخ از:</label>
                            <input type="date" id="filter-date-from" class="filter-date w-full p-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label for="filter-date-to" class="block text-sm font-medium text-gray-700 mb-1">تاریخ تا:</label>
                            <input type="date" id="filter-date-to" class="filter-date w-full p-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label for="filter-status" class="block text-sm font-medium text-gray-700 mb-1">وضعیت سفارش:</label>
                            <select id="filter-status" class="filter-status w-full p-2 border border-gray-300 rounded-md">
                                <option value="">همه</option>
                                <option value="پرداخت شده">پرداخت شده</option>
                                <option value="در حال آماده‌سازی">در حال آماده‌سازی</option>
                                <option value="ارسال شده">ارسال شده</option>
                                <option value="لغو شده">لغو شده</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button class="btn btn-primary" aria-label="اعمال فیلترها"><i class="fas fa-filter ml-2" aria-hidden="true"></i>اعمال فیلتر</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="dashboard-card" role="region" aria-labelledby="sales-chart-report-title">
                        <h4 class="text-xl font-bold text-brown-900 mb-4" id="sales-chart-report-title">نمودار فروش (ماهیانه)</h4>
                        <div class="chart-controls flex gap-4 mb-4">
                            <label for="sales-chart-type" class="sr-only">نوع نمودار فروش</label>
                            <select id="sales-chart-type" class="bg-white p-2 rounded border border-gray-300 text-sm">
                                <option value="bar">میله‌ای</option>
                                <option value="line">خطی</option>
                            </select>
                            <label for="sales-time-range" class="sr-only">بازه زمانی نمودار فروش</label>
                            <select id="sales-time-range" class="bg-white p-2 rounded border border-gray-300 text-sm">
                                <option value="daily">روزانه</option>
                                <option value="weekly">هفتگی</option>
                                <option value="monthly" selected>ماهیانه</option>
                            </select>
                        </div>
                        <canvas id="salesChart" class="w-full h-64" role="img" aria-label="نمودار فروش ماهیانه"></canvas>
                    </div>
                    <div class="dashboard-card" role="region" aria-labelledby="daily-sales-chart-title">
                        <h4 class="text-xl font-bold text-brown-900 mb-4" id="daily-sales-chart-title">نمودار فروش روزانه</h4>
                        <canvas id="dailySalesChart" class="w-full h-64" role="img" aria-label="نمودار فروش روزانه"></canvas>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                    <!-- گزارش‌های آماده پرکاربرد -->
                    <div class="dashboard-card" role="region" aria-labelledby="predefined-reports-title">
                        <h4 class="text-xl font-bold text-brown-900 mb-4" id="predefined-reports-title">گزارش‌های آماده</h4>
                        <div id="predefined-reports" class="grid grid-cols-2 gap-4">
                            <!-- Reports will be rendered here -->
                        </div>
                    </div>
                    <!-- گزارش‌های زمان‌بندی شده -->
                    <div class="dashboard-card" role="region" aria-labelledby="scheduled-reports-title">
                        <h4 class="text-xl font-bold text-brown-900 mb-4" id="scheduled-reports-title">گزارش‌های زمان‌بندی شده</h4>
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <label for="report-type-select" class="sr-only">نوع گزارش</label>
                                <select id="report-type-select" class="report-type flex-grow p-2 border border-gray-300 rounded-md text-sm">
                                    <option>گزارش فروش روزانه</option>
                                    <option>گزارش موجودی انبار</option>
                                    <option>گزارش مشتریان VIP</option>
                                </select>
                                <label for="report-frequency-select" class="sr-only">تناوب گزارش</label>
                                <select id="report-frequency-select" class="report-frequency p-2 border border-gray-300 rounded-md text-sm">
                                    <option>روزانه</option>
                                    <option>هفتگی</option>
                                    <option>ماهیانه</option>
                                </select>
                            </div>
                            <div>
                                <label for="report-email-input" class="sr-only">ایمیل دریافت کننده</label>
                                <input type="email" id="report-email-input" placeholder="ایمیل دریافت کننده" class="w-full p-2 border border-gray-300 rounded-md text-sm">
                            </div>
                            <button class="btn bg-blue-600 text-white w-full" aria-label="افزودن زمان‌بندی جدید">
                                <i class="fas fa-plus ml-2" aria-hidden="true"></i>افزودن زمان‌بندی
                            </button>
                        </div>
                        <div class="saved-reports bg-gray-50 p-4 rounded-lg shadow-inner mt-6" role="list" aria-labelledby="current-scheduled-reports-title">
                            <h5 class="font-bold text-brown-900 mb-3" id="current-scheduled-reports-title">گزارش‌های زمان‌بندی شده فعلی:</h5>
                            <ul class="text-gray-700 text-sm space-y-2">
                                <li class="flex justify-between items-center bg-white p-2 rounded border border-gray-200" role="listitem">
                                    <span>فروش روزانه - روزانه - user@example.com</span>
                                    <button class="text-red-500 hover:text-red-700" title="حذف گزارش زمان‌بندی شده" aria-label="حذف گزارش فروش روزانه"><i class="fas fa-trash" aria-hidden="true"></i></button>
                                </li>
                                <li class="flex justify-between items-center bg-white p-2 rounded border border-gray-200" role="listitem">
                                    <span>موجودی انبار - هفتگی - admin@example.com</span>
                                    <button class="text-red-500 hover:text-red-700" title="حذف گزارش زمان‌بندی شده" aria-label="حذف گزارش موجودی انبار"><i class="fas fa-trash" aria-hidden="true"></i></button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-card mt-6" role="region" aria-labelledby="financial-report-title">
                    <h4 class="text-xl font-bold text-brown-900 mb-4" id="financial-report-title">گزارش مالی</h4>
                    <p class="text-gray-700">میزان کل فروش: <span class="font-bold text-green-800">۳۴۵,۰۰۰,۰۰۰ تومان</span></p>
                    <p class="text-gray-700">تعداد سفارشات: <span class="font-bold">۱۵۰۰</span></p>
                    <div class="flex gap-4 mt-6">
                        <button class="btn bg-green-700 text-white export-btn" data-type="excel" data-section="all_data" aria-label="خروجی اکسل برای کل داده‌ها">
                            <i class="fas fa-file-excel ml-2" aria-hidden="true"></i>خروجی Excel کل داده‌ها
                        </button>
                        <button class="btn bg-red-600 text-white export-btn" data-type="pdf" data-target="reports-section" aria-label="خروجی PDF برای این بخش">
                            <i class="fas fa-file-pdf ml-2" aria-hidden="true"></i>خروجی PDF این بخش
                        </button>
                    </div>
                </div>

                <!-- پنل ذخیره گزارش‌های دلخواه -->
                <div class="saved-reports dashboard-card mt-6" role="region" aria-labelledby="saved-reports-title">
                    <h4 class="font-bold text-brown-900 mb-3" id="saved-reports-title">گزارش‌های ذخیره شده</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="saved-reports-list" role="list">
                        <div class="saved-report flex items-center justify-between p-3 border rounded-lg bg-gray-50" role="listitem">
                            <div>
                                <i class="fas fa-chart-line text-green-800 ml-2" aria-hidden="true"></i>
                                <span>فروش هفتگی (پیش فرض)</span>
                            </div>
                            <div class="flex gap-2">
                                <button class="text-blue-600 hover:text-blue-800" title="دانلود" aria-label="دانلود گزارش فروش هفتگی"><i class="fas fa-download" aria-hidden="true"></i></button>
                                <button class="text-red-600 hover:text-red-800" title="حذف" aria-label="حذف گزارش فروش هفتگی"><i class="fas fa-trash" aria-hidden="true"></i></button>
                            </div>
                        </div>
                        <div class="saved-report flex items-center justify-between p-3 border rounded-lg bg-gray-50" role="listitem">
                            <div>
                                <i class="fas fa-boxes text-purple-600 ml-2" aria-hidden="true"></i>
                                <span>موجودی انبار (پیش فرض)</span>
                            </div>
                            <div class="flex gap-2">
                                <button class="text-blue-600 hover:text-blue-800" title="دانلود" aria-label="دانلود گزارش موجودی انبار"><i class="fas fa-download" aria-hidden="true"></i></button>
                                <button class="text-red-600 hover:text-red-800" title="حذف" aria-label="حذف گزارش موجودی انبار"><i class="fas fa-trash" aria-hidden="true"></i></button>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-secondary mt-4 w-full" aria-label="ذخیره گزارش فعلی">ذخیره گزارش فعلی</button>
                </div>

            </section>
            
            <!-- لاگ امنیتی -->
            <section id="audit-logs-section" class="hidden space-y-6" role="region" aria-labelledby="audit-logs-title">
                <h2 class="text-3xl font-bold text-brown-900 mb-6" id="audit-logs-title">لاگ امنیتی</h2>
                <!-- کنترل‌های جدول لاگ امنیتی -->
                <div class="flex justify-end items-center mb-4">
                    <div class="flex items-center gap-2">
                        <label for="audit-logs-per-page" class="text-sm">نمایش:</label>
                        <select id="audit-logs-per-page" class="p-2 border rounded-md text-sm">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="20">20</option>
                        </select>
                    </div>
                </div>
                <div class="table-container" role="table" aria-label="جدول لاگ‌های امنیتی">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">زمان</th>
                                <th scope="col">کاربر</th>
                                <th scope="col">عملیات</th>
                                <th scope="col">جزئیات</th>
                                <th scope="col">آدرس IP</th>
                            </tr>
                        </thead>
                        <tbody id="audit-logs-body">
                            <!-- داده‌های لاگ امنیتی اینجا توسط جاوااسکریپت بارگذاری می‌شوند -->
                        </tbody>
                    </table>
                </div>
                <!-- Pagination controls -->
                <div id="audit-logs-pagination" class="pagination-controls" role="navigation" aria-label="صفحه‌بندی لاگ‌های امنیتی"></div>
            </section>

        </div>
    </div>

    <!-- Message box for notifications (همانند صفحه ثبت نام) -->
    <div id="message-box" class="message-box" role="alert" aria-live="polite">
        <i class="fas fa-check-circle ml-3" aria-hidden="true"></i>
        <span id="message-text"></span>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="spinner" role="status" aria-label="در حال بارگذاری..."></div>
        <p class="loading-text">در حال بارگذاری...</p>
    </div>

    <!-- Report Preview Modal (مخفی به صورت پیش‌فرض) -->
    <div id="report-preview-modal" class="report-preview-modal" role="dialog" aria-modal="true" aria-labelledby="preview-modal-title">
        <div class="report-preview-content">
            <div class="report-preview-header">
                <h3 id="preview-modal-title">پیش‌نمایش گزارش</h3>
                <button class="close-preview" id="close-preview-modal" aria-label="بستن پیش‌نمایش گزارش">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div id="preview-content-area">
                <!-- محتوای گزارش برای پیش‌نمایش اینجا بارگذاری می‌شود -->
            </div>
        </div>
    </div>

    <!-- Floating Report Actions Menu -->
    <div class="report-actions fixed bottom-8 left-8 z-50">
        <button class="w-14 h-14 rounded-full bg-green-800 text-white shadow-lg flex items-center justify-center text-xl" id="floating-report-toggle" aria-haspopup="true" aria-expanded="false" aria-label="منوی عملیات گزارش">
            <i class="fas fa-chart-pie" aria-hidden="true"></i>
        </button>
        <div class="report-menu hidden absolute bottom-full left-0 bg-white rounded-lg shadow-lg p-3 min-w-[200px]" role="menu">
            <h4 class="font-bold mb-2 border-b pb-2">گزارش‌گیری سریع</h4>
            <button class="export-option mt-2" data-type="excel" data-section="products" role="menuitem" aria-label="خروجی اکسل محصولات">
                <i class="fas fa-file-excel text-green-700" aria-hidden="true"></i>
                خروجی Excel (محصولات)
            </button>
            <button class="export-option" data-type="pdf" data-target="reports-section" role="menuitem" aria-label="خروجی PDF گزارشات">
                <i class="fas fa-file-pdf text-red-600" aria-hidden="true"></i>
                خروجی PDF (گزارشات)
            </button>
            <button class="export-option" data-type="print" data-target="reports-section" role="menuitem" aria-label="چاپ گزارش">
                <i class="fas fa-print text-blue-600" aria-hidden="true"></i>
                چاپ گزارش
            </button>
        </div>
    </div>

    <script>
        // تابع نمایش پیام (همانند قبل)
        function showMessage(message, type = 'success') {
            const messageBox = document.createElement('div');
            messageBox.id = 'temp-message-box';
            messageBox.className = 'message-box fixed top-20 right-20 bg-green-800 text-white p-4 rounded-lg shadow-lg flex items-center transform -translate-y-full opacity-0 transition-all duration-300 z-[9999]';

            messageBox.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-times-circle' : 'fa-info-circle'} ml-2" aria-hidden="true"></i>
                <span>${message}</span>
            `;
            if (type === 'error') {
                 messageBox.classList.remove('bg-green-800');
                 messageBox.classList.add('bg-red-600');
            } else if (type === 'info') {
                messageBox.classList.remove('bg-green-800');
                messageBox.classList.add('bg-blue-600');
            }


            document.body.appendChild(messageBox);

            setTimeout(() => {
                messageBox.classList.remove('-translate-y-full', 'opacity-0');
                messageBox.classList.add('translate-y-0', 'opacity-100');
            }, 10);

            setTimeout(() => {
                messageBox.classList.remove('translate-y-0', 'opacity-100');
                messageBox.classList.add('-translate-y-full', 'opacity-0');
                messageBox.addEventListener('transitionend', () => messageBox.remove());
            }, 3000);
        }

        // ---------- Loading Overlay Functions ----------
        const loadingOverlay = document.getElementById('loading-overlay');
        function showLoading() {
            loadingOverlay.classList.add('show');
        }

        function hideLoading() {
            loadingOverlay.classList.remove('show');
        }

        // ---------- Mock Data (داده‌های شبیه‌سازی شده) ----------
        let products = [
            { id: 1, name: 'چای سیاه ممتاز', category: 'چای سیاه', price: 120000, stock: 50, image: 'https://placehold.co/50x50/F0F4C3/212121?text=پ۱', description: 'چای سیاه قلم ممتاز با طعم و رنگ بی‌نظیر.' },
            { id: 2, name: 'چای سبز خالص', category: 'چای سبز', price: 95000, stock: 120, image: 'https://placehold.co/50x50/789a7f/fcf8f5?text=پ۲', description: 'چای سبز با کیفیت بالا و خواص آنتی‌اکسیدانی.' },
            { id: 3, name: 'دمنوش آرامش', category: 'دمنوش', price: 75000, stock: 80, image: 'https://placehold.co/50x50/b08f83/fcf8f5?text=پ۳', description: 'ترکیبی آرام‌بخش از گیاهان دارویی.' },
            { id: 4, name: 'چای ارل گری', category: 'چای سیاه', price: 135000, stock: 30, image: 'https://placehold.co/50x50/F0F4C3/212121?text=پ۴', description: 'چای سیاه معطر با اسانس برگاموت.' },
            { id: 5, name: 'چای سفید فاخر', category: 'چای سفید', price: 250000, stock: 15, image: 'https://placehold.co/50x50/EFEFEF/666666?text=پ۵', description: 'کمیاب‌ترین و لطیف‌ترین نوع چای.' },
            { id: 6, name: 'چای نعنا', category: 'دمنوش', price: 60000, stock: 90, image: 'https://placehold.co/50x50/b08f83/fcf8f5?text=پ۶', description: 'دمنوش نعنا تازه و گوارا.' },
            { id: 7, name: 'چای ترش', category: 'دمنوش', price: 80000, stock: 70, image: 'https://placehold.co/50x50/b08f83/fcf8f5?text=پ۷', description: 'دمنوش ترش و با طراوت.' },
            { id: 8, name: 'چای اولانگ', category: 'چای سبز', price: 180000, stock: 40, image: 'https://placehold.co/50x50/789a7f/fcf8f5?text=پ۸', description: 'چای نیمه تخمیری اولانگ.' },
            { id: 9, name: 'چای دارچین', category: 'دمنوش', price: 70000, stock: 110, image: 'https://placehold.co/50x50/b08f83/fcf8f5?text=پ۹', description: 'دمنوش دارچین گرم و دلپذیر.' },
            { id: 10, name: 'چای گل گاوزبان', category: 'دمنوش', price: 85000, stock: 65, image: 'https://placehold.co/50x50/b08f83/fcf8f5?text=پ۱۰', description: 'دمنوش آرامبخش گل گاوزبان.' },
            { id: 11, name: 'چای بهارنارنج', category: 'دمنوش', price: 90000, stock: 55, image: 'https://placehold.co/50x50/b08f83/fcf8f5?text=پ۱۱', description: 'دمنوش خوش عطر بهارنارنج.' },
            { id: 12, name: 'چای زنجبیل', category: 'دمنوش', price: 72000, stock: 75, image: 'https://placehold.co/50x50/b08f83/fcf8f5?text=پ۱۲', description: 'دمنوش گرم و انرژی‌بخش زنجبیل.' },
        ];

        let categories = [
            { id: 1, name: 'چای سیاه', productCount: 2 },
            { id: 2, name: 'چای سبز', productCount: 2 },
            { id: 3, name: 'دمنوش', productCount: 7 }, // Updated count
            { id: 4, name: 'چای سفید', productCount: 1 },
        ];

        let orders = [
            { id: '1001', user: 'علی احمدی', date: '۱۴۰۲/۰۳/۱۵', time: '14:30', total: 240000, status: 'در حال آماده‌سازی', products: [{name:'چای سیاه ممتاز', qty:2, price:120000}] },
            { id: '1002', user: 'فاطمه حسینی', date: '۱۴۰۲/۰۳/۱۶', time: '09:15', total: 95000, status: 'ارسال شده', products: [{name:'چای سبز خالص', qty:1, price:95000}] },
            { id: 1003, user: 'رضا کریمی', date: '۱۴۰۲/۰۳/۱۷', time: '11:00', total: 150000, status: 'لغو شده', products: [{name:'دمنوش آرامش', qty:2, price:75000}] },
            { id: '1004', user: 'سارا مولایی', date: '۱۴۰۲/0۳/۱۸', time: '16:45', total: 135000, status: 'ارسال شده', products: [{name:'چای ارل گری', qty:1, price:135000}] },
            { id: '1005', user: 'محمد رضایی', date: '۱۴۰۲/۰۴/۰۱', time: '10:00', total: 60000, status: 'پرداخت شده', products: [{name:'چای نعنا', qty:1, price:60000}] },
            { id: '1006', user: 'زهرا قاسمی', date: '۱۴۰۲/۰۴/۰۲', time: '12:30', total: 80000, status: 'در حال آماده‌سازی', products: [{name:'چای ترش', qty:1, price:80000}] },
            { id: '1007', user: 'امیر حسینی', date: '۱۴۰۲/۰۴/۰۳', time: '08:00', total: 180000, status: 'ارسال شده', products: [{name:'چای اولانگ', qty:1, price:180000}] },
            { id: 1008, user: 'نازنین کمالی', date: '۱۴۰۲/۰۴/۰۴', time: '15:20', total: 70000, status: 'پرداخت شده', products: [{name:'چای دارچین', qty:1, price:70000}] },
            { id: '1009', user: 'بهروز صادقی', date: '۱۴۰۲/۰۴/۰۵', time: '09:50', total: 85000, status: 'در حال آماده‌سازی', products: [{name:'چای گل گاوزبان', qty:1, price:85000}] },
            { id: '1010', user: 'مینا رحمانی', date: '۱۴۰۲/۰۴/۰۶', time: '17:00', total: 90000, status: 'ارسال شده', products: [{name:'چای بهارنارنج', qty:1, price:90000}] },
            { id: '1011', user: 'کوروش کیانی', date: '۱۴۰۲/۰۴/۰۷', time: '11:40', total: 72000, status: 'پرداخت شده', products: [{name:'چای زنجبیل', qty:1, price:72000}] },
            { id: '1012', user: 'لیلا شریفی', date: '۱۴۰۲/۰۴/۰۸', time: '13:00', total: 120000, status: 'لغو شده', products: [{name:'چای سیاه ممتاز', qty:1, price:120000}] },
            { id: '1013', user: 'علی حسینی', date: '۱۴۰۲/۰۴/۰۹', time: '10:10', total: 95000, status: 'در حال آماده‌سازی', products: [{name:'چای سبز خالص', qty:1, price:95000}] },
            { id: '1014', user: 'مریم نوری', date: '۱۴۰۲/۰۴/۱۰', time: '14:00', total: 75000, status: 'ارسال شده', products: [{name:'دمنوش آرامش', qty:1, price:75000}] },
            { id: '1015', user: 'جواد علوی', date: '۱۴۰۲/۰۴/۱۱', time: '09:30', total: 135000, status: 'پرداخت شده', products: [{name:'چای ارل گری', qty:1, price:135000}] },
        ];


        let users = [
            { id: 1, username: 'ali.ahmadi', email: 'ali@example.com', phone: '09123456789', regDate: '۱۴۰۲/۰۱/۱۰', role: 'کاربر' },
            { id: 2, username: 'fateme.h', email: 'fateme@example.com', phone: '09129876543', regDate: '۱۴۰۲/۰۱/۱۵', role: 'کاربر' },
            { id: 3, username: 'admin', email: 'admin@example.com', phone: '09101234567', regDate: '۱۴۰۱/۱۲/۰۱', role: 'مدیر کل' },
            { id: 4, username: 'staff', email: 'staff@example.com', phone: '09107654321', regDate: '۱۴۰۲/۲/۱', role: 'کارمند فروشگاه' },
            { id: 5, username: 'mohammad.r', email: 'mohammad@example.com', phone: '09191234567', regDate: '۱۴۰۲/۰۳/۰۵', role: 'کاربر' },
            { id: 6, username: 'zahra.g', email: 'zahra@example.com', phone: '09359876543', regDate: '۱۴۰۲/0۳/۱۰', role: 'کاربر' },
        ];

        let auditLogs = [
            { id: 1, time: '۱۴۰۲/۰۳/۱۸ - ۱۰:۳۰', user: 'admin', action: 'افزودن محصول', details: 'چای سفید فاخر', ip: '192.168.1.1' },
            { id: 2, time: '۱۴۰۲/0۳/۱۸ - ۰۹:۴۵', user: 'staff', action: 'تغییر وضعیت سفارش', details: 'سفارش #1002 به ارسال شده', ip: '192.168.1.5' },
            { id: 3, time: '۱۴۰۲/0۳/۱۷ - ۱۵:۰۰', user: 'admin', action: 'حذف دسته‌بندی', details: 'دسته‌بندی "چای سبز"', ip: '192.168.1.1' },
            { id: 4, time: '۱۴۰۲/0۳/۱۷ - ۰۸:۲۰', user: 'ali.ahmadi', action: 'ورود موفق', details: 'ورود کاربر عادی', ip: '172.20.10.2' },
            { id: 5, time: '۱۴۰۲/۰۴/۰۱ - ۱۱:۰۰', user: 'admin', action: 'ویرایش محصول', details: 'چای نعنا', ip: '192.168.1.1' },
            { id: 6, time: '۱۴۰۲/۰۴/۰۲ - ۱۴:۲۰', user: 'staff', action: 'افزودن سفارش', details: 'سفارش #1006', ip: '192.168.1.5' },
            { id: 7, time: '۱۴۰۲/۰۴/۰۳ - ۰۸:۱۵', user: 'admin', action: 'مشاهده گزارش', details: 'گزارش فروش ماهانه', ip: '192.168.1.1' },
            { id: 8, time: '۱۴۰۲/۰۴/۰۴ - ۱۶:۰۰', user: 'mohammad.r', action: 'تلاش برای ورود ناموفق', details: 'رمز عبور اشتباه', ip: '172.20.10.3' },
            { id: 9, time: '۱۴۰۲/۰۴/۰۵ - ۰۹:۰۰', user: 'admin', action: 'افزودن دسته‌بندی', details: 'دسته‌بندی "قهوه"', ip: '192.168.1.1' },
            { id: 10, time: '۱۴۰۲/۰۴/۰۶ - ۱۷:۳۰', user: 'staff', action: 'غیرفعال کردن کاربر', details: 'کاربر "testuser"', ip: '192.168.1.5' },
        ];


        // ---------- مدیریت بخش‌ها (نمایش/پنهان کردن) ----------
        const sidebarNavLinks = document.querySelectorAll('.sidebar-nav a');
        const contentSections = document.querySelectorAll('#content-area section');
        const currentSectionTitle = document.getElementById('current-section-title');

        function showSection(sectionId) {
            contentSections.forEach(section => {
                section.classList.add('hidden');
            });
            document.getElementById(`${sectionId}-section`).classList.remove('hidden');

            sidebarNavLinks.forEach(link => {
                link.classList.remove('active');
            });
            document.querySelector(`.sidebar-nav a[data-section="${sectionId}"]`).classList.add('active');

            currentSectionTitle.textContent = document.querySelector(`.sidebar-nav a[data-section="${sectionId}"] span`).textContent;

            // فراخوانی تابع رندر مربوط به هر بخش هنگام نمایش آن
            // Reset pagination and sorting when switching sections
            currentProductPage = 1;
            currentCategoryPage = 1;
            currentOrderPage = 1;
            currentUserPage = 1;
            currentAuditLogPage = 1;
            productSortColumn = null;
            productSortDirection = 'asc';
            productSearchInput.value = ''; // Clear search on section change

            // Reset order filters when navigating to orders section
            if (sectionId === 'orders') {
                document.getElementById('order-search-id').value = '';
                document.getElementById('order-search-user').value = '';
                document.getElementById('order-filter-status').value = '';
                document.getElementById('order-filter-date-from').value = '';
                document.getElementById('order-filter-date-to').value = '';
                orderFilterState = {
                    id: '',
                    user: '',
                    status: '',
                    dateFrom: '',
                    dateTo: '',
                };
                orderSortColumn = null; // Reset order sort
                orderSortDirection = 'asc'; // Reset order sort direction
            }

            if (sectionId === 'products') renderProductsTable();
            if (sectionId === 'categories') renderCategoriesTable();
            if (sectionId === 'orders') renderOrdersTable();
            if (sectionId === 'users') renderUsersTable();
            if (sectionId === 'dashboard' || sectionId === 'reports') renderCharts(); // رندر مجدد نمودارها در صورت نیاز
            if (sectionId === 'reports') renderPredefinedReports();
            if (sectionId === 'audit-logs') renderAuditLogsTable();
        }

        sidebarNavLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                showSection(e.currentTarget.dataset.section);
                // بستن سایدبار در حالت موبایل بعد از انتخاب گزینه
                if (window.innerWidth <= 768) {
                    document.getElementById('sidebar').classList.remove('active');
                }
            });
        });

        // ---------- Pagination Variables ----------
        let productsPerPage = parseInt(document.getElementById('products-per-page')?.value) || 5;
        let currentProductPage = 1;
        let productSortColumn = null;
        let productSortDirection = 'asc'; // 'asc' or 'desc'
        let productSearchTerm = '';

        let categoriesPerPage = parseInt(document.getElementById('categories-per-page')?.value) || 5;
        let currentCategoryPage = 1;

        let ordersPerPage = parseInt(document.getElementById('orders-per-page')?.value) || 5;
        let currentOrderPage = 1;
        let orderFilterState = {
            id: '',
            user: '',
            status: '',
            dateFrom: '',
            dateTo: '',
        };
        let orderSortColumn = null;
        let orderSortDirection = 'asc';


        let usersPerPage = parseInt(document.getElementById('users-per-page')?.value) || 5;
        let currentUserPage = 1;

        let auditLogsPerPage = parseInt(document.getElementById('audit-logs-per-page')?.value) || 5;
        let currentAuditLogPage = 1;

        // Function to render pagination controls
        function renderPaginationControls(containerId, totalItems, itemsPerPage, currentPage, renderTableFunction) {
            const container = document.getElementById(containerId);
            if (!container) return; // Exit if container doesn't exist

            container.innerHTML = '';
            const totalPages = Math.ceil(totalItems / itemsPerPage);

            // Previous button
            const prevButton = document.createElement('button');
            prevButton.className = 'pagination-button';
            prevButton.textContent = 'قبلی';
            prevButton.disabled = currentPage === 1;
            prevButton.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderTableFunction(currentPage);
                }
            });
            container.appendChild(prevButton);

            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                const pageButton = document.createElement('button');
                pageButton.className = `pagination-button ${i === currentPage ? 'active' : ''}`;
                pageButton.textContent = i;
                pageButton.addEventListener('click', () => {
                    currentPage = i;
                    renderTableFunction(currentPage);
                });
                container.appendChild(pageButton);
            }

            // Next button
            const nextButton = document.createElement('button');
            nextButton.className = 'pagination-button';
            nextButton.textContent = 'بعدی';
            nextButton.disabled = currentPage === totalPages;
            nextButton.addEventListener('click', () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    renderTableFunction(currentPage);
                }
            });
            container.appendChild(nextButton);

            // Update respective global page variables
            if (containerId === 'products-pagination') currentProductPage = currentPage;
            if (containerId === 'categories-pagination') currentCategoryPage = currentPage;
            if (containerId === 'orders-pagination') currentOrderPage = currentPage;
            if (containerId === 'users-pagination') currentUserPage = currentPage;
            if (containerId === 'audit-logs-pagination') currentAuditLogPage = currentPage;
        }

        // ---------- توابع رندر جدول‌ها (CRUD شبیه‌سازی شده) ----------

        // رندر جدول محصولات
        const productsTableBody = document.getElementById('products-table-body');
        const productFormContainer = document.getElementById('product-form-container');
        const productFormTitle = document.getElementById('product-form-title');
        const productForm = document.getElementById('product-form');
        const productIdInput = document.getElementById('product-id');
        const productNameInput = document.getElementById('product-name');
        const productCategoryInput = document.getElementById('product-category');
        const productPriceInput = document.getElementById('product-price');
        const productStockInput = document.getElementById('product-stock');
        const productDescriptionInput = document.getElementById('product-description');
        const addProductBtn = document.getElementById('add-product-btn');
        const cancelProductBtn = document.getElementById('cancel-product-btn');
        const productSearchInput = document.getElementById('product-search');
        const productsPerPageSelect = document.getElementById('products-per-page');

        function renderProductsTable(page = currentProductPage) {
            productsTableBody.innerHTML = '';
            
            let filteredProducts = products.filter(p => 
                p.name.toLowerCase().includes(productSearchTerm.toLowerCase()) ||
                p.category.toLowerCase().includes(productSearchTerm.toLowerCase())
            );

            if (productSortColumn) {
                filteredProducts.sort((a, b) => {
                    let valA = a[productSortColumn];
                    let valB = b[productSortColumn];

                    if (typeof valA === 'string') {
                        return productSortDirection === 'asc' ? valA.localeCompare(valB, 'fa') : valB.localeCompare(valA, 'fa');
                    }
                    return productSortDirection === 'asc' ? valA - valB : valB - valA;
                });
            }

            const startIndex = (page - 1) * productsPerPage;
            const endIndex = startIndex + productsPerPage;
            const itemsToRender = filteredProducts.slice(startIndex, endIndex);

            itemsToRender.forEach(product => {
                const row = productsTableBody.insertRow();
                row.innerHTML = `
                    <td class="py-2"><img src="${product.image}" alt="${product.name}" class="w-12 h-12 rounded-lg object-cover mx-auto"></td>
                    <td class="py-2">${product.name}</td>
                    <td class="py-2">${product.category}</td>
                    <td class="py-2">${product.price.toLocaleString('fa-IR')} تومان</td>
                    <td class="py-2">${product.stock}</td>
                    <td class="py-2">
                        <button class="btn btn-secondary text-sm px-3 py-1 ml-2 edit-product-btn" data-id="${product.id}" aria-label="ویرایش محصول ${product.name}">
                            <i class="fas fa-edit" aria-hidden="true"></i> ویرایش
                        </button>
                        <button class="btn btn-danger text-sm px-3 py-1 delete-product-btn" data-id="${product.id}" aria-label="حذف محصول ${product.name}">
                            <i class="fas fa-trash-alt" aria-hidden="true"></i> حذف
                        </button>
                    </td>
                `;
            });
            // افزودن شنونده رویداد برای دکمه‌های ویرایش و حذف
            document.querySelectorAll('.edit-product-btn').forEach(button => {
                button.addEventListener('click', (e) => editProduct(parseInt(e.currentTarget.dataset.id)));
            });
            document.querySelectorAll('.delete-product-btn').forEach(button => {
                button.addEventListener('click', (e) => deleteProduct(parseInt(e.currentTarget.dataset.id)));
            });

            // Update pagination controls
            renderPaginationControls('products-pagination', filteredProducts.length, productsPerPage, page, renderProductsTable);
        }

        // Product Table Search
        if (productSearchInput) {
            productSearchInput.addEventListener('input', (e) => {
                productSearchTerm = e.target.value;
                currentProductPage = 1; // Reset to first page on search
                renderProductsTable();
            });
        }

        // Products Per Page Change
        if (productsPerPageSelect) {
            productsPerPageSelect.addEventListener('change', (e) => {
                productsPerPage = parseInt(e.target.value);
                currentProductPage = 1; // Reset to first page
                renderProductsTable();
            });
        }

        // Product Table Sorting
        document.querySelectorAll('#products-section .data-table th[data-sort]').forEach(header => {
            header.addEventListener('click', (e) => {
                const column = e.currentTarget.dataset.sort;
                if (productSortColumn === column) {
                    productSortDirection = productSortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    productSortColumn = column;
                    productSortDirection = 'asc';
                }
                renderProductsTable();
            });
        });


        addProductBtn.addEventListener('click', () => {
            // شبیه‌سازی بررسی دسترسی
            if (!checkPermission(currentUser.role, 'manage_products')) {
                showMessage('شما اجازه افزودن محصول جدید را ندارید.', 'error');
                return;
            }
            productFormContainer.classList.remove('hidden');
            productFormTitle.textContent = 'افزودن محصول جدید';
            productForm.reset();
            productIdInput.value = '';
        });

        cancelProductBtn.addEventListener('click', () => {
            productFormContainer.classList.add('hidden');
        });

        productForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const id = productIdInput.value ? parseInt(productIdInput.value) : null;
            const name = sanitizeInput(productNameInput.value); // پاکسازی ورودی
            const category = productCategoryInput.value;
            const price = parseInt(productPriceInput.value);
            const stock = parseInt(productStockInput.value);
            const description = sanitizeInput(productDescriptionInput.value); // پاکسازی ورودی
            // در حالت واقعی، تصویر باید آپلود شود
            const image = products.find(p => p.id === id)?.image || 'https://placehold.co/50x50/EFEFEF/666666?text=تصویر';

            if (id) {
                 if (!checkPermission(currentUser.role, 'manage_products')) {
                    showMessage('شما اجازه ویرایش محصول را ندارید.', 'error');
                    return;
                }
                // ویرایش محصول
                const index = products.findIndex(p => p.id === id);
                if (index !== -1) {
                    products[index] = { ...products[index], name, category, price, stock, description, image };
                    showMessage('محصول با موفقیت ویرایش شد.', 'success');
                    logAdminAction(currentUser.username, 'ویرایش محصول', `محصول ${name} (ID: ${id})`);
                }
            } else {
                 if (!checkPermission(currentUser.role, 'manage_products')) {
                    showMessage('شما اجازه افزودن محصول را ندارید.', 'error');
                    return;
                }
                // افزودن محصول جدید
                const newId = products.length > 0 ? Math.max(...products.map(p => p.id)) + 1 : 1;
                products.push({ id: newId, name, category, price, stock, image, description });
                showMessage('محصول جدید با موفقیت اضافه شد.', 'success');
                logAdminAction(currentUser.username, 'افزودن محصول', `محصول ${name}`);
            }
            renderProductsTable();
            productFormContainer.classList.add('hidden');
        });

        function editProduct(id) {
            if (!checkPermission(currentUser.role, 'manage_products')) {
                showMessage('شما اجازه ویرایش محصول را ندارید.', 'error');
                return;
            }
            const product = products.find(p => p.id === id);
            if (product) {
                productFormContainer.classList.remove('hidden');
                productFormTitle.textContent = `ویرایش محصول: ${product.name}`;
                productIdInput.value = product.id;
                productNameInput.value = product.name;
                productCategoryInput.value = product.category;
                productPriceInput.value = product.price;
                productStockInput.value = product.stock;
                productDescriptionInput.value = product.description || '';
            }
        }

        function deleteProduct(id) {
            if (!checkPermission(currentUser.role, 'manage_products')) {
                showMessage('شما اجازه حذف محصول را ندارید.', 'error');
                return;
            }
            const productToDelete = products.find(p => p.id === id);
            if (confirm(`آیا از حذف محصول "${productToDelete.name}" مطمئن هستید؟`)) {
                products = products.filter(p => p.id !== id);
                renderProductsTable();
                showMessage('محصول با موفقیت حذف شد.', 'success');
                logAdminAction(currentUser.username, 'حذف محصول', `محصول ${productToDelete.name} (ID: ${id})`);
            }
        }


        // رندر جدول دسته‌بندی‌ها
        const categoriesTableBody = document.getElementById('categories-table-body');
        const categoryFormContainer = document.getElementById('category-form-container');
        const categoryFormTitle = document.getElementById('category-form-title');
        const categoryForm = document.getElementById('category-form');
        const categoryIdInput = document.getElementById('category-id');
        const categoryNameInput = document.getElementById('category-name');
        const addCategoryBtn = document.getElementById('add-category-btn');
        const cancelCategoryBtn = document.getElementById('cancel-category-btn');
        const categoriesPerPageSelect = document.getElementById('categories-per-page');


        function renderCategoriesTable(page = currentCategoryPage) {
            categoriesTableBody.innerHTML = '';
            const startIndex = (page - 1) * categoriesPerPage;
            const endIndex = startIndex + categoriesPerPage;
            const itemsToRender = categories.slice(startIndex, endIndex);

            itemsToRender.forEach(category => {
                const row = categoriesTableBody.insertRow();
                row.innerHTML = `
                    <td class="py-2">${category.name}</td>
                    <td class="py-2">${category.productCount}</td>
                    <td class="py-2">
                        <button class="btn btn-secondary text-sm px-3 py-1 ml-2 edit-category-btn" data-id="${category.id}" aria-label="ویرایش دسته‌بندی ${category.name}">
                            <i class="fas fa-edit" aria-hidden="true"></i> ویرایش
                        </button>
                        <button class="btn btn-danger text-sm px-3 py-1 delete-category-btn" data-id="${category.id}" aria-label="حذف دسته‌بندی ${category.name}">
                            <i class="fas fa-trash-alt" aria-hidden="true"></i> حذف
                        </button>
                    </td>
                `;
            });
            document.querySelectorAll('.edit-category-btn').forEach(button => {
                button.addEventListener('click', (e) => editCategory(parseInt(e.currentTarget.dataset.id)));
            });
            document.querySelectorAll('.delete-category-btn').forEach(button => {
                button.addEventListener('click', (e) => deleteCategory(parseInt(e.currentTarget.dataset.id)));
            });

            renderPaginationControls('categories-pagination', categories.length, categoriesPerPage, page, renderCategoriesTable);
        }

        if (categoriesPerPageSelect) {
            categoriesPerPageSelect.addEventListener('change', (e) => {
                categoriesPerPage = parseInt(e.target.value);
                currentCategoryPage = 1;
                renderCategoriesTable();
            });
        }


        addCategoryBtn.addEventListener('click', () => {
            if (!checkPermission(currentUser.role, 'manage_categories')) {
                showMessage('شما اجازه افزودن دسته‌بندی جدید را ندارید.', 'error');
                return;
            }
            categoryFormContainer.classList.remove('hidden');
            categoryFormTitle.textContent = 'افزودن دسته‌بندی جدید';
            categoryForm.reset();
            categoryIdInput.value = '';
        });

        cancelCategoryBtn.addEventListener('click', () => {
            categoryFormContainer.classList.add('hidden');
        });

        categoryForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const id = categoryIdInput.value ? parseInt(categoryIdInput.value) : null;
            const name = sanitizeInput(categoryNameInput.value); // پاکسازی ورودی

            if (id) {
                if (!checkPermission(currentUser.role, 'manage_categories')) {
                    showMessage('شما اجازه ویرایش دسته‌بندی را ندارید.', 'error');
                    return;
                }
                const index = categories.findIndex(c => c.id === id);
                if (index !== -1) {
                    categories[index] = { ...categories[index], name };
                    showMessage('دسته‌بندی با موفقیت ویرایش شد.', 'success');
                    logAdminAction(currentUser.username, 'ویرایش دسته‌بندی', `دسته‌بندی ${name} (ID: ${id})`);
                }
            } else {
                if (!checkPermission(currentUser.role, 'manage_categories')) {
                    showMessage('شما اجازه افزودن دسته‌بندی را ندارید.', 'error');
                    return;
                }
                const newId = categories.length > 0 ? Math.max(...categories.map(c => c.id)) + 1 : 1;
                categories.push({ id: newId, name, productCount: 0 }); // تعداد محصولات جدید 0 است
                showMessage('دسته‌بندی جدید با موفقیت اضافه شد.', 'success');
                logAdminAction(currentUser.username, 'افزودن دسته‌بندی', `دسته‌بندی ${name}`);
            }
            renderCategoriesTable();
            categoryFormContainer.classList.add('hidden');
        });

        function editCategory(id) {
            if (!checkPermission(currentUser.role, 'manage_categories')) {
                showMessage('شما اجازه ویرایش دسته‌بندی را ندارید.', 'error');
                return;
            }
            const category = categories.find(c => c.id === id);
            if (category) {
                categoryFormContainer.classList.remove('hidden');
                categoryFormTitle.textContent = `ویرایش دسته‌بندی: ${category.name}`;
                categoryIdInput.value = category.id;
                categoryNameInput.value = category.name;
            }
        }

        function deleteCategory(id) {
            if (!checkPermission(currentUser.role, 'manage_categories')) {
                showMessage('شما اجازه حذف دسته‌بندی را ندارید.', 'error');
                return;
            }
            const categoryToDelete = categories.find(c => c.id === id);
            if (confirm(`آیا از حذف دسته‌بندی "${categoryToDelete.name}" مطمئن هستید؟`)) {
                categories = categories.filter(c => c.id !== id);
                showMessage('دسته‌بندی با موفقیت حذف شد.', 'success');
                logAdminAction(currentUser.username, 'حذف دسته‌بندی', `دسته‌بندی ${categoryToDelete.name} (ID: ${id})`);
            }
        }


        // رندر جدول سفارش‌ها
        const ordersTableBody = document.getElementById('orders-table-body');
        const ordersPerPageSelect = document.getElementById('orders-per-page');
        const orderSearchIdInput = document.getElementById('order-search-id');
        const orderSearchUserInput = document.getElementById('order-search-user');
        const orderFilterStatusSelect = document.getElementById('order-filter-status');
        const orderFilterDateFromInput = document.getElementById('order-filter-date-from');
        const orderFilterDateToInput = document.getElementById('order-filter-date-to');
        const applyOrderFiltersBtn = document.getElementById('apply-order-filters');
        const clearOrderFiltersBtn = document.getElementById('clear-order-filters');
        const exportOrdersExcelBtn = document.getElementById('export-orders-excel-btn'); // New button

        function renderOrdersTable(page = currentOrderPage) {
            if (!checkPermission(currentUser.role, 'view_orders')) {
                ordersTableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-red-500">شما اجازه مشاهده سفارش‌ها را ندارید.</td></tr>'; // Changed colspan
                return;
            }
            ordersTableBody.innerHTML = '';
            
            let filteredOrders = orders.filter(order => {
                // To compare dates correctly, convert Persian date strings to Date objects.
                // Assuming Persian dates are inYYYY/MM/DD format, convert toYYYY-MM-DD for Date object.
                const orderDateString = order.date.replace(/(\d{4})\/(\d{2})\/(\d{2})/, '$1-$2-$3');
                const orderDateTime = new Date(`${orderDateString}T${order.time}`);
                
                // Filter by ID
                if (orderFilterState.id && !order.id.toString().includes(orderFilterState.id)) { // Convert ID to string for includes
                    return false;
                }
                // Filter by User
                if (orderFilterState.user && !order.user.toLowerCase().includes(orderFilterState.user.toLowerCase())) {
                    return false;
                }
                // Filter by Status
                if (orderFilterState.status && order.status !== orderFilterState.status) {
                    return false;
                }
                // Filter by Date From
                if (orderFilterState.dateFrom) {
                    const fromDate = new Date(orderFilterState.dateFrom);
                    if (orderDateTime < fromDate) {
                        return false;
                    }
                }
                // Filter by Date To
                if (orderFilterState.dateTo) {
                    const toDate = new Date(orderFilterState.dateTo);
                    // To include the end of the day for 'dateTo', add 23:59:59.999
                    const endOfDayToDate = new Date(toDate.getFullYear(), toDate.getMonth(), toDate.getDate(), 23, 59, 59, 999);
                    if (orderDateTime > endOfDayToDate) {
                        return false;
                    }
                }
                return true;
            });

            if (orderSortColumn) {
                filteredOrders.sort((a, b) => {
                    let valA, valB;

                    if (orderSortColumn === 'date' || orderSortColumn === 'time') {
                        // For date and time, compare full date objects for accurate sorting
                        const dateA = new Date(`${a.date.replace(/(\d{4})\/(\d{2})\/(\d{2})/, '$1-$2-$3')}T${a.time}`);
                        const dateB = new Date(`${b.date.replace(/(\d{4})\/(\d{2})\/(\d{2})/, '$1-$2-$3')}T${b.time}`);
                        valA = dateA.getTime();
                        valB = dateB.getTime();
                    } else {
                        valA = a[orderSortColumn];
                        valB = b[orderSortColumn];
                    }
                    
                    if (typeof valA === 'string') {
                        return orderSortDirection === 'asc' ? valA.localeCompare(valB, 'fa') : valB.localeCompare(valA, 'fa');
                    }
                    return orderSortDirection === 'asc' ? valA - valB : valB - valA;
                });
            }

            const startIndex = (page - 1) * ordersPerPage;
            const endIndex = startIndex + ordersPerPage;
            const itemsToRender = filteredOrders.slice(startIndex, endIndex);

            itemsToRender.forEach(order => {
                const row = ordersTableBody.insertRow();
                row.innerHTML = `
                    <td class="py-2">${order.id}</td>
                    <td class="py-2">${order.user}</td>
                    <td class="py-2">${order.date}</td>
                    <td class="py-2">${order.time}</td>
                    <td class="py-2">${order.total.toLocaleString('fa-IR')} تومان</td>
                    <td class="py-2">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold ${order.status === 'ارسال شده' ? 'bg-green-100 text-green-800' : order.status === 'در حال آماده‌سازی' ? 'bg-blue-100 text-blue-600' : order.status === 'لغو شده' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-700'}">
                            ${order.status}
                        </span>
                    </td>
                    <td class="py-2">
                        <button class="btn btn-secondary text-sm px-3 py-1 view-order-details" data-id="${order.id}" aria-label="مشاهده جزئیات سفارش ${order.id}">
                            <i class="fas fa-eye" aria-hidden="true"></i> جزئیات
                        </button>
                    </td>
                `;
            });
            document.querySelectorAll('.view-order-details').forEach(button => {
                button.addEventListener('click', (e) => viewOrderDetails(e.currentTarget.dataset.id));
            });
             // Add event listeners for sorting
            document.querySelectorAll('#orders-section .data-table th[data-sort]').forEach(header => {
                header.addEventListener('click', (e) => {
                    const column = e.currentTarget.dataset.sort;
                    if (orderSortColumn === column) {
                        orderSortDirection = orderSortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        orderSortColumn = column;
                        orderSortDirection = 'asc';
                    }
                    renderOrdersTable(); // Re-render with new sort order
                });
            });

            renderPaginationControls('orders-pagination', filteredOrders.length, ordersPerPage, page, renderOrdersTable);
        }

        // Order Filter Event Listeners
        if (applyOrderFiltersBtn) {
            applyOrderFiltersBtn.addEventListener('click', () => {
                orderFilterState.id = orderSearchIdInput.value.trim();
                orderFilterState.user = orderSearchUserInput.value.trim();
                orderFilterState.status = orderFilterStatusSelect.value;
                orderFilterState.dateFrom = orderFilterDateFromInput.value;
                orderFilterState.dateTo = orderFilterDateToInput.value;
                currentOrderPage = 1; // Reset to first page on filter
                renderOrdersTable();
            });
        }
        if (clearOrderFiltersBtn) {
            clearOrderFiltersBtn.addEventListener('click', () => {
                orderSearchIdInput.value = '';
                orderSearchUserInput.value = '';
                orderFilterStatusSelect.value = '';
                orderFilterDateFromInput.value = '';
                orderFilterDateToInput.value = '';
                orderFilterState = {
                    id: '',
                    user: '',
                    status: '',
                    dateFrom: '',
                    dateTo: '',
                };
                currentOrderPage = 1; // Reset to first page
                renderOrdersTable();
            });
        }

        if (ordersPerPageSelect) {
            ordersPerPageSelect.addEventListener('change', (e) => {
                ordersPerPage = parseInt(e.target.value);
                currentOrderPage = 1;
                renderOrdersTable();
            });
        }

        // Event listener for export orders Excel button
        if (exportOrdersExcelBtn) {
            exportOrdersExcelBtn.addEventListener('click', () => {
                if (!checkPermission(currentUser.role, 'view_orders')) { // Or a more specific 'export_orders' permission
                    showMessage('شما اجازه خروجی گرفتن از سفارش‌ها را ندارید.', 'error');
                    return;
                }
                const dataToExport = orders.map(o => ({
                    'شناسه سفارش': o.id,
                    'کاربر': o.user,
                    'تاریخ': o.date,
                    'زمان': o.time,
                    'مبلغ کل (تومان)': o.total,
                    'وضعیت': o.status,
                    'محصولات': o.products.map(p => `${p.name} (تعداد: ${p.qty})`).join(', ') // Include product details
                }));
                exportToExcel(dataToExport, 'گزارش_سفارشات');
            });
        }


        function viewOrderDetails(id) {
            if (!checkPermission(currentUser.role, 'view_orders')) {
                showMessage('شما اجازه مشاهده جزئیات سفارش را ندارید.', 'error');
                return;
            }
            const order = orders.find(o => o.id === id);
            if (order) {
                let productsList = order.products.map(p => `${p.name} (تعداد: ${p.qty}, قیمت واحد: ${p.price.toLocaleString('fa-IR')} تومان)`).join('<br>');
                showMessage(`
                    <div style="text-align: right; direction: rtl;">
                        <h4 style="font-weight: bold; margin-bottom: 5px;">جزئیات سفارش ${order.id}:</h4>
                        <p><strong>کاربر:</strong> ${order.user}</p>
                        <p><strong>تاریخ:</strong> ${order.date} ${order.time}</p>
                        <p><strong>وضعیت:</strong> ${order.status}</p>
                        <p><strong>محصولات:</strong><br>${productsList}</p>
                        <p><strong>مبلغ کل:</strong> ${order.total.toLocaleString('fa-IR')} تومان</p>
                    </div>
                `, 'info');
                logAdminAction(currentUser.username, 'مشاهده جزئیات سفارش', `سفارش ${order.id} - کاربر: ${order.user}`);
            }
        }

        // رندر جدول کاربران
        const usersTableBody = document.getElementById('users-table-body');
        const usersPerPageSelect = document.getElementById('users-per-page');

        function renderUsersTable(page = currentUserPage) {
            if (!checkPermission(currentUser.role, 'view_users')) {
                usersTableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-red-500">شما اجازه مشاهده کاربران را ندارید.</td></tr>';
                return;
            }
            usersTableBody.innerHTML = '';

            const startIndex = (page - 1) * usersPerPage;
            const endIndex = startIndex + usersPerPage;
            const itemsToRender = users.slice(startIndex, endIndex);

            itemsToRender.forEach(user => {
                const row = usersTableBody.insertRow();
                row.innerHTML = `
                    <td class="py-2">${user.username}</td>
                    <td class="py-2">${user.email}</td>
                    <td class="py-2">${user.phone}</td>
                    <td class="py-2">${user.regDate}</td>
                    <td class="py-2">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold ${user.role === 'مدیر کل' ? 'bg-purple-100 text-purple-600' : user.role === 'کارمند فروشگاه' ? 'bg-orange-100 text-orange-600' : user.role === 'غیرفعال شده' ? 'bg-gray-200 text-gray-600' : 'bg-gray-100 text-gray-700'}">
                            ${user.role}
                        </span>
                    </td>
                    <td class="py-2">
                        <button class="btn btn-secondary text-sm px-3 py-1 ml-2 view-user-details" data-id="${user.id}" aria-label="مشاهده جزئیات کاربر ${user.username}">
                            <i class="fas fa-eye" aria-hidden="true"></i> مشاهده
                        </button>
                        <button class="btn btn-danger text-sm px-3 py-1 toggle-user-status" data-id="${user.id}" data-status="${user.role === 'غیرفعال شده' ? 'enable' : 'disable'}" aria-label="${user.role === 'غیرفعال شده' ? 'فعال کردن' : 'غیرفعال کردن'} کاربر ${user.username}">
                            <i class="fas ${user.role === 'غیرفعال شده' ? 'fa-user-check' : 'fa-user-slash'}" aria-hidden="true"></i> ${user.role === 'غیرفعال شده' ? 'فعال کردن' : 'غیرفعال کردن'}
                        </button>
                    </td>
                `;
            });
            document.querySelectorAll('.view-user-details').forEach(button => {
                button.addEventListener('click', (e) => viewUserDetails(parseInt(e.currentTarget.dataset.id)));
            });
            document.querySelectorAll('.toggle-user-status').forEach(button => {
                button.addEventListener('click', (e) => toggleUserStatus(parseInt(e.currentTarget.dataset.id), e.currentTarget.dataset.status));
            });
            renderPaginationControls('users-pagination', users.length, usersPerPage, page, renderUsersTable);
        }

        if (usersPerPageSelect) {
            usersPerPageSelect.addEventListener('change', (e) => {
                usersPerPage = parseInt(e.target.value);
                currentUserPage = 1;
                renderUsersTable();
            });
        }

        function viewUserDetails(id) {
            if (!checkPermission(currentUser.role, 'view_users')) {
                showMessage('شما اجازه مشاهده جزئیات کاربر را ندارید.', 'error');
                return;
            }
            const user = users.find(u => u.id === id);
            if (user) {
                 showMessage(`
                    <div style="text-align: right; direction: rtl;">
                        <h4 style="font-weight: bold; margin-bottom: 5px;">جزئیات کاربر ${user.username}:</h4>
                        <p><strong>ایمیل:</strong> ${user.email}</p>
                        <p><strong>تلفن:</strong> ${user.phone}</p>
                        <p><strong>تاریخ ثبت‌نام:</strong> ${user.regDate}</p>
                        <p><strong>نقش:</strong> ${user.role}</p>
                    </div>
                `, 'info');
                logAdminAction(currentUser.username, 'مشاهده جزئیات کاربر', `کاربر: ${user.username} (ID: ${id})`);
            }
        }

        function toggleUserStatus(id, status) {
            if (!checkPermission(currentUser.role, 'manage_users')) {
                showMessage('شما اجازه تغییر وضعیت کاربران را ندارید.', 'error');
                return;
            }
            const user = users.find(u => u.id === id);
            if (user) {
                if (status === 'disable') {
                    if (user.role === 'مدیر کل') { // مدیر کل را نمی‌توان غیرفعال کرد
                        showMessage('نمی‌توانید مدیر کل را غیرفعال کنید.', 'error');
                        return;
                    }
                    if (user.role === 'کارمند فروشگاه' && currentUser.role !== 'مدیر کل') { // کارمند فقط توسط مدیر کل غیرفعال می‌شود
                         showMessage('شما اجازه غیرفعال کردن این نقش را ندارید.', 'error');
                         return;
                    }
                    user.role = 'غیرفعال شده'; // شبیه‌سازی غیرفعال کردن
                    showMessage(`کاربر ${user.username} غیرفعال شد.`, 'info');
                    logAdminAction(currentUser.username, 'غیرفعال کردن کاربر', `کاربر ${user.username} (ID: ${id})`);
                } else { // status === 'enable'
                    if (user.role === 'غیرفعال شده') { // فقط اگر قبلاً غیرفعال بوده
                        user.role = 'کاربر'; // شبیه‌سازی فعال کردن به نقش کاربر عادی
                        showMessage(`کاربر ${user.username} فعال شد.`, 'success');
                        logAdminAction(currentUser.username, 'فعال کردن کاربر', `کاربر ${user.username} (ID: ${id})`);
                    }
                }
                renderUsersTable(); // رندر مجدد جدول برای به‌روزرسانی وضعیت
            }
        }


        // ---------- مدیریت نمودارها با Chart.js ----------
        let salesChartInstance = null;
        let dailySalesChartInstance = null;

        function renderCharts() {
            // نمودار فروش ماهیانه
            const salesCtx = document.getElementById('salesChart');
            if (salesCtx && typeof Chart !== 'undefined') { // Check if Chart is defined before using it
                if (salesChartInstance) {
                    salesChartInstance.destroy(); // پاک کردن نمودار قبلی در صورت وجود
                }
                salesChartInstance = new Chart(salesCtx, {
                    type: document.getElementById('sales-chart-type')?.value || 'bar', // از نوع انتخابی استفاده کن
                    data: {
                        labels: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور'],
                        datasets: [{
                            label: 'میزان فروش (میلیون تومان)',
                            data: [120, 190, 130, 250, 220, 300],
                            backgroundColor: 'rgba(30, 90, 32, 0.7)',
                            borderColor: 'rgba(30, 90, 32, 1)',
                            borderWidth: 1,
                            borderRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true, // Changed to true
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    font: {
                                        family: 'Vazirmatn'
                                    }
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
                        }
                    }
                });
            } else if (salesCtx) {
                console.error("Chart.js is not loaded for salesChart. Or salesCtx is null.");
            }


            // نمودار فروش روزانه (برای بخش گزارش‌گیری)
            const dailySalesCtx = document.getElementById('dailySalesChart');
            if (dailySalesCtx && typeof Chart !== 'undefined') { // Check if Chart is defined before using it
                if (dailySalesChartInstance) {
                    dailySalesChartInstance.destroy(); // پاک کردن نمودار قبلی در صورت وجود
                }
                dailySalesChartInstance = new Chart(dailySalesCtx, {
                    type: 'line',
                    data: {
                        labels: ['۱', '۲', '۳', '۴', '۵', '۶', '۷'],
                        datasets: [{
                            label: 'فروش روزانه (هزار تومان)',
                            data: [20, 30, 25, 40, 35, 45, 50],
                            backgroundColor: 'rgba(37, 99, 235, 0.5)', /* رنگ آبی */
                            borderColor: 'rgba(37, 99, 235, 1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true, // Changed to true
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    font: {
                                        family: 'Vazirmatn'
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'میزان فروش (هزار تومان)',
                                    font: {
                                        family: 'Vazirmatn'
                                    }
                                },
                                ticks: {
                                    font: {
                                        family: 'Vazirmatn'
                                    }
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'روز',
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
                        }
                    }
                });
            } else if (dailySalesCtx) {
                 console.error("Chart.js is not loaded for dailySalesChart. Or dailySalesCtx is null.");
            }
        }

        // Event listener for sales chart type change
        const salesChartTypeSelect = document.getElementById('sales-chart-type');
        if (salesChartTypeSelect) {
            salesChartTypeSelect.addEventListener('change', renderCharts);
        }
        // Event listener for sales time range change
        const salesTimeRangeSelect = document.getElementById('sales-time-range');
        if (salesTimeRangeSelect) {
            salesTimeRangeSelect.addEventListener('change', (e) => {
                // Here you would typically fetch new data based on the time range
                // For now, we'll just re-render with existing mock data.
                showMessage(`نمودار ${e.target.value} نمایش داده می‌شود (داده‌های شبیه‌سازی شده).`, 'info');
                renderCharts();
            });
        }


        // ---------- توابع خروجی‌گیری و پیش‌نمایش ----------

        // Export to Excel
        function exportToExcel(data, filename) {
            if (!data || data.length === 0) {
                showMessage('هیچ داده‌ای برای خروجی گرفتن وجود ندارد.', 'error');
                return;
            }
            showLoading();
            setTimeout(() => { // Simulate processing time
                const ws = XLSX.utils.json_to_sheet(data);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Sheet1");
                XLSX.writeFile(wb, `${filename}.xlsx`);
                hideLoading();
                showMessage(`فایل Excel "${filename}.xlsx" با موفقیت ایجاد شد.`, 'success');
                logAdminAction(currentUser.username, 'خروجی اکسل', `فایل: ${filename}.xlsx`);
            }, 500); // Simulate network delay
        }

        // Export to PDF
        function exportToPDF(elementId, filename) {
            const element = document.getElementById(elementId);
            if (!element) {
                showMessage(`عنصر با شناسه "${elementId}" یافت نشد.`, 'error');
                return;
            }

            showMessage('در حال آماده‌سازی فایل PDF...', 'info');
            showLoading();

            html2canvas(element, { scale: 2 }).then(canvas => { // افزایش مقیاس برای کیفیت بهتر PDF
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf; // Get jsPDF from global scope

                const pdf = new jsPDF('p', 'mm', 'a4'); // 'p' for portrait, 'mm' for millimeters, 'a4' for A4 size
                const imgWidth = 210; // A4 width in mm
                const pageHeight = 297; // A4 height in mm
                let heightLeft = imgHeight;
                let position = 0;

                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;

                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }
                pdf.save(`${filename}.pdf`);
                hideLoading();
                showMessage(`فایل PDF "${filename}.pdf" با موفقیت ایجاد شد.`, 'success');
                logAdminAction(currentUser.username, 'خروجی PDF', `فایل: ${filename}.pdf`);
            }).catch(error => {
                console.error('Error generating PDF:', error);
                hideLoading();
                showMessage('خطا در تولید فایل PDF.', 'error');
            });
        }

        // Print Report
        function printReport(elementId) {
            const element = document.getElementById(elementId);
            if (!element) {
                showMessage(`عنصر با شناسه "${elementId}" یافت نشد.`, 'error');
                return;
            }
            showLoading();
            setTimeout(() => { // Simulate processing time
                const printWindow = window.open('', '_blank');
                printWindow.document.write('<html><head><title>چاپ گزارش</title>');
                printWindow.document.write('<link rel="stylesheet" href="https://cdn.tailwindcss.com"></link>');
                printWindow.document.write('<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">');
                printWindow.document.write('<style>body { font-family: \'Vazirmatn\', sans-serif; direction: rtl; text-align: right; }</style>');
                printWindow.document.write('</head><body>');
                printWindow.document.write(element.outerHTML);
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.print();
                hideLoading();
                logAdminAction(currentUser.username, 'چاپ گزارش', `بخش: ${elementId}`);
            }, 500);
        }

        // Preview Report
        const reportPreviewModal = document.getElementById('report-preview-modal');
        const previewContentArea = document.getElementById('preview-content-area');
        const closePreviewModalBtn = document.getElementById('close-preview-modal');
        const previewModalTitle = document.getElementById('preview-modal-title');

        function showReportPreview(title, contentHtml) {
            previewModalTitle.textContent = `پیش‌نمایش: ${title}`;
            previewContentArea.innerHTML = contentHtml;
            reportPreviewModal.classList.add('show');
            logAdminAction(currentUser.username, 'پیش‌نمایش گزارش', `گزارش: ${title}`);
        }

        closePreviewModalBtn.addEventListener('click', () => {
            reportPreviewModal.classList.remove('show');
            previewContentArea.innerHTML = ''; // Clear content
        });
        reportPreviewModal.addEventListener('click', (e) => {
            if (e.target === reportPreviewModal) { // Close if clicked on overlay
                reportPreviewModal.classList.remove('show');
                previewContentArea.innerHTML = '';
            }
        });


        // ---------- مدیریت دکمه‌های خروجی در بخش گزارش‌گیری ----------
        document.querySelectorAll('.export-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const type = e.currentTarget.dataset.type;
                const section = e.currentTarget.dataset.section; // Could be 'products', 'orders', 'all_data', or a specific div ID

                if (type === 'excel') {
                    if (section === 'all_data') {
                        showLoading();
                        setTimeout(() => {
                            // For a real app, you'd fetch combined data from backend
                            const allReportsData = {
                                products: products.map(p => ({نام: p.name, دسته‌بندی: p.category, قیمت: p.price, موجودی: p.stock})),
                                orders: orders.map(o => ({'شناسه سفارش': o.id, کاربر: o.user, تاریخ: o.date, زمان: o.time, مبلغ: o.total, وضعیت: o.status})),
                                users: users.map(u => ({نام_کاربری: u.username, ایمیل: u.email, تلفن: u.phone, نقش: u.role}))
                            };
                            // Create multiple sheets
                            const wb = XLSX.utils.book_new();
                            for (const key in allReportsData) {
                                const ws = XLSX.utils.json_to_sheet(allReportsData[key]);
                                XLSX.utils.book_append_sheet(wb, ws, key);
                            }
                            XLSX.writeFile(wb, `گزارش_جامع_چای_ابراهیم.xlsx`);
                            hideLoading();
                            showMessage('فایل Excel جامع با موفقیت ایجاد شد.', 'success');
                            logAdminAction(currentUser.username, 'خروجی اکسل جامع', `فایل: گزارش_جامع_چای_ابراهیم.xlsx`);
                        }, 500);

                    } else if (section === 'products') {
                        // Example: Export products data from mock data
                        const dataToExport = products.map(p => ({
                            'نام محصول': p.name,
                            'دسته‌بندی': p.category,
                            'قیمت (تومان)': p.price,
                            'موجودی': p.stock
                        }));
                        exportToExcel(dataToExport, 'گزارش_محصولات');
                    }
                    // You can add more `else if` for other data types (orders, users etc.)
                } else if (type === 'pdf') {
                    const targetElementId = e.currentTarget.dataset.target; // e.g., 'reports-section'
                    exportToPDF(targetElementId, 'گزارش_پنل_مدیریت');
                } else if (type === 'print') {
                    const targetElementId = e.currentTarget.dataset.target;
                    printReport(targetElementId);
                }
            });
        });


        // ---------- گزارش‌های آماده پرکاربرد ----------
        const predefinedReports = [
            { id: 1, name: 'گزارش فروش ماهانه', icon: 'fa-chart-line', type: 'sales_monthly' },
            { id: 2, name: 'گزارش موجودی انبار', icon: 'fa-boxes', type: 'stock_summary' },
            { id: 3, name: 'گزارش مشتریان VIP', icon: 'fa-crown', type: 'vip_customers' },
            { id: 4, name: 'گزارش محصولات پرفروش', icon: 'fa-star', type: 'bestsellers' }
        ];

        function renderPredefinedReports() {
            const container = document.getElementById('predefined-reports');
            if (!container) return; // Ensure container exists
            container.innerHTML = ''; // Clear previous content
            predefinedReports.forEach(report => {
                const reportCard = document.createElement('div');
                reportCard.className = 'report-card bg-white p-4 rounded-lg shadow cursor-pointer text-center';
                reportCard.setAttribute('role', 'button');
                reportCard.setAttribute('aria-label', `مشاهده گزارش ${report.name}`);
                reportCard.innerHTML = `
                    <i class="fas ${report.icon} text-3xl text-green-800 mb-2 block" aria-hidden="true"></i>
                    <h4 class="font-bold text-brown-900 text-lg">${report.name}</h4>
                `;
                reportCard.addEventListener('click', () => {
                    // Simulate loading report content for preview
                    let content = '';
                    let title = report.name;
                    if (report.type === 'sales_monthly') {
                        content = `
                            <h4 class="font-bold text-brown-900 mb-4">نمودار فروش ماهانه</h4>
                            <canvas id="previewSalesChart" class="w-full h-64" role="img" aria-label="نمودار پیش‌نمایش فروش ماهانه"></canvas>
                            <p class="mt-4">خلاصه‌ای از فروش ماهانه: در این ماه ${orders.length} سفارش به مبلغ کل ${orders.reduce((sum, o) => sum + o.total, 0).toLocaleString('fa-IR')} تومان ثبت شده است.</p>
                        `;
                        showReportPreview(title, content);
                        setTimeout(() => { // Ensure canvas is rendered before creating chart
                            const previewSalesCtx = document.getElementById('previewSalesChart');
                            // Check if Chart is defined before creating a new Chart instance
                            if (typeof Chart !== 'undefined') {
                                new Chart(previewSalesCtx, salesChartInstance.config); // Reuse main chart config
                            } else {
                                console.error("Chart.js is not loaded for preview chart.");
                            }
                        }, 50);
                    } else if (report.type === 'stock_summary') {
                        content = `<h4 class="font-bold text-brown-900 mb-4">گزارش موجودی انبار</h4>
                            <table class="data-table" role="table" aria-label="جدول موجودی انبار">
                                <thead><tr><th scope="col">محصول</th><th scope="col">موجودی</th></tr></thead>
                                <tbody>
                                    ${products.map(p => `<tr><td>${p.name}</td><td>${p.stock}</td></tr>`).join('')}
                                </tbody>
                            </table>
                            <p class="mt-4">این گزارش خلاصه‌ای از موجودی فعلی محصولات در انبار را نشان می‌دهد.</p>
                        `;
                        showReportPreview(title, content);
                    } else if (report.type === 'vip_customers') {
                        content = `<h4 class="font-bold text-brown-900 mb-4">گزارش مشتریان VIP (شبیه‌سازی)</h4>
                            <ul class="list-disc pr-6 space-y-2 text-gray-700" role="list">
                                <li role="listitem"><strong>رضا محمدی:</strong> 5 سفارش، 1.2 میلیون تومان خرید</li>
                                <li role="listitem"><strong>مریم علوی:</strong> 4 سفارش، 900 هزار تومان خرید</li>
                            </ul>
                            <p class="mt-4">مشتریان VIP بر اساس تعداد و مبلغ خرید شناسایی شده‌اند.</p>
                        `;
                        showReportPreview(title, content);
                    } else if (report.type === 'bestsellers') {
                         content = `<h4 class="font-bold text-brown-900 mb-4">گزارش محصولات پرفروش</h4>
                            <ul class="list-disc pr-6 space-y-2 text-gray-700" role="list">
                                <li role="listitem">چای سیاه ممتاز (۱۲۰ عدد)</li>
                                <li role="listitem">دمنوش به لیمو (۹۵ عدد)</li>
                                <li role="listitem">چای سبز چینی (۷۰ عدد)</li>
                                <li role="listitem">چای ارل گری (۶۵ عدد)</li>
                            </ul>
                            <p class="mt-4">این گزارش، پرفروش‌ترین محصولات در دوره زمانی جاری را نشان می‌دهد.</p>
                        `;
                        showReportPreview(title, content);
                    } else {
                        content = `<p>محتوای گزارش ${report.name} (شبیه‌سازی شده)</p>`;
                        showReportPreview(title, content);
                    }
                    showMessage(`گزارش "${report.name}" بارگذاری شد.`, 'info');
                });
                container.appendChild(reportCard);
            });
        }


        // ---------- توابع لاگ و امنیت (شبیه‌سازی شده) ----------

        // شبیه‌سازی کاربر فعلی (در یک سیستم واقعی از احراز هویت لاراول می‌آید)
        // برای تست می‌توانید نقش‌ها را تغییر دهید: 'مدیر کل', 'کارمند فروشگاه', 'کاربر'
        let currentUser = { id: 3, username: 'admin', role: 'مدیر کل', lastLocation: '192.168.1.1' };
        // let currentUser = { id: 4, username: 'staff', role: 'کارمند فروشگاه', lastLocation: '192.168.1.5' };
        // let currentUser = { id: 1, username: 'ali.ahmadi', role: 'کاربر', lastLocation: '172.20.10.2' };


        /**
         * تابع پاکسازی ورودی (برای جلوگیری از XSS در سمت کلاینت)
         * در یک سیستم واقعی، پاکسازی در سمت سرور نیز ضروری است.
         * @param {string} input - ورودی برای پاکسازی
         * @returns {string} - ورودی پاکسازی شده
         */
        function sanitizeInput(input) {
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(input));
            return div.innerHTML;
        }

        /**
         * شبیه‌سازی لاگ‌گیری فعالیت‌های ادمین
         * در یک سیستم واقعی، این لاگ‌ها به پایگاه داده در سمت سرور ارسال می‌شوند.
         * @param {string} userId - نام کاربری یا ID ادمین/کارمند
         * @param {string} action - نوع اقدام (مثلاً "افزودن محصول", "حذف کاربر")
         * @param {string} details - جزئیات بیشتر در مورد اقدام
         */
        function logAdminAction(user, action, details) {
            const now = new Date();
            const timeString = `${now.getFullYear()}/${(now.getMonth() + 1).toString().padStart(2, '0')}/${now.getDate().toString().padStart(2, '0')} - ${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;
            const ipAddress = '192.168.1.' + Math.floor(Math.random() * 255); // شبیه‌سازی IP
            
            auditLogs.unshift({ // اضافه کردن به ابتدای آرایه
                id: auditLogs.length > 0 ? Math.max(...auditLogs.map(l => l.id)) + 1 : 1,
                time: timeString,
                user: user,
                action: action,
                details: details,
                ip: ipAddress
            });

            // برای نمایش، لاگ‌ها را به 20 مورد آخر محدود می‌کنیم
            // No need to slice here, pagination will handle it.
            // if (auditLogs.length > 20) {
            //     auditLogs = auditLogs.slice(0, 20);
            // }
            console.log(`لاگ امنیتی: ${timeString} - کاربر: ${user}, اقدام: ${action}, جزئیات: ${details}, IP: ${ipAddress}`);
            
            // تنها در صورتی که عنصر موجود باشد، classList را دستکاری کن
            const auditLogsSection = document.getElementById('audit-logs-section');
            if (auditLogsSection && !auditLogsSection.classList.contains('hidden')) {
                renderAuditLogsTable();
            }
        }

        // رندر جدول لاگ امنیتی
        const auditLogsTableBody = document.getElementById('audit-logs-body');
        const auditLogsPerPageSelect = document.getElementById('audit-logs-per-page');

        function renderAuditLogsTable(page = currentAuditLogPage) {
            auditLogsTableBody.innerHTML = '';
            
            const startIndex = (page - 1) * auditLogsPerPage;
            const endIndex = startIndex + auditLogsPerPage;
            const itemsToRender = auditLogs.slice(startIndex, endIndex);

            itemsToRender.forEach(log => {
                const row = auditLogsTableBody.insertRow();
                row.innerHTML = `
                    <td class="py-2">${log.time}</td>
                    <td class="py-2">${log.user}</td>
                    <td class="py-2">${log.action}</td>
                    <td class="py-2">${log.details}</td>
                    <td class="py-2">${log.ip}</td>
                `;
            });
            renderPaginationControls('audit-logs-pagination', auditLogs.length, auditLogsPerPage, page, renderAuditLogsTable);
        }

        if (auditLogsPerPageSelect) {
            auditLogsPerPageSelect.addEventListener('change', (e) => {
                auditLogsPerPage = parseInt(e.target.value);
                currentAuditLogPage = 1;
                renderAuditLogsTable();
            });
        }


        /**
         * تابع برای دریافت CSRF Token از تگ متا.
         * در یک برنامه واقعی، این توکن توسط بک‌اند تولید و در تگ متا قرار می‌گیرد.
         * @returns {string|null} CSRF token or null if not found.
         */
        function getCSRFToken() {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            return metaTag ? metaTag.content : null;
        }

        /**
         * شبیه‌سازی احراز هویت دو مرحله‌ای (MFA)
         * در یک سیستم واقعی، این تابع با یک سرویس بک‌اند برای ارسال و تایید کد ارتباط برقرار می‌کند.
         * @param {number} userId - ID کاربر
         */
        function enableMFA(userId) {
            // این یک شبیه‌سازی سمت کلاینت است.
            // در واقعیت:
            // 1. یک درخواست به بک‌اند ارسال می‌شود.
            // 2. بک‌اند یک کد تأیید (مثلاً 6 رقمی) تولید می‌کند.
            // 3. کد به ایمیل یا شماره تلفن کاربر ارسال می‌شود.
            // 4. کد در پایگاه داده با یک زمان انقضا ذخیره می‌شود.
            // 5. کاربر کد را وارد می‌کند و بک‌اند آن را تأیید می‌کند.
            const verificationCode = Math.floor(100000 + Math.random() * 900000);
            showMessage(`کد تأیید MFA به شماره/ایمیل شما ارسال شد: ${verificationCode} (شبیه‌سازی)`, 'info');
            // در اینجا باید UI برای ورود کد را نمایش داد
            logAdminAction(currentUser.username, 'فعال‌سازی MFA', `شروع فرآیند MFA برای کاربر ${userId}`);
        }

        /**
         * شبیه‌سازی کنترل دسترسی مبتنی بر نقش (RBAC)
         * در یک سیستم واقعی، این تابع در سمت سرور (Laravel Middleware) هر درخواست را اعتبارسنجی می‌کند.
         * @param {string} userRole - نقش کاربر فعلی (مثلاً 'مدیر کل', 'کارمند فروشگاه', 'کاربر')
         * @param {string} action - عملیاتی که کاربر می‌خواهد انجام دهد (مثلاً 'manage_products', 'view_orders')
         * @returns {boolean} - true اگر کاربر اجازه انجام عمل را دارد، false در غیر این صورت.
         */
        const rolesPermissions = {
            'مدیر کل': ['*'], // دسترسی کامل
            'کارمند فروشگاه': ['view_orders', 'view_users', 'manage_products', 'manage_categories'], // دسترسی محدود
            'کاربر': ['view_orders'], // فقط مشاهده سفارشات خود (این نقش معمولا به پنل ادمین دسترسی ندارد)
            'غیرفعال شده': []
        };

        function checkPermission(userRole, action) {
            // اگر نقش در لیست تعریف شده نیست، هیچ دسترسی ندارد
            if (!rolesPermissions[userRole]) {
                return false;
            }
            // اگر نقش دارای دسترسی کامل است
            if (rolesPermissions[userRole].includes('*')) {
                return true;
            }
            // بررسی وجود دسترسی خاص
            return rolesPermissions[userRole].includes(action);
        }

        /**
         * شبیه‌سازی بررسی فعالیت‌های مشکوک
         * در یک سیستم واقعی، این تابع به طور مداوم یا در زمان ورود/فعالیت‌های حساس اجرا می‌شود.
         * @param {object} user - شیء کاربر شامل اطلاعاتی مانند lastLocation, currentIP
         */
        function checkSuspiciousActivity(user) {
            // شبیه‌سازی تشخیص مکان جدید
            const currentIP = '192.168.1.' + Math.floor(Math.random() * 255); // هر بار یک IP جدید
            if (user.lastLocation && user.lastLocation !== currentIP) {
                showMessage(`هشدار امنیتی: ورود از مکان جدید (${currentIP}) برای کاربر ${user.username} شناسایی شد!`, 'error');
                logAdminAction('سیستم امنیت', 'هشدار امنیتی', `ورود مشکوک برای ${user.username} از IP: ${currentIP} (قبلی: ${user.lastLocation})`);
                user.lastLocation = currentIP; // به‌روزرسانی برای جلوگیری از هشدارهای مکرر
            }
        }


        // ---------- رویدادهای اولیه و توگل سایدبار ----------
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');

        sidebarToggle.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('active'); // فقط در موبایل فعال می‌شود
            } else {
                sidebar.classList.toggle('collapsed');
            }
        });

        // مدیریت حالت سایدبار هنگام تغییر اندازه صفحه
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active'); // اطمینان از باز بودن در دسکتاپ
                sidebar.classList.remove('collapsed'); // برداشتن حالت جمع شده
            } else {
                sidebar.classList.remove('collapsed'); // در موبایل حالت جمع شده معنا ندارد
            }
        });

        // خروج از سیستم (شبیه‌سازی)
        const logoutBtn = document.getElementById('logout-btn');
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm('آیا مطمئن هستید که می‌خواهید خارج شوید؟')) {
                // شبیه‌سازی خروج با حذف اطلاعات کاربری (در محیط واقعی نیاز به ارتباط با بک‌اند)
                sessionStorage.removeItem('adminLoggedIn'); // یا هر متغیر دیگری که برای ورود ادمین استفاده می‌کنید
                showMessage('با موفقیت خارج شدید.', 'success');
                logAdminAction(currentUser.username, 'خروج از پنل', 'خروج موفق از سیستم');
                // هدایت به صفحه ورود یا صفحه اصلی سایت
                window.location.href = '/'; // Redirect to home page
            }
        });

        // Floating Report Actions Menu Toggle
        const floatingReportToggle = document.getElementById('floating-report-toggle');
        const reportActionsContainer = document.querySelector('.report-actions'); // The parent div with the menu

        floatingReportToggle.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent document click from immediately closing it
            reportActionsContainer.classList.toggle('active');
            // Update aria-expanded attribute
            const isExpanded = reportActionsContainer.classList.contains('active');
            floatingReportToggle.setAttribute('aria-expanded', isExpanded);
        });

        // Close floating menu if clicked outside
        document.addEventListener('click', (e) => {
            if (!reportActionsContainer.contains(e.target) && reportActionsContainer.classList.contains('active')) {
                reportActionsContainer.classList.remove('active');
                floatingReportToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Handle export options from floating menu
        document.querySelectorAll('.report-menu .export-option').forEach(button => {
            button.addEventListener('click', (e) => {
                const type = e.currentTarget.dataset.type;
                const section = e.currentTarget.dataset.section;
                const target = e.currentTarget.dataset.target;

                // در یک سیستم واقعی، اینجا درخواست به سرور ارسال می‌شود
                if (type === 'excel') {
                    // Example: Export products data from mock data
                    const dataToExport = products.map(p => ({
                        'نام محصول': p.name,
                        'دسته‌بندی': p.category,
                        'قیمت (تومان)': p.price,
                        'موجودی': p.stock
                    }));
                    exportToExcel(dataToExport, 'گزارش_محصولات_سریع');
                } else if (type === 'pdf') {
                    exportToPDF(target, 'گزارش_سریع_PDF');
                } else if (type === 'print') {
                    printReport(target);
                }
                reportActionsContainer.classList.remove('active'); // Close menu after action
                floatingReportToggle.setAttribute('aria-expanded', 'false');
            });
        });


        // مقداردهی اولیه هنگام بارگذاری صفحه
        window.onload = () => { // Changed from DOMContentLoaded to window.onload
            showSection('dashboard'); // نمایش داشبورد در ابتدا
            renderCharts(); // رندر اولیه نمودارها

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
    </script>
</body>
</html>
