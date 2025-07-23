// resources/js/cart.js
console.log('cart.js loaded and starting...');

// این فایل شامل کلاس CartManager است که مسئول مدیریت کلی سبد خرید،
// هماهنگی بین ماژول‌های API، Renderer و Events، و نگهداری وضعیت کلی است.

// ایمپورت کردن توابع مورد نیاز از ماژول‌های دیگر
// این توابع مسئول برقراری ارتباط با API بک‌اند هستند.
// تغییر: addItem به addToCart تغییر یافت
import { fetchCartContents, addToCart, updateCartItemQuantity, removeCartItem, clearCart, applyCoupon, removeCoupon } from './api.js';
// این توابع مسئول به‌روزرسانی رابط کاربری (DOM) بر اساس داده‌های سبد خرید هستند.
// تغییر: اکنون CartRenderer به عنوان یک شیء واحد ایمپورت می‌شود.
import { CartRenderer } from './renderer.js';
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
    const mainCartItemContainer = document.querySelector(`#cart-items-container [data-cart-item-id="${itemId}"]`);
    if (mainCartItemContainer) {
        // NEW: Targeting the input element with class 'item-quantity'
        const quantityInput = mainCartItemContainer.querySelector('.item-quantity');
        const subtotalSpan = mainCartItemContainer.querySelector('.item-subtotal'); // Assuming .item-subtotal exists

        if (quantityInput) { // Ensure quantityInput is found
            const oldQuantity = parseInt(quantityInput.value); // Read from value property of input

            quantityInput.value = newQuantity; // Update value property of input
            // No need to update data-quantity on input, it's read from the button's dataset.

            // آپدیت ساب‌توتال آیتم
            if (subtotalSpan) {
                // اطمینان از اینکه newQuantity و itemPrice عدد هستند قبل از ضرب
                const newSubtotal = (typeof newQuantity === 'number' && typeof itemPrice === 'number') ? (newQuantity * itemPrice) : NaN;
                if (!isNaN(newSubtotal)) {
                    subtotalSpan.textContent = newSubtotal.toLocaleString('fa-IR') + ' تومان';
                    subtotalSpan.setAttribute('data-subtotal', newSubtotal);
                } else {
                    console.warn(`Cannot calculate subtotal for item ${itemId}: newQuantity=${newQuantity}, itemPrice=${itemPrice}. Subtotal set to 'خطا در محاسبه'.`);
                    subtotalSpan.textContent = 'خطا در محاسبه';
                }
                // اضافه شدن هشدار برای قیمت صفر
                if (itemPrice === 0) {
                    console.warn(`Item ${itemId} has a unitPrice of 0. Check the 'data-unit-price' attribute on the cart item container.`);
                }
            }

            console.log(`Main cart item ${itemId} quantity updated from ${oldQuantity} to ${newQuantity}. Subtotal updated to: ${newQuantity * itemPrice}`);
        } else {
            console.warn(`Could not find .item-quantity input for cart item ${itemId} in main cart. Check your HTML structure.`);
        }
    }

    // آپدیت mini cart (این بخش به نظر می‌رسد به DOM.miniCartItemsContainer نیاز دارد)
    // اگر mini cart شما هم از ساختار مشابهی با input برای تعداد استفاده می‌کند،
    // باید در اینجا نیز آن را با .item-quantity یا کلاس مشابه به‌روز کنید.
    // در غیر این صورت، اگر از span استفاده می‌کند، باید آن را به همین شکل حفظ کنید.
    // با توجه به اینکه لاگ‌های شما فقط خطای main cart را نشان می‌دادند،
    // فرض می‌کنم mini cart شما از ساختار متفاوتی استفاده می‌کند یا این بخش در حال حاضر فعال نیست.
    // اگر mini cart هم نیاز به این تغییرات دارد، لطفاً HTML آن را هم ارائه دهید.
    // فعلاً این بخش را بدون تغییر نگه می‌دارم مگر اینکه نیاز باشد.
    const miniCartItem = document.querySelector(`#mini-cart-items-container [data-cart-item-id="${itemId}"]`);
    if (miniCartItem) {
        const quantitySpan = miniCartItem.querySelector('.mini-cart-item-quantity');
        const subtotalSpan = miniCartItem.querySelector('.mini-cart-item-subtotal');
        let oldQuantity = parseInt(quantitySpan?.textContent); // اضافه شدن ?. برای ایمنی بیشتر

        if (isNaN(oldQuantity)) {
            console.warn(`Mini cart item ${itemId}: oldQuantity read from DOM is NaN. Current textContent: "${quantitySpan?.textContent}". Ensure .mini-cart-item-quantity contains a valid number.`);
            oldQuantity = 0; // Fallback to 0 to prevent further NaN propagation
        }


        quantitySpan.textContent = newQuantity;
        quantitySpan.setAttribute('data-quantity', newQuantity);

        // آپدیت ساب‌توتال آیتم
        const newSubtotal = (typeof newQuantity === 'number' && typeof itemPrice === 'number') ? (newQuantity * itemPrice) : NaN;
        if (!isNaN(newSubtotal)) {
            subtotalSpan.textContent = newSubtotal.toLocaleString('fa-IR') + ' تومان';
            subtotalSpan.setAttribute('data-subtotal', newSubtotal);
        } else {
            console.warn(`Cannot calculate mini cart subtotal for item ${itemId}: newQuantity=${newQuantity}, itemPrice=${itemPrice}. Subtotal set to 'خطا در محاسبه'.`);
            subtotalSpan.textContent = 'خطا در محاسبه';
        }
        // اضافه شدن هشدار برای قیمت صفر
        if (itemPrice === 0) {
            console.warn(`Mini cart item ${itemId} has a unitPrice of 0. Check the 'data-unit-price' attribute on the cart item container or the source of itemPrice.`);
        }

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
                // فراخوانی addToCart از api.js
                const response = await addToCart(productId, quantity, productVariantId);
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

            // NEW: Get the parent container for the cart item and read unitPrice from its dataset
            const mainCartItemContainer = document.querySelector(`#cart-items-container [data-cart-item-id="${cartItemId}"]`);
            const quantityInput = mainCartItemContainer ? mainCartItemContainer.querySelector('.item-quantity') : null;
            const itemPrice = mainCartItemContainer ? parseFloat(mainCartItemContainer.dataset.unitPrice) : NaN;

            if (!mainCartItemContainer) {
                console.error(`Cart item container not found for itemId: ${cartItemId}. Check if data-cart-item-id is correctly set.`);
                window.showMessage('خطا در به‌روزرسانی تعداد. آیتم سبد خرید یافت نشد.', 'error');
                return;
            }

            if (!quantityInput) {
                console.error(`Quantity input with class '.item-quantity' not found inside cart item ${cartItemId}. Ensure your HTML structure is correct.`);
                window.showMessage('خطا در به‌روزرسانی تعداد. ورودی تعداد یافت نشد.', 'error');
                return;
            }

            if (isNaN(itemPrice)) {
                console.error(`Item price is NaN for cart item ${cartItemId}. Check the 'data-unit-price' attribute on the cart item container. Value: ${mainCartItemContainer.dataset.unitPrice}`);
                window.showMessage('خطا در به‌روزرسانی تعداد. قیمت محصول نامعتبر است.', 'error');
                return;
            }


            // --- مرحله ۱: بررسی و اصلاح کد گرفتن مقدار quantity از DOM ---
            const quantity = parseInt(quantityInput?.value);

            if (isNaN(quantity) || quantity < 1) {
                console.warn("مقدار نامعتبر: ", quantityInput?.value, `for cart item ${cartItemId}.`);
                window.showMessage('لطفاً یک عدد معتبر برای تعداد وارد کنید.', 'warning');
                return;
            }
            // --- پایان مرحله ۱ ---

            let newQuantity = quantity; // استفاده از quantity که از DOM گرفته شده
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

            // --- مرحله ۲: دیباگ پیشرفته (اختیاری اما مفید) ---
            console.log("Updating item", cartItemId, "با تعداد:", newQuantity, typeof newQuantity);
            // --- پایان مرحله ۲ ---

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
        CartRenderer.setCartLoadingState(true); // تغییر اینجا
        try {
            const response = await fetchCartContents();
            if (response.success) {
                const cartContents = response.data;
                // فقط در صورتی renderMainCart را فراخوانی کنید که DOM.cartItemsContainer وجود داشته باشد
                if (this.dom.cartItemsContainer) {
                    // تغییر در اینجا: ارسال cartContents.summary به عنوان cartTotals
                    CartRenderer.renderMainCart(cartContents.items, cartContents.summary); // تغییر اینجا
                }
                // تغییر در اینجا: ارسال cartContents.summary.totalQuantity و cartContents.summary.totalPrice
                CartRenderer.renderMiniCartDetails(cartContents.items, cartContents.summary.totalQuantity, cartContents.summary.totalPrice); // تغییر اینجا
                console.log('Cart contents loaded and rendered successfully.');
            } else {
                window.showMessage(response.message, 'error');
            }
        } catch (error) {
            console.error('Error loading cart contents:', error);
            window.showMessage('خطا در بارگذاری سبد خرید. لطفاً دوباره تلاش کنید.', 'error');
            CartRenderer.renderMainCart([], {}); // تغییر اینجا
            CartRenderer.renderMiniCartDetails([], 0, 0); // تغییر اینجا
        } finally {
            CartRenderer.setCartLoadingState(false); // تغییر اینجا
        }
    }

    // تابع addItem که قبلاً در اینجا بود، حذف شده است زیرا اکنون مستقیماً از addToCart ایمپورت شده از api.js استفاده می‌شود.

    /**
     * به‌روزرسانی تعداد آیتم در سبد خرید.
     * @param {string} cartItemId
     * @param {number} quantity
     */
    async updateItemQuantity(cartItemId, quantity) {
        CartRenderer.setCartLoadingState(true); // تغییر اینجا
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
            CartRenderer.setCartLoadingState(false); // تغییر اینجا
        }
    }

    /**
     * حذف آیتم از سبد خرید.
     * @param {string} cartItemId
     */
    async removeItem(cartItemId) {
        CartRenderer.setCartLoadingState(true); // تغییر اینجا
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
            CartRenderer.setCartLoadingState(false); // تغییر اینجا
        }
    }

    /**
     * پاک کردن کامل سبد خرید.
     */
    async clearCart() {
        CartRenderer.setCartLoadingState(true); // تغییر اینجا
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
            CartRenderer.setCartLoadingState(false); // تغییر اینجا
        }
    }

    /**
     * اعمال کد تخفیف به سبد خرید.
     * @param {string} couponCode
     */
    async applyCoupon(couponCode) {
        CartRenderer.setCartLoadingState(true); // تغییر اینجا
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
            CartRenderer.setCartLoadingState(false); // تغییر اینجا
        }
    }

    /**
     * حذف کد تخفیف از سبد خرید.
     */
    async removeCoupon() {
        CartRenderer.setCartLoadingState(true); // تغییر اینجا
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
            CartRenderer.setCartLoadingState(false); // تغییر اینجا
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
