// cart.js
console.log('cart.js loaded and starting...');

// این فایل شامل کلاس CartManager است که مسئول مدیریت کلی سبد خرید،
// هماهنگی بین ماژول‌های API، Renderer و Events، و نگهداری وضعیت کلی است.

// ایمپورت کردن توابع مورد نیاز از ماژول‌های دیگر
// این توابع مسئول برقراری ارتباط با API بک‌اند هستند.
import { fetchCartContents, addItem, updateCartItemQuantity, removeCartItem, clearCart, applyCoupon, removeCoupon } from './api.js';
// این توابع مسئول به‌روزرسانی رابط کاربری (DOM) بر اساس داده‌های سبد خرید هستند.
import { setCartLoadingState, renderMainCart, renderMiniCartDetails } from './renderer.js';
// این توابع مسئول کش کردن عناصر DOM و تنظیم Event Listenerها هستند.
import {
    initializeDOMCache,
    setupMiniCartToggle,
    getDOM, // اضافه کردن تابع getDOM
    debounce // اضافه کردن تابع debounce از events.js
} from './events.js';

// جلوگیری از اجرای مکرر initialization
let isInitialized = false;

/**
 * تابع برای آپدیت UI بدون reload کامل cart.
 * این تابع هم برای main cart و هم برای mini cart کار می‌کند.
 * @param {string} itemId - شناسه آیتم سبد خرید.
 * @param {number} newQuantity - تعداد جدید آیتم.
 * @param {number} itemPrice - قیمت واحد محصول.
 */
function updateQuantityInUI(itemId, newQuantity, itemPrice) {
    // آپدیت main cart
    const mainCartItem = document.querySelector(`#cart-items-container [data-cart-item-id="${itemId}"]`);
    if (mainCartItem) {
        const quantitySpan = mainCartItem.querySelector('.item-quantity');
        const subtotalSpan = mainCartItem.querySelector('.item-subtotal');
        const oldQuantity = parseInt(quantitySpan.textContent);

        quantitySpan.textContent = newQuantity;
        quantitySpan.setAttribute('data-quantity', newQuantity);

        // آپدیت ساب‌توتال آیتم
        const newSubtotal = newQuantity * itemPrice;
        subtotalSpan.textContent = newSubtotal.toLocaleString('fa-IR') + ' تومان';
        subtotalSpan.setAttribute('data-subtotal', newSubtotal);

        console.log(`Main cart item ${itemId} quantity updated from ${oldQuantity} to ${newQuantity}. Subtotal updated to: ${newSubtotal}`);
    }

    // آپدیت mini cart
    const miniCartItem = document.querySelector(`#mini-cart-items-container [data-cart-item-id="${itemId}"]`);
    if (miniCartItem) {
        const quantitySpan = miniCartItem.querySelector('.mini-cart-item-quantity');
        const subtotalSpan = miniCartItem.querySelector('.mini-cart-item-subtotal');
        const oldQuantity = parseInt(quantitySpan.textContent);

        quantitySpan.textContent = newQuantity;
        quantitySpan.setAttribute('data-quantity', newQuantity);

        // آپدیت ساب‌توتال آیتم
        const newSubtotal = newQuantity * itemPrice;
        subtotalSpan.textContent = newSubtotal.toLocaleString('fa-IR') + ' تومان';
        subtotalSpan.setAttribute('data-subtotal', newSubtotal);

        console.log(`Mini cart item ${itemId} quantity updated from ${oldQuantity} to ${newQuantity}. Subtotal updated to: ${newSubtotal}`);
    }
}

/**
 * کلاس CartManager مسئول مدیریت کلی وضعیت سبد خرید و هماهنگی بین API، Renderer و Events است.
 */
class CartManager {
    constructor() {
        if (isInitialized) {
            console.warn('CartManager already initialized. Skipping re-initialization.');
            return;
        }
        this.dom = getDOM(); // کش کردن عناصر DOM
        this.debouncedLoadAndRenderCart = debounce(this.loadAndRenderCart.bind(this), 300); // Debounce برای لود و رندر
        this.debouncedUpdateCartItemQuantity = debounce(this.updateItemQuantity.bind(this), 500); // Debounce برای آپدیت تعداد
        isInitialized = true;
        console.log('CartManager instance created.'); // اضافه شده برای دیباگ
    }

    /**
     * متد راه‌اندازی اولیه.
     * عناصر DOM را کش می‌کند و Event Listenerها را تنظیم می‌نماید.
     */
    init() {
        console.log('CartManager initializing...');
        initializeDOMCache(); // اطمینان از کش شدن DOM
        setupMiniCartToggle();
        this.setupEventListeners(); // ابتدا Event Listenerها را تنظیم کنید
        this.loadAndRenderCart(); // سپس محتویات سبد خرید را بارگذاری کنید
        console.log('CartManager init completed.'); // اضافه شده برای دیباگ
    }

