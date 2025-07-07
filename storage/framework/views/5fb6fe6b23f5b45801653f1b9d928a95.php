<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', config('app.name', 'چای ابراهیم - عطر و طعم اصیل ایرانی')); ?></title>

    
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

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
            background-color: rgba(0, 0, 0, 0.6); /* لایه تیره */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000; /* مطمئن شوید روی همه چیز باشد */
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            backdrop-filter: blur(5px); /* Add blur effect */
        }
        .custom-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .custom-modal-content {
            background-color: #fff;
            padding: 3rem; /* Increased padding */
            border-radius: 1rem; /* More rounded corners */
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2); /* shadow-xl */
            max-width: 500px; /* Max width for better readability */
            width: 90%;
            text-align: center;
            transform: translateY(-20px); /* برای انیمیشن ورودی */
            transition: transform 0.3s ease;
            position: relative;
        }
        .custom-modal-overlay.active .custom-modal-content {
            transform: translateY(0);
        }
        .custom-modal-close-btn {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6B7280; /* gray-400 */
            transition: color 0.2s ease;
        }
        .custom-modal-close-btn:hover {
            color: #EF4444; /* red-500 */
        }

        /* استایل‌های جدید برای مینی سبد خرید هاور */
        .mini-cart-dropdown {
            position: relative; /* برای قرار دادن دراپ‌داون به صورت مطلق */
            display: inline-block; /* برای اینکه عرض آن به اندازه محتوایش باشد */
        }

        .mini-cart-dropdown-content {
            position: absolute;
            right: 0; /* در سمت راست عنصر والد قرار می‌گیرد */
            top: 100%; /* زیر عنصر والد قرار می‌گیرد */
            margin-top: 0.5rem; /* فاصله از عنصر والد */
            width: 18rem; /* عرض کادر را کوچکتر کردیم (قبلا 20rem بود) */
            background-color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); /* سایه ظریف */
            border-radius: 0.5rem; /* گوشه‌های گرد */
            z-index: 50; /* مطمئن شوید روی بقیه عناصر قرار می‌گیرد */
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px); /* برای انیمیشن ورودی */
            transition: opacity 0.2s ease-out, transform 0.2s ease-out, visibility 0.2s ease-out;
            border: 1px solid #e5e7eb; /* border-gray-200 */
            overflow: hidden; /* برای اطمینان از اینکه محتوا از گوشه‌های گرد بیرون نزند */
        }

        .mini-cart-dropdown-content.active {
            opacity: 1; /* هنگام فعال شدن کامل مرئی شود */
            visibility: visible; /* هنگام فعال شدن قابل مشاهده شود */
            transform: translateY(0); /* به موقعیت اصلی برگردد */
        }

        /* استایل‌های بهبود یافته برای آیتم‌های داخل مینی سبد خرید */
        .mini-cart-dropdown-content .py-2 {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        .mini-cart-dropdown-content .text-sm {
            font-size: 0.875rem; /* 14px */
        }

        .mini-cart-dropdown-content .text-xs {
            font-size: 0.75rem; /* 12px */
        }

        /* استایل برای دکمه‌های "ادامه جهت تکمیل سفارش" و "مشاهده سبد خرید" */
        .mini-cart-dropdown-content .btn-primary,
        .mini-cart-dropdown-content .btn-secondary {
            padding-top: 0.6rem; /* کاهش پدینگ عمودی */
            padding-bottom: 0.6rem; /* کاهش پدینگ عمودی */
            font-size: 0.9rem; /* کمی کوچکتر کردن فونت دکمه‌ها */
            margin-top: 0.75rem; /* فاصله بین دکمه‌ها و از محتوای بالا */
        }

        /* Custom styles for sidebar transitions */
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
        #main-content-wrapper { /* New wrapper for main content to handle sidebar shift */
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
        /* Custom styles for the new monthly sales chart */
        .chart-container {
            position: relative;
            height: 300px; /* Adjust height as needed */
            width: 100%;
        }
        #monthlySalesChart {
            direction: ltr; /* Ensure Chart.js renders correctly for data interpretation */
        }

        /* Custom Button Styles (from your app.css, ensuring consistency) */
        .btn-primary {
            @apply bg-green-700 text-white font-bold py-3 px-6 rounded-lg shadow-md hover:bg-green-800 transition duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-lg;
        }

        .btn-secondary {
            @apply bg-white text-green-700 border border-green-700 font-semibold py-3 px-6 rounded-lg shadow-sm hover:bg-green-50 transition duration-300 ease-in-out;
        }

        .btn-disabled {
            @apply bg-gray-300 text-gray-500 font-semibold py-3 px-6 rounded-lg cursor-not-allowed opacity-75;
        }

        /* Additional styles for card hover effects (from your app.css) */
        .card-hover-effect {
            @apply transform transition-transform duration-300 hover:scale-105 hover:shadow-xl;
        }

        /* Message Box Styles (from your app.js) */
        .message-box {
            opacity: 1;
            transform: translate(-50%, 0);
        }
        .message-box.opacity-0 {
            opacity: 0;
            transform: translate(-50%, 100%);
        }
    </style>
