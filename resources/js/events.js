// events.js
// این ماژول مسئول تعریف و ثبت event listenerها روی المان‌های صفحه است.
// این توابع با ماژول API برای ارسال درخواست‌ها و با ماژول Renderer برای به‌روزرسانی UI تعامل می‌کنند.

// ایمپورت کردن توابع مورد نیاز از ماژول API
import * as api from './api.js'; // Import all from api as 'api' for dependency injection
// ایمپورت کردن توابع مورد نیاز از ماژول Renderer
import * as renderer from './renderer.js'; // Import all from renderer as 'renderer' for dependency injection

/**
 * @typedef {Object} ProductDetails
 * @property {string} title
 * @property {string} product_name // For main cart items
 * @property {number} price
 * @property {number} stock
 * @property {string} thumbnail_url_small
 * @property {string} image_url
 * @property {string} image // For main cart items
 */

/**
 * @typedef {Object} CartItem
 * @property {number} cart_item_id
 * @property {number} product_id
 * @property {number} quantity
 * @property {number} subtotal
 * @property {ProductDetails} product // For mini-cart
 * @property {string} product_name // For main cart
 * @property {number} product_price // For main cart
 * @property {number} stock // For main cart
 * @property {string} thumbnail_url_small // For main cart
 * @property {string} image // For main cart
 */


/**
 * تابعی کمکی برای فرمت کردن اعداد با کاما (لوکال فارسی).
 * @param {number} num - عددی که باید فرمت شود.
 * @returns {string} عدد فرمت شده به صورت رشته.
 */
function formatNumber(num) {
    return new Intl.NumberFormat('fa-IR').format(num);
}

/**
 * Constants for routes, messages, and general configuration.
 * ثابت‌ها برای مسیرها، پیام‌ها و پیکربندی کلی.
 */
const CART_ROUTES = {
    CHECKOUT: '/checkout',
    CART: '/cart'
};

const MESSAGES = {
    INVALID_PRODUCT_ID: 'خطا: شناسه محصول نامعتبر است.',
    INVALID_CART_ITEM_DATA: 'خطا: اطلاعات آیتم سبد خرید نامعتبر است.',
    INSUFFICIENT_STOCK: (stock) => `موجودی کافی برای افزودن بیشتر این محصول وجود ندارد. موجودی فعلی: ${formatNumber(stock)}`,
    REMOVE_ON_ZERO_QUANTITY: 'با کاهش تعداد به صفر، محصول از سبد خرید حذف خواهد شد.',
    CONFIRM_REMOVE_ITEM: 'آیا از حذف این محصول اطمینان دارید؟',
    ADD_TO_CART_FAILED: 'افزودن محصول به سبد خرید با خطا مواجه شد.',
    UPDATE_QUANTITY_FAILED: 'به‌روزرسانی تعداد محصول با خطا مواجه شد.',
    REMOVE_ITEM_FAILED: 'حذف محصول از سبد خرید با خطا مواجه شد.',
    SAFE_EXECUTION_FAILED: 'خطای داخلی در پردازش عملیات. لطفاً دوباره تلاش کنید.',
    GENERAL_FETCH_ERROR: 'خطا در دریافت اطلاعات سبد خرید. لطفاً اتصال اینترنت خود را بررسی کنید.'
    // می‌توانید پیام‌های دیگر را در اینجا اضافه کنید
};

const CONFIG = {
    DEBOUNCE_DELAY: 300, // Delay in milliseconds for quantity updates
    // MAX_QUANTITY: 999, // Max quantity per item (already handled by backend/stock)
    // MIN_QUANTITY: 1, // Min quantity per item
};

/**
 * Cache for frequently accessed DOM elements to improve performance.
 * کش برای عناصر DOM که به صورت مکرر استفاده می‌شوند تا عملکرد بهبود یابد.
 */