    /**
     * تنظیم Event Listenerهای اصلی برای تعاملات سبد خرید.
     */
    setupEventListeners() {
        console.log('Setting up event listeners...'); // اضافه شده برای دیباگ
        // Event Listener برای دکمه‌های افزودن به سبد خرید (در صفحات محصول)
        const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
        console.log('Found add-to-cart buttons:', addToCartButtons.length); // اضافه شده برای دیباگ
        addToCartButtons.forEach(button => {
            // ذخیره متن اصلی دکمه برای بازگرداندن پس از عملیات
            button.dataset.originalText = button.innerHTML;
            button.addEventListener('click', this.handleAddToCartClick.bind(this));
            console.log('Attached click listener to:', button); // اضافه شده برای دیباگ
        });

        // Event Listener برای دکمه‌های +/- و حذف در سبد خرید اصلی
        // این بخش فقط در صورتی اجرا می‌شود که DOM.cartItemsContainer وجود داشته باشد
        if (this.dom.cartItemsContainer) {
            this.dom.cartItemsContainer.addEventListener('click', this.handleCartItemAction.bind(this));
            console.log('Attached click listener to main cart container.'); // اضافه شده برای دیباگ
        }

        // Event Listener برای دکمه‌های اعمال/حذف کوپن
        const applyCouponBtn = document.getElementById('apply-coupon-btn');
        const removeCouponBtn = document.getElementById('remove-coupon-btn');
        const couponCodeInput = document.getElementById('coupon-code-input');

        if (applyCouponBtn && couponCodeInput) {
            applyCouponBtn.addEventListener('click', async () => {
                const couponCode = couponCodeInput.value;
                if (couponCode) {
                    await this.applyCoupon(couponCode);
                } else {
                    window.showMessage('لطفاً کد تخفیف را وارد کنید.', 'warning');
                }
            });
            console.log('Attached click listener to apply coupon button.'); // اضافه شده برای دیباگ
        }

        if (removeCouponBtn) {
            removeCouponBtn.addEventListener('click', async () => {
                await this.removeCoupon();
            });
            console.log('Attached click listener to remove coupon button.'); // اضافه شده برای دیباگ
        }

        // Event Listener برای دکمه پاکسازی سبد خرید
        const clearCartBtn = document.getElementById('clear-cart-btn');
        if (clearCartBtn) {
            clearCartBtn.addEventListener('click', async () => {
                // استفاده از showMessage به جای confirm برای جلوگیری از مشکلات iFrame
                window.showConfirmationModal('پاک کردن سبد خرید', 'آیا مطمئن هستید که می‌خواهید سبد خرید خود را پاک کنید؟', () => {
                    this.clearCart();
                });
            });
            console.log('Attached click listener to clear cart button.'); // اضافه شده برای دیباگ
        }
    }

    /**
     * هندل کردن کلیک روی دکمه "افزودن به سبد خرید".
     * @param {Event} event
     */
    async handleAddToCartClick(event) {
        console.log('Add to cart button clicked!'); // اضافه شده برای دیباگ
        event.preventDefault();
        const button = event.currentTarget;
        const productId = button.dataset.productId;
        const quantity = parseInt(button.dataset.quantity || 1); // مقدار پیش‌فرض 1
        const productVariantId = button.dataset.productVariantId || null;
        const originalButtonText = button.dataset.originalText; // بازیابی متن اصلی دکمه

        if (productId) {
            // نمایش وضعیت بارگذاری
            button.disabled = true;
            button.classList.add('opacity-50', 'cursor-not-allowed');
            button.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال افزودن...';

            console.log(`Adding product ${productId} with quantity ${quantity} to cart.`); // اضافه شده برای دیباگ
            try {
                const response = await this.addItem(productId, quantity, productVariantId);
                window.showMessage(response.message || 'محصول با موفقیت به سبد خرید اضافه شد.', 'success');
            } catch (error) {
                const errorMessage = error.response?.data?.message || 'خطا در افزودن محصول به سبد خرید. لطفاً دوباره تلاش کنید.';
                window.showMessage(errorMessage, 'error');
                console.error('Error adding product to cart:', error);
            } finally {
                // مخفی کردن وضعیت بارگذاری
                button.disabled = false;
                button.classList.remove('opacity-50', 'cursor-not-allowed');
                button.innerHTML = originalButtonText; // بازگرداندن متن اصلی دکمه
            }
        } else {
            console.error('Product ID not found for add to cart button.');
            window.showMessage('خطا: شناسه محصول یافت نشد.', 'error');
        }
    }

