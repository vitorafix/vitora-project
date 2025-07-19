// renderer.js
console.log('renderer.js loaded and starting...'); // اضافه شده برای دیباگ

import { getDOM } from './events.js'; // Import the getDOM function

/**
 * اعتبارسنجی وجود عناصر DOM مورد نیاز قبل از رندر کردن.
 * @param {Array<string>} elementIds - آرایه‌ای از شناسه‌های عناصر DOM که باید وجود داشته باشند.
 * @throws {Error} اگر هر یک از عناصر مورد نیاز یافت نشوند.
 */
function validateElements(elementIds) {
    const DOM = getDOM(); // اطمینان از دسترسی به DOM
    const missingElements = elementIds.filter(id => !DOM[id]);
    if (missingElements.length > 0) {
        console.error(`CRITICAL: Missing DOM elements for rendering: ${missingElements.join(', ')}`); // تغییر به console.error
        throw new Error(`عناصر DOM مورد نیاز یافت نشدند: ${missingElements.join(', ')}`);
    }
}

/**
 * اجرای یک تابع رندرینگ به صورت ایمن، با مدیریت خطاها و نمایش پیام.
 * @param {Function} renderFn - تابع رندرینگ برای اجرا.
 * @param {string} fallbackMessage - پیامی که در صورت بروز خطا نمایش داده می‌شود.
 */
function safeRender(renderFn, fallbackMessage) {
    try {
        renderFn();
    } catch (error) {
        console.error('Render error:', error);
        if (typeof window.showMessage === 'function') {
            window.showMessage(fallbackMessage || 'خطا در به‌روزرسانی رابط کاربری. لطفاً دوباره تلاش کنید.', 'error');
        } else {
            console.warn('window.showMessage is not available to display fallback message.');
        }
    }
}

/**
 * نمایش یا پنهان کردن وضعیت بارگذاری کلی سبد خرید.
 * @param {boolean} isLoading - اگر true باشد وضعیت بارگذاری نمایش داده می‌شود، در غیر این صورت پنهان.
 */
export function setCartLoadingState(isLoading) {
    const loadingOverlay = document.getElementById('cart-loading-overlay'); // فرض بر این است که چنین پوششی دارید
    if (loadingOverlay) {
        loadingOverlay.classList.toggle('hidden', !isLoading);
    }
    // به صورت اختیاری دکمه‌ها یا ورودی‌ها را در حین بارگذاری غیرفعال کنید
}

/**
 * رندر کردن جزئیات مینی‌کارت (نمایش در هدر).
 * @param {Array<Object>} items - آرایه‌ای از آیتم‌های سبد خرید.
 * @param {number} totalQuantity - تعداد کل آیتم‌ها.
 * @param {number} totalPrice - قیمت کل سبد خرید.
 */
export function renderMiniCartDetails(items, totalQuantity, totalPrice) {
    console.log('renderMiniCartDetails called with:', { items, totalQuantity, totalPrice }); // اضافه شده برای دیباگ
    safeRender(() => {
        validateElements(['miniCartItemsContainer', 'miniCartTotalQuantity', 'miniCartTotalPrice', 'miniCartEmptyMessage', 'miniCartSummary']);
        const DOM = getDOM();

        // به‌روزرسانی تعداد کل در آیکون هدر
        if (DOM.miniCartTotalQuantity) {
            DOM.miniCartTotalQuantity.textContent = totalQuantity.toLocaleString('fa-IR'); // فرمت برای نمایش فارسی
            DOM.miniCartTotalQuantity.classList.toggle('hidden', totalQuantity === 0);
        }

        // رندر آیتم‌ها در دراپ‌داون
        DOM.miniCartItemsContainer.innerHTML = ''; // پاک کردن آیتم‌های قبلی

        if (Array.isArray(items) && items.length === 0) {
            DOM.miniCartEmptyMessage.classList.remove('hidden');
            DOM.miniCartSummary.classList.add('hidden');
            DOM.miniCartItemsContainer.classList.add('hidden');
        } else if (Array.isArray(items)) {
            DOM.miniCartEmptyMessage.classList.add('hidden');
            DOM.miniCartSummary.classList.remove('hidden');
            DOM.miniCartItemsContainer.classList.remove('hidden');

            items.forEach(item => {
                // اطمینان از وجود product.image و product.name
                const imageUrl = item.product?.image || 'https://placehold.co/50x50/E0F2F7/000000?text=No+Image';
                const productName = item.product?.name || 'نامشخص';

                const itemHtml = `
                    <div class="flex items-center justify-between py-2 border-b last:border-b-0" 
                        data-cart-item-id="${item.id}"
                        data-product-id="${item.product.id}"
                        data-item-price="${item.unitPrice}">
                        <div class="flex items-center">
                            <img src="${imageUrl}" 
                                 onerror="this.onerror=null;this.src='https://placehold.co/50x50/E0F2F7/000000?text=No+Image';"
                                 alt="${productName}" class="w-12 h-12 object-cover ml-3 rounded">
                            <div>
                                <h4 class="text-sm font-medium text-gray-800">${productName}</h4>
                                <div class="flex items-center mt-1">
                                    <button type="button" class="quantity-btn minus-btn bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-full w-5 h-5 flex items-center justify-center text-sm font-bold transition-colors duration-200" aria-label="کاهش تعداد" data-action="decrease" data-cart-item-id="${item.id}">
                                        -
                                    </button>
                                    <span class="item-quantity mx-1 text-gray-700 text-xs font-medium" data-quantity="${item.quantity}">
                                        ${item.quantity}
                                    </span>
                                    <button type="button" class="quantity-btn plus-btn bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-full w-5 h-5 flex items-center justify-center text-sm font-bold transition-colors duration-200" aria-label="افزایش تعداد" data-action="increase" data-cart-item-id="${item.id}">
                                        +
                                    </button>
                                    <span class="mr-1 text-gray-600 text-xs">x ${item.formattedUnitPrice}</span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="remove-item-btn text-red-500 hover:text-red-700 text-lg" data-cart-item-id="${item.id}" aria-label="حذف آیتم">
                            &times;
                        </button>
                    </div>
                `;
                DOM.miniCartItemsContainer.insertAdjacentHTML('beforeend', itemHtml);
            });

            // به‌روزرسانی قیمت کل در خلاصه دراپ‌داون
            if (DOM.miniCartTotalPrice) {
                DOM.miniCartTotalPrice.textContent = totalPrice.toLocaleString('fa-IR') + ' تومان';
            }
        }
    }, 'خطا در رندر مینی‌کارت.');
}

