// cart.js
// این فایل شامل کلاس CartManager است که مسئول مدیریت کلی سبد خرید،
// هماهنگی بین ماژول‌های API، Renderer و Events، و نگهداری وضعیت کلی است.

// ایمپورت کردن توابع مورد نیاز از ماژول‌های دیگر
// این توابع مسئول برقراری ارتباط با API بک‌اند هستند.
import { fetchCartContents, addItemToCart, updateCartItem, removeCartItem } from './api.js'; 
// این توابع مسئول به‌روزرسانی رابط کاربری (DOM) بر اساس داده‌های سبد خرید هستند.
// توجه: renderMainCart و renderMiniCartDetails اکنون در CartManager تعریف شده‌اند.
import { setCartLoadingState } from './renderer.js';
// این توابع مسئول کش کردن عناصر DOM و تنظیم Event Listenerها هستند.
import {
    initializeDOMCache,
    setupAddToCartButtons,
    setupMainCartQuantityButtons,
    setupMiniCartToggle,
    setupMiniCartActionButtons,
    getDOM // ایمپورت getDOM برای بررسی عناصر در صورت نیاز
} from './events.js';

/**
 * کلاس CartManager مسئول مدیریت کلی منطق سبد خرید در سمت کلاینت است.
 * این کلاس ماژول‌های API، Renderer و Events را هماهنگ می‌کند.
 */
class CartManager {
    /**
     * سازنده کلاس CartManager.
     * @constructor
     */
    constructor() {
        // وضعیت اولیه سبد خرید
        this.cartData = {
            items: [],
            totalQuantity: 0,
            totalPrice: 0,
            cartTotals: {} // اضافه شد: جمع کل‌ها
        };
        // Observer pattern: لیست آبزرورها برای اطلاع‌رسانی تغییرات سبد خرید
        this.observers = [];
        // پرچم جدید برای ردیابی وجود عناصر اصلی سبد خرید (مانند صفحه cart.blade.php)
        this.hasMainCartElements = false; 
        console.log('CartManager initialized.');

        // 📌 کش کردن عناصر DOM اصلی سبد خرید در سازنده
        this.DOM = {
            cartEmptyMessage: document.getElementById('cart-empty-message'),
            cartSummary: document.getElementById('cart-summary'),
            cartItemsContainer: document.getElementById('cart-items-container'), // تغییر از #cart-items به #cart-items-container
            cartTotalPrice: document.getElementById('cart-total-price')
            // اگر عناصر دیگری برای subtotal, shipping, tax, discount دارید، اینجا اضافه کنید.
            // cartSubtotalPrice: document.getElementById('cart-subtotal-price'),
            // cartShippingPrice: document.getElementById('cart-shipping-price'),
            // cartTaxPrice: document.getElementById('cart-tax-price'),
            // cartDiscountPrice: document.getElementById('cart-discount-price'),
        };

        // کش کردن عناصر DOM مینی‌کارت در سازنده (برای renderMiniCartDetails)
        this.miniCartDOM = {
            miniCartToggle: document.getElementById('mini-cart-toggle'),
            miniCartDropdown: document.getElementById('mini-cart-dropdown'),
            miniCartItemsContainer: document.getElementById('mini-cart-items-container'),
            miniCartTotalQuantity: document.getElementById('mini-cart-total-quantity'),
            miniCartTotalPrice: document.getElementById('mini-cart-total-price'),
            miniCartEmptyMessage: document.getElementById('mini-cart-empty-message'),
            miniCartSummary: document.getElementById('mini-cart-summary'),
        };
    }

