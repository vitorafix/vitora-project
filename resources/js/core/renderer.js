// resources/js/renderer.js
console.log('renderer.js loaded and starting...');

// این فایل مسئول به‌روزرسانی رابط کاربری (DOM) بر اساس داده‌های سبد خرید است.

// تعریف یک شیء واحد برای نگهداری توابع رندرینگ
export const CartRenderer = {
    /**
     * وضعیت لودینگ سبد خرید را تنظیم می‌کند (مثلاً نمایش اسپینر).
     * @param {boolean} isLoading - اگر true باشد، اسپینر نمایش داده می‌شود.
     */
    setCartLoadingState: function(isLoading) {
        const miniCartToggle = document.getElementById('mini-cart-toggle');
        const mainCartContainer = document.getElementById('cart-page-container'); // فرض می‌کنیم یک کانتینر اصلی برای صفحه سبد خرید دارید

        if (miniCartToggle) {
            if (isLoading) {
                miniCartToggle.classList.add('opacity-50', 'pointer-events-none'); // کم‌رنگ کردن و غیرفعال کردن کلیک
                // می‌توانید یک اسپینر کوچک در اینجا اضافه کنید
            } else {
                miniCartToggle.classList.remove('opacity-50', 'pointer-events-none');
                // اسپینر را حذف کنید
            }
        }

        if (mainCartContainer) {
            if (isLoading) {
                mainCartContainer.classList.add('opacity-50', 'pointer-events-none');
                // می‌توانید یک اورلی با اسپینر بزرگتر روی کل صفحه سبد خرید اضافه کنید
            } else {
                mainCartContainer.classList.remove('opacity-50', 'pointer-events-none');
            }
        }
        console.log('Cart loading state set to:', isLoading);
    },

    /**
     * رندر کردن جزئیات سبد خرید اصلی (صفحه /cart).
     * @param {Array<Object>} items - آرایه‌ای از آیتم‌های سبد خرید.
     * @param {Object} cartTotals - شیء شامل مجموع‌های سبد خرید (subtotal, total, etc.).
     */
    renderMainCart: function(items, cartTotals) {
        // --- لاگ‌های دیباگ عمیق ---
        console.log('renderMainCart: Function called.');
        console.log('renderMainCart: Received items:', items);
        console.log('renderMainCart: Received cartTotals:', cartTotals);
        console.log('renderMainCart: Is items array an array?', Array.isArray(items));
        console.log('renderMainCart: Items array length:', items ? items.length : 'N/A');
        // --- پایان لاگ‌های دیباگ عمیق ---

        const cartItemsContainer = document.getElementById('cart-items-container');
        const cartEmptyMessage = document.getElementById('cart-empty-message');
        const cartSummary = document.getElementById('cart-summary');
        // عناصر خلاصه سبد خرید - ID ها با index.blade.php مطابقت داده شده‌اند.
        const cartTotalPriceElement = document.getElementById('cart-total-price');
        const cartSubtotalPriceElement = document.getElementById('cart-subtotal-price'); 
        const cartDiscountPriceElement = document.getElementById('cart-discount-price'); 
        const cartShippingPriceElement = document.getElementById('cart-shipping-price'); 
        const cartTaxPriceElement = document.getElementById('cart-tax-price'); 

        if (!cartItemsContainer || !cartEmptyMessage || !cartSummary || !cartTotalPriceElement || !cartSubtotalPriceElement || !cartDiscountPriceElement || !cartShippingPriceElement || !cartTaxPriceElement) {
            console.warn('One or more main cart DOM elements not found. Skipping main cart rendering.');
            console.warn('Missing elements:', {
                cartItemsContainer: !!cartItemsContainer,
                cartEmptyMessage: !!cartEmptyMessage,
                cartSummary: !!cartSummary,
                cartTotalPriceElement: !!cartTotalPriceElement,
                cartSubtotalPriceElement: !!cartSubtotalPriceElement,
                cartDiscountPriceElement: !!cartDiscountPriceElement,
                cartShippingPriceElement: !!cartShippingPriceElement,
                cartTaxPriceElement: !!cartTaxPriceElement
            });
            return;
        }

        cartItemsContainer.innerHTML = ''; // پاک کردن آیتم‌های قبلی

        // --- لاگ دیباگ برای شرط خالی بودن ---
        if (!Array.isArray(items) || items.length === 0) {
            console.log('renderMainCart: Condition for empty cart met. Displaying empty message.');
            cartEmptyMessage.classList.remove('hidden');
            cartItemsContainer.classList.add('hidden');
            cartSummary.classList.add('hidden');
            // همچنین مجموع‌ها را صفر کنید
            cartSubtotalPriceElement.textContent = '0 تومان';
            cartDiscountPriceElement.textContent = '0 تومان';
            cartShippingPriceElement.textContent = '0 تومان';
            cartTaxPriceElement.textContent = '0 تومان';
            cartTotalPriceElement.textContent = '0 تومان';
            return;
        }
        // --- پایان لاگ دیباگ برای شرط خالی بودن ---

        console.log('renderMainCart: Cart is NOT empty. Proceeding to render items.'); // DEBUG: Add this line
        cartEmptyMessage.classList.add('hidden');
        cartItemsContainer.classList.remove('hidden');
        cartSummary.classList.remove('hidden');

        items.forEach((item, index) => { // اضافه کردن index برای دیباگ
            console.log(`renderMainCart: Processing item ${index + 1}:`, item); // DEBUG: Add this line
            const itemElement = document.createElement('div');
            itemElement.classList.add('flex', 'items-center', 'justify-between', 'py-4', 'border-b', 'border-gray-200', 'last:border-b-0');
            itemElement.setAttribute('data-cart-item-id', item.id);
            // اطمینان از اینکه item.unitPrice یک عدد معتبر است
            itemElement.setAttribute('data-unit-price', item.unitPrice && !isNaN(item.unitPrice) ? item.unitPrice : 0); // استفاده از unitPrice

            // اصلاح شده: اطمینان از وجود item.product و item.product.image
            const imageUrl = (item.product && item.product.image) ? item.product.image : `https://placehold.co/100x100/E5E7EB/4B5563?text=No+Image`;
            const productName = item.product.name || 'نامشخص'; // استفاده از name به جای title

            itemElement.innerHTML = `
                <div class="flex items-center flex-grow">
                    <img src="${imageUrl}" 
                         onerror="this.onerror=null;this.src='https://placehold.co/100x100/E5E7EB/4B5563?text=No+Image';"
                         alt="${productName}" 
                         class="w-20 h-20 rounded-lg object-cover ml-4 shadow-sm">
                    <div class="flex-grow">
                        <h3 class="text-lg font-semibold text-gray-800">${productName}</h3>
                        ${item.product_variant ? `<p class="text-sm text-gray-500">نوع: ${item.product_variant.name}</p>` : ''}
                        <p class="text-green-700 font-bold mt-1">${new Intl.NumberFormat('fa-IR').format(item.unitPrice)} تومان</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="flex items-center border border-gray-300 rounded-md overflow-hidden ml-4">
                        <button class="quantity-btn p-2 bg-gray-100 hover:bg-gray-200" data-action="decrease" data-cart-item-id="${item.id}">
                            <i class="fas fa-minus text-gray-600"></i>
                        </button>
                        <!-- تغییر از span به input برای قابلیت ویرایش و خواندن مقدار -->
                        <input type="text" value="${item.quantity}" class="w-12 text-center border-x border-gray-300 py-2 focus:outline-none bg-white dark:bg-gray-700 dark:text-white item-quantity" readonly>
                        <button class="quantity-btn p-2 bg-gray-100 hover:bg-gray-200" data-action="increase" data-cart-item-id="${item.id}">
                            <i class="fas fa-plus text-gray-600"></i>
                        </button>
                    </div>
                    <p class="text-gray-800 font-semibold ml-4 item-subtotal" data-subtotal="${item.totalPrice}">${new Intl.NumberFormat('fa-IR').format(item.totalPrice)} تومان</p>
                    <button class="remove-item-btn p-2 text-red-500 hover:text-red-700 transition-colors duration-200" data-cart-item-id="${item.id}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            cartItemsContainer.appendChild(itemElement);
            console.log(`renderMainCart: Appended item ${item.id} to cartItemsContainer.`); // DEBUG: Add this line
        });

        // Update totals
        cartSubtotalPriceElement.textContent = new Intl.NumberFormat('fa-IR').format(cartTotals.subtotal ?? 0) + ' تومان';
        cartDiscountPriceElement.textContent = new Intl.NumberFormat('fa-IR').format(cartTotals.discount ?? 0) + ' تومان';
        cartShippingPriceElement.textContent = new Intl.NumberFormat('fa-IR').format(cartTotals.shipping ?? 0) + ' تومان';
        cartTaxPriceElement.textContent = new Intl.NumberFormat('fa-IR').format(cartTotals.tax ?? 0) + ' تومان';
        // اصلاح شده: استفاده از cartTotals.totalPrice به جای cartTotals.total
        cartTotalPriceElement.textContent = new Intl.NumberFormat('fa-IR').format(cartTotals.totalPrice ?? 0) + ' تومان';
        console.log('Main cart totals updated. Total price:', cartTotalPriceElement.textContent); // DEBUG LOG

        console.log('Main cart rendered successfully.');
    },

    /**
     * رندر کردن جزئیات مینی سبد خرید (در هدر).
     * @param {Array<Object>} items - آرایه‌ای از آیتم‌های سبد خرید.
     * @param {number} totalQuantity - تعداد کل محصولات در سبد خرید.
     * @param {float} totalPrice - قیمت کل محصولات در سبد خرید.
     */
    renderMiniCartDetails: function(items, totalQuantity, totalPrice) {
        console.log('renderMiniCartDetails called with items:', items, 'totalQuantity:', totalQuantity, 'totalPrice:', totalPrice);

        const miniCartItemsContainer = document.getElementById('mini-cart-items-container');
        const miniCartTotalQuantitySpan = document.getElementById('mini-cart-total-quantity');
        const miniCartTotalPriceSpan = document.getElementById('mini-cart-total-price');
        const miniCartEmptyMessage = document.getElementById('mini-cart-empty-message');
        const miniCartSummary = document.getElementById('mini-cart-summary');

        if (!miniCartItemsContainer || !miniCartTotalQuantitySpan || !miniCartTotalPriceSpan || !miniCartEmptyMessage || !miniCartSummary) {
            console.warn('One or more mini cart DOM elements not found. Skipping mini cart rendering.');
            return;
        }

        miniCartItemsContainer.innerHTML = ''; // پاک کردن آیتم‌های قبلی

        if (!Array.isArray(items) || items.length === 0) {
            miniCartEmptyMessage.classList.remove('hidden');
            miniCartItemsContainer.classList.add('hidden');
            miniCartSummary.classList.add('hidden');
            miniCartTotalQuantitySpan.classList.add('hidden');
            miniCartTotalQuantitySpan.textContent = '0';
            miniCartTotalPriceSpan.textContent = '0 تومان';
            console.log('Mini cart is empty. Displaying empty message.');
            return;
        }

        miniCartEmptyMessage.classList.add('hidden');
        miniCartItemsContainer.classList.remove('hidden');
        miniCartSummary.classList.remove('hidden');
        miniCartTotalQuantitySpan.classList.remove('hidden');

        items.forEach(item => {
            const itemElement = document.createElement('div');
            itemElement.classList.add('flex', 'items-center', 'py-2', 'border-b', 'border-gray-100', 'last:border-b-0');
            itemElement.setAttribute('data-cart-item-id', item.id);

            // اصلاح شده: اطمینان از وجود item.product و item.product.image
            const imageUrl = (item.product && item.product.image) ? item.product.image : `https://placehold.co/50x50/E5E7EB/4B5563?text=No+Image`;
            const productName = item.product.name || 'نامشخص'; // استفاده از name به جای title

            itemElement.innerHTML = `
                <img src="${imageUrl}" 
                     onerror="this.onerror=null;this.src='https://placehold.co/50x50/E5E7EB/4B5563?text=No+Image';"
                     alt="${productName}" 
                     class="w-12 h-12 rounded-md object-cover ml-3">
                <div class="flex-grow">
                    <p class="text-sm font-medium text-gray-800">${productName}</p>
                    ${item.product_variant ? `<p class="text-xs text-gray-500">نوع: ${item.product_variant.name}</p>` : ''}
                    <!-- تغییر در اینجا: "تعداد:" را از داخل span خارج کردیم -->
                    <p class="text-xs text-gray-600">تعداد: <span class="mini-cart-item-quantity" data-quantity="${item.quantity}">${item.quantity}</span></p>
                </div>
                <p class="text-sm font-bold text-green-700 mini-cart-item-subtotal" data-subtotal="${item.totalPrice}">${new Intl.NumberFormat('fa-IR').format(item.totalPrice)} تومان</p>
            `;
            miniCartItemsContainer.appendChild(itemElement);
        });

        miniCartTotalQuantitySpan.textContent = totalQuantity;
        miniCartTotalPriceSpan.textContent = new Intl.NumberFormat('fa-IR').format(totalPrice) + ' تومان';

        console.log('Mini cart rendered successfully.');
    }
};

// برای اهداف دیباگینگ: CartRenderer را به صورت سراسری در دسترس قرار دهید
if (typeof window !== 'undefined') {
    window.CartRenderer = CartRenderer;
    console.log('CartRenderer exposed globally for debugging.');
}
