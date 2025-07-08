

<?php $__env->startSection('title', 'ویرایش محصول - چای ابراهیم'); ?>

<?php $__env->startSection('content'); ?>
    <section class="container mx-auto px-4 py-8 md:py-16 max-w-4xl">
        <h1 class="text-4xl font-extrabold text-brown-900 mb-12 text-center">
            <i class="fas fa-edit text-green-700 ml-3"></i>
            ویرایش محصول: <?php echo e($product->title); ?>

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

        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
            <form action="<?php echo e(route('products.update', $product->slug)); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?> 

                
                <div class="mb-6">
                    <label for="title" class="block text-gray-700 text-lg font-semibold mb-2">عنوان محصول:</label>
                    <input type="text" id="title" name="title" value="<?php echo e(old('title', $product->title)); ?>"
                           class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-green-700 focus:border-green-700 transition-all duration-200"
                           placeholder="مثال: چای سیاه ممتاز سیلان" required>
                    <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-500 text-sm mt-2"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                
                <div class="mb-6">
                    <label for="description" class="block text-gray-700 text-lg font-semibold mb-2">توضیحات محصول:</label>
                    <textarea id="description" name="description" rows="6"
                              class="form-textarea w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-green-700 focus:border-green-700 transition-all duration-200"
                              placeholder="توضیحات کامل محصول را اینجا بنویسید."><?php echo e(old('description', $product->description)); ?></textarea>
                    <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-500 text-sm mt-2"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="price" class="block text-gray-700 text-lg font-semibold mb-2">قیمت (تومان):</label>
                        <input type="number" id="price" name="price" value="<?php echo e(old('price', $product->price)); ?>" step="0.01" min="0"
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-green-700 focus:border-green-700 transition-all duration-200"
                               placeholder="مثال: 150000" required>
                        <?php $__errorArgs = ['price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-red-500 text-sm mt-2"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label for="stock" class="block text-gray-700 text-lg font-semibold mb-2">موجودی:</label>
                        <input type="number" id="stock" name="stock" value="<?php echo e(old('stock', $product->stock)); ?>" min="0"
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-green-700 focus:border-green-700 transition-all duration-200"
                               placeholder="مثال: 100" required>
                        <?php $__errorArgs = ['stock'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-red-500 text-sm mt-2"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="category_id" class="block text-gray-700 text-lg font-semibold mb-2">دسته‌بندی:</label>
                        <select id="category_id" name="category_id"
                                class="form-select w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-green-700 focus:border-green-700 transition-all duration-200"
                                required>
                            <option value="">انتخاب دسته‌بندی</option>
                            
                            <?php if(isset($categories)): ?>
                                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($category->id); ?>" <?php echo e(old('category_id', $product->category_id) == $category->id ? 'selected' : ''); ?>>
                                        <?php echo e($category->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php else: ?>
                                
                                <option value="1" <?php echo e(old('category_id', $product->category_id) == 1 ? 'selected' : ''); ?>>چای سیاه</option>
                                <option value="2" <?php echo e(old('category_id', $product->category_id) == 2 ? 'selected' : ''); ?>>چای سبز</option>
                                <option value="3" <?php echo e(old('category_id', $product->category_id) == 3 ? 'selected' : ''); ?>>چای سفید</option>
                            <?php endif; ?>
                        </select>
                        <?php $__errorArgs = ['category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-red-500 text-sm mt-2"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label for="status" class="block text-gray-700 text-lg font-semibold mb-2">وضعیت:</label>
                        <select id="status" name="status"
                                class="form-select w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-green-700 focus:border-green-700 transition-all duration-200"
                                required>
                            <option value="1" <?php echo e(old('status', $product->status) == 1 ? 'selected' : ''); ?>>فعال</option>
                            <option value="0" <?php echo e(old('status', $product->status) == 0 ? 'selected' : ''); ?>>غیرفعال</option>
                        </select>
                        <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-red-500 text-sm mt-2"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                
                <div class="mb-6">
                    <label class="block text-gray-700 text-lg font-semibold mb-2">تصویر اصلی فعلی:</label>
                    <?php if($product->image): ?>
                        <div class="flex items-center space-x-4 rtl:space-x-reverse mb-4">
                            <img src="<?php echo e($product->image_url); ?>" alt="تصویر اصلی محصول" class="w-32 h-32 object-cover rounded-lg shadow">
                            <div class="flex items-center">
                                <input type="checkbox" id="remove_image" name="remove_image" value="1"
                                       class="form-checkbox h-5 w-5 text-red-600 rounded focus:ring-red-500">
                                <label for="remove_image" class="ml-2 text-gray-700">حذف تصویر اصلی</label>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 mb-4">تصویر اصلی برای این محصول وجود ندارد.</p>
                    <?php endif; ?>
                    <label for="image" class="block text-gray-700 text-lg font-semibold mb-2">آپلود تصویر اصلی جدید (اختیاری):</label>
                    <input type="file" id="image" name="image"
                           class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-green-700 focus:border-green-700 transition-all duration-200 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 cursor-pointer">
                    <?php $__errorArgs = ['image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-500 text-sm mt-2"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                
                <div class="mb-6">
                    <label class="block text-gray-700 text-lg font-semibold mb-2">تصاویر گالری فعلی:</label>
                    <?php if($product->images->count() > 0): ?>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-4" id="current-gallery-images">
                            <?php $__currentLoopData = $product->images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="relative group border border-gray-200 rounded-lg overflow-hidden shadow-sm">
                                    <img src="<?php echo e($image->image_url); ?>" alt="تصویر گالری" class="w-full h-32 object-cover">
                                    <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        <button type="button" class="remove-gallery-image-btn text-white bg-red-600 hover:bg-red-700 rounded-full p-2 text-lg" data-image-id="<?php echo e($image->id); ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    
                                    <input type="hidden" name="remove_gallery_images[]" class="remove-image-input" value="">
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 mb-4">هیچ تصویر گالری برای این محصول وجود ندارد.</p>
                    <?php endif; ?>

                    <label for="gallery_images" class="block text-gray-700 text-lg font-semibold mb-2">آپلود تصاویر گالری جدید (اختیاری):</label>
                    <input type="file" id="gallery_images" name="gallery_images[]" multiple
                           class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-green-700 focus:border-green-700 transition-all duration-200 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 cursor-pointer">
                    <?php $__errorArgs = ['gallery_images.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-500 text-sm mt-2"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                
                <div class="flex justify-end mt-8">
                    <button type="submit" class="btn-primary flex items-center justify-center px-8 py-3 text-xl font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                        <i class="fas fa-save ml-3"></i> به‌روزرسانی محصول
                    </button>
                </div>
            </form>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const currentGalleryImagesContainer = document.getElementById('current-gallery-images');

            if (currentGalleryImagesContainer) {
                currentGalleryImagesContainer.addEventListener('click', function(event) {
                    if (event.target.closest('.remove-gallery-image-btn')) {
                        const button = event.target.closest('.remove-gallery-image-btn');
                        const imageId = button.dataset.imageId;
                        const imageContainer = button.closest('.relative.group'); // The parent div for the image

                        if (confirm('آیا مطمئن هستید که می‌خواهید این تصویر را حذف کنید؟')) {
                            // Find the hidden input within this image's container
                            const hiddenInput = imageContainer.querySelector('.remove-image-input');
                            if (hiddenInput) {
                                hiddenInput.value = imageId; // Set the ID to be removed
                                // Hide the image visually
                                imageContainer.style.display = 'none';
                            }
                        }
                    }
                });
            }
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\myshop\resources\views\product-edit.blade.php ENDPATH**/ ?>