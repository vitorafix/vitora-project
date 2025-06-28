@extends('layouts.app')

@section('title', 'سبد خرید - چای ابراهیم')

@section('content')
<section class="container mx-auto px-4 py-8 md:py-16 max-w-6xl">
    <h1 class="text-4xl font-extrabold text-brown-900 mb-12 text-center">
        <i class="fas fa-shopping-cart text-green-700 ml-3"></i>
        سبد خرید شما
    </h1>

    {{-- Progress Bar --}}
    <div class="flex justify-between items-center bg-gray-100 rounded-full p-2 mb-10 shadow-inner text-sm md:text-base lg:text-lg">
        <div class="flex-1 text-center p-2 rounded-full bg-green-700 text-white font-bold flex items-center justify-center transition-all duration-300">
            <i class="fas fa-check-circle ml-2"></i> تکمیل سفارش
        </div>
        <div class="flex-1 text-center p-2 text-gray-600 font-semibold flex items-center justify-center transition-all duration-300">
            <i class="fas fa-credit-card ml-2"></i> انتخاب شیوه پرداخت
        </div>
        <div class="flex-1 text-center p-2 text-gray-600 font-semibold flex items-center justify-center transition-all duration-300">
            <i class="fas fa-truck ml-2"></i> انتخاب شیوه ارسال
        </div>
        <div class="flex-1 text-center p-2 text-gray-600 font-semibold flex items-center justify-center transition-all duration-300">
            <i class="fas fa-clipboard-check ml-2"></i> تایید سفارش
        </div>
    </div>

    <div id="cart-content" class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 p-6 md:p-8">
        {{-- این بخش توسط JavaScript (renderMainCart) پر می‌شود --}}
        <div id="cart-empty-message" class="text-center py-10 hidden">
            <i class="fas fa-shopping-cart text-gray-400 text-6xl mb-4"></i>
            <p class="text-gray-600 text-xl font-semibold mb-2">سبد خرید شما خالی است.</p>
            <p class="text-gray-500">برای شروع خرید، محصولات مورد علاقه خود را اضافه کنید.</p>
            <a href="{{ url('/products') }}" class="btn-primary mt-6 inline-flex items-center">
                شروع خرید
                <i class="fas fa-arrow-left mr-2"></i>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white hidden" id="cart-table">
                <thead>
                    <tr class="bg-gray-100 text-right text-gray-700 uppercase text-sm leading-normal">
                        <th class="py-3 px-6">عنوان محصول</th>
                        <th class="py-3 px-6 text-center">کد</th>
                        <th class="py-3 px-6 text-center">قیمت واحد</th>
                        <th class="py-3 px-6 text-center">تعداد / واحد</th>
                        <th class="py-3 px-6 text-left">مجموع</th>
                        <th class="py-3 px-6 text-center">حذف</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light" id="cart-table-body">
                    {{-- آیتم‌های سبد خرید اینجا با جاوااسکریپت اضافه می‌شوند --}}
                </tbody>
                <tfoot id="cart-total-row" class="border-t-2 border-green-700 font-bold text-lg hidden">
                    <tr>
                        <td colspan="4" class="py-4 px-6 text-right text-brown-900 bg-green-50 rounded-bl-xl">مجموع کل:</td>
                        <td class="py-4 px-6 text-left text-green-700 bg-green-50 rounded-br-xl">
                            <span id="cart-total-price-footer">0 تومان</span>
                        </td>
                        <td class="py-4 px-6 bg-green-50 rounded-br-xl"></td> {{-- Empty cell for alignment --}}
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="flex flex-col md:flex-row justify-center items-center mt-8 gap-4">
            <button id="checkout-button" class="btn-primary text-white text-lg px-8 py-3 transform transition-transform duration-300 hover:scale-105"
                    style="background-color: #2F855A; box-shadow: 0 4px 10px rgba(47, 133, 90, 0.4);">
                تایید سفارش
                <i class="fas fa-arrow-left mr-2"></i>
            </button>
            <a href="{{ url('/') }}" class="text-gray-600 hover:text-green-700 transition-colors duration-300 flex items-center">
                <i class="fas fa-arrow-right ml-2"></i>
                بازگشت به صفحه اصلی
            </a>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cartEmptyMessage = document.getElementById('cart-empty-message');
        const cartTable = document.getElementById('cart-table');
        const cartTableBody = document.getElementById('cart-table-body');
        const cartTotalPriceFooter = document.getElementById('cart-total-price-footer');
        const cartTotalRow = document.getElementById('cart-total-row');
        // Removed clearCartBtn and continue shopping from here, will re-add them below if needed based on final design decision
        const checkoutButton = document.getElementById('checkout-button');

        // تابع برای رندر کردن آیتم‌های اصلی سبد خرید
        async function renderMainCart() {
            try {
                const response = await fetch('/cart/contents');
                if (!response.ok) {
                    throw new Error('Failed to fetch cart contents.');
                }
                const data = await response.json();

                const cartItems = data.cartItems;
                const totalPrice = data.totalPrice;
                const totalItemsInCart = data.totalItemsInCart;

                cartTableBody.innerHTML = ''; // پاک کردن محتوای فعلی

                if (cartItems.length === 0) {
                    cartEmptyMessage.classList.remove('hidden');
                    cartTable.classList.add('hidden');
                    cartTotalRow.classList.add('hidden');
                    checkoutButton.classList.add('opacity-50', 'pointer-events-none'); // غیرفعال کردن دکمه تسویه حساب
                    // clearCartBtn.disabled = true; // غیرفعال کردن دکمه خالی کردن سبد
                } else {
                    cartEmptyMessage.classList.add('hidden');
                    cartTable.classList.remove('hidden');
                    cartTotalRow.classList.remove('hidden');
                    checkoutButton.classList.remove('opacity-50', 'pointer-events-none'); // فعال کردن دکمه تسویه حساب
                    // clearCartBtn.disabled = false; // فعال کردن دکمه خالی کردن سبد

                    cartItems.forEach(item => {
                        // Placeholder for product SKU/Code as it's not in the Product model
                        const productCode = item.product.sku || 'N/A'; // Assuming a 'sku' field or fallback

                        const row = document.createElement('tr');
                        row.classList.add('border-b', 'border-gray-200', 'hover:bg-gray-50');
                        row.innerHTML = `
                            <td class="py-4 px-6 text-right flex items-center">
                                <img src="${item.product.image ? '{{ asset('/') }}' + item.product.image : 'https://placehold.co/60x60/E5E7EB/4B5563?text=Product'}" alt="${item.product.title}" class="w-16 h-16 object-cover rounded-md ml-4">
                                <span class="font-semibold text-brown-900">${item.product.title}</span>
                            </td>
                            <td class="py-4 px-6 text-center text-gray-500 text-sm">${productCode}</td>
                            <td class="py-4 px-6 text-center">${Number(item.price).toLocaleString()} تومان</td>
                            <td class="py-4 px-6 text-center">
                                <div class="flex items-center justify-center space-x-2 space-x-reverse">
                                    <button class="quantity-btn bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-full w-8 h-8 flex items-center justify-center" data-id="${item.id}" data-action="decrease">-</button>
                                    <span class="quantity-display text-lg font-bold">${item.quantity}</span>
                                    <button class="quantity-btn bg-green-500 text-white hover:bg-green-600 rounded-full w-8 h-8 flex items-center justify-center" data-id="${item.id}" data-action="increase">+</button>
                                    <span class="mr-2 text-gray-600">بسته</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-left font-semibold">${Number(item.quantity * item.price).toLocaleString()} تومان</td>
                            <td class="py-4 px-6 text-center">
                                <button class="remove-item-btn text-red-500 hover:text-red-700 transition-colors duration-300" data-id="${item.id}">
                                    <i class="fas fa-times text-lg"></i> {{-- Changed icon to 'X' --}}
                                </button>
                            </td>
                        `;
                        cartTableBody.appendChild(row);
                    });

                    cartTotalPriceFooter.textContent = `${Number(totalPrice).toLocaleString()} تومان`;
                }

                // به‌روزرسانی تعداد آیتم‌ها در مینی سبد (ناوبار)
                updateMiniCartCount(totalItemsInCart);

            } catch (error) {
                console.error('Error rendering main cart:', error);
                window.showMessage('خطا در بارگذاری سبد خرید. لطفا صفحه را رفرش کنید.', 'error');
            }
        }

        // تابع برای به‌روزرسانی تعداد آیتم‌ها در مینی سبد (ناوبار)
        function updateMiniCartCount(count) {
            const miniCartCountElement = document.getElementById('mini-cart-count');
            if (miniCartCountElement) {
                miniCartCountElement.textContent = count;
                if (count > 0) {
                    miniCartCountElement.classList.remove('hidden');
                } else {
                    miniCartCountElement.classList.add('hidden');
                }
            }
        }

        // Event Listeners برای دکمه‌های افزایش/کاهش و حذف آیتم
        cartTableBody.addEventListener('click', async function(event) {
            const button = event.target.closest('.quantity-btn');
            if (button) {
                const itemId = button.dataset.id;
                const action = button.dataset.action;
                let currentQuantityElement = button.closest('.flex').querySelector('.quantity-display');
                let currentQuantity = parseInt(currentQuantityElement.textContent);
                let newQuantity;

                if (action === 'increase') {
                    newQuantity = currentQuantity + 1;
                } else {
                    newQuantity = currentQuantity - 1;
                }

                if (newQuantity < 0) return;

                try {
                    const response = await fetch(`/cart/update/${itemId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ quantity: newQuantity })
                    });
                    const result = await response.json();

                    if (response.ok) {
                        window.showMessage(result.message, 'success');
                        renderMainCart();
                    } else {
                        window.showMessage(result.message || 'خطا در بروزرسانی تعداد محصول.', 'error');
                    }
                } catch (error) {
                    console.error('Error updating cart item:', error);
                    window.showMessage('خطا در ارتباط با سرور. لطفا اتصال اینترنت خود را بررسی کنید.', 'error');
                }
            } else if (event.target.closest('.remove-item-btn')) {
                const removeButton = event.target.closest('.remove-item-btn');
                const itemId = removeButton.dataset.id;

                // جایگزینی confirm با یک مدال سفارشی یا منطق دیگر در آینده (به دلیل محدودیت‌های iframe)
                if (!confirm('آیا مطمئن هستید که می‌خواهید این محصول را از سبد خرید حذف کنید؟')) {
                    return;
                }

                try {
                    const response = await fetch(`/cart/remove/${itemId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    const result = await response.json();

                    if (response.ok) {
                        window.showMessage(result.message, 'success');
                        renderMainCart();
                    } else {
                        window.showMessage(result.message || 'خطا در حذف محصول از سبد.', 'error');
                    }
                } catch (error) {
                    console.error('Error removing cart item:', error);
                    window.showMessage('خطا در ارتباط با سرور. لطفا اتصال اینترنت خود را بررسی کنید.', 'error');
                }
            }
        });

        // Event Listener برای دکمه خالی کردن سبد (اگر در طراحی جدید لازم است)
        // این دکمه از طراحی جدید حذف شده، اما منطق آن را نگه می‌داریم اگر بعداً اضافه شود.
        // if (clearCartBtn) {
        //     clearCartBtn.addEventListener('click', async function() {
        //         if (!confirm('آیا مطمئن هستید که می‌خواهید کل سبد خرید را خالی کنید؟')) {
        //             return;
        //         }
        //         try {
        //             const response = await fetch('/cart/clear', {
        //                 method: 'POST',
        //                 headers: {
        //                     'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        //                 }
        //             });
        //             const result = await response.json();
        //             if (response.ok) {
        //                 window.showMessage(result.message, 'success');
        //                 renderMainCart();
        //             } else {
        //                 window.showMessage(result.message || 'خطا در خالی کردن سبد خرید.', 'error');
        //             }
        //         } catch (error) {
        //             console.error('Error clearing cart:', error);
        //             window.showMessage('خطا در ارتباط با سرور. لطفا اتصال اینترنت خود را بررسی کنید.', 'error');
        //         }
        //     });
        // }

        // فراخوانی اولیه برای لود شدن سبد خرید هنگام ورود به صفحه
        renderMainCart();
    });
</script>
@endpush
