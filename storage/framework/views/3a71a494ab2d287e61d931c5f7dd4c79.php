<?php $__env->startSection('title', $product->title . ' - چای ابراهیم'); ?>

<?php $__env->startSection('content'); ?>
<section class="container mx-auto px-4 py-8 md:py-16">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden md:flex md:items-center">
        
        <div class="md:w-1/2 p-6 md:p-8 flex justify-center items-center">
            <img src="<?php echo e($product->image ?: 'https://placehold.co/600x600/E5E7EB/4B5563?text=Product'); ?>" alt="<?php echo e($product->title); ?>" class="w-full max-w-md h-auto rounded-lg shadow-md object-cover transition-transform duration-300 hover:scale-105">
        </div>

        
        <div class="md:w-1/2 p-6 md:p-8 text-right flex flex-col justify-center">
            <h1 class="text-4xl font-extrabold text-brown-900 mb-4"><?php echo e($product->title); ?></h1>
            <p class="text-green-700 text-2xl font-bold mb-6"><?php echo e(number_format($product->price)); ?> تومان</p>

            <div class="mb-6">
                <h3 class="text-xl font-semibold text-brown-900 mb-2">توضیحات محصول:</h3>
                <p class="text-gray-700 leading-relaxed"><?php echo e($product->description); ?></p>
            </div>

            <div class="mb-6 text-gray-800">
                <p class="mb-2"><span class="font-semibold">دسته‌بندی:</span> <?php echo e($product->category->name); ?></p>
                <p><span class="font-semibold">موجودی:</span>
                    <?php if($product->stock > 0): ?>
                        <span class="text-green-600"><?php echo e($product->stock); ?> عدد موجود</span>
                    <?php else: ?>
                        <span class="text-red-600">ناموجود</span>
                    <?php endif; ?>
                </p>
            </div>

            
            <div class="flex items-center justify-end space-x-4 space-x-reverse mt-6">
                <input type="number" id="product-quantity" value="1" min="1" max="<?php echo e($product->stock); ?>" class="w-20 p-2 border border-gray-300 rounded-md text-center focus:outline-none focus:ring-2 focus:ring-green-700">
                <button class="btn-primary add-to-cart-btn flex items-center"
                        data-product-id="<?php echo e($product->id); ?>"
                        data-product-title="<?php echo e($product->title); ?>"
                        data-product-price="<?php echo e($product->price); ?>"
                        data-product-image="<?php echo e($product->image ?: 'https://placehold.co/400x400/E5E7EB/4B5563?text=Product'); ?>">
                    <i class="fas fa-cart-plus ml-2"></i>
                    افزودن به سبد
                </button>
            </div>

            
            <?php if($product->stock <= 0): ?>
                <p class="text-red-500 text-sm mt-4 text-right">این محصول در حال حاضر ناموجود است.</p>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const quantityInput = document.getElementById('product-quantity');
                        const addToCartBtn = document.querySelector('.add-to-cart-btn');
                        if (quantityInput) quantityInput.disabled = true;
                        if (addToCartBtn) addToCartBtn.disabled = true;
                    });
                </script>
            <?php endif; ?>

            
            <div class="mt-8 text-right">
                <a href="<?php echo e(url('/products')); ?>" class="text-green-800 hover:underline flex items-center justify-end">
                    <i class="fas fa-arrow-right ml-2"></i>
                    بازگشت به لیست محصولات
                </a>
            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\myshop\resources\views\products\show.blade.php ENDPATH**/ ?>