const domCache = {
    // Mini-cart elements
    miniCartBtn: null,
    miniCartDropdown: null,
    miniCartItemsContainer: null,
    miniCartTotalQuantity: null,
    miniCartTotalPrice: null,
    miniCartEmptyMessage: null,
    miniCartCheckoutBtn: null,
    miniCartViewCartBtn: null,

    // Main cart elements
    mainCartItemsContainer: null,
    mainCartEmptyMessage: null,
    mainCartSummary: null,
    mainCartTotalPriceElement: null,

    // Other elements
    addProductToCartBtns: null,
};

/**
 * Initializes the DOM cache by storing references to frequently used DOM elements.
 * This function should be called once when the DOM is ready.
 * عناصر DOM را کش می‌کند تا در طول عمر برنامه به سرعت قابل دسترسی باشند.
 * این تابع باید یک بار پس از بارگذاری کامل DOM فراخوانی شود.
 */
export function initializeDOMCache() {
    domCache.miniCartBtn = document.getElementById('mini-cart-btn');
    domCache.miniCartDropdown = document.getElementById('mini-cart-dropdown');
    domCache.miniCartItemsContainer = document.getElementById('mini-cart-items-container');
    domCache.miniCartTotalQuantity = document.getElementById('mini-cart-total-quantity');
    domCache.miniCartTotalPrice = document.getElementById('mini-cart-total-price');
    domCache.miniCartEmptyMessage = document.getElementById('mini-cart-empty-message');
    domCache.miniCartCheckoutBtn = document.getElementById('mini-cart-checkout-btn');
    domCache.miniCartViewCartBtn = document.getElementById('mini-cart-view-cart-btn');

    domCache.mainCartItemsContainer = document.getElementById('cart-items-container');
    domCache.mainCartEmptyMessage = document.getElementById('cart-empty-message');
    domCache.mainCartSummary = document.getElementById('cart-summary');
    domCache.mainCartTotalPriceElement = document.getElementById('cart-total-price');

    domCache.addProductToCartBtns = document.querySelectorAll('.add-to-cart-btn');
}

/**
 * Tracks a custom event for analytics purposes (e.g., Google Analytics).
 * Assumes window.gtag is available.
 * یک رویداد سفارشی را برای اهداف تحلیلی (مانند Google Analytics) ردیابی می‌کند.
 * فرض می‌شود که window.gtag در دسترس است.
 * @param {string} eventName - The name of the event (e.g., 'add_to_cart', 'remove_from_cart').
 * @param {Object} data - An object containing event parameters.
 */
function trackEvent(eventName, data = {}) {
    const eventData = {
        ...data,
        timestamp: Date.now(),
        page_url: window.location.href,
        // session_id and user_agent are typically handled by the analytics provider itself
        // or a higher-level wrapper, but can be added here if globally available.
        // session_id: window.sessionId || 'N/A',
        // user_agent: navigator.userAgent || 'N/A',
    };

    if (typeof window.gtag === 'function') {
        window.gtag('event', eventName, eventData);
    } else {
        console.warn(`Analytics tracking function (window.gtag) not found. Event "${eventName}" not tracked.`, eventData);
    }
}

/**
 * Measures the execution time of a function for performance monitoring.
 * زمان اجرای یک تابع را برای نظارت بر عملکرد اندازه‌گیری می‌کند.
 * @param {string} name - The name of the operation being measured.
 * @param {Function} fn - The function to execute and measure.
 * @returns {any} The result of the executed function.
 */
function measurePerformance(name, fn) {
    const start = performance.now();
    const result = fn();
    const end = performance.now();
    console.log(`Performance - ${name} took ${end - start} milliseconds.`);
    return result;
}

/**
 * Executes a synchronous function safely, catching errors and displaying a message.
 * This acts as a simple error boundary for synchronous code blocks.
 * @param {Function} fn - The synchronous function to execute.
 * @param {string} fallbackMessage - The message to display if an error occurs.
 * @returns {any | null} The result of the function execution, or null if an error occurred.
 */
