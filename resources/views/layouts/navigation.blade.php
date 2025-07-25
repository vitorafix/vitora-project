<nav x-data="{ mobileMenuOpen: false }" id="main-navbar" class="bg-gradient-to-r from-green-800 via-green-700 to-green-600 shadow-xl border-b-4 border-amber-400 sticky top-0 z-50 backdrop-blur-sm">
    <!-- Primary Navigation Menu -->
    <div class="max-w-full mx-auto px-0"> <!-- Changed to max-w-full and px-0 for full width -->
        <!-- Main flex container for logo, navigation links, and user/search section -->
        <div class="flex items-center h-20"> <!-- Removed justify-between here, will manage spacing with flex-grow and ml-auto -->
            <!-- Right Side - Logo & Brand -->
            <div class="flex items-center shrink-0">
                <!-- Logo -->
                <div class="flex items-center group">
                    <a href="{{ route('home') }}" class="flex items-center transition-transform duration-300 hover:scale-105 pr-4" dir="rtl">
                        <div class="relative">
                            <!-- Removed the leaf icon as requested -->
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xl font-black text-white">چای ابراهیم</span>
                            <span class="text-xs text-amber-200">عطر و طعم اصیل ایرانی</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Center - Main Navigation Links (now takes available space and centers its content) -->
            <div class="hidden lg:flex flex-1 justify-center items-center h-full">
                <div class="flex space-x-8 rtl:space-x-reverse h-full">
                    <x-nav-link :href="route('home')" :active="request()->routeIs('home')">
                        صفحه اصلی
                    </x-nav-link>
                    <x-nav-link :href="route('products.index')" :active="request()->routeIs('products.index')">
                        محصولات
                    </x-nav-link>
                    <x-nav-link :href="route('about')" :active="request()->routeIs('about')">
                        درباره ما
                    </x-nav-link>
                    <x-nav-link :href="route('contact')" :active="request()->routeIs('contact')">
                        تماس با ما
                    </x-nav-link>
                    <x-nav-link :href="route('blog.index')" :active="request()->routeIs('blog.index')">
                        وبلاگ
                    </x-nav-link>
                </div>
            </div>

            <!-- Left Side - Search, User, Cart -->
            <div class="flex items-center ml-auto pr-4">
                <!-- Search Icon (Desktop) -->
                <div class="hidden lg:flex items-center ml-4">
                    <a href="{{ route('search') }}" class="text-amber-200 hover:text-white transition-colors duration-200">
                        <i class="fas fa-search text-xl"></i>
                    </a>
                </div>

                <!-- User/Auth Section (Desktop) - Modified for click-to-open dropdown -->
                <!-- Added x-data to manage dropdown state and removed 'group' class -->
                <div class="relative ml-4 hidden lg:block" x-data="{ userMenuOpen: false }" @click.away="userMenuOpen = false">
                    <!-- User icon and text, now with @click to toggle dropdown -->
                    <a href="#" class="nav-link flex items-center text-amber-200 hover:text-white transition-colors duration-200" @click.prevent="userMenuOpen = !userMenuOpen">
                        <i class="fas fa-user-circle text-xl ml-2"></i>
                    </a>
                    <!-- Dropdown content, now controlled by x-show and x-transition -->
                    <div x-show="userMenuOpen"
                         x-transition:enter="duration-200 ease-out"
                         x-transition:enter-start="opacity-0 scale-95 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 transform translate-y-0"
                         x-transition:leave="duration-100 ease-in"
                         x-transition:leave-start="opacity-100 scale-100 transform translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 transform -translate-y-2"
                         class="absolute top-[95%] right-0 w-48 bg-white rounded-xl shadow-2xl border border-gray-100 z-50 p-4"
                         dir="rtl"
                         style="right: -120%; display: none;"> <!-- Adjusted top-full to top-[95%] and removed mt-3 -->
                        {{-- Both guest and logged-in states are rendered, JS will hide/show --}}
                        <div id="user-status-guest" class="space-y-2 hidden"> {{-- Initially hidden --}}
                            <a href="{{ route('auth.mobile-login-form') }}" class="nav-link-dropdown flex items-center justify-end" id="login-register-link">
                                <i class="fas fa-sign-in-alt mr-2"></i>
                                <span>ورود / ثبت‌نام</span>
                            </a>
                        </div>

                        <div id="user-status-logged-in" class="space-y-2 hidden"> {{-- Initially hidden --}}
                            {{-- User Profile Summary at the top of the dropdown --}}
                            <div class="user-profile-header flex items-center justify-end">
                                <span class="user-full-name text-right" id="logged-in-user-full-name"></span>
                                <i class="fas fa-user-circle user-icon mr-2"></i>
                                <i class="fas fa-chevron-down text-gray-500 text-xs mr-auto"></i>
                            </div>

                            <a href="{{ route('profile.edit') }}" class="nav-link-dropdown-compact flex items-center justify-end">
                                <span class="ml-auto">ویرایش پروفایل</span>
                                <i class="fas fa-user-cog mr-2"></i>
                            </a>
                            <a href="{{ route('orders.index') }}" class="nav-link-dropdown-compact flex items-center justify-end">
                                <span class="ml-auto">سفارشات من</span>
                                <i class="fas fa-box-seam mr-2"></i>
                            </a>
                            <a href="{{ route('addresses.index') }}" class="nav-link-dropdown-compact flex items-center justify-end">
                                <span class="ml-auto">آدرس‌ها</span>
                                <i class="fas fa-map-marker-alt mr-2"></i>
                            </a>
                            <a href="#" class="nav-link-dropdown-compact flex items-center justify-end">
                                <span class="ml-auto">لیست‌ها</span>
                                <i class="fas fa-list mr-2"></i>
                            </a>
                            <a href="#" class="nav-link-dropdown-compact flex items-center justify-end">
                                <span class="ml-auto">دیدگاه‌ها و پرسش‌ها</span>
                                <i class="fas fa-comments mr-2"></i>
                            </a>
                            <button type="button" id="logout-link" class="nav-link-dropdown-compact w-full text-right text-red-500 hover:bg-red-100 flex items-center justify-end">
                                <span class="ml-auto">خروج از حساب</span>
                                <i class="fas fa-sign-out-alt mr-2"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Cart Icon (Desktop) - Moved to the right of the user section -->
                {{-- تغییر: اضافه کردن div با id="mini-cart-root" برای رندر کردن MiniCart React --}}
                {{-- این div جایگزین Mini Cart Dropdown قدیمی می‌شود و مدیریت آن به React سپرده می‌شود --}}
                {{-- Removed hidden lg:block from the parent div, as the button will be inside it and its visibility will be handled by React. --}}
                <div class="relative ml-4 group" id="mini-cart-root">
                    {{-- دکمه سبد خرید اکنون مستقیماً داخل mini-cart-root قرار گرفته است --}}
                    <button
                        type="button"
                        id="mini-cart-toggle" {{-- اضافه کردن ID برای دکمه --}}
                        class="inline-flex justify-center items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        aria-expanded="false"
                        aria-haspopup="true"
                    >
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                    {{-- MiniCart React component will be rendered here via Portal --}}
                </div>

                <!-- Mobile Menu Button (Hamburger) -->
                <div class="lg:hidden flex items-center ml-4">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="inline-flex items-center justify-center p-2 rounded-md text-amber-200 hover:text-white hover:bg-green-700 focus:outline-none focus:bg-green-700 focus:text-white transition duration-150 ease-in-out">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': mobileMenuOpen, 'inline-flex': ! mobileMenuOpen }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': ! mobileMenuOpen, 'inline-flex': mobileMenuOpen }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu (Responsive) -->
    <div x-show="mobileMenuOpen" x-transition:enter="duration-200 ease-out" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="duration-100 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="lg:hidden bg-green-800 border-t border-amber-400 shadow-lg py-3">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
            <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')">
                صفحه اصلی
            </x-responsive-link>
            <x-responsive-nav-link :href="route('products.index')" :active="request()->routeIs('products.index')">
                محصولات
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('about')" :active="request()->routeIs('about')">
                درباره ما
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('contact')" :active="request()->routeIs('contact')">
                تماس با ما
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('blog.index')" :active="request()->routeIs('blog.index')">
                وبلاگ
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('search')" :active="request()->routeIs('search')">
                جستجو
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('cart.index')" :active="request()->routeIs('cart.index')">
                سبد خرید <span id="mobile-cart-count" class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full ml-2 hidden">0</span>
            </x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-3 border-t border-green-700">
            {{-- Mobile User Status (controlled by JS) --}}
            <div id="mobile-user-status-guest" class="space-y-2 px-4 hidden"> {{-- Initially hidden --}}
                <a href="{{ route('auth.mobile-login-form') }}" class="mobile-nav-link flex items-center justify-end">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    <span>ورود به حساب</span>
                </a>
                <a href="{{ route('auth.register-form') }}" class="mobile-nav-link flex items-center justify-end">
                    <i class="fas fa-user-plus mr-2"></i>
                    <span>ثبت‌نام</span>
                </a>
            </div>

            <div id="mobile-user-status-logged-in" class="space-y-2 px-4 hidden"> {{-- Initially hidden --}}
                <div class="flex items-center"> {{-- Removed pr-4 and justify-end --}}
                    <div class="shrink-0 mr-3">
                        <i class="fas fa-user-circle text-2xl text-amber-300"></i>
                    </div>
                    <div class="text-right">
                        <div class="font-medium text-base text-white" id="mobile-logged-in-user-name"></div>
                        <div class="font-medium text-sm text-amber-200" id="mobile-logged-in-user-mobile"></div>
                    </div>
                </div>

                <div class="space-y-2">
                    <a href="{{ route('profile.edit') }}" class="mobile-nav-link">
                        <i class="fas fa-user-cog mr-2"></i>
                        <span>ویرایش پروفایل</span>
                    </a>
                    <a href="{{ route('orders.index') }}" class="mobile-nav-link">
                        <i class="fas fa-box-seam mr-2"></i>
                        <span>سفارشات من</span>
                    </a>
                    <a href="{{ route('addresses.index') }}" class="mobile-nav-link">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        <span>آدرس‌ها</span>
                    </a>
                    {{-- NEW: Added missing mobile menu items --}}
                    <a href="#" class="mobile-nav-link">
                        <i class="fas fa-list mr-2"></i>
                        <span>لیست‌ها</span>
                    </a>
                    <a href="#" class="mobile-nav-link">
                        <i class="fas fa-comments mr-2"></i>
                        <span>دیدگاه‌ها و پرسش‌ها</span>
                    </a>
                    <button type="button" id="logout-link-mobile" class="mobile-nav-link w-full text-right text-red-300 hover:bg-red-800/20">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        <span>خروج از حساب</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</nav>
