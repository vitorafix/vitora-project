 

<?php $__env->startSection('title', 'داشبورد ویرایشگر - ' . config('app.name', 'چای ابراهیم')); ?>

<?php $__env->startSection('content'); ?>
<div class="container mx-auto px-4 py-8">
    <div class="bg-white shadow-lg rounded-lg p-6 mb-8">
        <h1 class="text-3xl font-bold text-green-800 mb-4">
            <i class="fas fa-tachometer-alt ml-2"></i> داشبورد ویرایشگر
        </h1>
        <p class="text-gray-700 text-lg">
            به پنل مدیریت محتوای خود خوش آمدید، <?php echo e(Auth::user()->name ?? 'کاربر عزیز'); ?>!
        </p>
        <p class="text-gray-600 mt-2">
            از اینجا می‌توانید پست‌ها، دیدگاه‌ها و دسته‌بندی‌ها را مدیریت کنید.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Card 1: مدیریت پست‌ها -->
        <a href="<?php echo e(route('editor.posts.index')); ?>" class="block">
            <div class="bg-white shadow-md rounded-lg p-6 text-center card-hover-effect">
                <i class="fas fa-newspaper text-green-600 text-5xl mb-4"></i>
                <h2 class="text-xl font-semibold text-brown-900 mb-2">مدیریت پست‌ها</h2>
                <p class="text-gray-600">ایجاد، ویرایش و حذف مقالات وبلاگ.</p>
            </div>
        </a>

        <!-- Card 2: مدیریت دیدگاه‌ها -->
        <a href="<?php echo e(route('editor.comments.index')); ?>" class="block">
            <div class="bg-white shadow-md rounded-lg p-6 text-center card-hover-effect">
                <i class="fas fa-comments text-amber-600 text-5xl mb-4"></i>
                <h2 class="text-xl font-semibold text-brown-900 mb-2">مدیریت دیدگاه‌ها</h2>
                <p class="text-gray-600">تایید یا حذف دیدگاه‌های کاربران.</p>
            </div>
        </a>

        <!-- Card 3: مدیریت دسته‌بندی‌ها -->
        <a href="<?php echo e(route('editor.categories.index')); ?>" class="block">
            <div class="bg-white shadow-md rounded-lg p-6 text-center card-hover-effect">
                <i class="fas fa-tags text-blue-600 text-5xl mb-4"></i>
                <h2 class="text-xl font-semibold text-brown-900 mb-2">مدیریت دسته‌بندی‌ها</h2>
                <p class="text-gray-600">افزودن و ویرایش دسته‌بندی‌های محتوا.</p>
            </div>
        </a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.editor', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\myshop\resources\views\editor\dashboard.blade.php ENDPATH**/ ?>