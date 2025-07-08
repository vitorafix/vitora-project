// cart.js
// این فایل شامل کلاس CartManager است که مسئول مدیریت کلی سبد خرید،
// هماهنگی بین ماژول‌های API، Renderer و Events، و نگهداری وضعیت کلی است.

// ایمپورت کردن توابع مورد نیاز از ماژول‌های دیگر
// این توابع مسئول برقراری ارتباط با API بک‌اند هستند.
import { fetchCartContents, addItemToCart, updateCartItem, removeCartItem } from './api.js'; 
// این توابع مسئول به‌روزرسانی رابط کاربری (DOM) بر اساس داده‌های سبد خرید هستند.
import { renderMiniCartDetails, renderMainCart, setCartLoadingState } from './renderer.js';
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
            totalPrice: 0
        };
        // Observer pattern: لیست آبزرورها برای اطلاع‌رسانی تغییرات سبد خرید
        this.observers = [];
        // پرچم جدید برای ردیابی وجود عناصر اصلی سبد خرید (مانند صفحه cart.blade.php)
        this.hasMainCartElements = false; 
        console.log('CartManager initialized.');
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
        // این تابع از renderer.js است.
        setCartLoadingState(true); 
        try {
            // فراخوانی API برای دریافت محتویات سبد خرید.
            // این تابع از api.js است و باید درخواست HTTP را به بک‌اند ارسال کند.
            const data = await fetchCartContents();

            // بررسی معتبر بودن داده‌های دریافتی با استفاده از نام‌گذاری کلیدهای snake_case
            // اطمینان حاصل کنید که ساختار داده‌های دریافتی از بک‌اند مطابق انتظار است.
            if (!data || !Array.isArray(data.items) || typeof data.total_quantity !== 'number' || typeof data.total_price !== 'number') {
                throw new Error('Invalid cart data received from API.');
            }

            // به‌روزرسانی وضعیت داخلی سبد خرید با نگاشت کلیدهای snake_case به camelCase
            this.cartData = {
                items: data.items,
                totalQuantity: data.total_quantity, // این مقدار از total_quantity در پاسخ API می‌آید
                totalPrice: data.total_price // این مقدار از total_price در پاسخ API می‌آید
            };

            // رندر کردن مینی‌کارت و سبد اصلی با داده‌های جدید
            // این توابع از renderer.js هستند و مسئول ساخت و به‌روزرسانی HTML مربوط به سبد خرید هستند.
            renderMiniCartDetails(this.cartData.items, this.cartData.totalQuantity, this.cartData.totalPrice);

            // فقط در صورتی که عناصر اصلی سبد خرید در صفحه وجود داشته باشند، سبد خرید اصلی را رندر کنید
            if (this.hasMainCartElements) {
                renderMainCart(this.cartData.items, this.cartData.totalQuantity, this.cartData.totalPrice);
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
            // این تابع از renderer.js است.
            setCartLoadingState(false); 
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
