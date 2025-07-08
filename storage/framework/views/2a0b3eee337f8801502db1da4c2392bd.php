

<?php $__env->startSection('title', 'تایید سفارش - چای ابراهیم'); ?>

<?php $__env->startSection('content'); ?>
<section class="container mx-auto px-4 py-8 md:py-16 max-w-4xl">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 md:p-8 text-center rtl:text-right">
        <i class="fas fa-check-circle text-green-600 text-6xl mb-6 animate-bounce"></i>
        <h1 class="text-4xl font-extrabold text-brown-900 mb-4">سفارش شما با موفقیت ثبت شد!</h1>
        <p class="text-gray-700 text-lg mb-8">از خرید شما متشکریم. جزئیات سفارش شما در زیر آمده است.</p>

        <div class="bg-gray-50 rounded-xl p-6 mb-8 text-right border border-gray-200">
            <h2 class="text-2xl font-bold text-brown-900 mb-4 flex items-center">
                <i class="fas fa-info-circle ml-2 text-green-700"></i>
                اطلاعات سفارش
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
                <p><strong>کد سفارش:</strong> <span class="text-green-700 font-semibold"><?php echo e($order->id); ?></span></p>
                
                <p><strong>تاریخ سفارش:</strong> <?php echo e(App\Helpers\DateHelper::toJalali($order->created_at, 'Y/m/d - H:i')); ?></p>
                <p><strong>وضعیت:</strong> <span class="font-semibold text-orange-600"><?php echo e(__($order->status)); ?></span></p> 
                <p><strong>مبلغ کل:</strong> <span class="font-bold text-brown-900"><?php echo e(number_format($order->total_amount)); ?> تومان</span></p>
            </div>
        </div>

        <div class="bg-gray-50 rounded-xl p-6 mb-8 text-right border border-gray-200">
            <h2 class="text-2xl font-bold text-brown-900 mb-4 flex items-center">
                <i class="fas fa-map-marker-alt ml-2 text-green-700"></i>
                اطلاعات ارسال
            </h2>
            <div class="text-gray-700 leading-relaxed">
                <?php if($order->user): ?> 
                    <p><?php echo e($order->user->name); ?></p>
                    <p><?php echo e($order->user->mobile_number ?? 'شماره تماس ثبت نشده'); ?></p>
                <?php endif; ?>
                <p><?php echo e($order->address); ?></p>
                <p><?php echo e($order->city); ?>, <?php echo e($order->province); ?> - <?php echo e($order->postal_code); ?></p>
                
                
            </div>
        </div>


        <div class="bg-gray-50 rounded-xl p-6 mb-8 text-right border border-gray-200">
            <h2 class="text-2xl font-bold text-brown-900 mb-4 flex items-center">
                <i class="fas fa-boxes ml-2 text-green-700"></i>
                اقلام سفارش
            </h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white rounded-lg overflow-hidden shadow-sm">
                    <thead class="bg-gray-200 text-gray-700 uppercase text-sm leading-normal">
                        <tr>
                            <th class="py-3 px-6 text-right">محصول</th>
                            <th class="py-3 px-6 text-center">تعداد</th>
                            <th class="py-3 px-6 text-center">قیمت واحد</th>
                            <th class="py-3 px-6 text-center">مجموع</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm font-light">
                        <?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="border-b last:border-b-0 hover:bg-gray-100">
                            <td class="py-3 px-6 text-right whitespace-nowrap">
                                <div class="flex items-center">
                                    <img src="<?php echo e($item->product->image ?: 'https://placehold.co/50x50/E5E7EB/4B5563?text=P'); ?>"
                                         onerror="this.onerror=null;this.src='https://placehold.co/50x50/E5E7EB/4B5563?text=P';"
                                         alt="<?php echo e($item->product->title); ?>" class="w-10 h-10 rounded-md object-cover ml-3">
                                    <span><?php echo e($item->product->title); ?></span>
                                </div>
                            </td>
                            <td class="py-3 px-6 text-center"><?php echo e($item->quantity); ?></td>
                            <td class="py-3 px-6 text-center"><?php echo e(number_format($item->price)); ?> تومان</td>
                            <td class="py-3 px-6 text-center font-bold text-brown-800"><?php echo e(number_format($item->quantity * $item->price)); ?> تومان</td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                    <tfoot class="bg-green-700 text-white font-bold text-lg">
                        <tr>
                            <td colspan="3" class="py-3 px-6 text-right">جمع کل سفارش:</td>
                            <td class="py-3 px-6 text-center"><?php echo e(number_format($order->total_amount)); ?> تومان</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="mt-8 flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4 sm:space-x-reverse">
            <a href="<?php echo e(route('home')); ?>" class="btn-primary inline-flex items-center">
                <i class="fas fa-home ml-2"></i>
                بازگشت به صفحه اصلی
            </a>
            
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\myshop\resources\views\order-confirmation.blade.php ENDPATH**/ ?>