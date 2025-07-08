

<?php $__env->startSection('title', 'محصولات ما - چای ابراهیم'); ?>

<?php $__env->startSection('content'); ?>
    <section class="container mx-auto px-4 py-8 md:py-16 max-w-6xl">
        <h1 class="text-4xl font-extrabold text-brown-900 mb-12 text-center">
            <i class="fas fa-box-open text-green-700 ml-3"></i>
            محصولات ما
        </h1>

        
        <?php if(session('success')): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">موفقیت!</strong>
                <span class="block sm:inline"><?php echo e(session('success')); ?></span>
            </div>
        <?php endif; ?>

        <?php if(session('error')): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">خطا!</strong>
                <span class="block sm:inline"><?php echo e(session('error')); ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 card-hover-effect">
                    
                    
                    <a href="<?php echo e(route('products.show', $product->slug ?? $product->id)); ?>">
                        
                        <img src="<?php echo e($product->image_url); ?>"
                             alt="<?php echo e($product->title); ?>"
                             class="w-full h-48 object-cover transition-transform duration-300 hover:scale-105"
                             onerror="this.onerror=null;this.src='https://placehold.co/400x400/E5E7EB/4B5563?text=Product';">
                    </a>

                    <div class="p-5 text-center rtl:text-right">
                        <h3 class="text-xl font-semibold text-brown-900 mb-2 truncate">
                            
                            
                            <a href="<?php echo e(route('products.show', $product->slug ?? $product->id)); ?>" class="hover:text-green-700 transition-colors duration-200">
                                <?php echo e($product->title); ?>

                            </a>
                        </h3>
                        <?php if($product->category): ?>
                            <p class="text-sm text-gray-500 mb-2">دسته‌بندی: <?php echo e($product->category->name); ?></p>
                        <?php endif; ?>
                        <p class="text-green-700 font-bold text-2xl mb-4">
                            <?php echo e(number_format($product->price)); ?> تومان
                        </p>
                        <?php if($product->stock > 0): ?>
                            <button class="add-to-cart-btn btn-primary w-full py-2 flex items-center justify-center text-lg"
                                    data-product-id="<?php echo e($product->id); ?>"
                                    data-product-title="<?php echo e($product->title); ?>"
                                    data-product-price="<?php echo e($product->price); ?>"
                                    data-product-image="<?php echo e($product->image_url); ?>">
                                <i class="fas fa-cart-plus ml-2"></i>
                                افزودن به سبد
                            </button>
                        <?php else: ?>
                            <button class="btn-disabled w-full py-2 flex items-center justify-center text-lg" disabled>
                                <i class="fas fa-ban ml-2"></i>
                                ناموجود
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="col-span-full text-center py-10">
                    <p class="text-gray-600 text-lg">
                        متاسفانه هیچ محصولی برای نمایش یافت نشد.
                    </p>
                    <a href="<?php echo e(route('home')); ?>" class="mt-4 inline-block btn-secondary">بازگشت به صفحه اصلی</a>
                </div>
            <?php endif; ?>
        </div>

        
        <div class="mt-10 flex justify-center">
            <?php echo e($products->links()); ?>

        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\myshop\resources\views/products.blade.php ENDPATH**/ ?>