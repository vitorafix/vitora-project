

<?php $__env->startSection('title', 'تکمیل و ثبت سفارش - چای ابراهیم'); ?>

<?php $__env->startSection('content'); ?>
<section class="container mx-auto px-4 py-8 md:py-16">
    <h1 class="text-4xl font-extrabold text-brown-900 mb-12 text-center">
        <i class="fas fa-clipboard-check text-green-700 ml-3"></i>
        تکمیل و ثبت سفارش
    </h1>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 p-6 md:p-8 flex flex-col md:flex-row gap-8">
        
        <div class="md:w-1/2">
            <h2 class="text-2xl font-semibold text-brown-900 mb-6 flex items-center">
                <i class="fas fa-map-marker-alt ml-3 text-red-500"></i>
                اطلاعات ارسال
            </h2>
            <form id="place-order-form" class="space-y-6">
                
                <div>
                    <label for="first_name" class="block text-gray-700 text-sm font-bold mb-2">نام:</label>
                    <input type="text" id="first_name" name="first_name" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-700 focus:border-green-700" placeholder="نام کوچک شما" required>
                </div>
                
                <div>
                    <label for="last_name" class="block text-gray-700 text-sm font-bold mb-2">نام خانوادگی:</label>
                    <input type="text" id="last_name" name="last_name" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-700 focus:border-green-700" placeholder="نام خانوادگی شما" required>
                </div>
                
                <div>
                    <label for="phone_number" class="block text-gray-700 text-sm font-bold mb-2">شماره تلفن:</label>
                    <input type="tel" id="phone_number" name="phone_number" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-700 focus:border-green-700" placeholder="مثال: 09123456789" required>
                </div>
                
                <div>
                    <label for="address" class="block text-gray-700 text-sm font-bold mb-2">آدرس کامل:</label>
                    <textarea id="address" name="address" rows="3" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-700 focus:border-green-700" placeholder="مثال: تهران، خیابان آزادی، کوچه اول، پلاک ۱۰" required></textarea>
                </div>
                
                <div>
                    <label for="province" class="block text-gray-700 text-sm font-bold mb-2">استان:</label>
                    <input type="text" id="province" name="province" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-700 focus:border-green-700" placeholder="مثال: تهران" required>
                </div>
                
                <div>
                    <label for="city" class="block text-gray-700 text-sm font-bold mb-2">شهر:</label>
                    <input type="text" id="city" name="city" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-700 focus:border-green-700" placeholder="مثال: تهران" required>
                </div>
                
                <div>
                    <label for="postal_code" class="block text-gray-700 text-sm font-bold mb-2">کد پستی:</label>
                    <input type="text" id="postal_code" name="postal_code" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-700 focus:border-green-700" placeholder="مثال: ۱۲۳۴۵۶۷۸۹۰" required>
                </div>

                <button type="submit" id="place-order-btn" class="btn-primary w-full flex items-center justify-center mt-8">
                    ثبت سفارش و پرداخت فرضی
                    <i class="fas fa-credit-card mr-2"></i>
                </button>
            </form>
        </div>

        
        <div class="md:w-1/2 bg-gray-50 p-6 rounded-lg shadow-inner">
            <h2 class="text-2xl font-semibold text-brown-900 mb-6 flex items-center justify-end">
                خلاصه سبد خرید شما
                <i class="fas fa-shopping-basket ml-3 text-orange-500"></i>
            </h2>
            <div class="space-y-4">
                <?php $__empty_1 = true; $__currentLoopData = $cartItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="flex justify-between items-center border-b pb-4 last:border-b-0 last:pb-0">
                        <div class="flex items-center">
                            <img src="<?php echo e($item->product->image ?: 'https://placehold.co/60x60/E5E7EB/4B5563?text=Product'); ?>" alt="<?php echo e($item->product->title); ?>" class="w-16 h-16 object-cover rounded-lg ml-3">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800"><?php echo e($item->product->title); ?></h3>
                                <p class="text-gray-600 text-sm"><?php echo e(number_format($item->quantity)); ?> عدد</p>
                            </div>
                        </div>
                        <span class="text-green-700 font-bold text-lg"><?php echo e(number_format($item->price * $item->quantity)); ?> تومان</span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="text-center text-gray-600 py-10">سبد خرید شما خالی است.</p>
                <?php endif; ?>
            </div>
            <?php if(!$cartItems->isEmpty()): ?>
                <div class="border-t border-gray-200 pt-4 mt-6 flex justify-between items-center text-xl font-bold text-brown-900">
                    <span>جمع کل:</span>
                    <span class="text-green-700"><?php echo e(number_format($cart->getTotalPrice())); ?> تومان</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const placeOrderForm = document.getElementById('place-order-form');
        const placeOrderBtn = document.getElementById('place-order-btn');

        if (placeOrderForm && placeOrderBtn) {
            console.log('DOMContentLoaded fired on checkout page.');
            console.log('placeOrderForm element:', placeOrderForm);
            console.log('placeOrderBtn element:', placeOrderBtn);
            console.log('Form and button elements found. Attaching event listener.');

            placeOrderForm.addEventListener('submit', async function(event) {
                event.preventDefault(); // جلوگیری از ارسال فرم به صورت پیش‌فرض

                placeOrderBtn.disabled = true; // غیرفعال کردن دکمه برای جلوگیری از کلیک‌های متعدد
                const originalBtnText = placeOrderBtn.innerHTML;
                placeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال ثبت سفارش...'; // تغییر متن دکمه به حالت لودینگ

                const formData = new FormData(placeOrderForm);
                const data = Object.fromEntries(formData.entries()); // تبدیل FormData به یک آبجکت ساده
                
                // گرفتن CSRF Token از تگ meta
                function getCsrfToken() {
                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    return csrfMeta ? csrfMeta.getAttribute('content') : '';
                }

                try {
                    const response = await fetch('/order/place', { // Changed endpoint to /order/place
                        method: 'POST', // متد POST
                        headers: {
                            'Content-Type': 'application/json', // نوع محتوا JSON
                            'X-CSRF-TOKEN': getCsrfToken() // ارسال CSRF Token
                        },
                        body: JSON.stringify(data) // تبدیل آبجکت داده به JSON string
                    });

                    const result = await response.json(); // انتظار پاسخ JSON از سرور

                    if (response.ok) { // اگر کد وضعیت 200-299 باشد
                        // فرض می‌کنیم showMessage یک تابع سراسری است (از app.js)
                        window.showMessage(result.message, 'success'); // Using window.showMessage as defined in app.js
                        // هدایت به صفحه تأیید سفارش
                        if (result.orderId) {
                            setTimeout(() => {
                                window.location.href = `/order/confirmation/${result.orderId}`; // Changed route to /order/confirmation
                            }, 1500); // تأخیر قبل از ریدایرکت برای نمایش پیام موفقیت
                        } else {
                            // اگر orderId برگردانده نشد (سناریوی کمتر محتمل)
                            // می‌توانید اینجا سبد خرید را در فرانت‌اند خالی کنید
                        }
                    } else { // اگر کد وضعیت 4xx یا 5xx باشد
                        // Handle validation errors from the backend (status 422)
                        if (response.status === 422 && result.errors) {
                            let errorMessage = 'لطفاً اطلاعات ورودی را بررسی کنید: <br>';
                            for (const field in result.errors) {
                                errorMessage += `- ${result.errors[field].join(', ')}<br>`;
                            }
                            window.showMessage(errorMessage, 'error', 5000); // Show for longer
                        } else {
                            window.showMessage(result.message || 'خطا در ثبت سفارش. لطفاً دوباره تلاش کنید.', 'error');
                        }
                        console.error('Order placement error:', result);
                    }
                } catch (error) {
                    console.error('Error placing order (network/parsing):', error);
                    window.showMessage('خطا در ارتباط با سرور. لطفاً اتصال اینترنت خود را بررسی کنید.', 'error');
                } finally {
                    // ریست کردن حالت دکمه در هر صورت (موفقیت یا خطا)
                    placeOrderBtn.innerHTML = originalBtnText;
                    placeOrderBtn.disabled = false;
                }
            });
        } else {
            console.log('Could not find placeOrderForm or placeOrderBtn elements on checkout page.');
        }
    });

    // تابع showMessage فرض می‌شود که در app.js تعریف شده و در دسترس است.
    // این تابع اینجا تکرار شده است، اما بهترین روش تعریف آن به صورت سراسری در app.js است.
    // در پروژه فعلی ما، showMessage در app.js تعریف شده است، پس این بخش را می‌توان حذف کرد.
    // function showMessage(message, type = 'info', duration = 3000) {
    //     const messageBox = document.createElement('div');
    //     messageBox.className = `fixed bottom-5 left-1/2 transform -translate-x-1/2 px-6 py-3 rounded-lg shadow-lg text-white z-50 transition-all duration-300 ease-out message-box ${type === 'success' ? 'bg-green-600' : (type === 'error' ? 'bg-red-600' : 'bg-gray-800')}`;
    //     messageBox.textContent = message;
    //     document.body.appendChild(messageBox);

    //     setTimeout(() => {
    //         messageBox.classList.add('opacity-0', 'translate-y-full');
    //         messageBox.addEventListener('transitionend', () => messageBox.remove());
    //     }, duration);
    // }
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\myshop\resources\views/checkout.blade.php ENDPATH**/ ?>