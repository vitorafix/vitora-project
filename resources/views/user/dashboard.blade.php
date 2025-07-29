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

                    <!-- Profile Information Link (as sub-item) -->
                    <div class="pl-8 mt-1">
                        {{-- لینک اطلاعات حساب به صفحه پروفایل ایجاد شده توسط ما اشاره می‌کند --}}
                        <a href="{{ route('profile.show') }}" 
                           class="flex items-center px-4 py-2 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-150 ease-in-out {{ request()->routeIs('profile.show') ? 'text-green-700 bg-green-50 dark:bg-green-900/30 dark:text-green-300' : '' }}">
                            <i class="fas fa-user-circle ml-3 text-purple-500"></i>
                            <span>{{ __('اطلاعات حساب') }}</span>
                        </a>
                    </div>

                    <!-- Orders Link -->
                    <a href="{{ route('profile.orders.index') }}" 
                       class="flex items-center px-4 py-2 text-md font-medium rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-150 ease-in-out {{ request()->routeIs('profile.orders.index') ? 'text-green-700 bg-green-50 dark:bg-green-900/30 dark:text-green-300' : '' }}">
                        <i class="fas fa-box-open ml-3 text-amber-500"></i>
                        <span>{{ __('سفارش‌ها') }}</span>
                    </a>

                    <!-- Addresses Link -->
                    <a href="{{ route('profile.addresses.index') }}" {{-- اطمینان حاصل کنید این لینک صحیح است --}}
                       class="flex items-center px-4 py-2 text-md font-medium rounded-lg text-green-700 bg-green-50 dark:bg-green-900/30 dark:text-green-300 hover:bg-green-100 dark:hover:bg-gray-700 transition duration-150 ease-in-out {{ request()->routeIs('profile.addresses.index') || request()->routeIs('profile.addresses.create') || request()->routeIs('profile.addresses.edit') ? 'text-green-700 bg-green-50 dark:bg-green-900/30 dark:text-green-300' : '' }}">
                        <i class="fas fa-map-marker-alt ml-3 text-blue-500"></i>
                        <span>{{ __('آدرس‌ها') }}</span>
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
                    {{ __('خلاصه فعالیت‌ها') }}
                </h3>
                <p class="text-gray-700 dark:text-gray-300">
                    {{ __('به داشبورد خود خوش آمدید،') }} {{ Auth::user()->name }}!
                </p>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    {{ __('در اینجا می‌توانید خلاصه فعالیت‌های خود، سفارش‌های اخیر و اعلان‌ها را مشاهده کنید.') }}
                </p>
                <!-- Add more dashboard widgets/content here -->
            </div>
        </div>
    </div>
</x-app-layout>
