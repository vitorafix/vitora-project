 

<?php $__env->startSection('title', 'تأیید کد - چای ابراهیم'); ?>

<?php $__env->startSection('content'); ?>
    <section class="container mx-auto px-4 py-8 md:py-16 max-w-md">
        <div class="w-full bg-white dark:bg-gray-800 shadow-xl rounded-lg p-8 sm:p-10 border border-gray-200 dark:border-gray-700" dir="rtl">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-2">
                    تأیید کد یکبار مصرف
                </h2>
                <p class="text-md text-gray-600 dark:text-gray-400">
                    کد تأیید به شماره موبایل شما ارسال شد. لطفاً کد را وارد کنید.
                    <span id="current-mobile-number" class="font-bold text-gray-800 dark:text-gray-200">
                        <?php echo e($mobileNumber ?? old('mobile_number')); ?>

                    </span>
                </p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    کد تا <span id="countdown-timer" class="font-bold text-green-600">02:00</span> دیگر معتبر است.
                </p>
            </div>

            
            <?php if(session('status')): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">موفقیت!</strong>
                    <span class="block sm:inline"><?php echo e(session('status')); ?></span>
                </div>
            <?php endif; ?>

            
            <?php if($errors->any()): ?>
                <div class="error-container fade-in mb-4" role="alert">
                    <strong class="font-bold">خطا!</strong>
                    <ul class="mt-3 list-disc list-inside text-sm">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('auth.verify-otp')); ?>" class="space-y-6">
                <?php echo csrf_field(); ?>

                <input type="hidden" name="mobile_number" id="hidden-mobile-number" value="<?php echo e($mobileNumber ?? old('mobile_number')); ?>">

                <!-- OTP Input -->
                <div>
                    <label for="otp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        کد تأیید
                    </label>
                    <input id="otp"
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400 text-center tracking-widest"
                           type="text"
                           name="otp"
                           value="<?php echo e(old('otp')); ?>"
                           placeholder="مثال: 123456"
                           required
                           autofocus
                           maxlength="6"
                           inputmode="numeric"
                           pattern="[0-9]*">
                    
                    <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('otp'),'class' => 'mt-2 text-sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('otp')),'class' => 'mt-2 text-sm']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                </div>

                <div class="flex items-center justify-center mt-6">
                    <button type="submit"
                            class="btn-primary w-full flex items-center justify-center">
                        ثبت و ورود
                        <i class="fas fa-sign-in-alt mr-2"></i> 
                    </button>
                </div>
            </form>

            <div class="flex flex-col items-center justify-center mt-4 space-y-2">
                <button id="resend-otp-button"
                        class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 bg-transparent hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all duration-200 ease-in-out opacity-50 cursor-not-allowed"
                        data-mobile-number="<?php echo e($mobileNumber ?? old('mobile_number')); ?>"
                        disabled
                        aria-disabled="true">
                    ارسال مجدد کد (<span id="resend-timer">02:00</span>)
                </button>

                <button id="change-mobile-button"
                        class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900/20 rounded-lg transition-all duration-200 ease-in-out">
                    تغییر شماره موبایل
                </button>
            </div>
        </div>
    </section>

    
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
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const countdownTimerElement = document.getElementById('countdown-timer');
            const resendButton = document.getElementById('resend-otp-button');
            const resendTimerElement = resendButton.querySelector('#resend-timer'); // Select the span inside the button
            const otpInput = document.getElementById('otp');
            const hiddenMobileNumberInput = document.getElementById('hidden-mobile-number');
            const currentMobileNumberSpan = document.getElementById('current-mobile-number');

            const changeMobileButton = document.getElementById('change-mobile-button');
            const changeMobileModal = document.getElementById('change-mobile-modal');
            const closeModalButton = document.getElementById('close-modal-button');
            const newMobileInput = document.getElementById('new_mobile_number');
            const sendNewOtpButton = document.getElementById('send-new-otp-button');
            const modalErrorMessage = document.getElementById('modal-error-message');

            let countdownSeconds = 120; // 2 minutes
            let resendCooldownSeconds = 120; // 2 minutes for resend
            let countdownInterval;
            let resendInterval;

            // Function to convert Persian/Arabic digits to English and remove non-digits
            const convertAndFilterDigits = (value) => {
                const persianToEnglishMap = {
                    '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
                    '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
                    '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
                    '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9'
                };
                let convertedValue = '';
                for (let i = 0; i < value.length; i++) {
                    const char = value[i];
                    convertedValue += persianToEnglishMap[char] || char;
                }
                // Remove any non-digit characters after conversion
                return convertedValue.replace(/\D/g, '');
            };

            // Apply digit conversion and filtering to OTP input
            if (otpInput) {
                otpInput.addEventListener('input', function(event) {
                    event.target.value = convertAndFilterDigits(event.target.value);
                });
            }

            // Apply digit conversion and filtering to new mobile number input in modal
            if (newMobileInput) {
                newMobileInput.addEventListener('input', function(event) {
                    event.target.value = convertAndFilterDigits(event.target.value);
                });
            }

            // --- Countdown Timer Logic ---
            function updateCountdownTimer() {
                const minutes = Math.floor(countdownSeconds / 60);
                const seconds = countdownSeconds % 60;
                countdownTimerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                if (countdownSeconds <= 0) {
                    clearInterval(countdownInterval);
                    resendButton.disabled = false;
                    resendButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    resendButton.removeAttribute('aria-disabled');
                    resendTimerElement.textContent = ''; // Clear timer text
                } else {
                    countdownSeconds--;
                }
            }

            function startCountdown() {
                clearInterval(countdownInterval); // Clear any existing interval
                countdownSeconds = 120; // Reset timer
                resendButton.disabled = true;
                resendButton.classList.add('opacity-50', 'cursor-not-allowed');
                resendButton.setAttribute('aria-disabled', 'true');
                resendTimerElement.textContent = '02:00'; // Reset resend timer display
                updateCountdownTimer(); // Call immediately to show initial time
                countdownInterval = setInterval(updateCountdownTimer, 1000);
            }

            // --- Resend OTP Cooldown Logic ---
            function updateResendCooldownTimer() {
                const minutes = Math.floor(resendCooldownSeconds / 60);
                const seconds = resendCooldownSeconds % 60;
                resendTimerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                if (resendCooldownSeconds <= 0) {
                    clearInterval(resendInterval);
                    resendButton.disabled = false;
                    resendButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    resendButton.removeAttribute('aria-disabled');
                    resendTimerElement.textContent = ''; // Clear timer text
                } else {
                    resendCooldownSeconds--;
                }
            }

            function startResendCooldown() {
                clearInterval(resendInterval); // Clear any existing interval
                resendCooldownSeconds = 120; // Reset cooldown
                resendButton.disabled = true;
                resendButton.classList.add('opacity-50', 'cursor-not-allowed');
                resendButton.setAttribute('aria-disabled', 'true');
                updateResendCooldownTimer(); // Call immediately to show initial time
                resendInterval = setInterval(updateResendCooldownTimer, 1000);
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
                        const response = await fetch('/auth/resend-otp', {
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
                            window.showMessage(data.message || 'خطا در ارسال مجدد کد.', 'error');
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
                    const newMobileNumber = convertAndFilterDigits(newMobileInput.value); // Use the new filtering function
                    const mobileRegex = /^09[0-9]{9}$/; // Basic Iranian mobile number regex

                    if (!mobileRegex.test(newMobileNumber)) {
                        modalErrorMessage.textContent = 'لطفاً یک شماره موبایل معتبر (مثال: 09123456789) وارد کنید.';
                        modalErrorMessage.classList.remove('hidden');
                        return;
                    } else {
                        modalErrorMessage.classList.add('hidden');
                    }

                    try {
                        const response = await fetch('/auth/change-mobile-number', {
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
                            modalErrorMessage.textContent = data.message || 'خطا در تغییر شماره موبایل.';
                            modalErrorMessage.classList.remove('hidden');
                        }
                    } catch (error) {
                        console.error('Error changing mobile number:', error);
                        window.showMessage('خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.', 'error');
                        modalErrorMessage.textContent = 'خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.';
                        modalErrorMessage.classList.remove('hidden');
                    }
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.guest', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\myshop\resources\views/auth/verify-otp.blade.php ENDPATH**/ ?>