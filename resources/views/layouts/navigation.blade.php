<nav x-data="{ mobileMenuOpen: false }" class="bg-gradient-to-r from-green-800 via-green-700 to-green-600 shadow-xl border-b-4 border-amber-400 sticky top-0 z-50 backdrop-blur-sm">
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
            <div class="hidden lg:flex flex-1 justify-center items-center space-x-reverse space-10" dir="rtl">
                <!-- Home -->
                <a href="{{ route('home') }}" 
                   class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                    <i class="fas fa-home ml-2"></i>
                    <span>صفحه اصلی</span>
                </a>

                <!-- Products with Dropdown -->
                <div class="relative group">
                    <a href="{{ route('products.index') }}" 
                       class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                        <i class="fas fa-leaf ml-2"></i>
                        <span>محصولات</span>
                        <i class="fas fa-chevron-down text-xs mr-1 transition-transform duration-300 group-hover:rotate-180"></i>
                    </a>
                    <!-- Dropdown Menu -->
                    <div class="absolute top-full right-0 mt-3 w-64 bg-white rounded-xl shadow-2xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform translate-y-2 group-hover:translate-y-0 z-50">
                        <div class="p-4">
                            <div class="text-sm text-gray-500 mb-3 font-semibold">دسته‌بندی محصولات</div>
                            <div class="space-y-2">
                                <a href="{{ route('products.index') }}?category=black-tea" class="dropdown-link">
                                    <i class="fas fa-circle text-amber-600 text-xs ml-2"></i>چای سیاه
                                </a>
                                <a href="{{ route('products.index') }}?category=green-tea" class="dropdown-link">
                                    <i class="fas fa-circle text-green-600 text-xs ml-2"></i>چای سبز
                                </a>
                                <a href="{{ route('products.index') }}?category=herbal-tea" class="dropdown-link">
                                    <i class="fas fa-circle text-purple-600 text-xs ml-2"></i>چای گیاهی
                                </a>
                                <a href="{{ route('products.index') }}?category=special" class="dropdown-link">
                                    <i class="fas fa-star text-yellow-500 text-xs ml-2"></i>محصولات ویژه
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Blog -->
                <a href="{{ route('blog.index') }}" 
                   class="nav-link {{ request()->routeIs('blog.*') ? 'active' : '' }}">
                    <i class="fas fa-newspaper ml-2"></i>
                    <span>وبلاگ</span>
                </a>

                <!-- About Us -->
                <a href="{{ route('about') }}" 
                   class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}">
                    <i class="fas fa-info-circle ml-2"></i>
                    <span>درباره ما</span>
                </a>

                <!-- Contact Us -->
                <a href="{{ route('contact') }}" 
                   class="nav-link {{ request()->routeIs('contact') ? 'active' : '' }}">
                    <i class="fas fa-phone-alt ml-2"></i>
                    <span>تماس با ما</span>
                </a>
            </div>

            <!-- Left Side - Search & User Menu (pushed to the left using ml-auto) -->
            <div class="flex items-center space-x-6 ml-auto">
                <!-- Search Bar with Live Results -->
                <div class="hidden md:block relative z-50"> 
                    <input type="text" 
                           id="live-search-input" 
                           name="q" 
                           placeholder="جستجو در محصولات..." 
                           class="w-64 px-4 py-2 pr-10 text-xs bg-white/90 backdrop-blur-sm border border-white/20 rounded-full focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition-all duration-300 placeholder-gray-500">
                    <i class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    
                    <!-- Search Results Container - Positioned absolutely below the search input -->
                    <div id="search-results-container" class="absolute top-full left-0 w-full mt-2 bg-white rounded-lg shadow-lg z-50 border border-gray-200 overflow-hidden hidden">
                        <!-- Results or initial message will be inserted here by JavaScript -->
                        <p class="text-gray-500 text-center py-4 text-sm" id="initial-message">شروع به تایپ کنید تا نتایج را مشاهده کنید.</p>
                    </div>
                </div>

                <!-- Cart with Counter and Hover Dropdown -->
                <div class="relative mini-cart-dropdown"> 
                    <a href="{{ route('cart.index') }}" 
                       class="nav-link {{ request()->routeIs('cart.*') ? 'active' : '' }} relative"
                       id="mini-cart-trigger">
                        <i class="fas fa-shopping-cart ml-2"></i>
                        <span>سبد خرید</span>
                        <!-- Cart Counter Badge -->
                        <span id="cart-item-count"
                              class="absolute -top-0 -left-0 bg-red-500 text-white text-xs rounded-full min-w-[40px] h-5 flex items-center justify-center font-bold hidden z-10 px-1 leading-none">
                            0
                        </span>
                    </a>
                    <!-- Mini-Cart Details Container (Hidden by default, shown on hover) -->
                    <div id="mini-cart-details-container" class="mini-cart-dropdown-content">
                        <!-- Content will be populated by JavaScript -->
                    </div>
                </div>

                <!-- User Menu Dropdown -->
                <div class="relative" x-data="{ userMenuOpen: false }">
                    <button @click="userMenuOpen = !userMenuOpen" 
                            class="flex items-center px-4 py-2 text-white hover:bg-white/10 rounded-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-amber-400" dir="rtl">
                        @auth
                            <div class="w-10 h-10 bg-amber-400 rounded-full flex items-center justify-center text-green-800 font-bold text-lg ml-3">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <div class="hidden sm:block text-right ml-2">
                                <div class="text-xs font-semibold">{{ Auth::user()->name }}</div>
                                <div class="text-xs text-amber-200">کاربر عضو</div>
                            </div>
                        @else
                            <div class="w-10 h-10 bg-gray-400 rounded-full flex items-center justify-center text-white ml-3">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="hidden sm:block text-right ml-2">
                                <div class="text-xs text-semibold">کاربر مهمان</div>
                                <div class="text-xs text-amber-200">عضو نشده</div>
                            </div>
                        @endauth
                        <i class="fas fa-chevron-down text-xs transition-transform duration-300" :class="{'rotate-180': userMenuOpen}"></i>
                    </button>

                    <!-- User Dropdown Menu -->
                    {{-- اضافه کردن x-cloak و hidden برای اطمینان از پنهان ماندن منو در ابتدا --}}
                    {{-- تغییر x-show به x-transition برای مدیریت نمایش و پنهان‌سازی با انیمیشن --}}
                    <div x-show="userMenuOpen" 
                         @click.away="userMenuOpen = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute left-0 mt-3 w-72 bg-white rounded-xl shadow-2xl border border-gray-100 py-2 z-50"
                         x-cloak> 
                        
                        @auth
                            <!-- User Info Header -->
                            <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-green-50 to-amber-50">
                                <div class="flex items-center" dir="rtl">
                                    <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center text-white font-bold text-lg ml-3">
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-800">{{ Auth::user()->name }}</div>
                                        <div class="text-xs text-gray-500">{{ Auth::user()->email }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Menu Items -->
                            <div class="py-2">
                                <a href="{{ route('profile.edit') }}" class="user-dropdown-link">
                                    <i class="fas fa-user-cog text-blue-500"></i>
                                    <span>ویرایش پروفایل</span>
                                    <i class="fas fa-chevron-left text-gray-400"></i>
                                </a>
                                
                                <a href="{{ route('dashboard') }}" class="user-dropdown-link">
                                    <i class="fas fa-tachometer-alt text-green-500"></i>
                                    <span>داشبورد</span>
                                    <i class="fas fa-chevron-left text-gray-400"></i>
                                </a>

                                <div class="border-t border-gray-100 my-2"></div>

                                <form method="POST" action="{{ route('auth.logout') }}"> {{-- Updated route --}}
                                    @csrf
                                    <button type="submit" class="user-dropdown-link w-full text-right text-red-600 hover:bg-red-50">
                                        <i class="fas fa-sign-out-alt text-red-500"></i>
                                        <span>خروج از حساب</span>
                                        <i class="fas fa-chevron-left text-gray-400"></i>
                                    </button>
                                </form>
                            </div>
                        @else
                            <!-- Guest User Menu -->
                            <div class="py-2">
                                <a href="{{ route('auth.mobile-login-form') }}" class="user-dropdown-link"> {{-- Updated route --}}
                                    <i class="fas fa-sign-in-alt text-green-500"></i>
                                    <span>ورود به حساب</span>
                                    <i class="fas fa-chevron-left text-gray-400"></i>
                                </a>
                                
                                {{-- لینک ثبت‌نام را به مسیر مستقیم register-form تغییر می‌دهیم --}}
                                <a href="{{ route('auth.register-form') }}" class="user-dropdown-link"> 
                                    <i class="fas fa-user-plus"></i>
                                    <span>ثبت‌نام</span>
                                    <i class="fas fa-chevron-left text-gray-400"></i>
                                </a>
                            </div>
                        @endauth
                    </div>
                </div>

                <!-- Mobile Menu Button -->
                <div class="lg:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" 
                            class="p-2 rounded-md text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-amber-400 transition-all duration-300">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': mobileMenuOpen, 'inline-flex': !mobileMenuOpen }" 
                                  class="inline-flex" 
                                  stroke-linecap="round" 
                                  stroke-linejoin="round" 
                                  stroke-width="2" 
                                  d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': !mobileMenuOpen, 'inline-flex': mobileMenuOpen }" 
                                  class="hidden" 
                                  stroke-linecap="round" 
                                  stroke-linejoin="round" 
                                  stroke-width="2" 
                                  d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div :class="{'block': mobileMenuOpen, 'hidden': !mobileMenuOpen}" class="hidden lg:hidden bg-green-800/95 backdrop-blur-sm border-t border-green-600">
        <!-- Mobile Search -->
        <div class="px-4 py-3 border-b border-green-600">
            <!-- Removed form here for mobile if live search is desired on mobile -->
            <div class="relative">
                <input type="text" 
                       id="mobile-live-search-input" 
                       name="q" 
                       placeholder="جستجو..." 
                       class="w-full px-4 py-2 pr-10 text-xs bg-white/90 border border-white/20 rounded-full focus:outline-none focus:ring-2 focus:ring-amber-400">
                <i class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <!-- Mobile Search Results Container -->
                <div id="mobile-search-results-container" class="absolute top-full left-0 w-full mt-2 bg-white rounded-lg shadow-lg z-50 border border-gray-200 overflow-hidden hidden">
                    <p class="text-gray-500 text-center py-4 text-sm" id="initial-message">شروع به تایپ کنید تا نتایج را مشاهده کنید.</p>
                </div>
            </div>
        </div>
        
        <!-- Mobile Navigation Links -->
        <div class="px-4 py-3 space-y-2">
            <a href="{{ route('home') }}" 
               class="mobile-nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                <i class="fas fa-home"></i>
                <span>صفحه اصلی</span>
            </a>
            
            <a href="{{ route('products.index') }}" 
               class="mobile-nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                <i class="fas fa-leaf"></i>
                <span>محصولات</span>
            </a>

            <a href="{{ route('blog.index') }}" 
               class="mobile-nav-link {{ request()->routeIs('blog.*') ? 'active' : '' }}">
                <i class="fas fa-newspaper"></i>
                <span>وبلاگ</span>
            </a>

            <a href="{{ route('about') }}" 
               class="mobile-nav-link {{ request()->routeIs('about') ? 'active' : '' }}">
                <i class="fas fa-info-circle"></i>
                <span>درباره ما</span>
            </a>

            <a href="{{ route('contact') }}" 
               class="mobile-nav-link {{ request()->routeIs('contact') ? 'active' : '' }}">
                <i class="fas fa-phone-alt"></i>
                <span>تماس با ما</span>
            </a>
            
            <a href="{{ route('cart.index') }}" 
               class="mobile-nav-link {{ request()->routeIs('cart.*') ? 'active' : '' }}">
                <i class="fas fa-shopping-cart"></i>
                <span>سبد خرید</span>
                {{-- Changed id to cart-item-count and removed style="display: none;" --}}
                {{-- Adjusted position and size for mobile counter --}}
                <span class="mr-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full hidden" id="cart-item-count">0</span>
            </a>
        </div>

        <!-- Mobile User Section -->
        <div class="border-t border-green-600 px-4 py-4">
            @auth
                <div class="flex items-center mb-4" dir="rtl">
                    <div class="w-12 h-12 bg-amber-400 rounded-full flex items-center justify-center text-green-800 font-bold text-lg ml-3">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <div>
                        <div class="font-semibold text-white">{{ Auth::user()->name }}</div>
                        <div class="text-xs text-amber-200">{{ Auth::user()->email }}</div>
                    </div>
                </div>
                
                <div class="space-y-2">
                    <a href="{{ route('profile.edit') }}" class="mobile-nav-link">
                        <i class="fas fa-user-cog"></i>
                        <span>ویرایش پروفایل</span>
                    </a>
                    
                    <form method="POST" action="{{ route('auth.logout') }}"> {{-- Updated route --}}
                        @csrf
                        <button type="submit" class="mobile-nav-link w-full text-right text-red-300 hover:bg-red-800/20">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>خروج از حساب</span>
                        </button>
                    </form>
                </div>
            @else
                <div class="space-y-2">
                    <a href="{{ route('auth.mobile-login-form') }}" class="mobile-nav-link"> {{-- Updated route --}}
                        <i class="fas fa-sign-in-alt"></i>
                        <span>ورود به حساب</span>
                    </a>
                    
                    {{-- لینک ثبت‌نام را به مسیر مستقیم register-form تغییر می‌دهیم --}}
                    <a href="{{ route('auth.register-form') }}" class="mobile-nav-link"> 
                        <i class="fas fa-user-plus"></i>
                        <span>ثبت‌نام</span>
                    </a>
                </div>
            @endauth
        </div>
    </div>
</nav>
