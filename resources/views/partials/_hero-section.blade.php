{{-- resources/views/partials/_hero-section.blade.php --}}

{{-- بخش اسلایدشو اصلی (Hero Carousel) --}}
<section id="hero-carousel" class="relative overflow-hidden flex flex-col items-center justify-center text-center text-white p-8" style="height: calc(100vh - var(--nav-height, 0px));">
    {{-- اسلاید 1 --}}
    {{-- opacity-100 برای اسلاید فعلی و opacity-0 برای اسلایدهای مخفی --}}
    <div class="hero-slide absolute inset-0 w-full h-full bg-cover bg-center transition-opacity duration-1000 ease-in-out opacity-100" style="background-image: url('{{ asset('uploads/hero-banner.jpg') }}');">
        <div class="absolute inset-0 bg-brown-900 opacity-60"></div> {{-- پوشش تیره --}}
        <div class="relative z-10 w-full px-4 sm:px-6 lg:px-8 flex flex-col items-center justify-center h-full">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold mb-4 leading-tight animate-fade-in-up">
                عطر و طعم اصیل <br> چای ایرانی
            </h1>
            <p class="text-lg md:text-xl mb-8 max-w-2xl animate-fade-in-up delay-200">
                تجربه‌ای بی‌نظیر از بهترین چای‌های دستچین شده، <br class="hidden md:block"> مستقیم از باغات سرسبز شمال.
            </p>
            <a href="{{ route('products.index') }}" class="btn-primary-hero animate-fade-in-up delay-400">
                مشاهده محصولات <i class="fas fa-arrow-left mr-2"></i>
            </a>
        </div>
    </div>

    {{-- اسلاید 2 --}}
    <div class="hero-slide absolute inset-0 w-full h-full bg-cover bg-center transition-opacity duration-1000 ease-in-out opacity-0" style="background-image: url('{{ asset('uploads/hero-banner-2.jpg') }}');">
        <div class="absolute inset-0 bg-green-900 opacity-60"></div>
        <div class="relative z-10 w-full px-4 sm:px-6 lg:px-8 flex flex-col items-center justify-center h-full">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold mb-4 leading-tight animate-fade-in-up">
                کیفیت بی‌نظیر، <br> سنت پایدار
            </h1>
            <p class="text-lg md:text-xl mb-8 max-w-2xl animate-fade-in-up delay-200">
                با هر جرعه، داستان یک تاریخ غنی و یک تجربه دلنشین را بنوشید.
            </p>
            <a href="{{ route('about') }}" class="btn-primary-hero animate-fade-in-up delay-400">
                درباره ما <i class="fas fa-info-circle mr-2"></i>
            </a>
        </div>
    </div>

    {{-- اسلاید 3 (مثال) --}}
    <div class="hero-slide absolute inset-0 w-full h-full bg-cover bg-center transition-opacity duration-1000 ease-in-out opacity-0" style="background-image: url('{{ asset('uploads/hero-banner-3.jpg') }}');">
        <div class="absolute inset-0 bg-brown-800 opacity-60"></div>
        <div class="relative z-10 w-full px-4 sm:px-6 lg:px-8 flex flex-col items-center justify-center h-full">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold mb-4 leading-tight animate-fade-in-up">
                با چای ابراهیم، <br> هر لحظه آرامش است
            </h1>
            <p class="text-lg md:text-xl mb-8 max-w-2xl animate-fade-in-up delay-200">
                انتخابی ایده‌آل برای شروع روزی پرانرژی یا پایانی دلنشین بر یک شب آرام.
            </p>
            <a href="{{ route('contact') }}" class="btn-primary-hero animate-fade-in-up delay-400">
                تماس با ما <i class="fas fa-phone mr-2"></i>
            </a>
        </div>
    </div>

    {{-- دکمه‌های ناوبری --}}
    <button class="absolute top-1/2 right-4 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-3 rounded-full z-20 hover:bg-opacity-75 transition-all duration-300 focus:outline-none" id="hero-prev-btn">
        <i class="fas fa-chevron-right text-2xl"></i>
    </button>
    <button class="absolute top-1/2 left-4 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-3 rounded-full z-20 hover:bg-opacity-75 transition-all duration-300 focus:outline-none" id="hero-next-btn">
        <i class="fas fa-chevron-left text-2xl"></i>
    </button>

    {{-- نشانگرهای اسلاید --}}
    <div class="absolute bottom-8 z-20 flex space-x-3 space-x-reverse" id="hero-indicators">
        {{-- نشانگرها اینجا با JavaScript اضافه می‌شوند --}}
    </div>
</section>

{{-- اسکریپت جاوااسکریپت برای کنترل اسلایدشو --}}
{{-- این اسکریپت با استفاده از @push('scripts') به انتهای app.blade.php اضافه می‌شود --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const slides = document.querySelectorAll('.hero-slide');
        const prevBtn = document.getElementById('hero-prev-btn');
        const nextBtn = document.getElementById('hero-next-btn');
        const indicatorsContainer = document.getElementById('hero-indicators');
        let currentSlide = 0;
        let slideInterval; // متغیر برای نگهداری اینتروال اسلایدشو

        function showSlide(index) {
            // اطمینان از اینکه index در محدوده صحیح باشد
            if (index >= slides.length) {
                currentSlide = 0;
            } else if (index < 0) {
                currentSlide = slides.length - 1;
            } else {
                currentSlide = index;
            }

            slides.forEach((slide, i) => {
                if (i === currentSlide) {
                    slide.classList.remove('opacity-0');
                    slide.classList.add('opacity-100');
                } else {
                    slide.classList.remove('opacity-100');
                    slide.classList.add('opacity-0');
                }
            });
            updateIndicators();
        }

        function nextSlide() {
            showSlide(currentSlide + 1);
        }

        function prevSlide() {
            showSlide(currentSlide - 1);
        }

        function startSlideShow() {
            stopSlideShow(); // توقف اینتروال قبلی قبل از شروع جدید
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
                indicator.classList.add('w-3', 'h-3', 'rounded-full', 'bg-white', 'bg-opacity-50', 'cursor-pointer', 'transition-all', 'duration-300');
                indicator.dataset.slideIndex = i;
                indicator.addEventListener('click', () => {
                    stopSlideShow();
                    showSlide(i);
                    startSlideShow(); // شروع مجدد اسلایدشو بعد از کلیک دستی
                });
                indicatorsContainer.appendChild(indicator);
            });
            updateIndicators();
        }

        function updateIndicators() {
            if (!indicatorsContainer) { // اضافه کردن چک null
                return;
            }
            const indicators = indicatorsContainer.querySelectorAll('div');
            indicators.forEach((indicator, i) => {
                if (i === currentSlide) {
                    indicator.classList.add('!bg-opacity-100', '!w-8'); // ! برای override کردن اولویت Tailwind
                } else {
                    indicator.classList.remove('!bg-opacity-100', '!w-8');
                }
            });
        }

        // فقط در صورتی که اسلایدشو وجود دارد آن را آغاز کنید
        const heroCarousel = document.getElementById('hero-carousel');
        if (heroCarousel) {
            if (slides.length > 0) {
                createIndicators(); // ایجاد نشانگرها
                showSlide(currentSlide); // نمایش اولین اسلاید
                startSlideShow(); // شروع اسلایدشو خودکار

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
                heroCarousel.addEventListener('mouseenter', stopSlideShow);
                heroCarousel.addEventListener('mouseleave', startSlideShow);
            } else {
                console.warn("No hero slides found. Hero carousel will not be initialized.");
            }
        }
    });
</script>
@endpush
