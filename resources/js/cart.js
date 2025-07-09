// cart.js
// این فایل شامل کلاس CartManager است که مسئول مدیریت کلی سبد خرید،
// هماهنگی بین ماژول‌های API، Renderer و Events، و نگهداری وضعیت کلی است.

// ایمپورت کردن توابع مورد نیاز از ماژول‌های دیگر
// این توابع مسئول برقراری ارتباط با API بک‌اند هستند.
import { fetchCartContents, addItemToCart, updateCartItem, removeCartItem } from './api.js'; 
// این توابع مسئول به‌روزرسانی رابط کاربری (DOM) بر اساس داده‌های سبد خرید هستند.
import { setCartLoadingState, renderMainCart, renderMiniCartDetails } from './renderer.js'; 
// این توابع مسئول کش کردن عناصر DOM و تنظیم Event Listenerها هستند.
import {
    initializeDOMCache,
    // setupAddToCartButtons, // حذف شد: مدیریت توسط event listener سراسری در پایین
    // setupMainCartQuantityButtons, // حذف شد: مدیریت توسط event listener سراسری در پایین
    setupMiniCartToggle,
    // setupMiniCartActionButtons, // حذف شد: مدیریت توسط event listener سراسری در پایین
    getDOM 
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
        if (quantitySpan) {
            quantitySpan.textContent = newQuantity;
            quantitySpan.setAttribute('data-quantity', newQuantity);
        }
        // آپدیت subtotal در UI
        const subtotalElement = mainCartItem.querySelector('.item-subtotal[data-subtotal]');
        if (subtotalElement && itemPrice > 0) {
            const newSubtotal = itemPrice * newQuantity;
            subtotalElement.setAttribute('data-subtotal', newSubtotal);
            const formattedPrice = new Intl.NumberFormat('fa-IR').format(newSubtotal);
            subtotalElement.textContent = `${formattedPrice} تومان`;
            console.log(`Subtotal updated in UI for main cart item ${itemId}: ${newSubtotal}`);
        }
    }
    
    // آپدیت mini cart
    const miniCartItem = document.querySelector(`#mini-cart-items-container [data-cart-item-id="${itemId}"]`);
    if (miniCartItem) {
        const quantitySpan = miniCartItem.querySelector('.item-quantity');
        if (quantitySpan) {
            quantitySpan.textContent = newQuantity;
            quantitySpan.setAttribute('data-quantity', newQuantity);
        }
        // آپدیت متن نمایش تعداد و قیمت در mini cart
        const quantityPriceTextElement = miniCartItem.querySelector('p.text-xs'); 
        if (quantityPriceTextElement && itemPrice > 0) {
            quantityPriceTextElement.textContent = `${newQuantity} x ${itemPrice.toLocaleString('fa-IR')} تومان`;
            console.log(`Mini cart quantity text updated for item ${itemId}`);
        }
    }
    
    // پس از به‌روزرسانی آیتم‌های تکی، یک رندر کامل سبد خرید را برای اطمینان از به‌روزرسانی مجموع کل‌ها فعال کنید.
    if (window.cartManager) {
        window.cartManager.loadAndRenderCart(); 
    }
}

/**
 * تابع برای حذف آیتم‌های تکراری در mini cart.
 * این تابع به جلوگیری از نمایش آیتم‌های تکراری در UI کمک می‌کند.
 */
function removeDuplicateMiniCartItems() {
    const miniCartContainer = document.getElementById('mini-cart-items-container');
    if (!miniCartContainer) {
        console.warn('Mini cart container not found for duplicate removal.');
        return;
    }
    
    const items = miniCartContainer.querySelectorAll('[data-cart-item-id]'); 
    const seenIds = new Set();
    
    // Iterate in reverse to safely remove elements
    for (let i = items.length - 1; i >= 0; i--) {
        const item = items[i];
        const itemId = item.getAttribute('data-cart-item-id');
        if (seenIds.has(itemId)) {
            console.log(`Removing duplicate mini cart item ${itemId}`);
            item.remove();
        } else {
            seenIds.add(itemId);
        }
    }
}

/**
 * تابع برای debugging وضعیت سبد خرید.
 */
