@extends('layouts.app')

@section('title', 'ورود / ثبت‌نام - چای ابراهیم')

@section('content')
    <section class="container mx-auto px-4 py-8 md:py-16 max-w-md">
        <div class="w-full bg-white dark:bg-gray-800 shadow-xl rounded-lg p-8 sm:p-10 border border-gray-200 dark:border-gray-700" dir="rtl">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-2">
                    {{ __('ورود / ثبت‌نام') }}
                </h2>
                <p class="text-md text-gray-600 dark:text-gray-400">
                    {{ __('برای ادامه، شماره موبایل خود را وارد کنید.') }}
                </p>
            </div>

            <!-- Session Status -->
            {{-- نمایش پیام‌های وضعیت عمومی --}}
            <x-auth-session-status class="mb-4" :status="session('status')" />

            {{-- نمایش پیام و لینک ثبت‌نام در صورت یافت نشدن کاربر --}}
            @if (session('show_register_link'))
                <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg relative mb-4" role="alert">
                    <strong class="font-bold">{{ __('توجه!') }}</strong>
                    <span class="block sm:inline">{{ session('status') }}</span>
                    <div class="mt-2 text-center">
                        <a href="{{ route('auth.register-form', ['mobile_number' => session('user_not_found_mobile')]) }}" 
                           class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 bg-transparent hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all duration-200 ease-in-out">
                            {{ __('ثبت‌نام کنید') }}
                            <i class="fas fa-user-plus mr-2"></i>
                        </a>
                    </div>
                </div>
            @endif

            <form id="send-otp-form" class="space-y-6"> {{-- Removed method="POST" action="..." --}}
                @csrf

                <!-- Mobile Number -->
                <div>
                    <label for="mobile_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('شماره موبایل') }}
                    </label>
                    <input id="mobile_number" 
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400" 
                           type="text" 
                           name="mobile_number" 
                           value="{{ old('mobile_number') }}" 
                           placeholder="مثال: 09123456789"
                           required 
                           autofocus>
                    <x-input-error :messages="$errors->get('mobile_number')" class="mt-2 text-sm" />
                </div>

                <div class="flex items-center justify-center mt-6">
                    <button type="button" id="send-otp-button" {{-- Changed type to button and added ID --}}
                            class="inline-flex items-center justify-center px-6 py-3 text-lg font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300 ease-in-out min-w-[180px]">
                        {{ __('ارسال کد تأیید') }}
                        <i class="fas fa-paper-plane mr-2"></i> {{-- آیکون ارسال --}}
                    </button>
                </div>
            </form>

            {{-- لینک ثبت‌نام مستقیم (برای کاربرانی که از ابتدا می‌خواهند ثبت‌نام کنند) --}}
            <div class="flex items-center justify-center mt-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('حساب کاربری ندارید؟') }}
                    <a href="{{ route('auth.register-form') }}" class="font-semibold text-green-600 hover:text-green-500 transition-colors duration-200 ease-in-out">
                        {{ __('ثبت‌نام کنید') }}
                    </a>
                </p>
            </div>
        </div>
    </section>
@endsection
