// resources/js/cart/cart.js
console.log('cart.js loaded and starting...');

// این فایل شامل کلاس CartManager است که مسئول مدیریت کلی سبد خرید،
// هماهنگی بین ماژول‌های API، Renderer و Events، و نگهداری وضعیت کلی است.

// ایمپورت کردن توابع مورد نیاز از ماژول‌های دیگر
// این توابع مسئول برقراری ارتباط با API بک‌اند هستند.
// تغییر: مسیرهای import به فولدر core اصلاح شده‌اند.
import { fetchCartContents, addToCart, updateCartItemQuantity, removeCartItem, clearCart, applyCoupon, removeCoupon, setGuestUuidHeader } from '../core/api.js';
// این توابع مسئول به‌روزرسانی رابط کاربری (DOM) بر اساس داده‌های سبد خرید هستند.
import { CartRenderer } from '../core/renderer.js';
// این توابع مسئول کش کردن عناصر DOM و تنظیم Event Listenerها هستند.
import {
    initializeDOMCache,
    setupMiniCartToggle,
    getDOM, // اضافه کردن تابع getDOM
    debounce // اضافه کردن تابع debounce از events.js
} from '../core/events.js'; // مسیر صحیح

// جلوگیری از اجرای مکرر initialization
let isInitialized = false;

// کلید برای ذخیره guest_uuid در localStorage
const GUEST_UUID_KEY = 'guest_uuid';

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
        const quantityInput = mainCartItemContainer.querySelector('.item-quantity');
        const subtotalSpan = mainCartItemContainer.querySelector('.item-subtotal');

        if (quantityInput) {
            const oldQuantity = parseInt(quantityInput.value);

            quantityInput.value = newQuantity;

            // آپدیت ساب‌توتال آیتم
            if (subtotalSpan) {
                const newSubtotal = (typeof newQuantity === 'number' && typeof itemPrice === 'number') ? (newQuantity * itemPrice) : NaN;
                if (!isNaN(newSubtotal)) {
                    subtotalSpan.textContent = new Intl.NumberFormat('fa-IR').format(newSubtotal) + ' تومان';
                    subtotalSpan.setAttribute('data-subtotal', newSubtotal);
                } else {
                    console.warn(`Cannot calculate subtotal for item ${itemId}: newQuantity=${newQuantity}, itemPrice=${itemPrice}. Subtotal set to 'خطا در محاسبه'.`);
                    subtotalSpan.textContent = 'خطا در محاسبه';
                }
                if (itemPrice === 0) {
                    console.warn(`Item ${itemId} has a unitPrice of 0. Check the 'data-unit-price' attribute on the cart item container.`);
                }
            }
            console.log(`Main cart item ${itemId} quantity updated from ${oldQuantity} to ${newQuantity}. Subtotal updated to: ${newQuantity * itemPrice}`);
        } else {
            console.warn(`Could not find .item-quantity input for cart item ${itemId} in main cart. Check your HTML structure.`);
        }
    }

    // آپدیت mini cart
    const miniCartItem = document.querySelector(`#mini-cart-items-container [data-cart-item-id="${itemId}"]`);
    if (miniCartItem) {
        const quantitySpan = miniCartItem.querySelector('.mini-cart-item-quantity');
        const subtotalSpan = miniCartItem.querySelector('.mini-cart-item-subtotal');
        let oldQuantity = parseInt(quantitySpan?.textContent);

        if (isNaN(oldQuantity)) {
            console.warn(`Mini cart item ${itemId}: oldQuantity read from DOM is NaN. Current textContent: "${quantitySpan?.textContent}". Ensure .mini-cart-item-quantity contains a valid number.`);
            oldQuantity = 0;
        }

        quantitySpan.textContent = newQuantity;
        quantitySpan.setAttribute('data-quantity', newQuantity);

        // آپدیت ساب‌توتال آیتم
        const newSubtotal = (typeof newQuantity === 'number' && typeof itemPrice === 'number') ? (newQuantity * itemPrice) : NaN;
        if (!isNaN(newSubtotal)) {
            subtotalSpan.textContent = new Intl.NumberFormat('fa-IR').format(newSubtotal) + ' تومان';
            subtotalSpan.setAttribute('data-subtotal', newSubtotal);
        } else {
            console.warn(`Cannot calculate mini cart subtotal for item ${itemId}: newQuantity=${newQuantity}, itemPrice=${itemPrice}. Subtotal set to 'خطا در محاسبه'.`);
            subtotalSpan.textContent = 'خطا در محاسبه';
        }
        if (itemPrice === 0) {
            console.warn(`Mini cart item ${itemId} has a unitPrice of 0. Check the 'data-unit-price' attribute on the cart item container or the source of itemPrice.`);
        }
        console.log(`Mini cart item ${itemId} quantity updated from ${oldQuantity} to ${newQuantity}. Subtotal updated to: ${newSubtotal}`);
    }
}

