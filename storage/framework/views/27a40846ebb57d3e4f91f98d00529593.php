


<header class="bg-white shadow-md p-4 flex justify-between items-center border-b border-gray-200">
    <div class="text-xl font-semibold text-brown-900">
        <?php echo $__env->yieldContent('title'); ?> 
    </div>
    <div class="flex items-center space-x-reverse space-x-4">
        
        <?php if(auth()->guard()->check()): ?>
            <div class="flex items-center">
                <span class="text-gray-700 text-sm ml-2">
                    <?php echo e(Auth::user()->name ?? 'کاربر'); ?>

                </span>
                <div class="w-8 h-8 bg-amber-400 rounded-full flex items-center justify-center text-green-800 font-bold text-sm">
                    <?php echo e(substr(Auth::user()->name ?? 'U', 0, 1)); ?>

                </div>
            </div>
        <?php endif; ?>
        
        
        <button class="text-gray-600 hover:text-green-800 focus:outline-none lg:hidden">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>
</header>
<?php /**PATH C:\xampp\htdocs\myshop\resources\views\editor\partials\header.blade.php ENDPATH**/ ?>