    /**
     * هندل کردن کلیک روی دکمه‌های +/- و حذف در سبد خرید اصلی.
     * @param {Event} event
     */
    handleCartItemAction(event) {
        // بررسی برای دکمه‌های افزایش/کاهش تعداد
        if (event.target.matches('.quantity-btn') || event.target.closest('.quantity-btn')) {
            event.preventDefault();
            const button = event.target.matches('.quantity-btn') ? event.target : event.target.closest('.quantity-btn');
            const action = button.dataset.action;
            const cartItemId = button.dataset.cartItemId;
            const quantitySpan = document.querySelector(`#cart-items-container [data-cart-item-id="${cartItemId}"] .item-quantity`);
            const itemPrice = parseFloat(document.querySelector(`#cart-items-container [data-cart-item-id="${cartItemId}"]`).dataset.unitPrice); // فرض می‌کنیم قیمت واحد در data-unit-price ذخیره شده

            if (!quantitySpan || !cartItemId || isNaN(itemPrice)) {
                console.error('Required data for quantity update not found.', { quantitySpan, cartItemId, itemPrice });
                window.showMessage('خطا در به‌روزرسانی تعداد. اطلاعات ناقص است.', 'error');
                return;
            }

            let newQuantity = parseInt(quantitySpan.textContent);
            if (action === 'increase') {
                newQuantity++;
            } else if (action === 'decrease') {
                newQuantity--;
            }

            // اگر تعداد به صفر رسید، آیتم را حذف می‌کنیم
            if (newQuantity <= 0) {
                // استفاده از showMessage به جای confirm برای جلوگیری از مشکلات iFrame
                window.showConfirmationModal('حذف محصول', 'آیا مطمئن هستید که می‌خواهید این محصول را از سبد خرید حذف کنید؟', () => {
                    this.removeItem(cartItemId);
                });
                return;
            }

            // آپدیت UI بلافاصله
            updateQuantityInUI(cartItemId, newQuantity, itemPrice);

            // آپدیت server از طریق CartManager با debounce
            this.debouncedUpdateCartItemQuantity(cartItemId, newQuantity); // استفاده از نسخه debounce شده

            return;
        }

        // بررسی برای دکمه‌های حذف (اگر جداگانه از دکمه‌های تعداد باشند)
        if (event.target.matches('.remove-item-btn') || event.target.closest('.remove-item-btn')) {
            event.preventDefault();
            const removeBtn = event.target.matches('.remove-item-btn') ? event.target : event.target.closest('.remove-item-btn');
            const cartItemId = removeBtn.getAttribute('data-cart-item-id');

            if (cartItemId) {
                console.log('Remove button clicked for item:', cartItemId);
                // استفاده از showMessage به جای confirm برای جلوگیری از مشکلات iFrame
                window.showConfirmationModal('حذف محصول', 'آیا مطمئن هستید که می‌خواهید این محصول را از سبد خرید حذف کنید؟', () => {
                    this.removeItem(cartItemId);
                });
            }
            return;
        }
    }

    /**
     * بارگذاری محتویات سبد خرید از API و رندر کردن آن در UI.
     */
    async loadAndRenderCart() {
        setCartLoadingState(true);
        try {
            const response = await fetchCartContents();
            if (response.success) {
                const cartContents = response.data;
                // فقط در صورتی renderMainCart را فراخوانی کنید که DOM.cartItemsContainer وجود داشته باشد
                if (this.dom.cartItemsContainer) {
                    // تغییر در اینجا: ارسال cartContents.summary به عنوان cartTotals
                    renderMainCart(cartContents.items, cartContents.summary); 
                }
                // تغییر در اینجا: ارسال cartContents.summary.totalQuantity و cartContents.summary.totalPrice
                renderMiniCartDetails(cartContents.items, cartContents.summary.totalQuantity, cartContents.summary.totalPrice); 
                console.log('Cart contents loaded and rendered successfully.');
            } else {
                window.showMessage(response.message, 'error');
            }
        } catch (error) {
            console.error('Error loading cart contents:', error);
            window.showMessage('خطا در بارگذاری سبد خرید. لطفاً دوباره تلاش کنید.', 'error');
            renderMainCart([], {}); // نمایش سبد خرید خالی در صورت خطا
            renderMiniCartDetails([], 0, 0);
        } finally {
            setCartLoadingState(false);
        }
    }