/**
 * تابعی برای دریافت یا تولید یک guest_uuid.
 * اگر guest_uuid در localStorage موجود نباشد، یک UUID جدید تولید و ذخیره می‌کند.
 * @returns {string} guest_uuid
 */
function getGuestUuid() {
    let guestUuid = localStorage.getItem(GUEST_UUID_KEY);
    if (!guestUuid) {
        guestUuid = crypto.randomUUID(); // تولید یک UUID جدید
        localStorage.setItem(GUEST_UUID_KEY, guestUuid);
        console.log('New guest_uuid generated and stored:', guestUuid);
    } else {
        console.log('Existing guest_uuid retrieved:', guestUuid);
    }
    return guestUuid;
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
        this.guestUuid = getGuestUuid(); // دریافت یا تولید guest_uuid در زمان ساخت نمونه
        setGuestUuidHeader(this.guestUuid); // تغییر: تنظیم guestUuid در هدرهای Axios به صورت سراسری
        isInitialized = true;
        console.log('CartManager instance created with guestUuid:', this.guestUuid);
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

        // --- نمایش کانتینر اصلی سبد خرید اگر در صفحه مربوطه هستیم ---
        const cartPageContainer = document.getElementById('cart-page-container');
        console.log('Current pathname:', window.location.pathname);
        if (cartPageContainer && window.location.pathname.includes('/cart')) {
            if (cartPageContainer.classList.contains('hidden')) {
                cartPageContainer.classList.remove('hidden');
                console.log('Main cart page container is now visible (hidden class removed).');
            } else {
                console.log('Main cart page container was already visible.');
            }
        } else {
            console.log('Main cart page container remains hidden. Condition not met or element not found.');
        }
        // --- پایان تغییر ---

        this.loadAndRenderCart(); // سپس محتویات سبد خرید را بارگذاری کنید
        console.log('CartManager init completed.');
    }

    /**
     * تنظیم Event Listenerهای اصلی برای تعاملات سبد خرید.
     */
    setupEventListeners() {
        console.log('Setting up event listeners...');
        // Event Listener برای دکمه‌های افزودن به سبد خرید (در صفحات محصول)
        const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
        console.log('Found add-to-cart buttons:', addToCartButtons.length);
        addToCartButtons.forEach(button => {
            button.dataset.originalText = button.innerHTML;
            button.addEventListener('click', this.handleAddToCartClick.bind(this));
            console.log('Attached click listener to:', button);
        });

        // Event Listener برای دکمه‌های +/- و حذف در سبد خرید اصلی
        if (this.dom.cartItemsContainer) {
            this.dom.cartItemsContainer.addEventListener('click', this.handleCartItemAction.bind(this));
            console.log('Attached click listener to main cart container.');
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
            console.log('Attached click listener to apply coupon button.');
        }

        if (removeCouponBtn) {
            removeCouponBtn.addEventListener('click', async () => {
                await this.removeCoupon();
            });
            console.log('Attached click listener to remove coupon button.');
        }

        // Event Listener برای دکمه پاکسازی سبد خرید
        const clearCartBtn = document.getElementById('clear-cart-btn');
        if (clearCartBtn) {
            clearCartBtn.addEventListener('click', async () => {
                window.showConfirmationModal('پاک کردن سبد خرید', 'آیا مطمئن هستید که می‌خواهید سبد خرید خود را پاک کنید؟', () => {
                    this.clearCart();
                });
            });
            console.log('Attached click listener to clear cart button.');
        }
    }

    /**
     * هندل کردن کلیک روی دکمه "افزودن به سبد خرید".
     * @param {Event} event
     */
    async handleAddToCartClick(event) {
        console.log('Add to cart button clicked!');
        event.preventDefault();
        const button = event.currentTarget;
        const productId = button.dataset.productId;
        const quantity = parseInt(button.dataset.quantity || 1);
        const productVariantId = button.dataset.productVariantId || null;
        const originalButtonText = button.dataset.originalText;

        if (productId) {
            button.disabled = true;
            button.classList.add('opacity-50', 'cursor-not-allowed');
            button.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال افزودن...';

            console.log(`Adding product ${productId} with quantity ${quantity} to cart.`);
            try {
                // تغییر: guestUuid دیگر به عنوان پارامتر ارسال نمی‌شود، زیرا به صورت سراسری تنظیم شده است.
                const response = await addToCart(productId, quantity, productVariantId);
                window.showMessage(response.message || 'محصول با موفقیت به سبد خرید اضافه شد.', 'success');
                // پس از افزودن موفقیت‌آمیز، سبد خرید را دوباره بارگذاری و رندر کنید
                await this.loadAndRenderCart();
            } catch (error) {
                const errorMessage = error.response?.data?.message || 'خطا در افزودن محصول به سبد خرید. لطفاً دوباره تلاش کنید.';
                window.showMessage(errorMessage, 'error');
                console.error('Error adding product to cart:', error);
            } finally {
                button.disabled = false;
                button.classList.remove('opacity-50', 'cursor-not-allowed');
                button.innerHTML = originalButtonText;
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
        if (event.target.matches('.quantity-btn') || event.target.closest('.quantity-btn')) {
            event.preventDefault();
            const button = event.target.matches('.quantity-btn') ? event.target : event.target.closest('.quantity-btn');
            const action = button.dataset.action;
            const cartItemId = button.dataset.cartItemId;

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

            const quantity = parseInt(quantityInput?.value);

            if (isNaN(quantity) || quantity < 1) {
                console.warn("مقدار نامعتبر: ", quantityInput?.value, `for cart item ${cartItemId}.`);
                window.showMessage('لطفاً یک عدد معتبر برای تعداد وارد کنید.', 'warning');
                return;
            }

            let newQuantity = quantity;
            if (action === 'increase') {
                newQuantity++;
            } else if (action === 'decrease') {
                newQuantity--;
            }

            if (newQuantity <= 0) {
                window.showConfirmationModal('حذف محصول', 'آیا مطمئن هستید که می‌خواهید این محصول را از سبد خرید حذف کنید؟', () => {
                    this.removeItem(cartItemId);
                });
                return;
            }

            updateQuantityInUI(cartItemId, newQuantity, itemPrice);
            console.log("Updating item", cartItemId, "با تعداد:", newQuantity, typeof newQuantity);
            this.debouncedUpdateCartItemQuantity(cartItemId, newQuantity);
            return;
        }

        if (event.target.matches('.remove-item-btn') || event.target.closest('.remove-item-btn')) {
            event.preventDefault();
            const removeBtn = event.target.matches('.remove-item-btn') ? event.target : event.target.closest('.remove-item-btn');
            const cartItemId = removeBtn.getAttribute('data-cart-item-id');

            if (cartItemId) {
                console.log('Remove button clicked for item:', cartItemId);
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
        CartRenderer.setCartLoadingState(true);
        try {
            // تغییر: guestUuid دیگر به عنوان پارامتر ارسال نمی‌شود، زیرا به صورت سراسری تنظیم شده است.
            const response = await fetchCartContents();
            if (response.success && response.data) { // اطمینان از وجود response.data
                const cartContents = response.data;
                // --- لاگ حیاتی جدید ---
                // اطمینان از وجود cartContents.items قبل از لاگ کردن
                if (cartContents.items) {
                    console.log('loadAndRenderCart: Items received from API for rendering:', cartContents.items);
                } else {
                    console.warn('loadAndRenderCart: No items array found in API response data.');
                }
                // --- پایان لاگ حیاتی ---
                if (this.dom.cartItemsContainer) {
                    // اطمینان از ارسال یک آرایه خالی در صورت عدم وجود items
                    CartRenderer.renderMainCart(cartContents.items || [], cartContents.summary || {});
                }
                // اطمینان از ارسال مقادیر پیش‌فرض در صورت عدم وجود summary
                CartRenderer.renderMiniCartDetails(cartContents.items || [], cartContents.summary?.totalQuantity || 0, cartContents.summary?.totalPrice || 0);
                console.log('Cart contents loaded and rendered successfully.');
            } else {
                // اگر response.success false باشد یا response.data وجود نداشته باشد
                window.showMessage(response.message || 'خطا در دریافت داده‌های سبد خرید.', 'error');
                CartRenderer.renderMainCart([], {});
                CartRenderer.renderMiniCartDetails([], 0, 0);
            }
        } catch (error) {
            console.error('Error loading cart contents:', error);
            window.showMessage('خطا در بارگذاری سبد خرید. لطفاً دوباره تلاش کنید.', 'error');
            // در صورت بروز خطا، سبد خرید را خالی رندر کنید
            CartRenderer.renderMainCart([], {});
            CartRenderer.renderMiniCartDetails([], 0, 0);
        } finally {
            CartRenderer.setCartLoadingState(false);
        }
    }

    /**
     * به‌روزرسانی تعداد آیتم در سبد خرید.
     * @param {string} cartItemId
     * @param {number} quantity
     */
    async updateItemQuantity(cartItemId, quantity) {
        CartRenderer.setCartLoadingState(true);
        try {
            // تغییر: guestUuid دیگر به عنوان پارامتر ارسال نمی‌شود، زیرا به صورت سراسری تنظیم شده است.
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
            CartRenderer.setCartLoadingState(false);
        }
    }

    /**
     * حذف آیتم از سبد خرید.
     * @param {string} cartItemId
     */
    async removeItem(cartItemId) {
        CartRenderer.setCartLoadingState(true);
        try {
            // تغییر: guestUuid دیگر به عنوان پارامتر ارسال نمی‌شود، زیرا به صورت سراسری تنظیم شده است.
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
            CartRenderer.setCartLoadingState(false);
        }
    }

    /**
     * پاک کردن کامل سبد خرید.
     */
    async clearCart() {
        CartRenderer.setCartLoadingState(true);
        try {
            // تغییر: guestUuid دیگر به عنوان پارامتر ارسال نمی‌شود، زیرا به صورت سراسری تنظیم شده است.
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
            CartRenderer.setCartLoadingState(false);
        }
    }

    /**
     * اعمال کد تخفیف به سبد خرید.
     * @param {string} couponCode
     */
    async applyCoupon(couponCode) {
        CartRenderer.setCartLoadingState(true);
        try {
            // تغییر: guestUuid دیگر به عنوان پارامتر ارسال نمی‌شود، زیرا به صورت سراسری تنظیم شده است.
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
            CartRenderer.setCartLoadingState(false);
        }
    }

    /**
     * حذف کد تخفیف از سبد خرید.
     */
    async removeCoupon() {
        CartRenderer.setCartLoadingState(true);
        try {
            // تغییر: guestUuid دیگر به عنوان پارامتر ارسال نمی‌شود، زیرا به صورت سراسری تنظیم شده است.
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
            CartRenderer.setCartLoadingState(false);
        }
    }
}

// تابع `initCart` برای فراخوانی توسط `app.js`
export function initCart() {
    // اطمینان حاصل کنید که این کد فقط زمانی اجرا می‌شود که DOM آماده باشد.
    // اگر `app.js` این تابع را بعد از `DOMContentLoaded` فراخوانی کند، نیازی به `addEventListener` نیست.
    // اما برای اطمینان بیشتر، می‌توانیم یک بررسی ساده انجام دهیم.
    if (document.readyState === 'loading') { // اگر DOM هنوز در حال بارگذاری است
        document.addEventListener('DOMContentLoaded', () => {
            const cartManager = new CartManager();
            window.cartManager = cartManager; // برای دسترسی گلوبال
            cartManager.init();
        });
    } else { // اگر DOM قبلاً بارگذاری شده است
        const cartManager = new CartManager();
        window.cartManager = cartManager; // برای دسترسی گلوبال
        cartManager.init();
    }
}

// نکته: خط `document.addEventListener('DOMContentLoaded', ...)` قبلی حذف شده است
// زیرا `app.js` مسئول فراخوانی `initCart()` پس از Dynamic Import است.