/**
 * رندر کردن محتویات سبد خرید اصلی در صفحه سبد خرید.
 * @param {Array<Object>} items - آرایه‌ای از آیتم‌های سبد خرید.
 * @param {Object} cartTotals - شیء شامل جزئیات جمع کل سبد خرید (subtotal, shipping, tax, discount, total).
 */
export function renderMainCart(items, cartTotals) {
    console.log('renderMainCart called with:', { items, cartTotals }); // اضافه شده برای دیباگ
    safeRender(() => {
        validateElements(['cartItemsContainer', 'cartEmptyMessage', 'cartSummary', 'cartTotalPrice']);
        const DOM = getDOM();

        DOM.cartItemsContainer.innerHTML = ''; // پاک کردن آیتم‌های قبلی

        if (Array.isArray(items) && items.length === 0) {
            DOM.cartEmptyMessage.classList.remove('hidden');
            DOM.cartItemsContainer.classList.add('hidden');
            DOM.cartSummary.classList.add('hidden');
        } else if (Array.isArray(items)) {
            DOM.cartEmptyMessage.classList.add('hidden');
            DOM.cartItemsContainer.classList.remove('hidden');
            DOM.cartSummary.classList.remove('hidden');

            items.forEach(item => {
                // اطمینان از وجود product.image و product.name
                const imageUrl = item.product?.image || 'https://placehold.co/64x64/E0F2F1/004D40?text=Product';
                const productName = item.product?.name || 'نامشخص';

                const itemHtml = `
                    <div class="flex justify-between items-center border-b pb-4 pt-4 first:pt-0 last:border-b-0 last:pb-0"
                        data-cart-item-id="${item.id}"
                        data-product-id="${item.product.id}"
                        data-item-price="${item.unitPrice}">
                        <div class="flex items-center">
                            <img src="${imageUrl}" 
                                 onerror="this.onerror=null;this.src='https://placehold.co/64x64/E0F2F1/004D40?text=Product';"
                                 alt="${productName}" class="w-16 h-16 object-cover rounded-lg ml-3">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">${productName}</h3>
                                <div class="flex items-center mt-1">
                                    <button type="button" class="quantity-btn minus-btn bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-full w-6 h-6 flex items-center justify-center text-lg font-bold transition-colors duration-200" aria-label="کاهش تعداد" data-action="decrease" data-cart-item-id="${item.id}">
                                        -
                                    </button>
                                    <span class="item-quantity mx-2 text-gray-700 text-base font-medium" data-quantity="${item.quantity}">
                                        ${item.quantity}
                                    </span>
                                    <button type="button" class="quantity-btn plus-btn bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-full w-6 h-6 flex items-center justify-center text-lg font-bold transition-colors duration-200" aria-label="افزایش تعداد" data-action="increase" data-cart-item-id="${item.id}">
                                        +
                                    </button>
                                    <span class="mr-2 text-gray-600 text-sm">عدد</span>
                                </div>
                                <button type="button" class="remove-item-btn text-red-500 hover:text-red-700 transition-colors duration-200 mt-2 text-sm" data-cart-item-id="${item.id}">
                                    <i class="fas fa-trash-alt ml-1"></i> حذف
                                </button>
                            </div>
                        </div>
                        <span class="item-subtotal text-green-700 font-bold text-lg" data-subtotal="${item.totalPrice}">
                            ${item.formattedTotalPrice}
                        </span>
                    </div>
                `;
                DOM.cartItemsContainer.insertAdjacentHTML('beforeend', itemHtml);
            });

            // به‌روزرسانی قیمت کل در خلاصه سبد خرید اصلی
            if (DOM.cartTotalPrice) {
                DOM.cartTotalPrice.textContent = (cartTotals.total ?? 0).toLocaleString('fa-IR') + ' تومان';
            }
            const cartSubtotalElement = document.getElementById('cart-subtotal-price'); 
            if (cartSubtotalElement) {
                cartSubtotalElement.textContent = (cartTotals.subtotal ?? 0).toLocaleString('fa-IR') + ' تومان';
            }
            const cartDiscountElement = document.getElementById('cart-discount-price'); 
            if (cartDiscountElement) {
                cartDiscountElement.textContent = (cartTotals.discount ?? 0).toLocaleString('fa-IR') + ' تومان';
            }
            // ... و برای shipping و tax
            const cartShippingElement = document.getElementById('cart-shipping-price'); 
            if (cartShippingElement) {
                cartShippingElement.textContent = (cartTotals.shipping ?? 0).toLocaleString('fa-IR') + ' تومان';
            }
            const cartTaxElement = document.getElementById('cart-tax-price'); 
            if (cartTaxElement) {
                cartTaxElement.textContent = (cartTotals.tax ?? 0).toLocaleString('fa-IR') + ' تومان';
            }
        }
    }, 'خطا در رندر سبد خرید اصلی.');
}
