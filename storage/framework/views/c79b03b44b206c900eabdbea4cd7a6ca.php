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

    
    <?php echo app('Illuminate\Foundation\Vite')->reactRefresh(); ?>

    
    <?php echo app('Illuminate\Foundation\Vite')([
        'resources/css/app.css',
        'resources/js/core/app.js',
        'resources/js/app.tsx', 
         
    ]); ?>

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
        /* Removed .mini-cart-dropdown and .mini-cart-dropdown-content styles as they will be managed by React Portal */
        /* and the MiniCart component itself will have its own positioning */

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
    
    
    <?php echo $__env->make('layouts.navigation', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <div class="main-content-wrapper">
        
        <main>
            
            <div id="react-root"></div>

            <?php echo $__env->yieldContent('content'); ?>
            
            
            <div id="cart-page-container" class="container mx-auto px-4 py-8 md:py-16 hidden">
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

        
        <?php echo $__env->make('layouts.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
        });
    </script>

    
    
    

    
    
    

</body>
</html>
<?php /**PATH /var/www/resources/views/layouts/app.blade.php ENDPATH**/ ?>