    /**
     * افزودن محصول به سبد خرید.
     * @param {number} productId
     * @param {number} quantity
     * @param {number|null} productVariantId
     */
    async addItem(productId, quantity, productVariantId = null) {
        setCartLoadingState(true);
        try {
            const response = await addItem(productId, quantity, productVariantId);
            if (response.success) {
                // پیام موفقیت از اینجا نمایش داده می‌شود
                // window.showMessage(response.message || 'محصول با موفقیت به سبد خرید اضافه شد.', 'success');
                await this.loadAndRenderCart(); // Reload cart contents after adding
            } else {
                // پیام خطا از اینجا نمایش داده می‌شود
                // window.showMessage(response.message, 'error');
            }
            return response; // برای استفاده در handleAddToCartClick
        } catch (error) {
            // خطا از اینجا مدیریت می‌شود
            // console.error('Error adding item to cart:', error);
            // window.showMessage('خطا در افزودن محصول به سبد خرید.', 'error');
            throw error; // برای اینکه handleAddToCartClick بتواند خطا را بگیرد
        } finally {
            setCartLoadingState(false);
        }
    }

    /**
     * به‌روزرسانی تعداد آیتم در سبد خرید.
     * @param {string} cartItemId
     * @param {number} quantity
     */
    async updateItemQuantity(cartItemId, quantity) {
        setCartLoadingState(true);
        try {
            // استفاده از updateCartItemQuantity که از api.js ایمپورت شده است.
            const response = await updateCartItemQuantity(cartItemId, quantity);
            if (response.success) {
                window.showMessage(response.message, 'success');
                await this.loadAndRenderCart();
            } else {
                window.showMessage(response.message, 'error');
            }
        } catch (error) {
            console.error('Error updating cart item quantity:', error);
            window.showMessage('خطا در به‌روزرسانی تعداد محصول در سبد خرید.', 'error');
        } finally {
            setCartLoadingState(false);
        }
    }

    /**
     * حذف آیتم از سبد خرید.
     * @param {string} cartItemId
     */
    async removeItem(cartItemId) {
        setCartLoadingState(true);
        try {
            const response = await removeCartItem(cartItemId);
            if (response.success) {
                window.showMessage(response.message, 'success');
                await this.loadAndRenderCart();
            } else {
                window.showMessage(response.message, 'error');
            }
        } catch (error) {
            console.error('Error removing item from cart:', error);
            window.showMessage('خطا در حذف محصول از سبد خرید.', 'error');
        } finally {
            setCartLoadingState(false);
        }
    }

    /**
     * پاک کردن کامل سبد خرید.
     */
    async clearCart() {
        setCartLoadingState(true);
        try {
            const response = await clearCart();
            if (response.success) {
                window.showMessage(response.message, 'success');
                await this.loadAndRenderCart();
            } else {
                    window.showMessage(response.message, 'error');
            }
        } catch (error) {
            console.error('Error clearing cart:', error);
            window.showMessage('خطا در پاک کردن سبد خرید.', 'error');
        } finally {
            setCartLoadingState(false);
        }
    }

    /**
     * اعمال کد تخفیف به سبد خرید.
     * @param {string} couponCode
     */
    async applyCoupon(couponCode) {
        setCartLoadingState(true);
        try {
            const response = await applyCoupon(couponCode);
            if (response.success) {
                window.showMessage(response.message, 'success');
                await this.loadAndRenderCart();
            } else {
                window.showMessage(response.message, 'error');
            }
        } catch (error) {
            console.error('Error applying coupon:', error);
            window.showMessage('خطا در اعمال کد تخفیف.', 'error');
        } finally {
            setCartLoadingState(false);
        }
    }

    /**
     * حذف کد تخفیف از سبد خرید.
     */
    async removeCoupon() {
        setCartLoadingState(true);
        try {
            const response = await removeCoupon();
            if (response.success) {
                window.showMessage(response.message, 'success');
                await this.loadAndRenderCart();
            } else {
                window.showMessage(response.message, 'error');
            }
        } catch (error) {
            console.error('Error removing coupon:', error);
            window.showMessage('خطا در حذف کد تخفیف.', 'error');
        } finally {
            setCartLoadingState(false);
        }
    }
}

// ایجاد یک نمونه از CartManager و راه‌اندازی آن پس از بارگذاری کامل DOM
document.addEventListener('DOMContentLoaded', () => {
    const cartManager = new CartManager();
    // cartManager را به صورت سراسری در دسترس قرار دهید تا ماژول‌های دیگر (مانند events.js) بتوانند به آن دسترسی داشته باشند
    window.cartManager = cartManager;
    cartManager.init();
});