function debugCartState() {
    console.log('=== Cart Debug Info ===');
    console.log('Main cart items:', document.querySelectorAll('#cart-items-container [data-cart-item-id]').length);
    console.log('Mini cart items:', document.querySelectorAll('#mini-cart-items-container [data-cart-item-id]').length);
    console.log('Quantity spans in main cart:', document.querySelectorAll('#cart-items-container .item-quantity').length);
    console.log('Quantity spans in mini cart:', document.querySelectorAll('#mini-cart-items-container .item-quantity').length);
    console.log('Quantity buttons in main cart:', document.querySelectorAll('#cart-items-container .quantity-btn').length);
    console.log('Quantity buttons in mini cart:', document.querySelectorAll('#mini-cart-items-container .quantity-btn').length);

    const mainContainer = document.getElementById('cart-items-container');
    if (mainContainer) {
        Array.from(mainContainer.children).forEach((item, index) => {
            const cartItemId = item.getAttribute('data-cart-item-id') || 'unknown';
            console.log(`\n=== Debugging: Main Cart Item ${index} (ID: ${cartItemId}) ===`);
            console.log('Debugging: Full HTML:', item.outerHTML);
            const quantitySpan = item.querySelector('.item-quantity');
            if (quantitySpan) {
                console.log(`Debugging:   Quantity span found: data-quantity=${quantitySpan.dataset.quantity}, text=${quantitySpan.textContent}`);
            } else {
                console.error(`Debugging:   Quantity span NOT found for item ${cartItemId}.`);
            }
        });
    }

    const miniContainer = document.getElementById('mini-cart-items-container');
    if (miniContainer) {
        Array.from(miniContainer.children).forEach((item, index) => {
            const cartItemId = item.getAttribute('data-cart-item-id') || 'unknown';
            console.log(`\n=== Debugging: Mini Cart Item ${index} (ID: ${cartItemId}) ===`);
            console.log('Debugging: Full HTML:', item.outerHTML);
            const quantitySpan = item.querySelector('.item-quantity');
            if (quantitySpan) {
                console.log(`Debugging:   Quantity span found: data-quantity=${quantitySpan.dataset.quantity}, text=${quantitySpan.textContent}`);
            } else {
                console.warn(`Debugging:   Quantity span NOT found for mini cart item ${cartItemId}. (Expected for mini cart if not explicitly rendered)`);
            }
        });
    }
}


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
            cartTotals: {} 
        };
        // Observer pattern: لیست آبزرورها برای اطلاع‌رسانی تغییرات سبد خرید
        this.observers = [];
        // پرچم جدید برای ردیابی وجود عناصر اصلی سبد خرید (مانند صفحه cart.blade.php)
        this.hasMainCartElements = false; 
        this.observer = null; // برای MutationObserver
        console.log('CartManager initialized.');

        // کش کردن عناصر DOM اصلی سبد خرید در سازنده
        this.DOM = {
            cartEmptyMessage: document.getElementById('cart-empty-message'),
            cartSummary: document.getElementById('cart-summary'),
            cartItemsContainer: document.getElementById('cart-items-container'), 
            cartTotalPrice: document.getElementById('cart-total-price')
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
        if (isInitialized) {
            console.log('CartManager already initialized, skipping init...');
            return;
        }
        console.log('Initializing CartManager...');

        this.validateDOM(); // اعتبارسنجی اولیه DOM

        this.hasMainCartElements = initializeDOMCache(); // کش DOM را راه‌اندازی کنید و عناصر حیاتی سبد خرید اصلی را بررسی کنید

        // تنظیم شنونده مینی‌کارت (بقیه شنونده‌ها توسط event listener سراسری در پایین مدیریت می‌شوند)
        setupMiniCartToggle();

        // حذف آیتم‌های تکراری در mini cart قبل از هر پردازش
        removeDuplicateMiniCartItems();
        
        // محتویات سبد خرید را بارگذاری و رندر کنید
        await this.loadAndRenderCart();

        // اضافه کردن لاگ‌های دیباگینگ در اینجا، پس از تلاش برای مقداردهی اولیه همه چیز
        debugCartState(); // استفاده از تابع سراسری debugCartState

        isInitialized = true; // تنظیم فلگ initialized
        console.log('CartManager initialization complete.');
    }

    /**
     * اعتبارسنجی وجود عناصر DOM کلیدی در زمان راه‌اندازی.
     * این متد به شناسایی سریع‌تر مشکلات مربوط به عدم وجود المنت‌ها کمک می‌کند.
     */
    validateDOM() {
        const requiredElements = [
            'mini-cart-toggle',
            'mini-cart-dropdown',
            'cart-items-container',
            'cart-empty-message',
            'cart-summary',
            'cart-total-price'
        ];
        
        requiredElements.forEach(id => {
            if (!document.getElementById(id)) {
                console.warn(`Required DOM element with ID "${id}" not found. Some functionalities might be affected.`);
            }
        });
    }

    /**
     * محتویات سبد خرید را از API دریافت کرده و UI را به‌روزرسانی می‌کند.
     * این متد می‌تواند پس از هر عملیات تغییر سبد (افزودن، حذف، به‌روزرسانی) فراخوانی شود.
     */
    async loadAndRenderCart() {
        setCartLoadingState(true); 
        try {
            const data = await fetchCartContents();

            if (!data || !Array.isArray(data.items) || data.totalQuantity === undefined || data.totalPrice === undefined || typeof data.cartTotals !== 'object') {
                throw new Error('Invalid cart data received from API.');
            }

            this.cartData = {
                items: data.items,
                totalQuantity: data.totalQuantity, 
                totalPrice: data.totalPrice,
                cartTotals: data.cartTotals 
            };

            renderMiniCartDetails(this.cartData.items, this.cartData.totalQuantity, this.cartData.totalPrice); 

            if (this.hasMainCartElements) {
                if (this.cartData.items.length === 0) {
                    this.renderEmptyCart(); 
                } else {
                    renderMainCart(this.cartData.items, this.cartData.cartTotals); 
                }
            } else {
                console.warn('Main cart elements not present, skipping main cart rendering.');
            }

            this.notify('cartChanged', this.cartData); 
        } catch (error) {
            console.error('Failed to load and render cart:', error);
            if (typeof window.showMessage === 'function') {
                window.showMessage('خطا در بارگذاری سبد خرید. لطفاً دوباره تلاش کنید.', 'error');
            }
        } finally {
            setCartLoadingState(false); 
        }
    }

    /**
     * رندر کردن وضعیت سبد خرید خالی در صفحه اصلی سبد خرید.
     */
    renderEmptyCart() {
        if (this.DOM.cartEmptyMessage) {
            this.DOM.cartEmptyMessage.classList.remove('hidden'); 
        }
        if (this.DOM.cartSummary) {
            this.DOM.cartSummary.classList.add('hidden'); 
        }
        if (this.DOM.cartItemsContainer) {
            this.DOM.cartItemsContainer.innerHTML = '<p class="text-center text-gray-500 py-10 text-lg">سبد خرید شما خالی است.</p>'; 
        }
    }

    /**
     * اضافه کردن یک محصول به سبد خرید یا به‌روزرسانی تعداد آن اگر قبلاً موجود باشد.
     * @param {string} productId - شناسه محصول.
     * @param {number} quantity - تعداد محصول برای اضافه کردن.
     */
    async addItem(productId, quantity = 1) {
        setCartLoadingState(true);
        try {
            // ابتدا بررسی کنید که آیا محصول قبلاً در client-side cartData وجود دارد
            const existingCartItem = this.cartData.items.find(item => item.product_id == productId);

            if (existingCartItem) {
                // اگر محصول موجود است، quantity آن را افزایش دهید
                const newQuantity = existingCartItem.quantity + quantity;
                console.log(`Product ${productId} already in cart. Updating quantity from ${existingCartItem.quantity} to ${newQuantity}.`);
                // فراخوانی متد updateItemQuantity که مسئول فراخوانی API و به‌روزرسانی UI است
                await this.updateItemQuantity(existingCartItem.cart_item_id, newQuantity);
            } else {
                // اگر محصول جدید است، آن را از طریق API به سبد اضافه کنید
                console.log(`Product ${productId} not in cart. Adding new item with quantity ${quantity}.`);
                const response = await addItemToCart(productId, quantity);
                if (response.success) {
                    await this.loadAndRenderCart(); // رندر کامل برای آیتم‌های جدید
                    if (typeof window.showMessage === 'function') {
                        window.showMessage('محصول به سبد خرید اضافه شد.', 'success');
                    }
                } else {
                    throw new Error(response.message || 'خطا در افزودن محصول به سبد خرید.');
                }
            }
        } catch (error) {
            console.error('Error adding/updating item in cart:', error);
            if (typeof window.showMessage === 'function') {
                window.showMessage(error.message || 'خطا در افزودن محصول به سبد خرید. لطفاً دوباره تلاش کنید.', 'error');
            }
        } finally {
            setCartLoadingState(false);
        }
    }

    /**
     * به‌روزرسانی تعداد یک آیتم در سبد خرید.
     * @param {string} cartItemId - شناسه آیتم سبد خرید.
     * @param {number} newQuantity - تعداد جدید محصول.
     */
    async updateItemQuantity(cartItemId, newQuantity) { 
        setCartLoadingState(true);
        try {
            const response = await updateCartItem(cartItemId, newQuantity); 
            if (response.success) {
                // یافتن قیمت محصول برای به‌روزرسانی صحیح UI
                const itemData = this.cartData.items.find(item => item.cart_item_id == cartItemId);
                const itemPrice = itemData ? itemData.product_price : 0;
                
                updateQuantityInUI(cartItemId, newQuantity, itemPrice); // آپدیت فوری UI
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
     * @param {string} cartItemId - شناسه آیتم سبد خرید برای حذف.
     */
    async removeItem(cartItemId) { 
        setCartLoadingState(true);
        try {
            const response = await removeCartItem(cartItemId); 
            if (response.success) {
                await this.loadAndRenderCart(); // همچنان loadAndRenderCart را صدا می‌زنیم تا کل وضعیت به‌روز شود
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

// Event listener سراسری برای مدیریت کلیک‌ها
document.addEventListener('click', function(event) {
    // بررسی برای دکمه‌های "افزودن به سبد خرید"
    if (event.target.matches('.add-to-cart-btn') || event.target.closest('.add-to-cart-btn')) {
        event.preventDefault();
        const addBtn = event.target.matches('.add-to-cart-btn') ? event.target : event.target.closest('.add-to-cart-btn');
        const productId = addBtn.getAttribute('data-product-id');
        const quantity = parseInt(addBtn.getAttribute('data-quantity')) || 1;
        
        if (productId && window.cartManager) {
            console.log(`Add to cart clicked for product ID: ${productId}, quantity: ${quantity}`);
            window.cartManager.addItem(productId, quantity);
        } else {
            console.error('Product ID or CartManager not available for add to cart.');
        }
        return;
    }

    // بررسی برای دکمه‌های افزایش/کاهش تعداد
    if (event.target.matches('.quantity-btn, .minus-btn, .plus-btn') || event.target.closest('.quantity-btn')) {
        event.preventDefault();
        
        const cartItem = event.target.closest('[data-cart-item-id]'); 
        if (!cartItem) {
            console.warn('Clicked quantity button, but no parent cart item found with data-cart-item-id.');
            return;
        }
        
        const itemId = cartItem.getAttribute('data-cart-item-id');
        
        const quantitySpan = cartItem.querySelector('.item-quantity');
        if (!quantitySpan) {
            console.error(`Quantity span not found for item ${itemId}. Cannot update UI.`);
            return;
        }
        
        const currentQuantity = parseInt(quantitySpan.getAttribute('data-quantity')) || 1;
        const isPlus = event.target.classList.contains('plus-btn') || 
                      (event.target.classList.contains('quantity-btn') && event.target.textContent.trim() === '+');
        
        let newQuantity = currentQuantity + (isPlus ? 1 : -1);
        newQuantity = Math.max(0, newQuantity); // جلوگیری از تعداد منفی

        // اگر تعداد جدید 0 است، درخواست حذف آیتم را ارسال کنید
        if (newQuantity === 0) {
            if (window.cartManager) {
                window.cartManager.removeItem(itemId);
            } else {
                console.error('CartManager not available to remove item.');
            }
            return; 
        }
        
        // آپدیت server از طریق CartManager
        if (window.cartManager) {
            window.cartManager.updateItemQuantity(itemId, newQuantity);
        } else {
            console.error('CartManager not available to update item quantity.');
        }
        return;
    }

    // بررسی برای دکمه‌های حذف (اگر جداگانه از دکمه‌های تعداد باشند)
    if (event.target.matches('.remove-item-btn') || event.target.closest('.remove-item-btn')) {
        event.preventDefault();
        const removeBtn = event.target.matches('.remove-item-btn') ? event.target : event.target.closest('.remove-item-btn');
        const cartItemId = removeBtn.getAttribute('data-cart-item-id');

        if (cartItemId) {
            console.log('Remove button clicked for item:', cartItemId);
            if (window.cartManager) {
                window.cartManager.removeItem(cartItemId);
            } else {
                console.error('CartManager not available to remove item.');
            }
        }
        return;
    }
});


// ایجاد یک نمونه از CartManager و راه‌اندازی آن پس از بارگذاری کامل DOM
document.addEventListener('DOMContentLoaded', () => {
    const cartManager = new CartManager();
    // cartManager را به صورت سراسری در دسترس قرار دهید تا ماژول‌های دیگر (مانند events.js) بتوانند به آن دسترسی داشته باشند
    window.cartManager = cartManager;
    cartManager.init(); 
});
