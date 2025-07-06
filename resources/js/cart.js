// cart.js
// این فایل شامل کلاس CartManager است که مسئول مدیریت کلی سبد خرید،
// هماهنگی بین ماژول‌های API، Renderer و Events، و نگهداری وضعیت کلی است.

// ایمپورت کردن توابع مورد نیاز از ماژول‌های دیگر
import { fetchCartContents } from './api.js';
import { renderMiniCartDetails, renderMainCart, setCartLoadingState } from './renderer.js';
import {
    initializeDOMCache,
    setupAddToCartButtons,
    setupMainCartQuantityButtons,
    setupMiniCartToggle,
    setupMiniCartActionButtons
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
        console.log('CartManager initialized.');
    }

    /**
     * متد اصلی برای راه‌اندازی سبد خرید.
     * این متد باید پس از بارگذاری کامل DOM فراخوانی شود.
     * مسئول کش کردن عناصر DOM، ثبت event listenerها و بارگذاری اولیه محتویات سبد خرید است.
     */
    async init() {
        console.log('Initializing CartManager...');

        // تعریف وظایف راه‌اندازی برای اجرای ایمن
        const setupTasks = [
            { fn: initializeDOMCache, message: 'خطا در کش کردن عناصر DOM.' },
            { fn: setupAddToCartButtons, message: 'خطا در راه‌اندازی دکمه‌های افزودن به سبد.' },
            { fn: setupMainCartQuantityButtons, message: 'خطا در راه‌اندازی دکمه‌های تعداد سبد اصلی.' },
            { fn: setupMiniCartToggle, message: 'خطا در راه‌اندازی دکمه مینی‌کارت.' },
            { fn: setupMiniCartActionButtons, message: 'خطا در راه‌اندازی دکمه‌های عملیات مینی‌کارت.' }
        ];

        // اجرای ایمن هر وظیفه راه‌اندازی
        for (const task of setupTasks) {
            this.safeExecute(task.fn, task.message);
        }

        // بارگذاری اولیه محتویات سبد خرید و رندر کردن آن‌ها
        await this.loadAndRenderCart();

        console.log('CartManager initialization complete.');
    }

    /**
     * محتویات سبد خرید را از API دریافت کرده و UI را به‌روزرسانی می‌کند.
     * این متد می‌تواند پس از هر عملیات تغییر سبد (افزودن، حذف، به‌روزرسانی) فراخوانی شود.
     */
    async loadAndRenderCart() {
        setCartLoadingState(true); // نمایش وضعیت بارگذاری کلی
        try {
            const data = await fetchCartContents();

            // بررسی معتبر بودن داده‌های دریافتی با استفاده از نام‌گذاری کلیدهای snake_case
            if (!data || !Array.isArray(data.items) || typeof data.total_quantity !== 'number' || typeof data.total_price !== 'number') {
                throw new Error('Invalid cart data received from API.');
            }

            // به‌روزرسانی وضعیت داخلی سبد خرید با نگاشت کلیدهای snake_case به camelCase
            this.cartData = {
                items: data.items,
                totalQuantity: data.total_quantity, // اصلاح شد
                totalPrice: data.total_price       // اصلاح شد
            };

            // رندر کردن مینی‌کارت و سبد اصلی با داده‌های جدید
            renderMiniCartDetails(this.cartData.items, this.cartData.totalQuantity, this.cartData.totalPrice);
            renderMainCart(this.cartData.items, this.cartData.totalQuantity, this.cartData.totalPrice);

            this.notify('cartChanged', this.cartData); // اطلاع‌رسانی به آبزرورها
        } catch (error) {
            console.error('Failed to load and render cart:', error);
            // نمایش پیام خطا به کاربر
            if (typeof window.showMessage === 'function') {
                window.showMessage('خطا در بارگذاری سبد خرید. لطفاً دوباره تلاش کنید.', 'error');
            }
        } finally {
            setCartLoadingState(false); // پنهان کردن وضعیت بارگذاری
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
    cartManager.init();
});