function safeExecute(fn, fallbackMessage) {
    try {
        return fn();
    } catch (error) {
        console.error('Safe execution failed:', error);
        if (typeof window.showMessage === 'function') {
            window.showMessage(fallbackMessage || MESSAGES.SAFE_EXECUTION_FAILED, 'error');
        } else {
            console.warn('window.showMessage is not available to display fallback message.');
        }
        return null;
    }
}

/**
 * رویدادهای مربوط به دکمه‌های "افزودن به سبد خرید" را ثبت می‌کند.
 * این دکمه‌ها معمولاً در صفحات لیست محصولات یا صفحه جزئیات محصول قرار دارند.
 * @param {Object} [dependencies] - Optional dependencies for testing.
 * @param {Object} [dependencies.api=api] - API module functions.
 * @param {Object} [dependencies.renderer=renderer] - Renderer module functions.
 * @param {Function} [dependencies.trackEvent=trackEvent] - Analytics tracking function.
 */
export function setupAddToCartButtons(dependencies = {}) {
    const {
        api: apiDep = api,
        renderer: rendererDep = renderer,
        trackEvent: trackEventDep = trackEvent
    } = dependencies;

    const addProductToCartBtns = domCache.addProductToCartBtns; // استفاده از کش DOM

    if (!addProductToCartBtns || addProductToCartBtns.length === 0) {
        console.warn('No "Add to Cart" buttons found. Skipping event listener setup for them.');
        return;
    }

    addProductToCartBtns.forEach(button => {
        button.addEventListener('click', async function() {
            // Measure performance of the click handler
            measurePerformance('AddToCartButtonClick', async () => {
                const productId = safeExecute(() => {
                    const id = parseInt(this.dataset.productId);
                    if (isNaN(id) || id <= 0) {
                        throw new Error('Invalid Product ID');
                    }
                    return id;
                }, MESSAGES.INVALID_PRODUCT_ID);

                if (productId === null) { // if safeExecute returned null, an error occurred
                    return;
                }

                rendererDep.setCartLoadingState(true); // نمایش وضعیت بارگذاری
                try {
                    await apiDep.addOrUpdateCartItem(productId, 1);
                    trackEventDep('add_to_cart', { product_id: productId, quantity: 1, product_name: this.dataset.productTitle || 'N/A' });

                    const updatedCartData = await apiDep.fetchCartContents();
                    rendererDep.renderMiniCartDetails(updatedCartData.items, updatedCartData.totalQuantity, updatedCartData.totalPrice);

                    const mainCartItemsContainer = domCache.mainCartItemsContainer; // استفاده از کش DOM
                    const mainCartSummary = domCache.mainCartSummary; // استفاده از کش DOM
                    if (mainCartItemsContainer && mainCartSummary) {
                        rendererDep.renderMainCart(updatedCartData.items, updatedCartData.totalQuantity, updatedCartData.totalPrice);
                    }
                } catch (error) {
                    console.error('Failed to add/update cart item from product page:', error);
                    // پیام خطا توسط تابع API مربوطه نمایش داده شده است، اما برای اطمینان یک پیام عمومی نیز نمایش می‌دهیم.
                    if (typeof window.showMessage === 'function') {
                        window.showMessage(MESSAGES.ADD_TO_CART_FAILED, 'error');
                    }
                } finally {
                    rendererDep.setCartLoadingState(false); // پنهان کردن وضعیت بارگذاری
                }
            });
        });
    });
}

/**
 * رویدادهای مربوط به دکمه‌های افزایش/کاهش تعداد و حذف آیتم در سبد خرید اصلی را ثبت می‌کند.
 * این تابع از Event Delegation برای مدیریت کلیک‌ها استفاده می‌کند.
 * @param {Object} [dependencies] - Optional dependencies for testing.
 * @param {Object} [dependencies.api=api] - API module functions.
 * @param {Object} [dependencies.renderer=renderer] - Renderer module functions.
 * @param {Function} [dependencies.trackEvent=trackEvent] - Analytics tracking function.
 */
