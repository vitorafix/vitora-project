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

    <style>
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
        }
        .custom-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .custom-modal-content {
            background-color: #fff;
            padding: 2.5rem;
            border-radius: 1.5rem; /* rounded-2xl */
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2); /* shadow-xl */
            max-width: 480px; /* max-w-md */
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
    </style>
</head>
<body class="font-sans antialiased flex flex-col min-h-screen bg-gray-100">

    
    <?php echo $__env->make('layouts.navigation', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?> 

    
    <?php if(isset($header)): ?>
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <?php echo e($header); ?>

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

    
    <?php echo $__env->make('partials.auth-modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?> 

    
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

    
    <?php echo $__env->make('partials.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?> 

    
    <?php echo $__env->yieldPushContent('scripts'); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // محاسبه و تنظیم ارتفاع navbar برای استفاده در CSS (اگر لازم باشد)
            const navBar = document.querySelector('nav');
            if (navBar) {
                const navHeight = navBar.offsetHeight;
                document.documentElement.style.setProperty('--nav-height', `${navHeight}px`);
            }

            // منطق اسلایدشو (Hero Carousel) - این کد از فایل اصلی شما حفظ شده است.
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
                stopSlideShow(); // اطمینان از عدم وجود چندین اینتروال
                slideInterval = setInterval(nextSlide, 5000); // هر 5 ثانیه اسلاید بعدی
            }

            function stopSlideShow() {
                    clearInterval(slideInterval);
            }

            function createIndicators() {
                // بررسی وجود indicatorsContainer قبل از دستکاری آن
                if (!indicatorsContainer) {
                    console.warn("Indicators container not found. Skipping indicator creation.");
                    return;
                }
                indicatorsContainer.innerHTML = ''; // پاک کردن نشانگرهای قبلی
                slides.forEach((_, i) => {
                    const indicator = document.createElement('div');
                    indicator.classList.add('w-3', 'h-3', 'rounded-full', 'bg-gray-300', 'bg-opacity-50', 'cursor-pointer', 'transition-all', 'duration-300');
                    indicator.addEventListener('click', () => {
                        stopSlideShow();
                        showSlide(i);
                        // بروزرسانی اسلاید فعلی پس از کلیک روی نشانگر
                        currentSlide = i;
                        startSlideShow();
                    });
                    indicatorsContainer.appendChild(indicator);
                });
                updateIndicators(currentSlide);
            }

            function updateIndicators(activeIndex) {
                if (!indicatorsContainer) { // اضافه کردن چک null
                    return;
                }
                const indicators = indicatorsContainer.querySelectorAll('div');
                indicators.forEach((indicator, i) => {
                    indicator.classList.remove('bg-gray-500', 'bg-opacity-100'); // حذف کلاس‌های قبلی
                    indicator.classList.add('bg-gray-300', 'bg-opacity-50');
                    if (i === activeIndex) {
                        indicator.classList.remove('bg-gray-300', 'bg-opacity-50'); // حذف کلاس‌های قبلی
                        indicator.classList.add('bg-gray-500', 'bg-opacity-100'); // افزودن کلاس‌های فعال
                    }
                });
            }

            // مقداردهی اولیه اسلایدشو
            // اضافه کردن چک slides.length > 0 برای جلوگیری از خطا اگر اسلایدی نیست
            if (slides.length > 0) {
                createIndicators(); // ایجاد نشانگرها
                showSlide(currentSlide); // نمایش اولین اسلاید
                startSlideShow(); // شروع اسلایدشو خودکار

                // افزودن Event Listener برای دکمه‌های ناوبری
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

                // اختیاری: مکث اسلایدشو هنگام قرار گرفتن ماوس روی آن
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