

<?php $__env->startSection('title', 'تکمیل و ثبت سفارش - چای ابراهیم'); ?>

<?php $__env->startSection('content'); ?>
<section class="container mx-auto px-4 py-8 md:py-16">
    <h1 class="text-4xl font-extrabold text-brown-900 mb-12 text-center">
        <i class="fas fa-clipboard-check text-green-700 ml-3"></i>
        تکمیل و ثبت سفارش
    </h1>

    
    
    <form id="place-order-form" class="space-y-6" method="POST" novalidate role="form" aria-label="فرم ثبت سفارش"
          data-addresses="<?php echo e(json_encode($addresses ?? [])); ?>"
          data-default-address="<?php echo e(json_encode($defaultAddress ?? null)); ?>">
        
        <?php echo csrf_field(); ?>

        
        <div class="w-full bg-gray-200 rounded-full h-2.5 mb-6 overflow-hidden">
            <div id="progress-bar" class="bg-green-600 h-2.5 rounded-full transition-all duration-500 ease-out" style="width: 0%;"></div>
        </div>

        
        <div id="form-errors-live-region" class="sr-only" aria-live="polite" aria-atomic="true"></div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 p-6 md:p-8 flex flex-col gap-8">
            
            <div class="flex flex-col md:flex-row gap-8 w-full">
                
                
                <div class="md:w-1/2">
                    <h2 class="text-2xl font-semibold text-brown-900 mb-6 flex items-center">
                        <i class="fas fa-map-marker-alt ml-3 text-red-500"></i>
                        اطلاعات ارسال
                    </h2>

                    
                    <?php if($addresses->isNotEmpty()): ?>
                    <div class="mb-6 border border-gray-200 rounded-lg p-4 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">انتخاب آدرس :</h3>
                        <div class="space-y-3" id="address-selection-radios">
                            <?php $__currentLoopData = $addresses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $address): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="flex items-start p-3 border rounded-md cursor-pointer hover:bg-gray-100 transition-colors duration-200
                                    <?php if($address->is_default): ?> border-green-500 bg-green-50 <?php else: ?> border-gray-200 <?php endif; ?>">
                                    <input type="radio" id="address_<?php echo e($address->id); ?>" name="selected_address_id"
                                           value="<?php echo e($address->id); ?>" class="form-radio h-5 w-5 text-green-700 mt-1 cursor-pointer"
                                           <?php if($address->is_default): ?> checked <?php endif; ?>>
                                    <label for="address_<?php echo e($address->id); ?>" class="mr-3 flex-1 cursor-pointer">
                                        <span class="font-medium text-gray-800"><?php echo e($address->title ?: 'آدرس بدون عنوان'); ?></span>
                                        <p class="text-gray-600 text-sm"><?php echo e($address->address); ?></p>
                                        <p class="text-gray-600 text-sm"><?php echo e($address->city); ?>, <?php echo e($address->province); ?> - <?php echo e($address->postal_code); ?></p>
                                        <p class="text-gray-600 text-sm">تلفن: <?php echo e($address->phone_number); ?></p>
                                    </label>
                                    <?php if($address->is_default): ?>
                                        <span class="bg-amber-500 text-white text-xs font-bold px-2 py-1 rounded-full mr-2">پیش‌فرض</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            
                        </div>
                    </div>
                    <?php else: ?>
                        <p class="text-gray-600 text-sm mb-4">شما هنوز آدرسی ثبت نکرده‌اید. لطفاً آدرس خود را از پروفایل کاربری اضافه کنید.</p>
                    <?php endif; ?>

                    
                </div>

                
                
                <div class="md:w-1/2 bg-gray-50 p-6 rounded-lg shadow-inner">
                    <h2 class="text-2xl font-semibold text-brown-900 mb-6 flex items-center justify-end">
                        خلاصه سبد خرید شما
                        <i class="fas fa-shopping-basket ml-3 text-orange-500"></i>
                    </h2>
                    <div class="space-y-4" id="cart-items-summary"> 
                        <?php $__empty_1 = true; $__currentLoopData = $cartItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="flex justify-between items-center border-b pb-4 last:border-b-0 last:pb-0"
                                data-item-id="<?php echo e($item->product->id); ?>"
                                data-item-price="<?php echo e($item->product->price); ?>"
                                data-item-quantity="<?php echo e($item->quantity); ?>">
                                <div class="flex items-center">
                                    
                                    <img src="<?php echo e($item->product->image ?: 'https://placehold.co/60x60/E5E7EB/4B5563?text=Product'); ?>" alt="<?php echo e($item->product->title); ?>" class="w-16 h-16 object-cover rounded-lg ml-3">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800"><?php echo e($item->product->title); ?></h3>
                                        
                                        <div class="flex items-center mt-1">
                                            <button type="button" class="quantity-btn minus-btn bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-full w-6 h-6 flex items-center justify-center text-lg font-bold transition-colors duration-200" aria-label="کاهش تعداد">
                                                -
                                            </button>
                                            <span class="item-quantity mx-2 text-gray-700 text-base font-medium" data-quantity="<?php echo e($item->quantity); ?>">
                                                <?php echo e(number_format($item->quantity)); ?>

                                            </span>
                                            <button type="button" class="quantity-btn plus-btn bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-full w-6 h-6 flex items-center justify-center text-lg font-bold transition-colors duration-200" aria-label="افزایش تعداد">
                                                +
                                            </button>
                                            <span class="mr-2 text-gray-600 text-sm">عدد</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <span class="item-subtotal text-green-700 font-bold text-lg" data-subtotal="<?php echo e($item->price * $item->quantity); ?>">
                                    <?php echo e(number_format($item->price * $item->quantity)); ?> تومان
                                </span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            
                            <p class="text-center text-gray-600 py-10">سبد خرید شما خالی است.</p>
                        <?php endif; ?>
                    </div>
                    <?php if(!$cartItems->isEmpty()): ?>
                        
                        <div class="border-t border-gray-200 pt-4 mt-6 flex justify-between items-center text-xl font-bold text-brown-900">
                            <span>جمع کل:</span>
                            <span id="cart-total-price" class="text-green-700" data-total-price="<?php echo e($cart->getTotalPrice()); ?>">
                                <?php echo e(number_format($cart->getTotalPrice())); ?> تومان
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            
            
            <div class="w-full mt-8 pt-8 border-t border-gray-200">
                <h2 class="text-2xl font-semibold text-brown-900 mb-6 flex items-center">
                    <i class="fas fa-shipping-fast ml-3 text-purple-600"></i>
                    انتخاب روش ارسال
                </h2>
                <div class="space-y-4">
                    
                    <div class="flex items-center">
                        <input type="radio" id="shipping_post" name="shipping_method" value="post" class="form-radio h-5 w-5 text-green-700 focus:ring-green-700" checked required aria-describedby="shipping_method-error">
                        <label for="shipping_post" class="mr-3 text-gray-800 text-lg font-medium">پست پیشتاز</label>
                    </div>
                    <p class="text-gray-600 text-sm mr-8">تحویل ۲-۵ روز کاری. هزینه: ۱۵,۰۰۰ تومان</p>

                    
                    <div class="flex items-center mt-4">
                        <input type="radio" id="shipping_courier" name="shipping_method" value="courier" class="form-radio h-5 w-5 text-green-700 focus:ring-green-700" required aria-describedby="shipping_method-error">
                        <label for="shipping_courier" class="mr-3 text-gray-800 text-lg font-medium">پیک موتوری</label>
                    </div>
                    <p class="text-gray-600 text-sm mr-8">تحویل ۱ روز کاری (فقط تهران). هزینه: ۳۰,۰۰۰ تومان</p>
                    <div id="shipping_method-error" class="text-red-500 text-sm mt-1 hidden" role="alert"></div>

                    
                    <div class="mt-6">
                        <label for="delivery_notes" class="block text-gray-700 text-sm font-bold mb-2">یادداشت برای پیک (اختیاری):</label>
                        <textarea id="delivery_notes" name="delivery_notes" rows="2" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-700 focus:border-green-700 p-2.5" placeholder="مثلاً: تحویل به همسایه طبقه پایین در صورت عدم حضور"></textarea>
                    </div>
                </div>
            </div>

            
            
            <div class="w-full mt-8 pt-8 border-t border-gray-200">
                <h2 class="text-2xl font-semibold text-brown-900 mb-6 flex items-center">
                    <i class="fas fa-wallet ml-3 text-blue-500"></i>
                    روش پرداخت
                </h2>
                <div class="space-y-4">
                    
                    <div class="flex items-center">
                        <input type="radio" id="payment_online" name="payment_method" value="online" class="form-radio h-5 w-5 text-green-700 focus:ring-green-700" checked required aria-describedby="payment_method-error">
                        <label for="payment_online" class="mr-3 text-gray-800 text-lg font-medium">پرداخت آنلاین</label>
                    </div>
                    <p class="text-gray-600 text-sm mr-8">پرداخت از طریق درگاه‌های بانکی امن.</p>

                    
                    <div class="flex items-center mt-4">
                        <input type="radio" id="payment_cod" name="payment_method" value="cod" class="form-radio h-5 w-5 text-green-700 focus:ring-green-700" required aria-describedby="payment_method-error">
                        <label for="payment_cod" class="mr-3 text-gray-800 text-lg font-medium">پرداخت در محل</label>
                    </div>
                    <p class="text-gray-600 text-sm mr-8">پرداخت نقدی یا با کارتخوان هنگام تحویل سفارش.</p>
                    <div id="payment_method-error" class="text-red-500 text-sm mt-1 hidden" role="alert"></div>
                </div>
            </div>

            
            
            <div class="w-full mt-8 pt-6 border-t border-gray-200">
                <div class="flex items-center mb-6">
                    <input type="checkbox" id="terms_agree" name="terms_agree" class="form-checkbox h-5 w-5 text-green-700 rounded focus:ring-green-700" required aria-describedby="terms_agree-error">
                    <label for="terms_agree" class="mr-3 text-gray-700 text-base">
                        <a href="<?php echo e(route('rules')); ?>" class="text-blue-600 hover:underline" target="_blank">قوانین و مقررات</a> را مطالعه کرده و می‌پذیرم.
                    </label>
                </div>
                <div id="terms_agree-error" class="text-red-500 text-sm mt-1 hidden" role="alert"></div>
                
                
                <button type="submit" id="place-order-btn" class="btn-primary w-full flex items-center justify-center" aria-label="ثبت سفارش و پرداخت">
                    ثبت سفارش و پرداخت فرضی
                    <i class="fas fa-credit-card mr-2"></i>
                </button>
            </div>
        </div>
    </form>
</section>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>

<script src="<?php echo e(asset('js/checkout.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\myshop\resources\views/checkout.blade.php ENDPATH**/ ?>