@extends('layouts.guest') {{-- Assuming you have a guest layout for authentication pages --}}

@section('title', 'تأیید کد - چای ابراهیم')

@section('content')
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

            <form method="POST" action="{{ route('auth.verify-otp') }}" class="space-y-6">
                @csrf

                <input type="hidden" name="mobile_number" id="hidden-mobile-number" value="{{ $mobileNumber ?? old('mobile_number') }}">

                <!-- OTP Input (Multi-digit) -->
                <div>
                    <label for="otp-digit-1" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        کد تأیید
                    </label>
                    <div class="flex justify-center space-x-2 rtl:space-x-reverse">
                        <input id="otp-digit-1" class="otp-digit-input w-12 h-12 text-center text-2xl font-bold rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
                        <input id="otp-digit-2" class="otp-digit-input w-12 h-12 text-center text-2xl font-bold rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
                        <input id="otp-digit-3" class="otp-digit-input w-12 h-12 text-center text-2xl font-bold rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
                        <input id="otp-digit-4" class="otp-digit-input w-12 h-12 text-center text-2xl font-bold rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
                        <input id="otp-digit-5" class="otp-digit-input w-12 h-12 text-center text-2xl font-bold rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
                        <input id="otp-digit-6" class="otp-digit-input w-12 h-12 text-center text-2xl font-bold rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
                    </div>
                    {{-- Using the x-input-error component for displaying OTP validation errors --}}
                    <x-input-error :messages="$errors->get('otp')" class="mt-2 text-sm" />
                </div>

                <div class="flex items-center justify-center mt-6">
                    <button type="submit"
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

                <button id="change-mobile-button"
                        class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900/20 rounded-lg transition-all duration-300 ease-in-out">
                    تغییر شماره موبایل
                </button>
            </div>
        </div>
    </section>

    {{-- Change Mobile Number Modal --}}
    <div id="change-mobile-modal" class="custom-modal-overlay" x-cloak>
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
                   required
                   maxlength="11"
                   inputmode="numeric"
                   pattern="[0-9]*">
            <p id="modal-error-message" class="error-message hidden text-right text-red-500 text-sm mt-2"></p>
            <button id="send-new-otp-button" class="btn-primary w-full flex items-center justify-center mt-6">
                ارسال کد تأیید جدید
            </button>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Utility function for debouncing
        const debounce = (func, delay) => {
            let timeoutId;
            return (...args) => {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => func.apply(null, args), delay);
            };
        };

        // Class to manage countdown timers
        class CountdownTimer {
            constructor(element, initialSeconds, onCompleteCallback) {
                this.element = element;
                this.seconds = initialSeconds;
                this.onCompleteCallback = onCompleteCallback;
                this.interval = null;
            }

            start() {
                this.stop(); // Ensure any existing timer is stopped
                this.updateDisplay(); // Update immediately
                this.interval = setInterval(() => {
                    this.seconds--;
                    this.updateDisplay();
                    if (this.seconds <= 0) {
                        this.stop();
                        this.onCompleteCallback?.(); // Call callback if provided
                    }
                }, 1000);
            }

            stop() {
                clearInterval(this.interval);
                this.interval = null;
            }

            reset(newSeconds) {
                this.stop();
                this.seconds = newSeconds;
                this.updateDisplay();
            }

            updateDisplay() {
                const minutes = Math.floor(this.seconds / 60);
                const seconds = this.seconds % 60;
                this.element.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const countdownTimerElement = document.getElementById('countdown-timer');
            const resendButton = document.getElementById('resend-otp-button');
            const resendTimerElement = resendButton.querySelector('#resend-timer'); // Select the span inside the button
            const otpDigitInputs = document.querySelectorAll('.otp-digit-input'); // New: Get all OTP digit inputs
            const hiddenMobileNumberInput = document.getElementById('hidden-mobile-number');
            const currentMobileNumberSpan = document.getElementById('current-mobile-number');

            const changeMobileButton = document.getElementById('change-mobile-button');
            const changeMobileModal = document.getElementById('change-mobile-modal');
            const closeModalButton = document.getElementById('close-modal-button');
            const newMobileInput = document.getElementById('new_mobile_number');
            const sendNewOtpButton = document.getElementById('send-new-otp-button');
            const modalErrorMessage = document.getElementById('modal-error-message');

            let mainCountdownTimer;
            let resendCooldownTimer;

            // Function to convert Persian/Arabic digits to English and remove non-digits
            // این تابع اکنون در VerifyOtpRequest.php مدیریت می‌شود و نیازی به تکرار در اینجا نیست.
            // const convertAndFilterDigits = (value) => {
            //     const persianToEnglishMap = {
            //         '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
            //         '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
            //         '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
            //         '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9'
            //     };
            //     let convertedValue = '';
            //     for (let i = 0; i < value.length; i++) {
            //         const char = value[i];
            //         convertedValue += persianToEnglishMap[char] || char;
            //     }
            //     // Remove any non-digit characters after conversion
            //     return convertedValue.replace(/\D/g, '');
            // };

            // New: Function to get the combined OTP string from individual inputs
            const getCombinedOtp = () => {
                let otp = '';
                otpDigitInputs.forEach(input => {
                    otp += input.value;
                });
                return otp;
            };

            // Apply digit conversion and filtering to each OTP input, and handle auto-focus/backspace
            otpDigitInputs.forEach((input, index) => {
                input.addEventListener('input', function(event) {
                    // Convert and filter the single digit - این بخش در prepareForValidation در Form Request مدیریت می‌شود.
                    // event.target.value = convertAndFilterDigits(event.target.value);

                    // Auto-focus to the next input if a digit is entered and it's not the last input
                    if (event.target.value.length === 1 && index < otpDigitInputs.length - 1) {
                        otpDigitInputs[index + 1].focus();
                    }
                });

                input.addEventListener('keydown', function(event) {
                    // Handle backspace to move to previous input if current input is empty and it's not the first input
                    if (event.key === 'Backspace' && event.target.value === '' && index > 0) {
                        otpDigitInputs[index - 1].focus();
                    }
                });
            });

            // Apply digit conversion and filtering to new mobile number input in modal
            if (newMobileInput) {
                newMobileInput.addEventListener('input', function(event) {
                    // این بخش در prepareForValidation در Form Request مدیریت می‌شود.
                    // event.target.value = convertAndFilterDigits(event.target.value);
                });
            }

            // Initialize main countdown timer
            mainCountdownTimer = new CountdownTimer(countdownTimerElement, 120, () => {
                // Callback when main countdown finishes
                resendButton.disabled = false;
                resendButton.classList.remove('opacity-50', 'cursor-not-allowed');
                resendButton.removeAttribute('aria-disabled');
                resendTimerElement.textContent = ''; // Clear timer text
            });

            // Initialize resend cooldown timer (initially not running)
            resendCooldownTimer = new CountdownTimer(resendTimerElement, 120, () => {
                // Callback when resend cooldown finishes
                resendButton.disabled = false;
                resendButton.classList.remove('opacity-50', 'cursor-not-allowed');
                resendButton.removeAttribute('aria-disabled');
                resendTimerElement.textContent = ''; // Clear timer text
            });

            // Update startCountdown function to use the new class
            function startCountdown() {
                mainCountdownTimer.reset(120);
                mainCountdownTimer.start();
                resendButton.disabled = true;
                resendButton.classList.add('opacity-50', 'cursor-not-allowed');
                resendButton.setAttribute('aria-disabled', 'true');
                resendTimerElement.textContent = '02:00'; // Reset resend timer display
            }

            // Update startResendCooldown function to use the new class
            function startResendCooldown() {
                resendCooldownTimer.reset(120);
                resendCooldownTimer.start();
                resendButton.disabled = true;
                resendButton.classList.add('opacity-50', 'cursor-not-allowed');
                resendButton.setAttribute('aria-disabled', 'true');
            }

            // --- Event Listeners ---
            if (resendButton) {
                resendButton.addEventListener('click', async function() {
                    const mobileNumber = this.dataset.mobileNumber;
                    if (!mobileNumber) {
                        window.showMessage('شماره موبایل یافت نشد.', 'error');
                        return;
                    }

                    try {
                        const response = await fetch('{{ route('api.auth.send-otp') }}', { // Changed URL to use route() helper
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ mobile_number: mobileNumber })
                        });

                        const data = await response.json();

                        if (response.ok) {
                            window.showMessage(data.message || 'کد تأیید مجدداً ارسال شد.', 'success');
                            startCountdown(); // Restart main countdown
                            startResendCooldown(); // Start resend cooldown
                        } else {
                            // Display validation errors from server if any
                            if (data.errors) {
                                const errorMessages = Object.values(data.errors).flat().join('\n');
                                window.showMessage(errorMessages || 'خطا در ارسال مجدد کد.', 'error');
                            } else {
                                window.showMessage(data.message || 'خطا در ارسال مجدد کد.', 'error');
                            }
                        }
                    } catch (error) {
                        console.error('Error resending OTP:', error);
                        window.showMessage('خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.', 'error');
                    }
                });
            }

            // --- Change Mobile Modal Logic ---
            if (changeMobileButton) {
                changeMobileButton.addEventListener('click', function() {
                    changeMobileModal.classList.add('active');
                    modalErrorMessage.classList.add('hidden'); // Hide any previous error messages
                    modalErrorMessage.classList.remove('animate-pulse'); // Ensure pulse is removed
                    newMobileInput.value = ''; // Clear input
                });
            }

            if (closeModalButton) {
                closeModalButton.addEventListener('click', function() {
                    changeMobileModal.classList.remove('active');
                });
            }

            if (sendNewOtpButton) {
                sendNewOtpButton.addEventListener('click', async function() {
                    const newMobileNumber = newMobileInput.value; // نیازی به پاکسازی در اینجا نیست، در Form Request انجام می‌شود.
                    // const mobileRegex = /^09[0-9]{9}$/; // این اعتبارسنجی به Form Request منتقل شده است.

                    // if (!mobileRegex.test(newMobileNumber)) { // این اعتبارسنجی به Form Request منتقل شده است.
                    //     modalErrorMessage.textContent = 'لطفاً یک شماره موبایل معتبر (مثال: 09123456789) وارد کنید.';
                    //     modalErrorMessage.classList.remove('hidden');
                    //     modalErrorMessage.classList.add('animate-pulse'); // Add pulse animation for error
                    //     setTimeout(() => modalErrorMessage.classList.remove('animate-pulse'), 2000); // Remove pulse after 2 seconds
                    //     return;
                    // } else {
                    //     modalErrorMessage.classList.add('hidden');
                    //     modalErrorMessage.classList.remove('animate-pulse');
                    // }

                    try {
                        const response = await fetch('{{ route('auth.change-mobile-number') }}', { // Changed URL to use route() helper for web route
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ new_mobile_number: newMobileNumber })
                        });

                        const data = await response.json();

                        if (response.ok) {
                            window.showMessage(data.message || 'شماره موبایل با موفقیت تغییر یافت. کد جدید ارسال شد.', 'success');
                            hiddenMobileNumberInput.value = newMobileNumber; // Update hidden input
                            currentMobileNumberSpan.textContent = newMobileNumber; // Update displayed number
                            changeMobileModal.classList.remove('active'); // Close modal
                            startCountdown(); // Restart countdown for the new OTP
                            startResendCooldown(); // Start resend cooldown for the new OTP
                        } else {
                            // Display validation errors from server if any
                            if (data.errors) {
                                const errorMessages = Object.values(data.errors).flat().join('\n');
                                modalErrorMessage.textContent = errorMessages;
                                modalErrorMessage.classList.remove('hidden');
                                modalErrorMessage.classList.add('animate-pulse');
                                setTimeout(() => modalErrorMessage.classList.remove('animate-pulse'), 2000);
                            } else {
                                modalErrorMessage.textContent = data.message || 'خطا در تغییر شماره موبایل.';
                                modalErrorMessage.classList.remove('hidden');
                                modalErrorMessage.classList.add('animate-pulse'); // Add pulse animation for error
                                setTimeout(() => modalErrorMessage.classList.remove('animate-pulse'), 2000); // Remove pulse after 2 seconds
                            }
                        }
                    } catch (error) {
                        console.error('Error changing mobile number:', error);
                        window.showMessage('خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.', 'error');
                        modalErrorMessage.textContent = 'خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.';
                        modalErrorMessage.classList.remove('hidden');
                        modalErrorMessage.classList.add('animate-pulse'); // Add pulse animation for error
                        setTimeout(() => modalErrorMessage.classList.remove('animate-pulse'), 2000); // Remove pulse after 2 seconds
                    }
                });
            }

            // Modify form submission to use combined OTP from multi-digit inputs
            const otpForm = document.querySelector('form[action="{{ route('auth.verify-otp') }}"]');
            if (otpForm) {
                otpForm.addEventListener('submit', function(event) {
                    // Create a hidden input for the combined OTP value
                    let combinedOtpInput = document.createElement('input');
                    combinedOtpInput.type = 'hidden';
                    combinedOtpInput.name = 'otp'; // Ensure the name matches what Laravel expects
                    combinedOtpInput.value = getCombinedOtp();
                    otpForm.appendChild(combinedOtpInput);

                    // If the original 'otp' input (if it existed) had a name, you might need to disable it
                    // to prevent duplicate form fields, but since we replaced it, this is fine.
                });
            }

            // Initial start of the countdown when the page loads
            startCountdown();
        });

        /**
         * Displays a temporary message box (toast notification) on the screen.
         * تابع سراسری برای نمایش پیام‌ها (مثل پیام‌های موفقیت، خطا یا اطلاعاتی).
         * This function should be defined globally in app.js or similar.
         * If not defined, a simple alert will be used as a fallback.
         *
         * @param {string} message - The message to display.
         * @param {string} [type='info'] - The type of message ('success', 'error', 'info'). Affects background color.
         * @param {number} [duration=3000] - The duration (in milliseconds) for which the message is displayed.
         */
        if (typeof window.showMessage !== 'function') {
            window.showMessage = function(message, type = 'info', duration = 3000) {
                const existingMessageBox = document.querySelector('.message-box');
                if (existingMessageBox) {
                    existingMessageBox.remove();
                }

                const messageBox = document.createElement('div');
                messageBox.className = `fixed bottom-5 left-1/2 transform -translate-x-1/2 px-6 py-3 rounded-lg shadow-lg text-white z-50 transition-all duration-300 ease-out message-box`;

                if (type === 'success') {
                    messageBox.classList.add('bg-green-600');
                } else if (type === 'error') {
                    messageBox.classList.add('bg-red-600');
                } else {
                    messageBox.classList.add('bg-gray-800');
                }

                messageBox.textContent = message;
                document.body.appendChild(messageBox);

                setTimeout(() => {
                    messageBox.classList.add('opacity-0', 'translate-y-full');
                    messageBox.addEventListener('transitionend', () => messageBox.remove());
                }, duration);
            };
        }
    </script>
@endpush
