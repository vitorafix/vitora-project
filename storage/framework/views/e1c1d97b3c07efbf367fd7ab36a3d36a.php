 

<?php $__env->startSection('title', 'لیست پست‌ها - داشبورد ویرایشگر'); ?>

<?php $__env->startSection('content'); ?>
<div class="container mx-auto px-4 py-8">
    <div class="bg-white shadow-lg rounded-lg p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-green-800">
                <i class="fas fa-newspaper ml-2"></i> مدیریت پست‌ها
            </h1>
            <a href="<?php echo e(route('editor.posts.create')); ?>" class="btn-primary">
                <i class="fas fa-plus ml-2"></i> افزودن پست جدید
            </a>
        </div>

        <?php if(session('success')): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo e(session('success')); ?></span>
            </div>
        <?php endif; ?>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-lg overflow-hidden">
                <thead class="bg-green-700 text-white">
                    <tr>
                        <th class="py-3 px-4 text-right">عنوان</th>
                        <th class="py-3 px-4 text-right">دسته‌بندی</th>
                        <th class="py-3 px-4 text-right">وضعیت</th>
                        <th class="py-3 px-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    
                    <?php $__empty_1 = true; $__currentLoopData = [
                        ['title' => 'چگونه چای سبز دم کنیم؟', 'category' => 'نوشیدنی‌ها', 'status' => 'منتشر شده', 'slug' => 'how-to-brew-green-tea'],
                        ['title' => 'تاریخچه چای در ایران', 'category' => 'تاریخچه', 'status' => 'پیش‌نویس', 'slug' => 'history-of-tea-in-iran'],
                        ['title' => 'فواید چای سیاه برای سلامتی', 'category' => 'سلامتی', 'status' => 'منتشر شده', 'slug' => 'benefits-of-black-tea'],
                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $post): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="py-3 px-4"><?php echo e($post['title']); ?></td>
                            <td class="py-3 px-4"><?php echo e($post['category']); ?></td>
                            <td class="py-3 px-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo e($post['status'] == 'منتشر شده' ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800'); ?>">
                                    <?php echo e($post['status']); ?>

                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <a href="<?php echo e(route('editor.posts.edit', ['post' => $post['slug']])); ?>" class="text-blue-600 hover:text-blue-800 mx-1">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <button type="button" class="text-red-600 hover:text-red-800 mx-1 delete-post-btn" data-post-slug="<?php echo e($post['slug']); ?>" data-post-title="<?php echo e($post['title']); ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="4" class="py-4 px-4 text-center text-gray-500">پستی برای نمایش وجود ندارد.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.delete-post-btn').forEach(button => {
            button.addEventListener('click', function() {
                const postSlug = this.dataset.postSlug;
                const postTitle = this.dataset.postTitle;

                // نمایش مدال تایید سفارشی
                window.showConfirmationModal(
                    'حذف پست',
                    `آیا از حذف پست "${postTitle}" مطمئن هستید؟ این عمل غیرقابل بازگشت است.`,
                    function() {
                        // منطق حذف پس از تایید کاربر
                        window.showMessage(`پست "${postTitle}" حذف شد.`, 'success');
                        // اینجا می‌توانید درخواست AJAX برای حذف را ارسال کنید
                        // fetch(`/editor/posts/${postSlug}`, {
                        //     method: 'DELETE',
                        //     headers: {
                        //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        //         'Content-Type': 'application/json'
                        //     }
                        // }).then(response => {
                        //     if (response.ok) {
                        //         window.showMessage('پست با موفقیت حذف شد.', 'success');
                        //         // رفرش صفحه یا حذف ردیف از جدول
                        //         location.reload();
                        //     } else {
                        //         window.showMessage('خطا در حذف پست.', 'error');
                        //     }
                        // }).catch(error => {
                        //     console.error('Error:', error);
                        //     window.showMessage('خطایی رخ داد.', 'error');
                        // });
                    },
                    function() {
                        // منطق لغو عملیات
                        window.showMessage('عملیات حذف لغو شد.', 'info');
                    }
                );
            });
        });
    });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.editor', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\myshop\resources\views\editor\posts\index.blade.php ENDPATH**/ ?>