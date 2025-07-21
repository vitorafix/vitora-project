@extends('layouts.app')

@section('title', 'ثبت‌نام کاربر جدید - چای ابراهیم')

@section('content')
    <section class="container mx-auto px-4 py-8 md:py-16 max-w-md">
        <div class="w-full bg-white dark:bg-gray-800 shadow-xl rounded-lg p-8 sm:p-10 border border-gray-200 dark:border-gray-700" dir="rtl">
            <div class="text-center mb-8">
                <h2 class="2xl font-extrabold text-gray-900 dark:text-white mb-2">
                    ثبت‌نام کاربر جدید
                </h2>
                <p class="text-md text-gray-600 dark:text-gray-400">
                    لطفاً اطلاعات خود را برای ثبت‌نام وارد کنید.
                </p>
                {{-- نمایش شماره موبایل در صورتی که از MobileAuthController به اینجا هدایت شده باشد --}}
                @if (isset($mobileNumber) && !empty($mobileNumber))
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{-- Displaying verified mobile number if redirected from MobileAuthController --}}
                        شماره موبایل تأیید شده: <span class="font-bold text-gray-800 dark:text-gray-200">{{ $mobileNumber }}</span>
                    </p>
                @endif
            </div>

            {{-- نمایش پیام‌های وضعیت از سشن (مانند ثبت‌نام موفق) --}}
            @if (session('status'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">موفقیت!</strong>
                    <span class="block sm:inline">{{ session('status') }}</span>
                </div>
            @endif

            {{-- نمایش خطاهای اعتبارسنجی از سرور --}}
            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">خطا!</strong>
                    <ul class="mt-3 list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- اکشن فرم به مسیر صحیح برای درخواست POST بر اساس web.php --}}
            <form id="register-form" class="space-y-6"> {{-- Removed method="POST" action="..." --}}
                @csrf {{-- توکن CSRF برای فرم‌های Laravel --}}

                <!-- Name Input -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        نام
                    </label>
                    <input id="name"
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400"
                           type="text"
                           name="name"
                           value="{{ old('name') }}"
                           required
                           autofocus
                           placeholder="نام خود را وارد کنید">
                    {{-- نمایش خطای اعتبارسنجی برای فیلد نام --}}
                    @error('name')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Last Name Input -->
                <div>
                    <label for="lastname" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        نام خانوادگی (اختیاری)
                    </label>
                    <input id="lastname"
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400"
                           type="text"
                           name="lastname"
                           value="{{ old('lastname') }}"
                           placeholder="نام خانوادگی خود را وارد کنید">
                    {{-- نمایش خطای اعتبارسنجی برای فیلد نام خانوادگی --}}
                    @error('lastname')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Mobile Number Input -->
                <div>
                    <label for="mobile_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        شماره موبایل
                    </label>
                    <input id="mobile_number"
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400"
                           type="text"
                           name="mobile_number"
                           value="{{ $mobileNumber ?? old('mobile_number') }}" {{-- Pre-fill if redirected from login --}}
                           required
                           placeholder="مثال: 09123456789">
                    {{-- نمایش خطای اعتبارسنجی برای فیلد شماره موبایل --}}
                    @error('mobile_number')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-center mt-6">
                    <button type="button" id="register-button" {{-- Changed type to button and added ID --}}
                            class="inline-flex items-center justify-center px-6 py-3 text-lg font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300 ease-in-out min-w-[180px]">
                        ثبت‌نام و دریافت کد
                        <i class="fas fa-paper-plane mr-2"></i> {{-- آیکون ارسال --}}
                    </button>
                </div>

                <div class="flex items-center justify-center mt-4">
                    {{-- لینک به‌روز شده به مسیر صحیح برای صفحه ورود بر اساس web.php --}}
                    <a href="{{ route('auth.mobile-login-form') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900/20 rounded-lg transition-all duration-200 ease-in-out">
                        بازگشت به صفحه ورود
                    </a>
                </div>
            </form>
        </div>
    </section>
@endsection
