    @extends('layouts.guest') {{-- Assuming you have a guest layout for authentication pages --}}

    @section('title', 'تأیید کد - چای ابراهیم')

    @section('content')
        {{-- Fallback for browsers with JavaScript disabled --}}
        <noscript>
            <div class="fixed inset-0 bg-red-600 bg-opacity-90 flex items-center justify-center z-[9999]">
                <div class="bg-white p-8 rounded-lg shadow-2xl text-center max-w-sm mx-4">
                    <h2 class="text-2xl font-bold text-red-800 mb-4">جاوااسکریپت غیرفعال است!</h2>
                    <p class="text-gray-700 mb-4">
                        برای استفاده کامل از این صفحه و تأیید کد یکبار مصرف، لطفاً جاوااسکریپت را در مرورگر خود فعال کنید.
                    </p>
                    <p class="text-sm text-gray-500">
                        بدون جاوااسکریپت، برخی از قابلیت‌های اصلی صفحه کار نخواهند کرد.
                    </p>
                </div>
            </div>
        </noscript>

        <section class="container mx-auto px-4 py-8 md:py-16 max-w-md">
            <div class="w-full bg-white dark:bg-gray-800 shadow-xl rounded-lg p-8 sm:p-10 border border-gray-200 dark:border-gray-700" dir="rtl">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-2">
                        تأیید کد یکبار مصرف
                    </h2>
                    <p class="text-md text-gray-600 dark:text-gray-400">
                        کد تأیید به شماره موبایل شما ارسال شد. لطفاً کد را وارد کنید.
                        <span id="current-mobile-number" class="font-bold text-gray-800 dark:text-gray-200">
                            {{ $mobileNumber ?? old('mobile_number') }}
                        </span>
                    </p>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        کد تا <span id="countdown-timer" class="font-bold text-green-600">02:00</span> دیگر معتبر است.
                    </p>
                </div>

                {{-- Displaying session status messages --}}
                @if (session('status'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <strong class="font-bold">موفقیت!</strong>
                        <span class="block sm:inline">{{ session('status') }}</span>
                    </div>
                @endif

                {{-- Displaying validation errors --}}
                @if ($errors->any())
                    <div class="error-container fade-in mb-4" role="alert">
                        <strong class="font-bold">خطا!</strong>
                        <ul class="mt-3 list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Form for OTP verification (now handled by AJAX) --}}
                <form id="otp-verify-form" class="space-y-6"> {{-- Removed method="POST" action="..." --}}
                    @csrf

                    <input type="hidden" name="mobile_number" id="hidden-mobile-number" value="{{ $mobileNumber ?? old('mobile_number') }}">

                    <!-- OTP Input (Multi-digit) -->
                    <div>
                        <label for="otp-digit-1" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            کد تأیید
                        </label>
                        {{-- Added dir="ltr" to ensure cursor moves from left to right --}}
                        <div dir="ltr" class="flex justify-center space-x-2">
                            <input id="otp-digit-1" class="otp-digit-input w-12 h-12 text-center text-2xl font-bold rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out" type="text" maxlength="1" inputmode="numeric">
                            <input id="otp-digit-2" class="otp-digit-input w-12 h-12 text-center text-2xl font-bold rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out" type="text" maxlength="1" inputmode="numeric">
                            <input id="otp-digit-3" class="otp-digit-input w-12 h-12 text-center text-2xl font-bold rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out" type="text" maxlength="1" inputmode="numeric">
                            <input id="otp-digit-4" class="otp-digit-input w-12 h-12 text-center text-2xl font-bold rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out" type="text" maxlength="1" inputmode="numeric">
                            <input id="otp-digit-5" class="otp-digit-input w-12 h-12 text-center text-2xl font-bold rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out" type="text" maxlength="1" inputmode="numeric">
                            <input id="otp-digit-6" class="otp-digit-input w-12 h-12 text-center text-2xl font-bold rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out" type="text" maxlength="1" inputmode="numeric">
                        </div>
                        {{-- Using the x-input-error component for displaying OTP validation errors --}}
                        <x-input-error :messages="$errors->get('otp')" class="mt-2 text-sm" />
                    </div>

                    <div class="flex items-center justify-center mt-6">
                        <button type="button" id="verify-otp-ajax-button" {{-- Changed type to button and added ID --}}
                                class="btn-primary w-full flex items-center justify-center">
                            ثبت و ورود
                            <i class="fas fa-sign-in-alt mr-2"></i> {{-- Icon for login --}}
                        </button>
                    </div>
                </form>

                <div class="flex flex-col items-center justify-center mt-4 space-y-2">
                    <button id="resend-otp-button"
                            class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 bg-transparent hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all duration-300 ease-in-out opacity-50 cursor-not-allowed"
                            data-mobile-number="{{ $mobileNumber ?? old('mobile_number') }}"
                            disabled
                            aria-disabled="true">
                        ارسال مجدد کد (<span id="resend-timer">02:00</span>)
                    </button>

                    {{-- Changed "تغییر شماره موبایل" to "بازگشت به صفحه ورود" and updated href to use window.history.back() --}}
                    <a href="javascript:void(0);" onclick="window.history.back();"
                            class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900/20 rounded-lg transition-all duration-300 ease-in-out">
                        بازگشت به صفحه قبلی
                    </a>
                </div>
            </div>
        </section>

        {{-- Removed Change Mobile Number Modal --}}
        {{--
        <div id="change-mobile-modal" class="custom-modal-overlay">
            <div class="custom-modal-content">
                <button id="close-modal-button" class="custom-modal-close-btn text-gray-600 dark:text-gray-400 hover:text-red-500">
                    <i class="fas fa-times"></i>
                </button>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">تغییر شماره موبایل</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">لطفاً شماره موبایل جدید خود را وارد کنید.</p>
                <input id="new_mobile_number"
                       class="input-field text-center block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400"
                       type="text"
                       placeholder="مثال: 09123456789"
                       maxlength="11"
                       inputmode="numeric">
                <p id="modal-error-message" class="error-message hidden text-right text-red-500 text-sm mt-2"></p>
                <button id="send-new-otp-button" class="btn-primary w-full flex items-center justify-center mt-6">
                    ارسال کد تأیید جدید
                </button>
            </div>
        </div>
        --}}

        {{-- Hidden div to pass routes to JavaScript --}}
        <div id="app-routes"
             data-send-otp-route="{{ route('api.auth.send-otp') }}" {{-- Changed to use the API route --}}
             data-change-mobile-number-route="{{ route('api.auth.send-otp') }}" {{-- Changed to use the API route --}}
             data-verify-otp-route="{{ route('api.auth.verify-otp') }}"> {{-- Changed to use the API route --}}
        </div>
    @endsection

    {{-- Remove this entire @push('scripts') block --}}
    {{-- <script>
        window.showMessage = function(message, type = 'info') {
            const container = document.createElement('div');
            container.className = `fixed bottom-4 left-1/2 -translate-x-1/2 px-6 py-3 rounded-lg shadow-lg text-white text-center z-[1000] transition-all duration-300 ease-out transform opacity-0 scale-95`;

            if (type === 'success') {
                container.classList.add('bg-green-500');
            } else if (type === 'error') {
                container.classList.add('bg-red-500');
            } else {
                container.classList.add('bg-blue-500');
            }

            container.textContent = message;
            document.body.appendChild(container);

            setTimeout(() => {
                container.classList.add('opacity-100', 'scale-100', 'translate-y-0');
            }, 100);

            setTimeout(() => {
                container.classList.remove('opacity-100', 'scale-100', 'translate-y-0');
                container.classList.add('opacity-0', 'scale-95');
                container.addEventListener('transitionend', () => container.remove(), { once: true });
            }, 3000);
        };
    </script>
    @vite(['resources/js/api.js', 'resources/js/auth.js']) --}}
    {{-- End of block to remove --}}
