{{-- استفاده از لایه‌بندی اصلی برنامه که شامل هدر و فوتر است --}}
<x-app-layout>
    <div class="py-12 bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white dark:bg-gray-800 shadow-xl rounded-lg p-8 sm:p-10 border border-gray-200 dark:border-gray-700" dir="rtl">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-2">
                    {{ __('تأیید کد') }}
                </h2>
                <p class="text-md text-gray-600 dark:text-gray-400">
                    {{ __('کد تأیید به شماره موبایل شما ارسال شد. لطفا کد را وارد کنید.') }}
                </p>
                @if (session('mobile_number'))
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('شماره موبایل:') }} <span class="font-bold text-gray-800 dark:text-gray-200">{{ session('mobile_number') }}</span>
                    </p>
                @endif
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('auth.verify-otp') }}" class="space-y-6">
                @csrf

                <!-- Mobile Number (Hidden field to pass mobile_number) -->
                <input type="hidden" name="mobile_number" value="{{ session('mobile_number') }}">

                <!-- OTP -->
                <div>
                    <label for="otp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('کد تأیید') }}
                    </label>
                    <input id="otp" 
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400 text-center tracking-widest" 
                           type="text" 
                           name="otp" 
                           placeholder="کد ۶ رقمی"
                           maxlength="6"
                           required 
                           autofocus>
                    <x-input-error :messages="$errors->get('otp')" class="mt-2 text-sm" />
                </div>

                <div class="flex items-center justify-between mt-6">
                    <a href="{{ route('auth.mobile-login-form') }}" 
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg shadow-sm transition-all duration-200 ease-in-out">
                        {{ __('تغییر شماره موبایل') }}
                    </a>

                    <button type="submit" 
                            class="inline-flex items-center justify-center px-6 py-3 text-lg font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300 ease-in-out min-w-[150px]">
                        {{ __('تأیید و ورود') }}
                        <i class="fas fa-check-circle mr-2"></i> {{-- آیکون تأیید --}}
                    </button>
                </div>
                
                <div class="flex items-center justify-center mt-4">
                    <form method="POST" action="{{ route('auth.send-otp') }}">
                        @csrf
                        <input type="hidden" name="mobile_number" value="{{ session('mobile_number') }}">
                        <button type="submit" 
                                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-green-600 dark:text-green-400 bg-transparent hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-all duration-200 ease-in-out">
                            {{ __('ارسال مجدد کد') }}
                            <i class="fas fa-redo-alt mr-2"></i> {{-- آیکون ارسال مجدد --}}
                        </button>
                    </form>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
