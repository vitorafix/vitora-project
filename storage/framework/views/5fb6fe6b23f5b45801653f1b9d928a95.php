<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', config('app.name', 'چای ابراهیم - عطر و طعم اصیل ایرانی')); ?></title>

    
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    
    <script>
        if (typeof window !== 'undefined' && window.localStorage) {
            let guestUUIDFromLocalStorage = localStorage.getItem('guest_uuid');
            let guestUUIDFromBackend = '<?php echo e($guestUuidFromBackend ?? 'null'); ?>'; // مقدار ارسال شده از کنترلر

            // اگر بک‌اند یک UUID معتبر ارسال کرده باشد، آن را به عنوان اولویت قرار دهید
            if (guestUUIDFromBackend !== 'null' && guestUUIDFromBackend) {
                window.guestUUID = guestUUIDFromBackend;
                // اگر UUID بک‌اند با UUID در localStorage متفاوت است، localStorage را به‌روز کنید
                if (guestUUIDFromLocalStorage !== guestUUIDFromBackend) {
                    localStorage.setItem('guest_uuid', guestUUIDFromBackend);
                    console.log('Blade/Initial Load Guest UUID: Updated localStorage with backend UUID:', window.guestUUID);
                } else {
                    console.log('Blade/Initial Load Guest UUID: Using existing localStorage UUID (matches backend):', window.guestUUID);
                }
            } else if (guestUUIDFromLocalStorage) {
                // اگر بک‌اند UUID ارسال نکرده اما در localStorage موجود است، از آن استفاده کنید
                window.guestUUID = guestUUIDFromLocalStorage;
                console.log('Blade/Initial Load Guest UUID: Using existing localStorage UUID (no backend UUID):', window.guestUUID);
            } else {
                // اگر هیچ کدام موجود نبود، یک UUID جدید تولید کنید
                const newGuestUUID = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                    var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
                    return v.toString(16);
                });
                localStorage.setItem('guest_uuid', newGuestUUID);
                window.guestUUID = newGuestUUID;
                console.log('Blade/Initial Load Guest UUID: Generated new UUID and stored in localStorage:', window.guestUUID);
            }
        } else {
            console.warn('localStorage is not available. Guest UUID cannot be persisted.');
            window.guestUUID = null; // Fallback if localStorage is not available
        }
    </script>

    
    
    
    <?php echo app('Illuminate\Foundation\Vite')([
        'resources/css/app.css',
        'resources/js/app.js', 
    ]); ?>

    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        /* این استایل برای جلوگیری از نمایش لحظه‌ای المنت‌های x-cloak قبل از بارگذاری Alpine.js ضروری است */
        [x-cloak] { display: none !important; }

        /* CSS برای مدال سفارشی (اینجا نگه داشته می‌شود چون ممکن است در بخش‌های عمومی هم استفاده شود) */
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

        /* Custom styles for sidebar transitions - MOVED TO APP.CSS OR ADMIN.BLADE.PHP'S STYLE BLOCK */
        /* .sidebar { ... } */
        /* .sidebar-expanded { ... } */
        /* .sidebar-collapsed { ... } */
        /* .sidebar-collapsed .nav-text { ... } */
        /* #main-content-wrapper { ... } */
        /* .main-content-shifted { ... } */
        /* .main-content-full { ... } */
        .section-content {
            display: none;
        }
        .section-content.active {
            display: block;
        }
        /* Custom scrollbar for activity log - MOVED TO APP.CSS OR ADMIN.BLADE.PHP'S STYLE BLOCK */
        /* .custom-scrollbar::-webkit-scrollbar { ... } */
        /* .custom-scrollbar::-webkit-scrollbar-track { ... } */
        /* .custom-scrollbar::-webkit-scrollbar-thumb { ... } */
        /* .custom-scrollbar::-webkit-scrollbar-thumb:hover { ... } */
        /* Custom styles for the new monthly sales chart - MOVED TO APP.CSS OR ADMIN.BLADE.PHP'S STYLE BLOCK */
        /* .chart-container { ... } */
        /* #monthlySalesChart { ... } */

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

        /* Custom CSS to ensure header is at the top and content is below it */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh; /* Ensure body takes full viewport height */
        }
        /* Make sure the nav is fixed at the top */
        nav {
            position: fixed; /* Changed from sticky to fixed for stronger adherence */
            top: 0;
            width: 100%;
            z-index: 50; /* Ensure it's above other content */
        }
        /* Main content area needs padding to not be hidden by fixed nav */
        .main-content-wrapper {
            flex-grow: 1; /* Allows this wrapper to take all available vertical space */
            padding-top: var(--nav-height); /* Dynamic padding based on nav height */
            display: flex; /* Make it a flex container for its children */
            flex-direction: column; /* Stack children vertically */
        }
        main {
            flex-grow: 1; /* Allow main content to take up remaining space within its wrapper */
        }
        /* Define a CSS variable for navigation height to use in main content padding */
        :root {
            --nav-height: 0px; /* Default value, will be updated by JS */
        }
    </style>