export function setupMainCartQuantityButtons(dependencies = {}) {
    const {
        api: apiDep = api,
        renderer: rendererDep = renderer,
        trackEvent: trackEventDep = trackEvent
    } = dependencies;

    const mainCartItemsContainer = domCache.mainCartItemsContainer; // استفاده از کش DOM

    if (!mainCartItemsContainer) {
        console.warn('Main cart items container not found. Skipping quantity button setup.');
        return;
    }

    let quantityUpdateTimeout; // برای Debouncing

    mainCartItemsContainer.addEventListener('click', async function(event) {
        const target = event.target;

        if (target.classList.contains('quantity-btn')) {
            const itemElement = target.closest('[data-item-id]');
            if (!itemElement) {
                console.warn('Clicked quantity button, but parent item element not found.');
                return;
            }

            // Measure performance of the quantity update handler
            measurePerformance('MainCartQuantityUpdate', async () => {
                // Use safeExecute for parsing and initial validation of data attributes
                const itemData = safeExecute(() => {
                    const itemId = parseInt(itemElement.dataset.itemId);
                    const itemPrice = parseFloat(itemElement.dataset.itemPrice);
                    const productStock = parseInt(itemElement.dataset.productStock);
                    const quantitySpan = itemElement.querySelector('.item-quantity');
                    const currentQuantity = parseInt(quantitySpan.dataset.quantity);
                    const itemSubtotalElement = itemElement.querySelector('.item-subtotal');
                    const productName = itemElement.querySelector('h3')?.textContent || 'N/A'; // برای Analytics

                    if (isNaN(itemId) || itemId <= 0 || isNaN(itemPrice) || itemPrice < 0 || isNaN(productStock) || productStock < 0 || isNaN(currentQuantity) || currentQuantity < 0) {
                        throw new Error('Invalid data attributes');
                    }
                    return { itemId, itemPrice, productStock, quantitySpan, currentQuantity, itemSubtotalElement, productName };
                }, MESSAGES.INVALID_CART_ITEM_DATA);

                if (itemData === null) { // if safeExecute returned null, an error occurred
                    return;
                }

                const { itemId, itemPrice, productStock, quantitySpan, currentQuantity, itemSubtotalElement, productName } = itemData;
                const oldQuantity = currentQuantity;

                if (target.classList.contains('plus-btn')) {
                    if (currentQuantity < productStock) {
                        itemData.currentQuantity++; // Update quantity in itemData object
                    } else {
                        if (typeof window.showMessage === 'function') {
                            window.showMessage(MESSAGES.INSUFFICIENT_STOCK(productStock), 'warning');
                        }
                        return;
                    }
                } else if (target.classList.contains('minus-btn')) {
                    if (currentQuantity > 1) {
                        itemData.currentQuantity--; // Update quantity in itemData object
                    } else {
                        let confirmed = true;
                        if (typeof window.showConfirmDialog === 'function') {
                            // فرض می‌کنیم window.showConfirmDialog یک مودال سفارشی را نمایش می‌دهد
                            // و یک Promise برمی‌گرداند که به true/false حل می‌شود.
                            confirmed = await window.showConfirmDialog(MESSAGES.CONFIRM_REMOVE_ITEM);
                        } else {
                            // Fallback: اگر تابع showConfirmDialog وجود نداشت، یک پیام اطلاع‌رسانی نمایش دهید.
                            if (typeof window.showMessage === 'function') {
                                window.showMessage(MESSAGES.REMOVE_ON_ZERO_QUANTITY, 'info', 3000);
                            }
                        }

                        if (!confirmed) {
                            // اگر کاربر حذف را تأیید نکرد، تعداد را به حالت قبلی برگردانید
                            quantitySpan.textContent = formatNumber(oldQuantity);
                            quantitySpan.dataset.quantity = oldQuantity;
                            itemSubtotalElement.textContent = `${formatNumber(itemPrice * oldQuantity)} تومان`;
                            itemSubtotalElement.dataset.subtotal = itemPrice * oldQuantity;
                            return;
                        }

                        rendererDep.setCartLoadingState(true);
                        try {
                            await apiDep.removeCartItem(itemId);
                            trackEventDep('remove_from_cart', { cart_item_id: itemId, product_name: productName });

                            const updatedCartData = await apiDep.fetchCartContents();
                            rendererDep.renderMainCart(updatedCartData.items, updatedCartData.totalQuantity, updatedCartData.totalPrice);
                            rendererDep.renderMiniCartDetails(updatedCartData.items, updatedCartData.totalQuantity, updatedCartData.totalPrice);
                            return;
                        } catch (error) {
                            console.error('Failed to remove cart item:', error);
                            if (typeof window.showMessage === 'function') {
                                window.showMessage(MESSAGES.REMOVE_ITEM_FAILED, 'error');
                            }
                            return;
                        } finally {
                            rendererDep.setCartLoadingState(false);
                        }
                    }
                }
                // Update local display for responsiveness
                quantitySpan.textContent = formatNumber(itemData.currentQuantity);
                quantitySpan.dataset.quantity = itemData.currentQuantity;
                // Update ARIA attributes for accessibility
                quantitySpan.setAttribute('aria-label', `تعداد: ${formatNumber(itemData.currentQuantity)}`);


                const newSubtotal = itemPrice * itemData.currentQuantity;
                itemSubtotalElement.textContent = `${formatNumber(newSubtotal)} تومان`;
                itemSubtotalElement.dataset.subtotal = newSubtotal;

                // Debouncing for server update request
                clearTimeout(quantityUpdateTimeout);
                quantityUpdateTimeout = setTimeout(async () => {
                    rendererDep.setCartLoadingState(true);
                    try {
                        await apiDep.updateCartItemQuantity(itemId, itemData.currentQuantity);
                        trackEventDep('update_cart_quantity', { cart_item_id: itemId, new_quantity: itemData.currentQuantity, old_quantity: oldQuantity, product_name: productName });

                        const updatedCartData = await apiDep.fetchCartContents();
                        rendererDep.renderMainCart(updatedCartData.items, updatedCartData.totalQuantity, updatedCartData.totalPrice);
                        rendererDep.renderMiniCartDetails(updatedCartData.items, updatedCartData.totalQuantity, updatedCartData.totalPrice);
                    } catch (error) {
                        console.error('Error updating cart item quantity on server:', error);
                        if (typeof window.showMessage === 'function') {
                            window.showMessage(MESSAGES.UPDATE_QUANTITY_FAILED, 'error');
                        }
                        // Revert local quantity if server update fails
                        quantitySpan.textContent = formatNumber(oldQuantity);
                        quantitySpan.dataset.quantity = oldQuantity;
                        itemSubtotalElement.textContent = `${formatNumber(itemPrice * oldQuantity)} تومان`;
                        itemSubtotalElement.dataset.subtotal = itemPrice * oldQuantity;
                        // Revert ARIA attribute
                        quantitySpan.setAttribute('aria-label', `تعداد: ${formatNumber(oldQuantity)}`);

                        const updatedCartData = await apiDep.fetchCartContents();
                        rendererDep.renderMainCart(updatedCartData.items, updatedCartData.totalQuantity, updatedCartData.totalPrice);
                        rendererDep.renderMiniCartDetails(updatedCartData.items, updatedCartData.totalQuantity, updatedCartData.totalPrice);
                    } finally {
                        rendererDep.setCartLoadingState(false);
                    }
                }, CONFIG.DEBOUNCE_DELAY); // Debounce delay from CONFIG
            });
        }
    });
}

