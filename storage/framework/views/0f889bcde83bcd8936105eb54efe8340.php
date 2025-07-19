<?php $__env->startSection('title', 'ورود / ثبت‌نام - چای ابراهیم'); ?>

<?php $__env->startSection('content'); ?>
    <section class="container mx-auto px-4 py-8 md:py-16 max-w-md">
        <div class="w-full bg-white dark:bg-gray-800 shadow-xl rounded-lg p-8 sm:p-10 border border-gray-200 dark:border-gray-700" dir="rtl">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-2">
                    <?php echo e(__('ورود / ثبت‌نام')); ?>

                </h2>
                <p class="text-md text-gray-600 dark:text-gray-400">
                    <?php echo e(__('برای ادامه، شماره موبایل خود را وارد کنید.')); ?>

                </p>
            </div>

            <!-- Session Status -->
            
            <?php if (isset($component)) { $__componentOriginal7c1bf3a9346f208f66ee83b06b607fb5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7c1bf3a9346f208f66ee83b06b607fb5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.auth-session-status','data' => ['class' => 'mb-4','status' => session('status')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('auth-session-status'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mb-4','status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(session('status'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7c1bf3a9346f208f66ee83b06b607fb5)): ?>
<?php $attributes = $__attributesOriginal7c1bf3a9346f208f66ee83b06b607fb5; ?>
<?php unset($__attributesOriginal7c1bf3a9346f208f66ee83b06b607fb5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7c1bf3a9346f208f66ee83b06b607fb5)): ?>
<?php $component = $__componentOriginal7c1bf3a9346f208f66ee83b06b607fb5; ?>
<?php unset($__componentOriginal7c1bf3a9346f208f66ee83b06b607fb5); ?>
<?php endif; ?>

            
            <?php if(session('show_register_link')): ?>
                <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg relative mb-4" role="alert">
                    <strong class="font-bold"><?php echo e(__('توجه!')); ?></strong>
                    <span class="block sm:inline"><?php echo e(session('status')); ?></span>
                    <div class="mt-2 text-center">
                        <a href="<?php echo e(route('auth.register-form', ['mobile_number' => session('user_not_found_mobile')])); ?>" 
                           class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 bg-transparent hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all duration-200 ease-in-out">
                            <?php echo e(__('ثبت‌نام کنید')); ?>

                            <i class="fas fa-user-plus mr-2"></i>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('auth.send-otp')); ?>" class="space-y-6">
                <?php echo csrf_field(); ?>

                <!-- Mobile Number -->
                <div>
                    <label for="mobile_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?php echo e(__('شماره موبایل')); ?>

                    </label>
                    <input id="mobile_number" 
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400" 
                           type="text" 
                           name="mobile_number" 
                           value="<?php echo e(old('mobile_number')); ?>" 
                           placeholder="مثال: 09123456789"
                           required 
                           autofocus>
                    <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('mobile_number'),'class' => 'mt-2 text-sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('mobile_number')),'class' => 'mt-2 text-sm']); ?>
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
                            class="inline-flex items-center justify-center px-6 py-3 text-lg font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300 ease-in-out min-w-[180px]">
                        <?php echo e(__('ارسال کد تأیید')); ?>

                        <i class="fas fa-paper-plane mr-2"></i> 
                    </button>
                </div>
            </form>

            
            <div class="flex items-center justify-center mt-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <?php echo e(__('حساب کاربری ندارید؟')); ?>

                    <a href="<?php echo e(route('auth.register-form')); ?>" class="font-semibold text-green-600 hover:text-green-500 transition-colors duration-200 ease-in-out">
                        <?php echo e(__('ثبت‌نام کنید')); ?>

                    </a>
                </p>
            </div>
        </div>
    </section>

    
    <?php $__env->startPush('scripts'); ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const mobileNumberInput = document.getElementById('mobile_number');

                if (mobileNumberInput) {
                    mobileNumberInput.addEventListener('input', function(event) {
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
    <?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\myshop\resources\views/auth/login.blade.php ENDPATH**/ ?>