    /**
     * متد اصلی برای راه‌اندازی سبد خرید.
     * این متد باید پس از بارگذاری کامل DOM فراخوانی شود.
     * مسئول کش کردن عناصر DOM، ثبت event listenerها و بارگذاری اولیه محتویات سبد خرید است.
     */
    async init() {
        console.log('Initializing CartManager...');

        // مرحله 1: کش DOM را راه‌اندازی کنید و عناصر حیاتی سبد خرید اصلی را بررسی کنید
        // این تابع از events.js است و تعیین می‌کند که آیا عناصر اصلی سبد خرید (مانند #cart-items-container) در صفحه وجود دارند یا خیر.
        this.hasMainCartElements = initializeDOMCache();

        // مرحله 2: شنونده‌های رویداد را تنظیم کنید، فقط در صورتی که عناصر مربوطه یافت شوند
        // این توابع از events.js هستند و دکمه‌ها و تعاملات کاربری را به منطق CartManager متصل می‌کنند.
        this.safeExecute(setupAddToCartButtons, 'خطا در راه‌اندازی دکمه‌های افزودن به سبد.');
        this.safeExecute(setupMiniCartToggle, 'خطا در راه‌اندازی دکمه مینی‌کارت.');
        this.safeExecute(setupMiniCartActionButtons, 'خطا در راه‌اندازی دکمه‌های عملیات مینی‌کارت.');

        // فقط در صورتی که عناصر اصلی سبد خرید وجود داشته باشند، دکمه‌های تعداد سبد اصلی را راه‌اندازی کنید
        if (this.hasMainCartElements) {
            this.safeExecute(setupMainCartQuantityButtons, 'خطا در راه‌اندازی دکمه‌های تعداد سبد اصلی.');
        } else {
            console.warn('Main cart elements not found, skipping setup for main cart quantity buttons.');
        }

        // مرحله 3: محتویات سبد خرید را بارگذاری و رندر کنید
        // این مهم‌ترین بخش برای نمایش اولیه سبد خرید است.
        await this.loadAndRenderCart();

        console.log('CartManager initialization complete.');
    }

    /**
     * محتویات سبد خرید را از API دریافت کرده و UI را به‌روزرسانی می‌کند.
     * این متد می‌تواند پس از هر عملیات تغییر سبد (افزودن، حذف، به‌روزرسانی) فراخوانی شود.
     */
    async loadAndRenderCart() {
        // نمایش وضعیت بارگذاری (مثلاً یک اسپینر یا پیام "در حال بارگذاری...")
        setCartLoadingState(true); 
        try {
            // فراخوانی API برای دریافت محتویات سبد خرید.
            // این تابع از api.js است و باید درخواست HTTP را به بک‌اند ارسال کند.
            const data = await fetchCartContents();

            // بررسی معتبر بودن داده‌های دریافتی
            if (!data || !Array.isArray(data.items) || data.totalQuantity === undefined || data.totalPrice === undefined || typeof data.cartTotals !== 'object') {
                throw new Error('Invalid cart data received from API.');
            }

            // به‌روزرسانی وضعیت داخلی سبد خرید با داده‌های جدید
            this.cartData = {
                items: data.items,
                totalQuantity: data.totalQuantity, 
                totalPrice: data.totalPrice,
                cartTotals: data.cartTotals 
            };

            // رندر کردن مینی‌کارت با داده‌های جدید
            this.renderMiniCartDetails(this.cartData.items, this.cartData.totalQuantity, this.cartData.totalPrice);

            // فقط در صورتی که عناصر اصلی سبد خرید وجود داشته باشند، سبد خرید اصلی را رندر کنید
            if (this.hasMainCartElements) {
                // ✅ مدیریت سبد خالی
                if (this.cartData.items.length === 0) {
                    this.renderEmptyCart();
                } else {
                    this.renderMainCart(this.cartData.items, this.cartData.cartTotals);
                }
            } else {
                console.warn('Main cart elements not present, skipping main cart rendering.');
            }

            this.notify('cartChanged', this.cartData); // اطلاع‌رسانی به آبزرورها
        } catch (error) {
            console.error('Failed to load and render cart:', error);
            // نمایش پیام خطا به کاربر
            if (typeof window.showMessage === 'function') {
                window.showMessage('خطا در بارگذاری سبد خرید. لطفاً دوباره تلاش کنید.', 'error');
            }
        } finally {
            // پنهان کردن وضعیت بارگذاری
            setCartLoadingState(false); 
        }
    }

    /**
     * رندر کردن وضعیت سبد خرید خالی در صفحه اصلی سبد خرید.
     */
    renderEmptyCart() {
        if (this.DOM.cartEmptyMessage) {
            this.DOM.cartEmptyMessage.classList.remove('hidden'); // نمایش پیام خالی بودن
        }
        if (this.DOM.cartSummary) {
            this.DOM.cartSummary.classList.add('hidden'); // پنهان کردن خلاصه سبد
        }
        if (this.DOM.cartItemsContainer) {
            this.DOM.cartItemsContainer.innerHTML = '<p class="text-center text-gray-500 py-10 text-lg">سبد خرید شما خالی است.</p>'; // پاک کردن آیتم‌ها و نمایش پیام
        }
    }