/**
 * رویداد مربوط به دکمه مینی‌کارت (باز و بسته کردن دراپ‌داون) را ثبت می‌کند.
 * @param {Object} [dependencies] - Optional dependencies for testing.
 * @param {Object} [dependencies.api=api] - API module functions.
 * @param {Object} [dependencies.renderer=renderer] - Renderer module functions.
 */
export function setupMiniCartToggle(dependencies = {}) {
    const {
        api: apiDep = api,
        renderer: rendererDep = renderer
    } = dependencies;

    const miniCartBtn = domCache.miniCartBtn; // استفاده از کش DOM
    const miniCartDropdown = domCache.miniCartDropdown; // استفاده از کش DOM

    if (!miniCartBtn || !miniCartDropdown) {
        console.warn('Mini-cart button or dropdown not found. Skipping toggle setup.');
        return;
    }

    // Set initial ARIA attributes
    miniCartBtn.setAttribute('aria-haspopup', 'true');
    miniCartBtn.setAttribute('aria-expanded', 'false');
    miniCartBtn.setAttribute('aria-controls', 'mini-cart-dropdown');


    miniCartBtn.addEventListener('click', async function(event) {
        event.stopPropagation(); // جلوگیری از بسته شدن فوری دراپ‌داون در صورت کلیک روی دکمه
        const isHidden = miniCartDropdown.classList.toggle('hidden');
        miniCartBtn.setAttribute('aria-expanded', (!isHidden).toString()); // Update ARIA expanded state


        if (!isHidden) { // If dropdown is now visible
            rendererDep.setCartLoadingState(true, 'mini-cart-items-container'); // Set loading state specifically for mini-cart
            try {
                const data = await apiDep.fetchCartContents();
                rendererDep.renderMiniCartDetails(data.items, data.totalQuantity, data.totalPrice);
            } catch (error) {
                console.error('Failed to fetch mini-cart contents on toggle:', error);
                // پیام خطا توسط تابع API مربوطه نمایش داده شده است
                if (typeof window.showMessage === 'function') {
                    window.showMessage(MESSAGES.GENERAL_FETCH_ERROR, 'error');
                }
            } finally {
                rendererDep.setCartLoadingState(false, 'mini-cart-items-container'); // Clear loading state
            }
        }
    });

    // بسته شدن مینی‌کارت هنگام کلیک در خارج از آن
    document.addEventListener('click', function(event) {
        if (!miniCartBtn.contains(event.target) && !miniCartDropdown.contains(event.target)) {
            miniCartDropdown.classList.add('hidden');
            miniCartBtn.setAttribute('aria-expanded', 'false'); // Update ARIA expanded state
        }
    });
}

