<x-app-layout>
    <div class="py-12 bg-gray-100 dark:bg-gray-900 min-h-screen flex">
        <!-- Sidebar - Left Column -->
        <div class="w-full lg:w-1/4 xl:w-1/5 bg-white dark:bg-gray-800 shadow-xl border-r border-gray-200 dark:border-gray-700 min-h-screen lg:min-h-0 relative" x-data="{ open: window.innerWidth >= 1024 ? true : false }">
            <!-- Toggle button for mobile -->
            <div class="lg:hidden p-4">
                <button @click="open = !open"
                        class="p-2 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition-all duration-300">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>

            <!-- Sidebar content -->
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full"
                 class="w-full lg:w-auto h-full absolute lg:relative bg-white dark:bg-gray-800 lg:block z-40">
                <div class="p-6 pb-2 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-reverse space-x-3">
                        <div class="w-12 h-12 bg-amber-400 rounded-full flex items-center justify-center text-green-800 font-bold text-xl">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ Auth::user()->name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ Auth::user()->email }}</div>
                        </div>
                    </div>
                </div>

                <nav class="mt-4 px-4 space-y-1">
                    <!-- Dashboard Link -->
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center px-4 py-2 text-md font-medium rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-150 ease-in-out {{ request()->routeIs('dashboard') ? 'text-green-700 bg-green-50 dark:bg-green-900/30 dark:text-green-300' : '' }}"
                       aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}">
                        <i class="fas fa-home ml-3 text-green-600 dark:text-green-400"></i>
                        <span>{{ __('داشبورد اصلی') }}</span>
                    </a>

                    <!-- Orders Link -->
                    <a href="{{ route('profile.orders.index') }}"
                       class="flex items-center px-4 py-2 text-md font-medium rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-150 ease-in-out {{ request()->routeIs('profile.orders.index') ? 'text-green-700 bg-green-50 dark:bg-green-900/30 dark:text-green-300' : '' }}">
                        <i class="fas fa-box-open ml-3 text-amber-500"></i>
                        <span>{{ __('سفارش‌ها') }}</span>
                    </a>

                    <!-- Addresses Link -->
                    <a href="{{ route('profile.addresses.index') }}"
                       class="flex items-center px-4 py-2 text-md font-medium rounded-lg text-green-700 bg-green-50 dark:bg-green-900/30 dark:text-green-300 hover:bg-green-100 dark:hover:bg-gray-700 transition duration-150 ease-in-out {{ request()->routeIs('profile.addresses.index') || request()->routeIs('profile.addresses.create') || request()->routeIs('profile.addresses.edit') ? 'text-green-700 bg-green-50 dark:bg-green-900/30 dark:text-green-300' : '' }}">
                        <i class="fas fa-map-marker-alt ml-3 text-blue-500"></i>
                        <span>{{ __('آدرس‌ها') }}</span>
                    </a>

                    <!-- Profile Information Link - Changed from profile.edit to profile.show -->
                    <a href="{{ route('profile.show') }}"
                       class="flex items-center px-4 py-2 text-md font-medium rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-150 ease-in-out {{ request()->routeIs('profile.show') ? 'text-green-700 bg-green-50 dark:bg-green-900/30 dark:text-green-300' : '' }}">
                        <i class="fas fa-user-circle ml-3 text-purple-500"></i>
                        <span>{{ __('اطلاعات حساب') }}</span>
                    </a>

                    <!-- Notifications Link -->
                    <a href="#"
                       class="flex items-center px-4 py-2 text-md font-medium rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-150 ease-in-out">
                        <i class="fas fa-bell ml-3 text-red-500"></i>
                        <span>{{ __('اعلان‌ها') }}</span>
                    </a>

                    <!-- Wishlist Link (if applicable, currently not in PRD) -->
                    {{-- <a href="#"
                       class="flex items-center px-4 py-2 text-md font-medium rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-150 ease-in-out">
                        <i class="fas fa-heart ml-3 text-pink-500"></i>
                        <span>{{ __('علاقه‌مندی‌ها') }}</span>
                    </a> --}}

                    <!-- Transactions Link -->
                    <a href="#"
                       class="flex items-center px-4 py-2 text-md font-medium rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-150 ease-in-out">
                        <i class="fas fa-credit-card ml-3 text-indigo-500"></i>
                        <span>{{ __('تراکنش‌ها') }}</span>
                    </a>

                    <!-- Support Link -->
                    <a href="#"
                       class="flex items-center px-4 py-2 text-md font-medium rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-150 ease-in-out">
                        <i class="fas fa-life-ring ml-3 text-green-500"></i>
                        <span>{{ __('پشتیبانی') }}</span>
                    </a>

                    <!-- My Reviews Link -->
                    <a href="#"
                       class="flex items-center px-4 py-2 text-md font-medium rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-150 ease-in-out">
                        <i class="fas fa-star ml-3 text-yellow-500"></i>
                        <span>{{ __('نظرات من') }}</span>
                    </a>

                    <!-- Logout Link -->
                    <form method="POST" action="{{ route('auth.logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex items-center w-full text-right px-4 py-2 text-md font-medium rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition duration-150 ease-in-out">
                            <i class="fas fa-sign-out-alt ml-3 text-red-500"></i>
                            <span>{{ __('خروج') }}</span>
                        </button>
                    </form>
                </nav>
            </div>
        </div>

        <!-- Main Content Area - Right Column -->
        <div class="flex-1 p-6 lg:p-8">
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg border border-gray-200 dark:border-gray-700 p-6 sm:p-8">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                    {{ __('آدرس‌های من') }}
                </h3>

                <!-- Session Status Message -->
                @if (session('status'))
                    <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/20 p-3 rounded-lg flex items-center">
                        <i class="fas fa-check-circle ml-2"></i>
                        {{ session('status') }}
                    </div>
                @endif

                <!-- Session Error Message -->
                @if (session('error'))
                    <div class="mb-4 font-medium text-sm text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/20 p-3 rounded-lg flex items-center">
                        <i class="fas fa-exclamation-circle ml-2"></i>
                        {{ session('error') }}
                    </div>
                @endif

                <div class="mb-6 flex justify-end">
                    <a href="{{ route('profile.addresses.create') }}"
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300">
                        <i class="fas fa-plus ml-2"></i>
                        {{ __('افزودن آدرس جدید') }}
                    </a>
                </div>

                @if ($addresses->isEmpty())
                    <div class="text-center py-10">
                        <i class="fas fa-map-marker-alt text-gray-400 text-6xl mb-4"></i>
                        <p class="text-lg text-gray-600 dark:text-gray-400">{{ __('شما هنوز آدرسی ثبت نکرده‌اید.') }}</p>
                        <a href="{{ route('profile.addresses.create') }}"
                           class="mt-6 inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300">
                            {{ __('افزودن اولین آدرس') }}
                            <i class="fas fa-chevron-left mr-2"></i>
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($addresses as $address)
                            {{-- اضافه کردن border-red-500 و border-2 در صورت پیش‌فرض بودن آدرس --}}
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-600 relative
                                @if ($address->is_default) border-red-500 border-2 @endif">
                                @if ($address->is_default)
                                    <span class="absolute top-2 left-2 bg-amber-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                                        {{ __('پیش‌فرض') }}
                                    </span>
                                @endif
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                                    <i class="fas fa-home ml-2 text-blue-500"></i>
                                    {{ $address->title ?: __('آدرس بدون عنوان') }}
                                </h4>
                                <p class="text-gray-700 dark:text-gray-300 text-sm mb-1">{{ $address->address }}</p>
                                <p class="text-gray-600 dark:text-gray-400 text-sm mb-1">
                                    {{ $address->city }}, {{ $address->province }}
                                </p>
                                @if ($address->postal_code)
                                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-1">
                                        {{ __('کد پستی:') }} {{ $address->postal_code }}
                                    </p>
                                @endif
                                {{-- Added fixed_phone display --}}
                                @if ($address->fixed_phone)
                                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">
                                        {{ __('شماره تماس ثابت:') }} {{ $address->fixed_phone }}
                                    </p>
                                @endif
                                <div class="flex justify-end space-x-reverse space-x-2 border-t border-gray-200 dark:border-gray-600 pt-3 mt-3">
                                    <a href="{{ route('profile.addresses.edit', $address->id) }}"
                                       class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-300">
                                        {{ __('ویرایش') }}
                                    </a>
                                    {{-- Changed onsubmit to data-confirm --}}
                                    <form action="{{ route('profile.addresses.destroy', $address->id) }}" method="POST"
                                          data-confirm="آیا از حذف این آدرس مطمئن هستید؟">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-300 ml-2">
                                            {{ __('حذف') }}
                                        </button>
                                    </form>
                                </div>
                                {{-- دکمه تغییر به پیش فرض --}}
                                @unless ($address->is_default)
                                    <div class="mt-3 text-center">
                                        {{-- Changed onsubmit to data-confirm --}}
                                        <form action="{{ route('profile.addresses.set-default', $address->id) }}" method="POST" data-confirm="آیا می‌خواهید این آدرس را به عنوان آدرس پیش‌فرض خود تنظیم کنید؟">
                                            @csrf
                                            <button type="submit"
                                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-300 w-full justify-center">
                                                <i class="fas fa-star ml-2"></i>
                                                {{ __('تغییر به پیش‌فرض') }}
                                            </button>
                                        </form>
                                    </div>
                                @endunless
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Custom Confirmation Modal HTML -->
    <div id="confirmation-modal-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 opacity-0 invisible transition-all duration-300">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-sm transform scale-95 opacity-0 transition-all duration-300">
            <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-3 mb-4">
                <h5 id="confirmation-modal-title" class="text-lg font-semibold text-gray-900 dark:text-white"></h5>
                <button id="confirmation-modal-close-btn" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200 text-xl font-bold" style="display: none;">&times;</button>
            </div>
            <p id="confirmation-modal-message" class="text-gray-700 dark:text-gray-300 mb-6"></p>
            <div class="flex justify-end space-x-2">
                <button id="confirmation-modal-cancel-btn" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors duration-200">
                    {{ __('لغو') }}
                </button>
                <button id="confirmation-modal-confirm-btn" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors duration-200">
                    {{ __('تایید') }}
                </button>
            </div>
        </div>
    </div>

    <style>
        #confirmation-modal-overlay {
            /* Ensure it's hidden by default */
            opacity: 0;
            visibility: hidden;
            pointer-events: none; /* Allows clicks to pass through when not active */
        }

        #confirmation-modal-overlay.active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto; /* Enables clicks when active */
        }
        #confirmation-modal-overlay.active > div {
            transform: scale(1);
            opacity: 1;
        }
    </style>

    {{-- Custom Confirmation Modal Script --}}
    <script>
        // این تابع را در یک فایل JS جداگانه (مثلاً app.js) یا در بخش <script> در app.blade.php قرار دهید
        // تا در سراسر برنامه در دسترس باشد.
        function showCustomConfirmation(message, isError = false) {
            const modalOverlay = document.getElementById('confirmation-modal-overlay');
            const modalTitle = document.getElementById('confirmation-modal-title');
            const modalMessage = document.getElementById('confirmation-modal-message');
            const confirmBtn = document.getElementById('confirmation-modal-confirm-btn');
            const cancelBtn = document.getElementById('confirmation-modal-cancel-btn');
            const closeBtn = document.getElementById('confirmation-modal-close-btn');

            modalTitle.textContent = isError ? 'خطا' : 'تایید عملیات'; // عنوان ثابت برای همه تاییدیه ها
            modalMessage.textContent = message;

            // Clear previous listeners to prevent multiple bindings
            if (confirmBtn._handler) confirmBtn.removeEventListener('click', confirmBtn._handler);
            if (cancelBtn._handler) cancelBtn.removeEventListener('click', cancelBtn._handler);
            if (closeBtn._handler) closeBtn.removeEventListener('click', closeBtn._handler);

            return new Promise((resolve) => {
                modalOverlay.classList.add('active');

                const resetAndResolve = (result) => {
                    modalOverlay.classList.remove('active');
                    if (confirmBtn._handler) confirmBtn.removeEventListener('click', confirmBtn._handler);
                    if (cancelBtn._handler) cancelBtn.removeEventListener('click', cancelBtn._handler);
                    if (closeBtn._handler) closeBtn.removeEventListener('click', closeBtn._handler);

                    // Reset button styles to default for confirmation (visible confirm/cancel, hidden close)
                    confirmBtn.style.display = ''; // Revert to default display (block or inline-block)
                    cancelBtn.style.display = ''; // Revert to default display
                    closeBtn.style.display = 'none'; // Hide close button by default for confirmations
                    resolve(result);
                };

                if (isError) {
                    confirmBtn.style.display = 'none';
                    cancelBtn.style.display = 'none';
                    closeBtn.style.display = 'block'; // Show close button for errors

                    closeBtn._handler = () => resetAndResolve(false); // Error messages resolve to false (no confirmation)
                    closeBtn.addEventListener('click', closeBtn._handler);
                } else {
                    confirmBtn.style.display = 'block';
                    cancelBtn.style.display = 'block';
                    closeBtn.style.display = 'none'; // Hide close button for confirmations

                    confirmBtn._handler = () => resetAndResolve(true);
                    cancelBtn._handler = () => resetAndResolve(false);

                    confirmBtn.addEventListener('click', confirmBtn._handler);
                    cancelBtn.addEventListener('click', cancelBtn._handler);
                }
            });
        }

        // override the default confirm behavior
        window.confirm = (message) => {
            return showCustomConfirmation(message); // Directly return the promise
        };

        // Ensure the custom confirmation modal is ready on DOMContentLoaded
        document.addEventListener('DOMContentLoaded', function() {
            // Find all forms that use the data-confirm attribute
            document.querySelectorAll('form[data-confirm]').forEach(form => {
                form.addEventListener('submit', async function(event) {
                    event.preventDefault(); // Prevent default form submission immediately

                    // Removed client-side check for last address, as this is now handled by the controller.
                    // const isDeleteForm = this.action.includes('addresses/destroy');
                    // const addressCount = parseInt(this.dataset.addressCount);
                    // if (isDeleteForm && addressCount === 1) {
                    //     await showCustomConfirmation('شما نمی‌توانید آخرین آدرس را حذف کنید. حداقل یک آدرس باید وجود داشته باشد.', true);
                    //     return;
                    // }

                    const message = this.dataset.confirm; // Get message from data attribute
                    const confirmed = await showCustomConfirmation(message);

                    if (confirmed) {
                        this.submit(); // Programmatically submit the form if confirmed
                    }
                });
            });

            // --- Existing slideshow logic (kept for completeness) ---
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
</x-app-layout>