    /**
     * رندر کردن محتویات سبد خرید اصلی در صفحه سبد خرید (وقتی سبد خالی نیست).
     * @param {Array<Object>} items - آرایه‌ای از آیتم‌های سبد خرید.
     * @param {Object} cartTotals - شیء شامل جزئیات جمع کل سبد خرید (subtotal, shipping, tax, discount, total).
     */
    renderMainCart(items, cartTotals) {
        if (this.DOM.cartEmptyMessage) {
            this.DOM.cartEmptyMessage.classList.add('hidden'); // پنهان کردن پیام خالی بودن
        }
        if (this.DOM.cartSummary) {
            this.DOM.cartSummary.classList.remove('hidden'); // نمایش خلاصه سبد
        }

        if (this.DOM.cartItemsContainer) {
            this.DOM.cartItemsContainer.innerHTML = ''; // پاک کردن آیتم‌های قبلی

            items.forEach(item => {
                const itemHtml = `
                    <div class="flex flex-col md:flex-row items-center justify-between bg-white p-4 md:p-6 rounded-lg shadow-sm mb-4 border border-gray-200 transition-all duration-300 hover:shadow-md" data-product-id="${item.product_id}" data-cart-item-id="${item.cart_item_id}">
                        <div class="flex items-center w-full md:w-auto mb-4 md:mb-0">
                            <img src="${item.product.image_url || 'https://placehold.co/80x80/E0F2F7/000000?text=No+Image'}" alt="${item.product_name}" class="w-20 h-20 rounded-lg object-cover ml-4 flex-shrink-0">
                            <div class="flex-grow text-center md:text-right">
                                <h3 class="text-lg font-bold text-brown-900 mb-1">${item.product_name}</h3>
                                ${item.product_variant_id ? `<p class="text-sm text-gray-600">نوع: ${item.variant_name} - ${item.variant_value}</p>` : ''}
                                <p class="text-gray-700 text-sm">قیمت واحد: ${item.product_price.toLocaleString('fa-IR')} تومان</p>
                                <button type="button" class="remove-item-btn text-red-500 hover:text-red-700 transition-colors duration-200 mt-2 text-sm">
                                    <i class="fas fa-trash-alt ml-1"></i> حذف
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center justify-center md:justify-end w-full md:w-auto">
                            <div class="flex items-center quantity-control bg-gray-100 rounded-full px-3 py-1 shadow-inner">
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
                        </div>
                        <span class="item-subtotal text-green-700 font-bold text-lg mt-4 md:mt-0" data-subtotal="${item.subtotal}">
                            ${item.subtotal.toLocaleString('fa-IR')} تومان
                        </span>
                    </div>
                `;
                this.DOM.cartItemsContainer.insertAdjacentHTML('beforeend', itemHtml);
            });
        }

        if (this.DOM.cartTotalPrice) {
            this.DOM.cartTotalPrice.textContent = (cartTotals.total ?? 0).toLocaleString('fa-IR') + ' تومان';
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
    }

    /**
     * رندر کردن جزئیات مینی‌کارت (نمایش در هدر).
     * @param {Array<Object>} items - آرایه‌ای از آیتم‌های سبد خرید.
     * @param {number} totalQuantity - تعداد کل محصولات در سبد خرید.
     * @param {number} totalPrice - قیمت کل سبد خرید.
     */
    renderMiniCartDetails(items, totalQuantity, totalPrice) {
        const DOM = this.miniCartDOM; // استفاده از DOM کش شده برای مینی‌کارت

        // بررسی وجود عناصر مینی‌کارت قبل از ادامه
        if (!DOM.miniCartItemsContainer || !DOM.miniCartTotalQuantity || !DOM.miniCartTotalPrice || !DOM.miniCartEmptyMessage || !DOM.miniCartSummary) {
            console.warn('Mini cart DOM elements not fully available. Skipping mini cart rendering.');
            return;
        }

        // به‌روزرسانی تعداد کل در آیکون هدر
        if (DOM.miniCartTotalQuantity) {
            DOM.miniCartTotalQuantity.textContent = totalQuantity.toLocaleString('fa-IR');
            DOM.miniCartTotalQuantity.classList.toggle('hidden', totalQuantity === 0);
        }

        // رندر آیتم‌ها در دراپ‌داون
        DOM.miniCartItemsContainer.innerHTML = ''; // پاک کردن آیتم‌های قبلی
        if (items.length === 0) {
            DOM.miniCartEmptyMessage.classList.remove('hidden');
            DOM.miniCartSummary.classList.add('hidden');
            DOM.miniCartItemsContainer.classList.add('hidden');
        } else {
            DOM.miniCartEmptyMessage.classList.add('hidden');
            DOM.miniCartSummary.classList.remove('hidden');
            DOM.miniCartItemsContainer.classList.remove('hidden');

            items.forEach(item => {
                const itemHtml = `
                    <div class="flex items-center justify-between py-2 border-b border-gray-200 last:border-b-0" data-product-id="${item.product_id}">
                        <div class="flex items-center">
                            <img src="${item.product.image_url || 'https://placehold.co/50x50/E0F2F7/000000?text=No+Image'}" alt="${item.product_name}" class="w-12 h-12 rounded-lg object-cover ml-3">
                            <div>
                                <h4 class="text-sm font-medium text-gray-800">${item.product_name}</h4>
                                <p class="text-xs text-gray-500">${item.quantity} x ${item.product_price.toLocaleString('fa-IR')} تومان</p>
                            </div>
                        </div>
                        <button type="button" class="remove-item-btn text-red-500 hover:text-red-700 transition-colors duration-200" aria-label="حذف آیتم">
                            <i class="fas fa-times-circle"></i>
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
    }


    /**
     * اضافه کردن یک محصول به سبد خرید.
     * @param {string} productId - شناسه محصول.
     * @param {number} quantity - تعداد محصول برای اضافه کردن.
     */
    async addItem(productId, quantity = 1) {
        setCartLoadingState(true);
        try {
            // فراخوانی تابع API برای افزودن آیتم. این تابع از api.js است.
            const response = await addItemToCart(productId, quantity);
            if (response.success) {
                // پس از موفقیت، سبد خرید را دوباره بارگذاری و رندر می‌کند.
                await this.loadAndRenderCart();
                if (typeof window.showMessage === 'function') {
                    window.showMessage('محصول به سبد خرید اضافه شد.', 'success');
                }
            } else {
                throw new Error(response.message || 'خطا در افزودن محصول به سبد خرید.');
            }
        } catch (error) {
            console.error('Error adding item to cart:', error);
            if (typeof window.showMessage === 'function') {
                window.showMessage(error.message || 'خطا در افزودن محصول به سبد خرید. لطفاً دوباره تلاش کنید.', 'error');
            }
        } finally {
            setCartLoadingState(false);
        }
    }

    /**
     * به‌روزرسانی تعداد یک آیتم در سبد خرید.
     * @param {string} productId - شناسه محصول.
     * @param {number} newQuantity - تعداد جدید محصول.
     */
    async updateItemQuantity(productId, newQuantity) {
        setCartLoadingState(true);
        try {
            // فراخوانی تابع API برای به‌روزرسانی آیتم. این تابع از api.js است.
            const response = await updateCartItem(productId, newQuantity);
            if (response.success) {
                // پس از موفقیت، سبد خرید را دوباره بارگذاری و رندر می‌کند.
                await this.loadAndRenderCart();
                if (typeof window.showMessage === 'function') {
                    window.showMessage('تعداد محصول به‌روزرسانی شد.', 'success');
                }
            } else {
                throw new Error(response.message || 'خطا در به‌روزرسانی تعداد محصول.');
            }
        } catch (error) {
            console.error('Error updating item quantity:', error);
            if (typeof window.showMessage === 'function') {
                window.showMessage(error.message || 'خطا در به‌روزرسانی تعداد محصول. لطفاً دوباره تلاش کنید.', 'error');
            }
        } finally {
            setCartLoadingState(false);
        }
    }

    /**
     * حذف یک آیتم از سبد خرید.
     * @param {string} productId - شناسه محصول برای حذف.
     */
    async removeItem(productId) {
        setCartLoadingState(true);
        try {
            // فراخوانی تابع API برای حذف آیتم. این تابع از api.js است.
            const response = await removeCartItem(productId);
            if (response.success) {
                // پس از موفقیت، سبد خرید را دوباره بارگذاری و رندر می‌کند.
                await this.loadAndRenderCart();
                if (typeof window.showMessage === 'function') {
                    window.showMessage('محصول از سبد خرید حذف شد.', 'success');
                }
            } else {
                throw new Error(response.message || 'خطا در حذف محصول از سبد خرید.');
            }
        } catch (error) {
            console.error('Error removing item from cart:', error);
            if (typeof window.showMessage === 'function') {
                window.showMessage(error.message || 'خطا در حذف محصول از سبد خرید. لطفاً دوباره تلاش کنید.', 'error');
            }
        } finally {
            setCartLoadingState(false);
        }
    }

    /**
     * Executes a synchronous function safely, catching errors and displaying a message.
     * This acts as a simple error boundary for synchronous code blocks.
     * @param {Function} fn - The synchronous function to execute.
     * @param {string} fallbackMessage - The message to display if an error occurs.
     * @returns {any | null} The result of the function execution, or null if an error occurred.
     */
    safeExecute(fn, fallbackMessage) {
        try {
            return fn();
        } catch (error) {
            console.error('Safe execution failed:', error);
            if (typeof window.showMessage === 'function') {
                window.showMessage(fallbackMessage || 'خطای داخلی در پردازش عملیات. لطفاً دوباره تلاش کنید.', 'error');
            } else {
                console.warn('window.showMessage is not available to display fallback message.');
            }
            return null;
        }
    }

    /**
     * دریافت یک آیتم خاص از سبد خرید بر اساس شناسه محصول.
     * @param {number} productId - شناسه محصول.
     * @returns {Object | undefined} آیتم سبد خرید یا undefined اگر یافت نشود.
     */
    getCartItem(productId) {
        return this.cartData.items.find(item => item.product_id === productId);
    }

    /**
     * محاسبه کل قیمت فعلی سبد خرید.
     * @returns {number} قیمت کل سبد خرید.
     */
    getTotalPrice() {
        return this.cartData.totalPrice;
    }

    /**
     * بررسی خالی بودن سبد خرید.
     * @returns {boolean} true اگر سبد خرید خالی باشد، در غیر این صورت false.
     */
    isEmpty() {
        return this.cartData.items.length === 0;
    }

    /**
     * دریافت خلاصه‌ای از وضعیت فعلی سبد خرید.
     * @returns {Object} خلاصه سبد خرید شامل تعداد کل، قیمت کل و وضعیت خالی بودن.
     */
    getCartSummary() {
        return {
            totalQuantity: this.cartData.totalQuantity,
            totalPrice: this.cartData.totalPrice,
            itemCount: this.cartData.items.length,
            isEmpty: this.isEmpty()
        };
    }

    /**
     * ثبت یک آبزرور برای گوش دادن به تغییرات سبد خرید.
     * @param {Function} observer - تابعی که هنگام تغییر سبد خرید فراخوانی می‌شود.
     */
    subscribe(observer) {
        if (typeof observer === 'function') {
            this.observers.push(observer);
        } else {
            console.warn('Observer must be a function.');
        }
    }

    /**
     * حذف یک آبزرور از لیست.
     * @param {Function} observer - تابعی که باید حذف شود.
     */
    unsubscribe(observer) {
        this.observers = this.observers.filter(obs => obs !== observer);
    }

    /**
     * اطلاع‌رسانی به تمامی آبزرورها در مورد تغییرات سبد خرید.
     * @param {string} eventType - نوع رویداد (مثلاً 'cartChanged').
     * @param {Object} data - داده‌های مربوط به رویداد.
     */
    notify(eventType, data) {
        this.observers.forEach(observer => {
            try {
                observer(eventType, data);
            } catch (error) {
                console.error('Error notifying observer:', error);
            }
        });
    }
}

// ایجاد یک نمونه از CartManager و راه‌اندازی آن پس از بارگذاری کامل DOM
document.addEventListener('DOMContentLoaded', () => {
    const cartManager = new CartManager();
    // cartManager را به صورت سراسری در دسترس قرار دهید تا ماژول‌های دیگر (مانند events.js) بتوانند به آن دسترسی داشته باشند
    window.cartManager = cartManager;
    cartManager.init();
});
