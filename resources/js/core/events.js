// resources/js/core/events.js
// این فایل مسئول مدیریت رویدادهای DOM و کش کردن عناصر است.

let DOM = {}; // Object to store cached DOM elements

/**
 * تابع debounce برای محدود کردن تعداد فراخوانی یک تابع.
 * @param {Function} func - تابعی که باید debounce شود.
 * @param {number} delay - تأخیر بر حسب میلی‌ثانیه.
 * @returns {Function} - نسخه debounce شده تابع.
 */
export function debounce(func, delay) {
    let timeout;
    return function(...args) {
        const context = this;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), delay);
    };
}

/**
 * کش کردن عناصر DOM مورد نیاز برای عملیات سبد خرید.
 * این تابع باید یک بار پس از بارگذاری کامل DOM فراخوانی شود.
 * @returns {boolean} true اگر عناصر اصلی سبد خرید (مانند کانتینر آیتم‌ها، پیام خالی بودن، خلاصه و قیمت کل) یافت شوند، در غیر این صورت false.
 */
export function initializeDOMCache() {
    // Mini Cart Elements
    DOM.miniCartToggle = document.getElementById('mini-cart-toggle');
    DOM.miniCartDropdown = document.getElementById('mini-cart-dropdown');
    DOM.miniCartItemsContainer = document.getElementById('mini-cart-items-container');
    DOM.miniCartTotalQuantity = document.getElementById('mini-cart-total-quantity');
    DOM.miniCartTotalPrice = document.getElementById('mini-cart-total-price');
    DOM.miniCartEmptyMessage = document.getElementById('mini-cart-empty-message');
    DOM.miniCartSummary = document.getElementById('mini-cart-summary');

    // Main Cart Elements
    DOM.cartItemsContainer = document.getElementById('cart-items-container');
    DOM.cartEmptyMessage = document.getElementById('cart-empty-message');
    DOM.cartSummary = document.getElementById('cart-summary');
    DOM.cartTotalPrice = document.getElementById('cart-total-price'); // این عنصر در cart.blade.php اضافه شده است

    // Add detailed logging for debugging
    console.log('--- initializeDOMCache Debug ---');
    console.log('DOM.miniCartToggle:', DOM.miniCartToggle);
    console.log('DOM.miniCartDropdown:', DOM.miniCartDropdown);
    console.log('DOM.miniCartItemsContainer (mini):', DOM.miniCartItemsContainer);
    console.log('DOM.miniCartTotalQuantity:', DOM.miniCartTotalQuantity);
    console.log('DOM.miniCartTotalPrice (mini):', DOM.miniCartTotalPrice);
    console.log('DOM.miniCartEmptyMessage:', DOM.miniCartEmptyMessage);
    console.log('DOM.miniCartSummary (mini):', DOM.miniCartSummary);

    console.log('DOM.cartItemsContainer (main):', DOM.cartItemsContainer);
    console.log('DOM.cartEmptyMessage (main):', DOM.cartEmptyMessage);
    console.log('DOM.cartSummary (main):', DOM.cartSummary);
    console.log('DOM.cartTotalPrice (main):', DOM.cartTotalPrice);
    console.log('--- End initializeDOMCache Debug ---');

    // بررسی وجود عناصر اصلی سبد خرید
    const mainCartElementsFound = !!(DOM.cartItemsContainer && DOM.cartEmptyMessage && DOM.cartSummary && DOM.cartTotalPrice);
    if (!mainCartElementsFound) {
        console.error('CRITICAL: One or more main cart DOM elements were NOT found during initial cache. This indicates a potential HTML loading or script timing issue.');
        // Optional: Try a delayed check to see if they appear later
        setTimeout(() => {
            const delayedCartItemsContainer = document.getElementById('cart-items-container');
            const delayedCartEmptyMessage = document.getElementById('cart-empty-message');
            const delayedCartSummary = document.getElementById('cart-summary');
            const delayedCartTotalPrice = document.getElementById('cart-total-price');
            console.log('--- Delayed initializeDOMCache Check (500ms) ---');
            console.log('Delayed DOM.cartItemsContainer:', delayedCartItemsContainer);
            console.log('Delayed DOM.cartEmptyMessage:', delayedCartEmptyMessage);
            console.log('Delayed DOM.cartSummary:', delayedCartSummary);
            console.log('Delayed DOM.cartTotalPrice:', delayedCartTotalPrice);
            console.log('--- End Delayed Check ---');
            if (delayedCartItemsContainer && delayedCartEmptyMessage && delayedCartSummary && delayedCartTotalPrice) {
                console.warn('Main cart elements found after a delay. Consider adjusting script loading order or using a more robust DOM ready event.');
            } else {
                console.error('Main cart elements still NOT found after a 500ms delay. The HTML might not be rendering correctly or is being removed.');
            }
        }, 500); // Check again after 500ms
    }

    return mainCartElementsFound;
}

