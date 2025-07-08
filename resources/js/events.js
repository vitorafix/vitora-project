// events.js
// این فایل مسئول مدیریت رویدادهای DOM و کش کردن عناصر است.

let DOM = {}; // Object to store cached DOM elements

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

    // Add to Cart Buttons (این خط دیگر نیازی به querySelectorAll ندارد زیرا از delegation استفاده می‌کنیم)
    // DOM.addToCartButtons = document.querySelectorAll('.add-to-cart-btn');

    // بررسی وجود عناصر حیاتی سبد خرید اصلی
    const hasMainCartElements = DOM.cartItemsContainer && DOM.cartEmptyMessage && DOM.cartSummary && DOM.cartTotalPrice;
    if (!hasMainCartElements) {
        console.warn('One or more main cart DOM elements not found. Main cart functionality may be limited on this page.');
    }
    return hasMainCartElements;
}

/**
 * تنظیم event listenerها برای دکمه‌های "افزودن به سبد خرید" با استفاده از Event Delegation.
 * این تابع رویداد کلیک را به document.body واگذار می‌کند تا دکمه‌های دینامیک نیز مدیریت شوند.
 */
export function setupAddToCartButtons() {
    // به جای DOM.addToCartButtons، مستقیماً به document.body گوش می‌دهیم
    document.body.addEventListener('click', async (event) => {
        const target = event.target;
        // بررسی می‌کنیم که آیا عنصر کلیک شده یا یکی از والد‌های آن، کلاس 'add-to-cart-btn' را دارد
        if (target.classList.contains('add-to-cart-btn')) {
            event.preventDefault(); // جلوگیری از عملکرد پیش‌فرض لینک/دکمه اگر وجود داشته باشد
            const productId = target.dataset.productId;
            if (productId) {
                console.log(`Add to cart clicked for product ID: ${productId}`);
                if (typeof window.cartManager !== 'undefined' && window.cartManager.addItem) {
                    await window.cartManager.addItem(productId, 1);
                } else {
                    console.error('CartManager instance not available or addItem method missing.');
                    if (typeof window.showMessage === 'function') {
                        window.showMessage('خطا: سیستم سبد خرید آماده نیست.', 'error');
                    }
                }
            }
        }
    });
    console.log('Setup event listener for "Add to Cart" buttons (via delegation on document.body).');
    // هشدار 'No "Add to Cart" buttons found' دیگر نیازی نیست زیرا همیشه به body گوش می‌دهیم.
}

/**
 * تنظیم event listenerها برای دکمه‌های افزایش/کاهش تعداد در سبد خرید اصلی.
 */
export function setupMainCartQuantityButtons() {
    // فقط در صورتی که کانتینر اصلی سبد خرید وجود داشته باشد، راه‌اندازی شود
    if (!DOM.cartItemsContainer) {
        console.warn('Main cart items container not found. Skipping quantity button setup.');
        return;
    }

    // واگذاری رویداد برای دکمه‌های تعداد در سبد خرید اصلی
    DOM.cartItemsContainer.addEventListener('click', async (event) => {
        const target = event.target;
        if (target.classList.contains('quantity-btn')) {
            const itemElement = target.closest('[data-product-id]');
            if (!itemElement) {
                console.error('Could not find parent item element for quantity button.');
                return;
            }

            const productId = itemElement.dataset.productId;
            if (!productId) {
                console.error('Product ID not found for cart item.');
                return;
            }

            // فرض بر این است که یک نمونه سراسری از cartManager یا راهی برای دسترسی به آن وجود دارد
            if (typeof window.cartManager === 'undefined' || !window.cartManager.updateItemQuantity) {
                console.error('CartManager instance not available or updateItemQuantity method missing.');
                if (typeof window.showMessage === 'function') {
                    window.showMessage('خطا: سیستم سبد خرید آماده نیست.', 'error');
                }
                return;
            }

            let currentQuantity = parseInt(itemElement.querySelector('.item-quantity').dataset.quantity, 10);
            let newQuantity;

            if (target.classList.contains('plus-btn')) {
                newQuantity = currentQuantity + 1;
            } else if (target.classList.contains('minus-btn')) {
                newQuantity = currentQuantity - 1;
            } else {
                return; // دکمه تعداد نیست
            }

            if (newQuantity < 0) newQuantity = 0; // جلوگیری از تعداد منفی

            await window.cartManager.updateItemQuantity(productId, newQuantity);
        }
    });
    console.log('Setup event listeners for main cart quantity buttons (via delegation).');
}

/**
 * تنظیم event listener برای دکمه باز و بسته کردن مینی‌کارت.
 */
export function setupMiniCartToggle() {
    if (DOM.miniCartToggle && DOM.miniCartDropdown) {
        DOM.miniCartToggle.addEventListener('click', (event) => {
            // event.preventDefault(); // این خط حذف شد تا لینک سبد خرید عمل ناوبری خود را انجام دهد
            DOM.miniCartDropdown.classList.toggle('active');
        });

        // بستن مینی‌کارت با کلیک در بیرون
        document.addEventListener('click', (event) => {
            // اطمینان حاصل کنید که کلیک روی خود دکمه toggle یا داخل dropdown نیست
            if (!DOM.miniCartToggle.contains(event.target) && !DOM.miniCartDropdown.contains(event.target)) {
                DOM.miniCartDropdown.classList.remove('active');
            }
        });
        console.log('Setup event listener for mini cart toggle.');
    } else {
        console.warn('Mini cart toggle or dropdown not found. Skipping mini cart toggle setup.');
    }
}

/**
 * تنظیم event listenerها برای دکمه‌های عملیاتی مینی‌کارت (مثلاً حذف آیتم).
 */
export function setupMiniCartActionButtons() {
    if (!DOM.miniCartItemsContainer) {
        console.warn('Mini cart items container not found. Skipping mini cart action button setup.');
        return;
    }

    // واگذاری رویداد برای دکمه‌های عملیاتی در مینی‌کارت
    DOM.miniCartItemsContainer.addEventListener('click', async (event) => {
        const target = event.target;
        if (target.classList.contains('remove-item-btn')) { // فرض بر وجود کلاسی برای دکمه‌های حذف
            const itemElement = target.closest('[data-product-id]');
            if (!itemElement) {
                console.error('Could not find parent item element for remove button.');
                return;
            }
            const productId = itemElement.dataset.productId;
            if (productId) {
                // فرض بر این است که یک نمونه سراسری از cartManager یا راهی برای دسترسی به آن وجود دارد
                if (typeof window.cartManager !== 'undefined' && window.cartManager.removeItem) {
                    await window.cartManager.removeItem(productId);
                } else {
                    console.error('CartManager instance not available or removeItem method missing.');
                    if (typeof window.showMessage === 'function') {
                        window.showMessage('خطا: سیستم سبد خرید آماده نیست.', 'error');
                    }
                }
            }
        }
    });
    console.log('Setup event listeners for mini cart action buttons (via delegation).');
}

/**
 * دسترسی به عناصر DOM کش شده.
 * @returns {Object} یک شی حاوی عناصر DOM کش شده.
 */
export function getDOM() {
    return DOM;
}
