

<?php $__env->startSection('title', $product->title . ' - چای ابراهیم'); ?>

<?php $__env->startSection('content'); ?>
    <section class="container mx-auto px-4 py-8 md:py-16 max-w-6xl">
        <nav class="text-sm text-gray-500 mb-6 rtl:text-right" aria-label="breadcrumb">
            <ol class="list-none p-0 inline-flex">
                <li class="flex items-center">
                    <a href="<?php echo e(route('home')); ?>" class="text-green-700 hover:text-green-900">خانه</a>
                    <i class="fas fa-angle-left mx-2"></i>
                </li>
                <li class="flex items-center">
                    <a href="<?php echo e(route('products.index')); ?>" class="text-green-700 hover:text-green-900">محصولات</a>
                    <i class="fas fa-angle-left mx-2"></i>
                </li>
                <li class="flex items-center">
                    <span class="text-gray-900"><?php echo e($product->title); ?></span>
                </li>
            </ol>
        </nav>

        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 md:p-8 flex flex-col lg:flex-row gap-8 lg:gap-12 items-start">
            
            <div class="lg:w-1/2 w-full flex justify-center items-center rounded-xl overflow-hidden shadow-md">
                <img src="<?php echo e($product->image ? asset($product->image) : 'https://placehold.co/600x600/E5E7EB/4B5563?text=No+Image'); ?>"
                     onerror="this.onerror=null;this.src='https://placehold.co/600x600/E5E7EB/4B5563?text=No+Image';"
                     alt="<?php echo e($product->title); ?>"
                     class="w-full h-auto max-h-[500px] object-contain rounded-xl">
            </div>

            
            <div class="lg:w-1/2 w-full space-y-6 rtl:text-right">
                <h1 class="text-4xl font-extrabold text-brown-900 leading-tight"><?php echo e($product->title); ?></h1>
                
                <?php if($product->category): ?>
                    <p class="text-sm text-gray-600">
                        دسته‌بندی: <a href="#" class="text-green-700 hover:underline font-semibold"><?php echo e($product->category->name); ?></a>
                    </p>
                <?php endif; ?>

                <div class="flex items-center text-3xl font-bold text-green-700">
                    <span class="ml-2"><?php echo e(number_format($product->price)); ?></span>
                    <span>تومان</span>
                </div>

                <p class="text-gray-700 leading-relaxed text-base">
                    <?php echo e($product->description); ?>

                </p>

                
                <div class="text-lg font-semibold flex items-center">
                    <?php if($product->stock > 0): ?>
                        <span class="text-green-600 flex items-center">
                            <i class="fas fa-check-circle ml-2"></i>
                            موجود در انبار: <?php echo e(number_format($product->stock)); ?> عدد
                        </span>
                    <?php else: ?>
                        <span class="text-red-600 flex items-center">
                            <i class="fas fa-times-circle ml-2"></i>
                            ناموجود
                        </span>
                    <?php endif; ?>
                </div>

                
                <div class="mt-8">
                    <?php if($product->stock > 0): ?>
                        <button class="add-to-cart-btn btn-primary w-full flex items-center justify-center py-3 text-lg"
                                data-product-id="<?php echo e($product->id); ?>"
                                data-product-title="<?php echo e($product->title); ?>"
                                data-product-price="<?php echo e($product->price); ?>">
                            <i class="fas fa-cart-plus ml-3"></i>
                            افزودن به سبد خرید
                        </button>
                    <?php else: ?>
                        <button class="btn-disabled w-full flex items-center justify-center py-3 text-lg" disabled>
                            <i class="fas fa-ban ml-3"></i>
                            ناموجود
                        </button>
                    <?php endif; ?>
                </div>

                
                <div class="mt-12 border-t pt-8 border-gray-200">
                    <h2 class="text-2xl font-bold text-brown-900 mb-6">دیدگاه‌ها</h2>
                    <p class="text-gray-600">هنوز دیدگاهی برای این محصول ثبت نشده است. اولین دیدگاه را شما بنویسید!</p>
                    
                </div>
            </div>
        </div>

        
        <?php if($relatedProducts->isNotEmpty()): ?> 
            <div class="mt-16">
                <h2 class="text-3xl font-bold text-brown-900 text-center mb-8">محصولات مرتبط</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <?php $__currentLoopData = $relatedProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $relatedProduct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 p-4 text-center card-hover-effect">
                            <a href="<?php echo e(route('products.show', $relatedProduct->id)); ?>">
                                <img src="<?php echo e($relatedProduct->image ?: 'https://placehold.co/300x300/E5E7EB/4B5563?text=Related+Product'); ?>"
                                     onerror="this.onerror=null;this.src='https://placehold.co/300x300/E5E7EB/4B5563?text=Related+Product';"
                                     alt="<?php echo e($relatedProduct->title); ?>"
                                     class="w-full h-40 object-cover mb-4 rounded-lg transition-transform duration-300 hover:scale-105">
                            </a>
                            <h3 class="text-lg font-semibold text-brown-900 mb-1 truncate">
                                <a href="<?php echo e(route('products.show', $relatedProduct->id)); ?>" class="hover:text-green-700 transition-colors duration-200">
                                    <?php echo e($relatedProduct->title); ?>

                                </a>
                            </h3>
                            <p class="text-green-700 font-bold"><?php echo e(number_format($relatedProduct->price)); ?> تومان</p>
                            <button class="add-to-cart-btn btn-secondary text-sm mt-3 flex items-center justify-center w-full"
                                    data-product-id="<?php echo e($relatedProduct->id); ?>"
                                    data-product-title="<?php echo e($relatedProduct->title); ?>"
                                    data-product-price="<?php echo e($relatedProduct->price); ?>">
                                <i class="fas fa-cart-plus ml-1"></i>
                                افزودن به سبد
                            </button>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\myshop\resources\views/product-single.blade.php ENDPATH**/ ?>