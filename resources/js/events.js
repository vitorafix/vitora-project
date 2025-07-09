// events.js
// این فایل مسئول مدیریت رویدادهای DOM و کش کردن عناصر است.

let DOM = {}; // Object to store cached DOM elements

/**
 * تابع debounce برای محدود کردن تعداد فراخوانی یک تابع.
 * @param {Function} func - تابعی که باید debounce شود.
 * @param {number} delay - تأخیر بر حسب میلی‌ثانیه.
 * @returns {Function} - نسخه debounce شده تابع.
 */
function debounce(func, delay) {
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

    // بررسی وجود عناصر اصلی سبد خرید
    return !!(DOM.cartItemsContainer && DOM.cartEmptyMessage && DOM.cartSummary && DOM.cartTotalPrice);
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
            const productPrice = addToCartBtn.getAttribute('data-product-price'); // اگر نیاز بود
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
 * تنظیم Event Listener برای دکمه‌های افزایش/کاهش تعداد در سبد خرید اصلی.
 * این تابع باید پس از رندر شدن آیتم‌های سبد خرید اصلی فراخوانی شود.
 */
export function setupMainCartQuantityButtons() {
    const cartItemsContainer = DOM.cartItemsContainer;
    if (!cartItemsContainer) {
        console.warn('Main cart items container not found for quantity buttons setup.');
        return;
    }

    // استفاده از event delegation برای مدیریت کلیک‌ها روی دکمه‌های افزایش/کاهش و حذف
    cartItemsContainer.addEventListener('click', async (event) => {
        const target = event.target;
        let cartItemId;
        let currentQuantityElement;
        let newQuantity;

        // Debounced handler for quantity changes
        const debouncedUpdateQuantity = debounce(async (itemId, quantity) => {
            if (window.cartManager) {
                await window.cartManager.updateItemQuantity(itemId, quantity);
            } else {
                console.error('CartManager not available to update item quantity.');
            }
        }, 300); // 300ms debounce delay

        // Handle increase quantity
        if (target.matches('.increase-quantity-btn') || target.closest('.increase-quantity-btn')) {
            event.preventDefault();
            const btn = target.matches('.increase-quantity-btn') ? target : target.closest('.increase-quantity-btn');
            cartItemId = btn.getAttribute('data-cart-item-id');
            currentQuantityElement = btn.closest('.quantity-controls').querySelector('.item-quantity');
            newQuantity = parseInt(currentQuantityElement.textContent) + 1;

            if (newQuantity <= 0) return; // Prevent negative quantity

            // Update UI immediately for responsiveness
            currentQuantityElement.textContent = newQuantity;
            updateSubtotalInUI(cartItemId, newQuantity);

            debouncedUpdateQuantity(cartItemId, newQuantity);

        }
        // Handle decrease quantity
        else if (target.matches('.decrease-quantity-btn') || target.closest('.decrease-quantity-btn')) {
            event.preventDefault();
            const btn = target.matches('.decrease-quantity-btn') ? target : target.closest('.decrease-quantity-btn');
            cartItemId = btn.getAttribute('data-cart-item-id');
            currentQuantityElement = btn.closest('.quantity-controls').querySelector('.item-quantity');
            newQuantity = parseInt(currentQuantityElement.textContent) - 1;

            if (newQuantity <= 0) {
                // If quantity becomes 0, prompt for removal
                if (typeof window.showMessage === 'function') {
                    window.showMessage('آیا از حذف این محصول اطمینان دارید؟', 'confirm', async () => {
                        // User confirmed, proceed with removal
                        if (window.cartManager) {
                            await window.cartManager.removeItem(cartItemId);
                        } else {
                            console.error('CartManager not available to remove item.');
                        }
                    });
                } else {
                    // Fallback to native confirm if custom message box is not available
                    if (confirm('آیا از حذف این محصول اطمینان دارید؟')) {
                        if (window.cartManager) {
                            await window.cartManager.removeItem(cartItemId);
                        } else {
                            console.error('CartManager not available to remove item.');
                        }
                    }
                }
                return; // Prevent further action for this click
            }

            // Update UI immediately for responsiveness
            currentQuantityElement.textContent = newQuantity;
            updateSubtotalInUI(cartItemId, newQuantity);

            debouncedUpdateQuantity(cartItemId, newQuantity);
        }
        // Handle remove item button
        else if (target.matches('.remove-item-btn') || target.closest('.remove-item-btn')) {
            event.preventDefault();
            const removeBtn = target.matches('.remove-item-btn') ? target : target.closest('.remove-item-btn');
            cartItemId = removeBtn.getAttribute('data-cart-item-id');

            if (cartItemId) {
                console.log('Remove button clicked for item:', cartItemId);
                if (typeof window.showMessage === 'function') {
                    window.showMessage('آیا از حذف این محصول اطمینان دارید؟', 'confirm', async () => {
                        if (window.cartManager) {
                            await window.cartManager.removeItem(cartItemId);
                        } else {
                            console.error('CartManager not available to remove item.');
                        }
                    });
                } else {
                    if (confirm('آیا از حذف این محصول اطمینان دارید؟')) {
                        if (window.cartManager) {
                            await window.cartManager.removeItem(cartItemId);
                        } else {
                            console.error('CartManager not available to remove item.');
                        }
                    }
                }
            }
        }
    });
}

/**
 * به‌روزرسانی فوری زیرمجموع یک آیتم در UI.
 * @param {string} cartItemId - شناسه آیتم سبد خرید.
 * @param {number} newQuantity - تعداد جدید آیتم.
 */
function updateSubtotalInUI(cartItemId, newQuantity) {
    const cartItemElement = document.querySelector(`[data-cart-item-id="${cartItemId}"]`).closest('.cart-item-card');
    if (cartItemElement) {
        const itemPriceElement = cartItemElement.querySelector('.item-price');
        const subtotalElement = cartItemElement.querySelector('.item-subtotal');
        if (itemPriceElement && subtotalElement) {
            const itemPrice = parseFloat(itemPriceElement.getAttribute('data-price'));
            const newSubtotal = newQuantity * itemPrice;
            subtotalElement.textContent = newSubtotal.toLocaleString('fa-IR') + ' تومان';
            subtotalElement.setAttribute('data-subtotal', newSubtotal);
            console.log(`Subtotal updated to: ${newSubtotal} for item ${cartItemId}`);
        }
    }
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