</head>
<body>
    
    
    <?php echo $__env->make('layouts.navigation', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    
    <div class="main-content-wrapper">
        
        <main>
            <?php echo $__env->yieldContent('content'); ?>
            
            
            <div id="cart-page-container" class="container mx-auto px-4 py-8 hidden">
                <h2 class="text-3xl font-bold text-gray-800 mb-6">سبد خرید شما</h2>
                <div id="cart-items-container" class="space-y-6">
                    
                </div>
                <div id="cart-empty-message" class="text-center py-10 hidden">
                    <p class="text-gray-600 text-lg">سبد خرید شما خالی است.</p>
                    <a href="<?php echo e(route('products.index')); ?>" class="btn-primary mt-4">شروع خرید</a>
                </div>
                <div id="cart-summary" class="mt-8 pt-8 border-t-2 border-green-700 hidden">
                    <div class="flex justify-between items-center text-xl font-semibold text-gray-800 mb-4">
                        <span>مجموع فرعی:</span>
                        <span id="cart-subtotal-price">0 تومان</span>
                    </div>
                    <div class="flex justify-between items-center text-xl font-semibold text-gray-800 mb-4">
                        <span>تخفیف:</span>
                        <span id="cart-discount-price">0 تومان</span>
                    </div>
                    <div class="flex justify-between items-center text-xl font-semibold text-gray-800 mb-4">
                        <span>هزینه ارسال:</span>
                        <span id="cart-shipping-price">0 تومان</span>
                    </div>
                    <div class="flex justify-between items-center text-xl font-semibold text-gray-800 mb-4">
                        <span>مالیات:</span>
                        <span id="cart-tax-price">0 تومان</span>
                    </div>
                    <div class="flex justify-between items-center text-2xl font-bold text-green-700 mb-4">
                        <span>مجموع کل:</span>
                        <span id="cart-total-price">0 تومان</span>
                    </div>
                    
                    <div class="flex justify-end space-x-4 mt-6">
                        <button id="clear-cart-btn" class="btn-secondary">
                            پاک کردن سبد خرید
                        </button>
                        <a href="<?php echo e(route('checkout.index')); ?>" class="btn-primary">
                            تکمیل سفارش <i class="fas fa-arrow-left mr-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </main>

        
        
        <?php echo $__env->make('layouts.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>

    
    <?php echo $__env->yieldPushContent('scripts'); ?>

    
    
    <div id="confirm-modal-overlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50 custom-modal-overlay">
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-sm w-full text-center custom-modal-content">
            <h3 class="text-2xl font-bold text-gray-800 mb-4" id="modal-title"></h3>
            <p class="text-gray-600 mb-6" id="confirm-message"></p>
            <div class="flex justify-center space-x-4">
                <button id="confirm-yes" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-300">
                    تایید
                </button>
                <button id="confirm-no" class="px-6 py-3 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition duration-300">
                    لغو
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Calculate and set navbar height for CSS
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
                    indicator.classList.add('w-3', 'h-3', 'rounded-full', 'bg-gray-300', 'bg-opacity-50', 'cursor-pointer', 'mx-1', 'transition-all', 'duration-300');
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