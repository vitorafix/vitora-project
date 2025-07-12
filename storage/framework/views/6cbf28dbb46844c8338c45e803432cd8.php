 

<?php $__env->startSection('title', 'تأیید کد - چای ابراهیم'); ?>

<?php $__env->startSection('content'); ?>
    <section class="container mx-auto px-4 py-8 md:py-16 max-w-md">
        <div class="w-full bg-white dark:bg-gray-800 shadow-xl rounded-lg p-8 sm:p-10 border border-gray-200 dark:border-gray-700" dir="rtl">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-2">
                    <?php echo e(__('تأیید کد')); ?>

                </h2>
                <p class="text-md text-gray-600 dark:text-gray-400">
                    <?php echo e(__('کد تأیید به شماره موبایل شما ارسال شد. لطفا کد را وارد کنید.')); ?>

                </p>
                <?php if(isset($mobileNumber)): ?>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        <?php echo e(__('شماره موبایل:')); ?> <span class="font-bold text-gray-800 dark:text-gray-200"><?php echo e($mobileNumber); ?></span>
                    </p>
                <?php endif; ?>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-4 mb-6">
                    <?php echo e(__('زمان باقی‌مانده:')); ?> <span id="countdown-timer" class="font-bold text-red-600 dark:text-red-400">02:00</span>
                </p>
                
                <div aria-live="polite" aria-atomic="true" class="sr-only" id="timer-announcement">
                    <?php echo e(__('زمان باقی‌مانده:')); ?> <span id="timer-text">02:00</span>
                </div>
            </div>

            
            <?php if(session('status')): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">موفقیت!</strong>
                    <span class="block sm:inline"><?php echo e(session('status')); ?></span>
                </div>
            <?php endif; ?>

            
            <?php if($errors->any()): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
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

                
                <input type="hidden" name="mobile_number" value="<?php echo e($mobileNumber ?? ''); ?>">
                <input type="hidden" name="attempt_count" value="<?php echo e($attemptCount ?? 0); ?>">

                <div>
                    <label for="otp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?php echo e(__('کد تأیید')); ?>

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
                           value="<?php echo e(old('otp')); ?>"
                           aria-describedby="otp-help otp-error"
                           autocomplete="one-time-code">
                    <span id="otp-help" class="sr-only"><?php echo e(__('لطفاً کد تأیید ۶ رقمی که به شماره موبایل شما ارسال شده است را وارد کنید.')); ?></span>
                    <?php $__errorArgs = ['otp'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-2 text-sm text-red-500"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="flex items-center justify-between mt-6">
                    <a href="<?php echo e(route('auth.mobile-login-form')); ?>"
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900/20 rounded-lg transition-all duration-200 ease-in-out ml-4">
                        <?php echo e(__('تغییر شماره موبایل')); ?>

                    </a>

                    <button type="submit"
                            class="inline-flex items-center justify-center px-6 py-3 text-lg font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300 ease-in-out min-w-[180px]">
                        <?php echo e(__('تأیید و ورود')); ?>

                        <i class="fas fa-check-circle mr-2"></i>
                    </button>
                </div>
            </form>

            <div class="flex items-center justify-center mt-4">
                <form id="resend-otp-form" method="POST" action="<?php echo e(route('auth.send-otp')); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="mobile_number" value="<?php echo e($mobileNumber ?? ''); ?>">
                    <button type="submit" id="resend-otp-button"
                            class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-green-700 dark:text-green-400 bg-transparent hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-all duration-200 ease-in-out font-bold">
                        <?php echo e(__('ارسال مجدد کد')); ?>

                        <i class="fas fa-redo-alt mr-2"></i>
                    </button>
                </form>
            </div>
        </div>
    </section>

    
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

            if (!timerElement || !resendButton || !resendForm || !otpInput || !timerAnnouncement || !timerText) {
                console.error("یکی از المنت‌های مورد نیاز برای اسکریپت تأیید OTP یافت نشد. اسکریپت اجرا نخواهد شد.");
                return;
            }

            // Hardcoding expiry time for standalone page, assuming 2 minutes (120 seconds)
            // در محیط لاراول واقعی، این مقدار می‌تواند از config('auth.otp.expiry_minutes', 2) * 60 خوانده شود.
            let timeLeft = 120;
            let timerInterval;

            function updateTimer() {
                let minutes = Math.floor(timeLeft / 60);
                let seconds = timeLeft % 60;

                minutes = minutes < 10 ? '0' + minutes : minutes;
                seconds = seconds < 10 ? '0' + seconds : seconds;

                timerElement.textContent = minutes + ':' + seconds;
                timerText.textContent = minutes + ':' + seconds;

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    timerElement.textContent = '<?php echo e(__("منقضی شد!")); ?>';
                    timerText.textContent = '<?php echo e(__("منقضی شد!")); ?>';
                    resendButton.disabled = false;
                    resendButton.classList.remove('opacity-50', 'cursor-not-allowed');
                } else {
                    timeLeft--;
                    resendButton.disabled = true;
                    resendButton.classList.add('opacity-50', 'cursor-not-allowed');
                }
            }

            timerInterval = setInterval(updateTimer, 1000);
            updateTimer();

            resendForm.addEventListener('submit', function() {
                resendButton.disabled = true;
                resendButton.classList.add('opacity-50', 'cursor-not-allowed');
                resendButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> <?php echo e(__("در حال ارسال...")); ?>';
                // فراخوانی‌های gtag حذف شده‌اند
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
                    // فراخوانی‌های gtag حذف شده‌اند
                    if ('vibrate' in navigator) {
                        navigator.vibrate([50, 50, 50]);
                    }
                }
            }, 300);

            otpInput.addEventListener('input', debouncedOtpInput);

            // فراخوانی‌های gtag حذف شده‌اند
            const errorContainer = document.querySelector('.error-container');
            if (errorContainer && 'vibrate' in navigator) {
                navigator.vibrate(100);
            }
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.guest', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\myshop\resources\views/auth/verify-otp.blade.php ENDPATH**/ ?>