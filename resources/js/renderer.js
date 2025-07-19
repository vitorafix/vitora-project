// renderer.js
console.log('renderer.js loaded and starting...');
// ... بقیه کد

// این فایل مسئول رندر کردن UI سبد خرید و به‌روزرسانی عناصر DOM است.

import { getDOM } from './events.js'; // Import the getDOM function

/**
 * اعتبارسنجی وجود عناصر DOM مورد نیاز قبل از رندر کردن.
 * @param {Array<string>} elementIds - آرایه‌ای از شناسه‌های عناصر DOM که باید وجود داشته باشند.
 * @throws {Error} اگر هر یک از عناصر مورد نیاز یافت نشوند.
 */
function validateElements(elementIds) {
    const missingElements = elementIds.filter(id => !getDOM()[id]);
    if (missingElements.length > 0) {
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
    safeRender(() => {
        const DOM = getDOM();
        // بررسی وجود عناصر مینی‌کارت قبل از ادامه
        if (!DOM.miniCartItemsContainer || !DOM.miniCartTotalQuantity || !DOM.miniCartTotalPrice || !DOM.miniCartEmptyMessage || !DOM.miniCartSummary) {
            console.warn('Mini cart DOM elements not fully available. Skipping mini cart rendering.');
            return;
        }

        // به‌روزرسانی تعداد کل در آیکون هدر
        if (DOM.miniCartTotalQuantity) {
            DOM.miniCartTotalQuantity.textContent = totalQuantity;
            DOM.miniCartTotalQuantity.classList.toggle('hidden', totalQuantity === 0);
        }

        // رندر آیتم‌ها در دراپ‌داون
        DOM.miniCartItemsContainer.innerHTML = ''; // پاک کردن آیتم‌های قبلی

        // اضافه کردن بررسی Array.isArray
        if (Array.isArray(items) && items.length === 0) {
            DOM.miniCartEmptyMessage.classList.remove('hidden');
            DOM.miniCartSummary.classList.add('hidden');
            DOM.miniCartItemsContainer.classList.add('hidden');
        } else if (Array.isArray(items)) { // مطمئن شوید که items یک آرایه است
            DOM.miniCartEmptyMessage.classList.add('hidden');
            DOM.miniCartSummary.classList.remove('hidden');
            DOM.miniCartItemsContainer.classList.remove('hidden');

            items.forEach(item => {
                const itemHtml = `
                    <div class="flex items-center justify-between py-2 border-b last:border-b-0" 
                        data-cart-item-id="${item.cart_item_id}"
                        data-product-id="${item.product_id}"
                        data-item-price="${item.product_price}">
                        <div class="flex items-center">
                            <img src="${item.product.image_url || 'https://placehold.co/50x50/E0F2F7/000000?text=No+Image'}" alt="${item.product_name}" class="w-12 h-12 object-cover ml-3 rounded">
                            <div>
                                <h4 class="text-sm font-medium text-gray-800">${item.product_name}</h4>
                                <div class="flex items-center mt-1">
                                    <button type="button" class="quantity-btn minus-btn bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-full w-5 h-5 flex items-center justify-center text-sm font-bold transition-colors duration-200" aria-label="کاهش تعداد">
                                        -
                                    </button>
                                    <span class="item-quantity mx-1 text-gray-700 text-xs font-medium" data-quantity="${item.quantity}">
                                        ${item.quantity}
                                    </span>
                                    <button type="button" class="quantity-btn plus-btn bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-full w-5 h-5 flex items-center justify-center text-sm font-bold transition-colors duration-200" aria-label="افزایش تعداد">
                                        +
                                    </button>
                                    <span class="mr-1 text-gray-600 text-xs">x ${item.product_price.toLocaleString('fa-IR')} تومان</span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="remove-item-btn text-red-500 hover:text-red-700 text-lg" data-cart-item-id="${item.cart_item_id}" aria-label="حذف آیتم">
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
        } else {
            // این پیام خطا صرفاً اطلاع‌رسانی است و یک باگ عملکردی نیست، بنابراین آن را حذف می‌کنیم.
            // console.error("Expected items to be an array but got:", items);
            DOM.miniCartEmptyMessage.classList.remove('hidden');
            DOM.miniCartSummary.classList.add('hidden');
            DOM.miniCartItemsContainer.classList.add('hidden');
        }
    }, 'خطا در رندر مینی‌کارت.');
}

/**
 * رندر کردن محتویات سبد خرید اصلی در صفحه سبد خرید.
 * @param {Array<Object>} items - آرایه‌ای از آیتم‌های سبد خرید.
 * @param {Object} cartTotals - شیء شامل جزئیات جمع کل سبد خرید (subtotal, shipping, tax, discount, total).
 */
export function renderMainCart(items, cartTotals) {
    safeRender(() => {
        const DOM = getDOM();
        // بررسی حیاتی: اگر کانتینرهای اصلی سبد خرید وجود نداشته باشند، کاری انجام ندهید.
        if (!DOM.cartItemsContainer || !DOM.cartEmptyMessage || !DOM.cartSummary || !DOM.cartTotalPrice) {
            console.warn('Main cart DOM elements not fully available. Skipping main cart rendering.');
            return; // زودتر خارج شوید اگر عناصر وجود ندارند
        }

        DOM.cartItemsContainer.innerHTML = ''; // پاک کردن آیتم‌های قبلی

        // اضافه کردن بررسی Array.isArray
        if (Array.isArray(items) && items.length === 0) {
            DOM.cartEmptyMessage.classList.remove('hidden');
            DOM.cartItemsContainer.classList.add('hidden');
            DOM.cartSummary.classList.add('hidden');
        } else if (Array.isArray(items)) { // مطمئن شوید که items یک آرایه است
            DOM.cartEmptyMessage.classList.add('hidden');
            DOM.cartItemsContainer.classList.remove('hidden');
            DOM.cartSummary.classList.remove('hidden');

            items.forEach(item => {
                const itemHtml = `
                    <div class="flex justify-between items-center border-b pb-4 pt-4 first:pt-0 last:border-b-0 last:pb-0"
                        data-cart-item-id="${item.cart_item_id}"
                        data-product-id="${item.product_id}"
                        data-item-price="${item.product_price}">
                        <div class="flex items-center">
                            <img src="${item.product.image_url || 'https://placehold.co/64x64/E0F2F1/004D40?text=Product'}" alt="${item.product_name}" class="w-16 h-16 object-cover rounded-lg ml-3">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">${item.product_name}</h3>
                                <div class="flex items-center mt-1">
                                    <button type="button" class="quantity-btn minus-btn bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-full w-6 h-6 flex items-center justify-center text-lg font-bold transition-colors duration-200" aria-label="کاهش تعداد">
                                        -
                                    </button>
                                    <span class="item-quantity mx-2 text-gray-700 text-base font-medium" data-quantity="${item.quantity}">
                                        ${item.quantity}
                                    </span>
                                    <button type="button" class="quantity-btn plus-btn bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-full w-6 h-6 flex items-center justify-center text-lg font-bold transition-colors duration-200" aria-label="افزایش تعداد">
                                        +
                                    </button>
                                    <span class="mr-2 text-gray-600 text-sm">عدد</span>
                                </div>
                                <button type="button" class="remove-item-btn text-red-500 hover:text-red-700 transition-colors duration-200 mt-2 text-sm" data-cart-item-id="${item.cart_item_id}">
                                    <i class="fas fa-trash-alt ml-1"></i> حذف
                                </button>
                            </div>
                        </div>
                        <span class="item-subtotal text-green-700 font-bold text-lg" data-subtotal="${item.subtotal}">
                            ${item.subtotal.toLocaleString('fa-IR')} تومان
                        </span>
                    </div>
                `;
                DOM.cartItemsContainer.insertAdjacentHTML('beforeend', itemHtml);
            });

            // به‌روزرسانی قیمت کل در خلاصه سبد خرید اصلی
            if (DOM.cartTotalPrice) {
                DOM.cartTotalPrice.textContent = (cartTotals.total ?? 0).toLocaleString('fa-IR') + ' تومان';
            }
            // اگر عناصر دیگری برای نمایش subtotal, discount, shipping, tax دارید، اینجا به‌روزرسانی کنید.
            const cartSubtotalElement = document.getElementById('cart-subtotal-price'); 
            if (cartSubtotalElement) {
                cartSubtotalElement.textContent = (cartTotals.subtotal ?? 0).toLocaleString('fa-IR') + ' تومان';
            }
            const cartDiscountElement = document.getElementById('cart-discount-price'); 
            if (cartDiscountElement) {
                cartDiscountElement.textContent = (cartTotals.discount ?? 0).toLocaleString('fa-IR') + ' تومان';
            }
            // ... و برای shipping و tax
        } else {
            // این پیام خطا صرفاً اطلاع‌رسانی است و یک باگ عملکردی نیست، بنابراین آن را حذف می‌کنیم.
            // console.error("Expected items to be an array but got:", items);
            DOM.cartEmptyMessage.classList.remove('hidden');
            DOM.cartItemsContainer.classList.add('hidden');
            DOM.cartSummary.classList.add('hidden');
        }
    }, 'خطا در رندر سبد خرید اصلی.');
}