</head>
<body class="font-sans antialiased flex h-screen overflow-hidden">

    
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
        
        <?php if(isset($header)): ?>
            <header class="bg-white shadow rounded-lg p-4 mb-4 flex items-center justify-between">
                <h2 class="text-xl font-semibold text-brown-900">
                    <?php echo e($header); ?>

                </h2>
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
        <?php else: ?>
            
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
        <?php endif; ?>

        
        <main class="flex-grow">
            
            <?php echo $__env->yieldContent('hero_section'); ?>

            
            <?php if(isset($slot)): ?>
                <?php echo e($slot); ?> 
            <?php else: ?>
                <?php echo $__env->yieldContent('content'); ?> 
            <?php endif; ?>
        </main>

        
        <div id="confirmation-modal-overlay" class="custom-modal-overlay hidden">
            <div class="custom-modal-content">
                <button id="confirmation-modal-close-btn" class="custom-modal-close-btn">
                    <i class="fas fa-times"></i>
                </button>
                <i class="fas fa-question-circle text-amber-600 text-5xl mb-6"></i>
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
    </div>

    
    

    
    <?php echo $__env->yieldPushContent('scripts'); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Calculate and set navbar height for CSS (if needed)
            const navBar = document.querySelector('nav');
            if (navBar) {
                const navHeight = navBar.offsetHeight;
                document.documentElement.style.setProperty('--nav-height', `${navHeight}px`);
            }

            // Hero Carousel logic (preserved from your original app.blade.php)
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
                stopSlideShow(); // Ensure no multiple intervals
                slideInterval = setInterval(nextSlide, 5000); // Next slide every 5 seconds
            }

            function stopSlideShow() {
                    clearInterval(slideInterval);
            }

            function createIndicators() {
                // Check for indicatorsContainer before manipulating it
                if (!indicatorsContainer) {
                    console.warn("Indicators container not found. Skipping indicator creation.");
                    return;
                }
                indicatorsContainer.innerHTML = ''; // Clear previous indicators
                slides.forEach((_, i) => {
                    const indicator = document.createElement('div');
                    indicator.classList.add('w-3', 'h-3', 'rounded-full', 'bg-gray-300', 'bg-opacity-50', 'cursor-pointer', 'transition-all', 'duration-300');
                    indicator.addEventListener('click', () => {
                        stopSlideShow();
                        showSlide(i);
                        // Update current slide after clicking indicator
                        currentSlide = i;
                        startSlideShow();
                    });
                    indicatorsContainer.appendChild(indicator);
                });
                updateIndicators(currentSlide);
            }

            function updateIndicators(activeIndex) {
                if (!indicatorsContainer) { // Add null check
                    return;
                }
                const indicators = indicatorsContainer.querySelectorAll('div');
                indicators.forEach((indicator, i) => {
                    indicator.classList.remove('bg-gray-500', 'bg-opacity-100'); // Remove previous classes
                    indicator.classList.add('bg-gray-300', 'bg-opacity-50');
                    if (i === activeIndex) {
                        indicator.classList.remove('bg-gray-300', 'bg-opacity-50'); // Remove previous classes
                        indicator.classList.add('bg-gray-500', 'bg-opacity-100'); // Add active classes
                    }
                });
            }

            // Initialize slideshow
            // Add slides.length > 0 check to prevent errors if no slides
            if (slides.length > 0) {
                createIndicators(); // Create indicators
                showSlide(currentSlide); // Show first slide
                startSlideShow(); // Start automatic slideshow

                // Add Event Listeners for navigation buttons
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

                // Optional: Pause slideshow on mouse hover
                const heroCarousel = document.getElementById('hero-carousel');
                if (heroCarousel) {
                    heroCarousel.addEventListener('mouseenter', stopSlideShow);
                    heroCarousel.addEventListener('mouseleave', startSlideShow);
                }
            } else {
                console.warn("No hero slides found. Hero carousel will not be initialized.");
            }
        });
    </script>
    
</body>
</html>
<?php /**PATH C:\xampp\htdocs\myshop\resources\views/layouts/app.blade.php ENDPATH**/ ?>