    // resources/js/cart.js

    document.addEventListener('DOMContentLoaded', function() {
        // تابع کمکی برای نمایش پیام‌ها
        // این تابع می‌تواند در app.js به صورت سراسری تعریف شود
        // اما برای فعلا اینجا تعریف می‌کنیم تا کار کند.
        function showMessage(message, type = 'info', duration = 3000) {
            const messageBox = document.createElement('div');
            messageBox.className = `fixed bottom-5 left-1/2 transform -translate-x-1/2 px-6 py-3 rounded-lg shadow-lg text-white z-50 transition-all duration-300 ease-out message-box ${type === 'success' ? 'bg-green-600' : (type === 'error' ? 'bg-red-600' : 'bg-gray-800')}`;
            messageBox.textContent = message;
            document.body.appendChild(messageBox);

            setTimeout(() => {
                messageBox.classList.add('opacity-0', 'translate-y-full');
                messageBox.addEventListener('transitionend', () => messageBox.remove());
            }, duration);
        }

        // انتخاب تمام دکمه‌های "افزودن به سبد" در صفحه
        const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');

        // اضافه کردن Event Listener به هر دکمه
        addToCartButtons.forEach(button => {
            button.addEventListener('click', async function() {
                // دریافت اطلاعات محصول از ویژگی‌های data- (data-product-id, data-product-title, ...)
                const productId = this.dataset.productId;
                const productTitle = this.dataset.productTitle;
                const productPrice = this.dataset.productPrice;
                // const productImage = this.dataset.productImage; // اگر نیاز به تصویر در سمت بک‌اند برای سبد خرید دارید

                // غیرفعال کردن دکمه و تغییر متن آن در حین ارسال درخواست
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال افزودن...';
                this.disabled = true;

                try {
                    const response = await fetch('/cart/add', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') // دریافت CSRF Token
                        },
                        body: JSON.stringify({
                            product_id: productId,
                            quantity: 1, // فعلا مقدار 1 را به صورت پیش‌فرض ارسال می‌کنیم
                            price: productPrice // ارسال قیمت محصول
                        })
                    });

                    const result = await response.json(); // دریافت پاسخ JSON از سرور

                    if (response.ok) { // اگر درخواست موفقیت‌آمیز بود (کد وضعیت 2xx)
                        showMessage(`"${productTitle}" با موفقیت به سبد خرید اضافه شد.`, 'success');
                        // TODO: در مرحله بعدی تعداد آیتم‌های سبد خرید در نوار ناوبری را به‌روزرسانی می‌کنیم.
                        console.log('Product added to cart:', result);
                    } else { // اگر خطا رخ داد
                        showMessage(result.message || 'خطایی در افزودن محصول به سبد رخ داد.', 'error');
                        console.error('Error adding product to cart:', result);
                    }

                } catch (error) {
                    showMessage('خطا در ارتباط با سرور. لطفاً اتصال اینترنت خود را بررسی کنید.', 'error');
                    console.error('Network or parsing error:', error);
                } finally {
                    // فعال کردن مجدد دکمه و برگرداندن متن اصلی آن
                    this.innerHTML = originalText;
                    this.disabled = false;
                }
            });
        });
    });
    