/**
 * بازگرداندن آبجکت کش شده DOM.
 * @returns {object} آبجکت DOM.
 */
export function getDOM() {
    return DOM;
}

/**
 * تنظیم Event Listener برای دکمه‌های "افزودن به سبد خرید" در صفحات محصولات.
 * این تابع باید پس از بارگذاری محصولات فراخوانی شود.
 */
export function setupAddToCartButtons() {
    // از event delegation استفاده می‌کنیم تا به دکمه‌های پویا هم اعمال شود
    document.body.addEventListener('click', async (event) => {
        const addToCartBtn = event.target.closest('.add-to-cart-btn');
        if (addToCartBtn) {
            event.preventDefault();
            const productId = addToCartBtn.getAttribute('data-product-id');
            const productTitle = addToCartBtn.getAttribute('data-product-title');
            // const productPrice = addToCartBtn.getAttribute('data-product-price'); // اگر نیاز بود
            const quantity = 1; // مقدار پیش‌فرض

            if (typeof window.cartManager === 'undefined') {
                console.error('CartManager is not defined. Cannot add item to cart.');
                if (typeof window.showMessage === 'function') {
                    window.showMessage('خطا: سیستم سبد خرید آماده نیست.', 'error');
                }
                return;
            }

            // نمایش وضعیت بارگذاری
            if (typeof window.showMessage === 'function') {
                window.showMessage(`در حال افزودن ${productTitle} به سبد خرید...`, 'info', 1500); // نمایش موقت
            }

            try {
                await window.cartManager.addItem(productId, quantity);
                // پیام موفقیت و به‌روزرسانی UI توسط CartManager انجام می‌شود
            } catch (error) {
                console.error('Error adding item to cart:', error);
                // پیام خطا توسط CartManager یا تابع showMessage مدیریت می‌شود
            }
        }
    });
}

/**
 * تنظیم Event Listener برای دکمه باز و بسته کردن مینی سبد خرید.
 */
export function setupMiniCartToggle() {
    if (DOM.miniCartToggle && DOM.miniCartDropdown) {
        DOM.miniCartToggle.addEventListener('click', (event) => {
            event.stopPropagation(); // جلوگیری از بسته شدن فوری در صورت کلیک روی دکمه
            DOM.miniCartDropdown.classList.toggle('hidden');
        });

        // بستن مینی سبد خرید با کلیک در هر جای دیگر صفحه
        document.addEventListener('click', (event) => {
            if (!DOM.miniCartDropdown.contains(event.target) && !DOM.miniCartToggle.contains(event.target)) {
                DOM.miniCartDropdown.classList.add('hidden');
            }
        });
    }
}

/**
 * تنظیم Event Listener برای دکمه‌های اقدام در مینی سبد خرید (مانند "مشاهده سبد" و "تکمیل سفارش").
 */
export function setupMiniCartActionButtons() {
    const miniCartDropdown = DOM.miniCartDropdown;
    if (!miniCartDropdown) {
        console.warn('Mini cart dropdown not found for action buttons setup.');
        return;
    }

    miniCartDropdown.addEventListener('click', (event) => {
        if (event.target.matches('#view-cart-btn')) {
            window.location.href = '/cart'; // مسیر صفحه سبد خرید
        } else if (event.target.matches('#checkout-btn')) {
            window.location.href = '/checkout'; // مسیر صفحه تکمیل سفارش
        }
    });
}
