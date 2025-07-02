{{-- استفاده از لایه‌بندی اصلی برنامه که شامل هدر و فوتر است --}}
<x-app-layout>
    <div class="py-12 bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white dark:bg-gray-800 shadow-xl rounded-lg p-8 sm:p-10 border border-gray-200 dark:border-gray-700" dir="rtl">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-2">
                    {{ __('تکمیل ثبت‌نام') }}
                </h2>
                <p class="text-md text-gray-600 dark:text-gray-400">
                    {{ __('شماره موبایل شما تأیید شد. لطفاً اطلاعات خود را وارد کنید.') }}
                </p>
                {{-- نمایش شماره موبایل تایید شده --}}
                @if (isset($mobileNumber))
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('شماره موبایل:') }} <span class="font-bold text-gray-800 dark:text-gray-200">{{ $mobileNumber }}</span>
                    </p>
                @endif
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('auth.register') }}" class="space-y-6">
                @csrf

                <!-- Mobile Number (Hidden field) -->
                {{-- شماره موبایل از کنترلر RegisterController به این ویو ارسال شده است --}}
                <input type="hidden" name="mobile_number" value="{{ $mobileNumber ?? '' }}">

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('نام') }}
                    </label>
                    <input id="name" 
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400" 
                           type="text" 
                           name="name" 
                           value="{{ old('name') }}" 
                           required 
                           autofocus>
                    <x-input-error :messages="$errors->get('name')" class="mt-2 text-sm" />
                </div>

                <!-- Last Name -->
                <div>
                    <label for="lastname" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('نام خانوادگی (اختیاری)') }}
                    </label>
                    <input id="lastname" 
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400" 
                           type="text" 
                           name="lastname" 
                           value="{{ old('lastname') }}">
                    <x-input-error :messages="$errors->get('lastname')" class="mt-2 text-sm" />
                </div>

                <div class="flex items-center justify-center mt-6">
                    <button type="submit" 
                            class="inline-flex items-center justify-center px-6 py-3 text-lg font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300 ease-in-out min-w-[180px]">
                        {{ __('ثبت‌نام') }}
                        <i class="fas fa-user-plus mr-2"></i> {{-- آیکون ثبت‌نام --}}
                    </button>
                </div>

                <div class="flex items-center justify-center mt-4">
                    <a href="{{ route('auth.mobile-login-form') }}" 
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900/20 rounded-lg transition-all duration-200 ease-in-out">
                        {{ __('بازگشت به صفحه ورود') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