/**
 * رویدادهای مربوط به دکمه‌های "مشاهده سبد خرید" و "تسویه حساب" در مینی‌کارت را ثبت می‌کند.
 * @param {Object} [dependencies] - Optional dependencies for testing.
 * @param {Function} [dependencies.trackEvent=trackEvent] - Analytics tracking function.
 */
export function setupMiniCartActionButtons(dependencies = {}) {
    const {
        trackEvent: trackEventDep = trackEvent
    } = dependencies;

    const miniCartCheckoutBtn = domCache.miniCartCheckoutBtn; // استفاده از کش DOM
    const miniCartViewCartBtn = domCache.miniCartViewCartBtn; // استفاده از کش DOM

    if (miniCartCheckoutBtn) {
        miniCartCheckoutBtn.addEventListener('click', function() {
            trackEventDep('begin_checkout');
            window.location.href = CART_ROUTES.CHECKOUT;
        });
    } else {
        console.warn('Mini-cart checkout button not found. Skipping setup.');
    }

    if (miniCartViewCartBtn) {
        miniCartViewCartBtn.addEventListener('click', function() {
            trackEventDep('view_cart');
            window.location.href = CART_ROUTES.CART;
        });
    } else {
        console.warn('Mini-cart view cart button not found. Skipping setup.');
    }
}
