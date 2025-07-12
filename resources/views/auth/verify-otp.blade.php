@extends('layouts.guest') {{-- ارث‌بری از لایه‌بندی جدید guest --}}

@section('title', 'تأیید کد - چای ابراهیم')

@section('content')
    <section class="container mx-auto px-4 py-8 md:py-16 max-w-md">
        <div class="w-full bg-white dark:bg-gray-800 shadow-xl rounded-lg p-8 sm:p-10 border border-gray-200 dark:border-gray-700" dir="rtl">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-2">
                    {{ __('تأیید کد') }}
                </h2>
                <p class="text-md text-gray-600 dark:text-gray-400">
                    {{ __('کد تأیید به شماره موبایل شما ارسال شد. لطفا کد را وارد کنید.') }}
                </p>
                @if (isset($mobileNumber))
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('شماره موبایل:') }} <span id="current-mobile-number" class="font-bold text-gray-800 dark:text-gray-200">{{ $mobileNumber }}</span>
                    </p>
                @endif
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-4 mb-6">
                    {{ __('زمان باقی‌مانده:') }} <span id="countdown-timer" class="font-bold text-red-600 dark:text-red-400">02:00</span>
                </p>
                {{-- بهبود Accessibility: اضافه کردن live region برای screen readers --}}
                <div aria-live="polite" aria-atomic="true" class="sr-only" id="timer-announcement">
                    {{ __('زمان باقی‌مانده:') }} <span id="timer-text">02:00</span>
                </div>
            </div>

            {{-- نمایش پیام‌های وضعیت سشن --}}
            @if (session('status'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">موفقیت!</strong>
                    <span class="block sm:inline">{{ session('status') }}</span>
                </div>
            @endif

            {{-- نمایش خطاهای اعتبارسنجی --}}
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

            <form method="POST" action="{{ route('auth.verify-otp') }}" class="space-y-6">
                @csrf

                {{-- این فیلدهای hidden برای ارسال شماره موبایل و تعداد تلاش‌ها به کنترلر ضروری هستند --}}
                <input type="hidden" name="mobile_number" id="hidden-mobile-number" value="{{ $mobileNumber ?? '' }}">
                <input type="hidden" name="attempt_count" value="{{ $attemptCount ?? 0 }}">

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
                           autofocus
                           inputmode="numeric"
                           pattern="[0-9]*"
                           value="{{ old('otp') }}"
                           aria-describedby="otp-help otp-error"
                           autocomplete="one-time-code">
                    <span id="otp-help" class="sr-only">{{ __('لطفاً کد تأیید ۶ رقمی که به شماره موبایل شما ارسال شده است را وارد کنید.') }}</span>
                    @error('otp')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between mt-6">
                    {{-- دکمه تغییر شماره موبایل که مودال را باز می‌کند --}}
                    <button type="button" id="change-mobile-button"
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900/20 rounded-lg transition-all duration-200 ease-in-out ml-4"
                       role="button"
                       tabindex="0"
                    >
                        {{ __('تغییر شماره موبایل') }}
                    </button>

                    <button type="submit"
                            class="inline-flex items-center justify-center px-6 py-3 text-lg font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300 ease-in-out min-w-[180px]">
                        {{ __('تأیید و ورود') }}
                        <i class="fas fa-check-circle mr-2"></i>
                    </button>
                </div>
            </form>

            <div class="flex items-center justify-center mt-4">
                <form id="resend-otp-form" method="POST" action="{{ route('auth.send-otp') }}">
                    @csrf
                    <input type="hidden" name="mobile_number" value="{{ $mobileNumber ?? '' }}">
                    <button type="submit" id="resend-otp-button"
                            class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-green-700 dark:text-green-400 bg-transparent hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-all duration-200 ease-in-out font-bold">
                        {{ __('ارسال مجدد کد') }}
                        <i class="fas fa-redo-alt mr-2"></i>
                    </button>
                </form>
            </div>
        </div>
    </section>

    {{-- Modal for changing mobile number --}}
    <div id="change-mobile-modal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center p-4 z-50 hidden" aria-modal="true" role="dialog">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-8 w-full max-w-sm relative" dir="rtl">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                {{ __('تغییر شماره موبایل') }}
            </h3>

            <button type="button" id="close-modal-button" class="absolute top-4 left-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span class="sr-only">{{ __('بستن') }}</span>
            </button>

            <form id="new-mobile-form" class="space-y-4">
                @csrf
                <div>
                    <label for="new_mobile_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('شماره موبایل جدید') }}
                    </label>
                    <input id="new_mobile_number"
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400"
                           type="text"
                           name="mobile_number"
                           placeholder="مثال: 09123456789"
                           required
                           inputmode="numeric"
                           pattern="[0-9]*">
                    <p id="modal-error-message" class="mt-2 text-sm text-red-500 hidden"></p>
                    <p id="modal-success-message" class="mt-2 text-sm text-green-500 hidden"></p>
                </div>

                <div class="flex justify-center mt-6">
                    <button type="submit" id="send-new-otp-button"
                            class="inline-flex items-center justify-center px-6 py-3 text-lg font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300 ease-in-out min-w-[150px]">
                        {{ __('ارسال کد به شماره جدید') }}
                        <i class="fas fa-paper-plane mr-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- اسکریپت‌های جاوااسکریپت مستقیماً در اینجا قرار می‌گیرند --}}
    <script>
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        document.addEventListener('DOMContentLoaded', function() {
            let timerElement = document.getElementById('countdown-timer');
            let resendButton = document.getElementById('resend-otp-button');
            let resendForm = document.getElementById('resend-otp-form');
            let otpInput = document.getElementById('otp');
            let timerAnnouncement = document.getElementById('timer-announcement');
            let timerText = document.getElementById('timer-text');
            let currentMobileNumberSpan = document.getElementById('current-mobile-number');
            let hiddenMobileNumberInput = document.getElementById('hidden-mobile-number');

            // Modal elements
            let changeMobileButton = document.getElementById('change-mobile-button');
            let changeMobileModal = document.getElementById('change-mobile-modal');
            let closeModalButton = document.getElementById('close-modal-button');
            let newMobileForm = document.getElementById('new-mobile-form');
            let newMobileInput = document.getElementById('new_mobile_number');
            let sendNewOtpButton = document.getElementById('send-new-otp-button');
            let modalErrorMessage = document.getElementById('modal-error-message');
            let modalSuccessMessage = document.getElementById('modal-success-message');

            if (!timerElement || !resendButton || !resendForm || !otpInput || !timerAnnouncement || !timerText || !currentMobileNumberSpan || !hiddenMobileNumberInput || !changeMobileButton || !changeMobileModal || !closeModalButton || !newMobileForm || !newMobileInput || !sendNewOtpButton || !modalErrorMessage || !modalSuccessMessage) {
                console.error("یکی از المنت‌های مورد نیاز برای اسکریپت تأیید OTP یا مودال یافت نشد. اسکریپت اجرا نخواهد شد.");
                return;
            }

            let timeLeft = 120; // Default 2 minutes
            let timerInterval;

            function startTimer() {
                clearInterval(timerInterval); // Clear any existing timer
                timeLeft = 120; // Reset timer
                resendButton.disabled = true;
                resendButton.classList.add('opacity-50', 'cursor-not-allowed');
                resendButton.innerHTML = '{{ __("ارسال مجدد کد") }} <i class="fas fa-redo-alt mr-2"></i>'; // Reset button text

                timerInterval = setInterval(updateTimer, 1000);
                updateTimer(); // Call immediately to show initial time
            }

            function updateTimer() {
                let minutes = Math.floor(timeLeft / 60);
                let seconds = timeLeft % 60;

                minutes = minutes < 10 ? '0' + minutes : minutes;
                seconds = seconds < 10 ? '0' + seconds : seconds;

                timerElement.textContent = minutes + ':' + seconds;
                timerText.textContent = minutes + ':' + seconds; // For screen readers

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    timerElement.textContent = '{{ __("منقضی شد!") }}';
                    timerText.textContent = '{{ __("منقضی شد!") }}';
                    resendButton.disabled = false;
                    resendButton.classList.remove('opacity-50', 'cursor-not-allowed');
                } else {
                    timeLeft--;
                }
            }

            startTimer(); // Initial call to start the timer

            resendForm.addEventListener('submit', function() {
                resendButton.disabled = true;
                resendButton.classList.add('opacity-50', 'cursor-not-allowed');
                resendButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> {{ __("در حال ارسال...") }}';
                // gtag calls removed as per instructions
            });

            const debouncedOtpInput = debounce(function() {
                let persianDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
                let arabicDigits = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];

                let englishValue = this.value.split('').map(function(char) {
                    if (persianDigits.includes(char)) {
                        return persianDigits.indexOf(char);
                    } else if (arabicDigits.includes(char)) {
                        return arabicDigits.indexOf(char);
                    } else {
                        return char;
                    }
                }).join('');

                englishValue = englishValue.replace(/[^0-9]/g, '');
                this.value = englishValue;

                if (this.value.length === 6) {
                    document.querySelector('form button[type="submit"]').focus();
                    if ('vibrate' in navigator) {
                        navigator.vibrate([50, 50, 50]);
                    }
                }
            }, 300);

            otpInput.addEventListener('input', debouncedOtpInput);

            const errorContainer = document.querySelector('.error-container');
            if (errorContainer && 'vibrate' in navigator) {
                navigator.vibrate(100);
            }

            // Modal Logic
            changeMobileButton.addEventListener('click', function() {
                changeMobileModal.classList.remove('hidden');
                newMobileInput.value = ''; // Clear input when opening
                modalErrorMessage.classList.add('hidden');
                modalSuccessMessage.classList.add('hidden');
            });

            closeModalButton.addEventListener('click', function() {
                changeMobileModal.classList.add('hidden');
            });

            // Close modal if clicked outside
            changeMobileModal.addEventListener('click', function(event) {
                if (event.target === changeMobileModal) {
                    changeMobileModal.classList.add('hidden');
                }
            });

            // Handle new mobile number form submission via AJAX
            newMobileForm.addEventListener('submit', async function(event) {
                event.preventDefault(); // Prevent default form submission

                modalErrorMessage.classList.add('hidden');
                modalSuccessMessage.classList.add('hidden');
                sendNewOtpButton.disabled = true;
                sendNewOtpButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> {{ __("در حال ارسال...") }}';

                const newMobileNumber = newMobileInput.value;
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content'); // Assuming you have a CSRF token meta tag

                try {
                    const response = await fetch('{{ route('auth.send-otp') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken // Include CSRF token
                        },
                        body: JSON.stringify({ mobile_number: newMobileNumber })
                    });

                    const data = await response.json();

                    if (response.ok) {
                        // Success: Update mobile number on page and hidden input
                        currentMobileNumberSpan.textContent = newMobileNumber;
                        hiddenMobileNumberInput.value = newMobileNumber;
                        modalSuccessMessage.textContent = data.message || '{{ __("کد تأیید به شماره جدید ارسال شد.") }}';
                        modalSuccessMessage.classList.remove('hidden');
                        changeMobileModal.classList.add('hidden');
                        startTimer(); // Restart the timer for the new OTP
                        otpInput.value = ''; // Clear OTP input field
                        otpInput.focus(); // Focus on OTP input
                    } else {
                        // Error: Display error message
                        modalErrorMessage.textContent = data.message || '{{ __("خطا در ارسال کد به شماره جدید.") }}';
                        modalErrorMessage.classList.remove('hidden');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    modalErrorMessage.textContent = '{{ __("خطایی رخ داد. لطفاً دوباره تلاش کنید.") }}';
                    modalErrorMessage.classList.remove('hidden');
                } finally {
                    sendNewOtpButton.disabled = false;
                    sendNewOtpButton.innerHTML = '{{ __("ارسال کد به شماره جدید") }} <i class="fas fa-paper-plane mr-2"></i>';
                }
            });

            // JavaScript for converting Persian/Arabic digits to English for the new mobile number input
            if (newMobileInput) {
                newMobileInput.addEventListener('input', function(event) {
                    let value = event.target.value;
                    let convertedValue = '';

                    const persianToEnglishMap = {
                        '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
                        '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
                        '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
                        '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9'
                    };

                    for (let i = 0; i < value.length; i++) {
                        const char = value[i];
                        convertedValue += persianToEnglishMap[char] || char;
                    }

                    event.target.value = convertedValue;
                });
            }
        });
    </script>
@endsection
