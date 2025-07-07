// renderer.js
// این ماژول مسئول رندر کردن اطلاعات سبد خرید در بخش‌های مختلف صفحه (مینی‌کارت و کارت اصلی) است.
// تمام توابع در اینجا قبل از دسترسی به DOM، وجود المان‌های هدف را بررسی می‌کنند.

/**
 * @typedef {Object} ProductDetails
 * @property {string} title
 * @property {string} product_name // برای آیتم‌های سبد اصلی
 * @property {number} price
 * @property {number} stock
 * @property {string} thumbnail_url_small
 * @property {string} image_url // اضافه شده برای هماهنگی با Accessor
 * @property {string} image // برای آیتم‌های سبد اصلی
 */

/**
 * @typedef {Object} CartItem
 * @property {number} cart_item_id
 * @property {number} product_id
 * @property {number} quantity
 * @property {number} subtotal
 * @property {ProductDetails} product // برای مینی‌کارت و سبد اصلی (شامل image_url)
 * @property {string} product_name // برای سبد اصلی
 * @property {number} product_price // برای سبد اصلی
 * @property {number} stock // برای سبد اصلی
 * @property {string} thumbnail_url_small // برای سبد اصلی
 * @property {string} image // برای سبد اصلی
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
 * Constants for common CSS classes.
 * ثابت‌ها برای کلاس‌های CSS رایج.
 */
const CSS_CLASSES = {
    HIDDEN: 'hidden',
    LOADING: 'loading',
    CART_ITEM_BASE: 'flex justify-between items-center border-b pb-4',
    CART_ITEM_LAST: 'last:border-b-0 last:pb-0',
    MINI_CART_ITEM: 'flex items-center justify-between py-2 border-b border-gray-200 last:border-b-0',
    LOADING_OVERLAY: 'absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10 rounded-lg'
};

/**
 * عناصر DOM ضروری برای هر بخش از سبد خرید.
 * در صورت عدم وجود این عناصر، رندر متوقف می‌شود و خطا پرتاب می‌شود.
 */
const REQUIRED_ELEMENTS = {
    miniCart: [
        'mini-cart-items-container',
        'mini-cart-total-quantity',
        'mini-cart-total-price',
        'mini-cart-empty-message'
    ],
    mainCart: [
        'cart-items-container',
        'cart-empty-message',
        'cart-summary',
        'cart-total-price'
    ]
};

/**
 * بررسی می‌کند که آیا تمامی عناصر DOM مورد نیاز در صفحه وجود دارند یا خیر.
 * @param {string[]} elementIds - آرایه‌ای از شناسه‌های (ID) عناصر DOM.
 * @throws {Error} اگر یک یا چند عنصر یافت نشوند، خطا پرتاب می‌کند.
 */
function validateElements(elementIds) {
    const missing = elementIds.filter(id => !document.getElementById(id));
    if (missing.length > 0) {
        throw new Error(`عناصر DOM مورد نیاز یافت نشدند: ${missing.join(', ')}`);
    }
}

/**
 * Executes a rendering function safely, catching errors and logging them.
 * @param {Function} renderFn - The rendering function to execute.
 * @param {Function} [fallbackFn] - An optional function to call if an error occurs.
 */
function safeRender(renderFn, fallbackFn = null) {
    try {
        renderFn();
    } catch (error) {
        console.error('Render error:', error);
        if (typeof window.showMessage === 'function') {
            window.showMessage('خطا در نمایش محتوای سبد خرید.', 'error');
        }
        if (fallbackFn) fallbackFn();
    }
}

/**
 * یک تابع کمکی برای ایجاد تمپلیت HTML برای تصویر محصول.
 * @param {CartItem} item - شیء آیتم محصول.
 * @param {string} size - اندازه تصویر ('small' برای مینی‌کارت، 'large' برای سبد اصلی).
 * @returns {string} رشته HTML برای تصویر.
 */
function createImageTemplate(item, size = 'small') {
    // از item.product.image_url استفاده می‌کنیم که از Accessor مدل Product می‌آید.
    // این فرض می‌کند که API بک‌اند، آبجکت 'product' را با 'image_url' برای هر آیتم سبد خرید برمی‌گرداند.
    const imageUrl = item.product?.image_url;
    const altText = item.product?.title || item.product_name || 'Product'; // فال‌بک برای متن جایگزین
    const fallbackUrl = size === 'small' ? 'https://placehold.co/60x60/E5E7EB/4B5563?text=Product' : 'https://placehold.co/80x80/E5E7EB/4B5563?text=Product';
    const width = size === 'small' ? 'w-12' : 'w-16';
    const height = size === 'small' ? 'h-12' : 'h-16';

    return `
        <img src="${imageUrl || fallbackUrl}"
             onerror="this.onerror=null;this.src='${fallbackUrl}';"
             alt="${altText}" class="${width} ${height} object-cover rounded-md ml-3">
    `;
}

/**
 * یک تابع کمکی برای ایجاد تمپلیت HTML برای جزئیات محصول در مینی‌کارت.
 * @param {CartItem} item - شیء آیتم سبد خرید.
 * @returns {string} رشته HTML برای جزئیات محصول.
 */
function createMiniCartDetailsTemplate(item) {
    return `
        <div>
            <p class="text-sm font-medium text-gray-800">${item.product.title}</p>
            <p class="text-xs text-gray-600">${formatNumber(item.quantity)} × ${formatNumber(item.product.price)} تومان</p>
        </div>
    `;
}

/**
 * یک تابع کمکی برای ایجاد تمپلیت HTML برای جزئیات محصول در سبد اصلی.
 * @param {CartItem} item - شیء آیتم سبد خرید.
 * @returns {string} رشته HTML برای جزئیات محصول.
 */
function createMainCartDetailsTemplate(item) {
    return `
        <div>
            <h3 class="text-lg font-semibold text-gray-800">${item.product_name}</h3>
            <div class="flex items-center mt-1">
                <button type="button" class="quantity-btn minus-btn bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-full w-6 h-6 flex items-center justify-center text-lg font-bold transition-colors duration-200" aria-label="کاهش تعداد">
                    -
                </button>
                <span class="item-quantity mx-2 text-gray-700 text-base font-medium" data-quantity="${item.quantity}" aria-live="polite" aria-atomic="true">
                    ${formatNumber(item.quantity)}
                </span>
                <button type="button" class="quantity-btn plus-btn bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-full w-6 h-6 flex items-center justify-center text-lg font-bold transition-colors duration-200" aria-label="افزایش تعداد">
                    +
                </button>
                <span class="mr-2 text-gray-600 text-sm">عدد</span>
            </div>
        </div>
    `;
}

/**
 * رندر کردن محتویات مینی‌کارت (سبد خرید کوچک در هدر).
 * این تابع ابتدا وجود تمامی المان‌های DOM مورد نیاز را بررسی می‌کند.
 * @param {CartItem[]} items - آرایه‌ای از آیتم‌های سبد خرید.
 * @param {number} totalQuantity - تعداد کل آیتم‌های سبد خرید.
 * @param {number} totalPrice - قیمت کل آیتم‌های سبد خرید.
 */
export function renderMiniCartDetails(items, totalQuantity, totalPrice) {
    safeRender(() => {
        validateElements(REQUIRED_ELEMENTS.miniCart);

        const miniCartItemsContainer = document.getElementById('mini-cart-items-container');
        const miniCartTotalQuantity = document.getElementById('mini-cart-total-quantity');
        const miniCartTotalPrice = document.getElementById('mini-cart-total-price');
        const miniCartEmptyMessage = document.getElementById('mini-cart-empty-message');
        const miniCartCheckoutBtn = document.getElementById('mini-cart-checkout-btn');
        const miniCartViewCartBtn = document.getElementById('mini-cart-view-cart-btn');

        miniCartTotalQuantity.textContent = formatNumber(totalQuantity);
        miniCartTotalPrice.textContent = `${formatNumber(totalPrice)} تومان`;

        // استفاده از DocumentFragment برای بهبود عملکرد رندر
        const fragment = document.createDocumentFragment();

        if (items && items.length > 0) {
            miniCartEmptyMessage.classList.add(CSS_CLASSES.HIDDEN);
            miniCartItemsContainer.classList.remove(CSS_CLASSES.HIDDEN);
            if (miniCartCheckoutBtn) miniCartCheckoutBtn.classList.remove(CSS_CLASSES.HIDDEN);
            if (miniCartViewCartBtn) miniCartViewCartBtn.classList.remove(CSS_CLASSES.HIDDEN);

            items.forEach(item => {
                const itemElement = document.createElement('div');
                itemElement.className = CSS_CLASSES.MINI_CART_ITEM;
                itemElement.setAttribute('role', 'listitem'); // Accessibility: ARIA attribute
                itemElement.setAttribute('aria-label', `محصول ${item.product.title} در مینی‌کارت`); // Accessibility: ARIA label

                itemElement.innerHTML = `
                    <div class="flex items-center">
                        ${createImageTemplate(item, 'small')}
                        ${createMiniCartDetailsTemplate(item)}
                    </div>
                    <div class="text-sm font-semibold text-green-700">
                        ${formatNumber(item.quantity * item.product.price)} تومان
                    </div>
                `;
                fragment.appendChild(itemElement);
            });
        } else {
            miniCartEmptyMessage.classList.remove(CSS_CLASSES.HIDDEN);
            miniCartItemsContainer.classList.add(CSS_CLASSES.HIDDEN);
            if (miniCartCheckoutBtn) miniCartCheckoutBtn.classList.add(CSS_CLASSES.HIDDEN);
            if (miniCartViewCartBtn) miniCartViewCartBtn.classList.add(CSS_CLASSES.HIDDEN);
        }

        // پاکسازی و افزودن Fragment به DOM
        miniCartItemsContainer.innerHTML = '';
        miniCartItemsContainer.appendChild(fragment);
    });
}

/**
 * رندر کردن محتویات سبد خرید اصلی در صفحه سبد خرید.
 * این تابع ابتدا وجود تمامی المان‌های DOM مورد نیاز را بررسی می‌کند.
 * @param {CartItem[]} items - آرایه‌ای از آیتم‌های سبد خرید.
 * @param {number} totalQuantity - تعداد کل آیتم‌های سبد خرید.
 * @param {number} totalPrice - قیمت کل آیتم‌های سبد خرید.
 */
export function renderMainCart(items, totalQuantity, totalPrice) {
    safeRender(() => {
        validateElements(REQUIRED_ELEMENTS.mainCart);

        const mainCartItemsContainer = document.getElementById('cart-items-container');
        const mainCartEmptyMessage = document.getElementById('cart-empty-message');
        const mainCartSummary = document.getElementById('cart-summary');
        const mainCartTotalPriceElement = document.getElementById('cart-total-price');

        // استفاده از DocumentFragment برای بهبود عملکرد رندر
        const fragment = document.createDocumentFragment();

        if (items && items.length > 0) {
            mainCartEmptyMessage.classList.add(CSS_CLASSES.HIDDEN);
            mainCartItemsContainer.classList.remove(CSS_CLASSES.HIDDEN);
            mainCartSummary.classList.remove(CSS_CLASSES.HIDDEN);

            items.forEach(item => {
                const itemElement = document.createElement('div');
                itemElement.className = `${CSS_CLASSES.CART_ITEM_BASE} ${CSS_CLASSES.CART_ITEM_LAST}`;
                itemElement.dataset.itemId = item.cart_item_id;
                itemElement.dataset.itemPrice = item.product_price;
                itemElement.dataset.itemQuantity = item.quantity;
                itemElement.dataset.productStock = item.stock;
                itemElement.setAttribute('role', 'listitem'); // Accessibility: ARIA attribute
                itemElement.setAttribute('aria-label', `محصول ${item.product_name} در سبد خرید`); // Accessibility: ARIA label

                itemElement.innerHTML = `
                    <div class="flex items-center">
                        ${createImageTemplate(item, 'large')}
                        ${createMainCartDetailsTemplate(item)}
                    </div>
                    <span class="item-subtotal text-green-700 font-bold text-lg" data-subtotal="${item.subtotal}">
                        ${formatNumber(item.subtotal)} تومان
                    </span>
                `;
                fragment.appendChild(itemElement);
            });

            mainCartTotalPriceElement.textContent = `${formatNumber(totalPrice)} تومان`;
            mainCartTotalPriceElement.dataset.totalPrice = totalPrice;
        } else {
            mainCartEmptyMessage.classList.remove(CSS_CLASSES.HIDDEN);
            mainCartItemsContainer.classList.add(CSS_CLASSES.HIDDEN);
            mainCartSummary.classList.add(CSS_CLASSES.HIDDEN);
        }

        // پاکسازی و افزودن Fragment به DOM
        mainCartItemsContainer.innerHTML = '';
        mainCartItemsContainer.appendChild(fragment);
    });
}

/**
 * وضعیت بارگذاری سبد خرید را تنظیم می‌کند.
 * این تابع کلاس 'loading' را به کانتینرهای مشخص شده اضافه یا حذف می‌کند.
 * @param {boolean} isLoading - اگر true باشد، وضعیت بارگذاری فعال می‌شود؛ در غیر این صورت غیرفعال می‌شود.
 * @param {string | null} containerId - شناسه (ID) کانتینر خاصی که باید وضعیت بارگذاری آن تغییر کند.
 * اگر null باشد، تمامی کانتینرهای پیش‌فرض (مینی‌کارت و سبد اصلی) تحت تأثیر قرار می‌گیرند.
 */
export function setCartLoadingState(isLoading, containerId = null) {
    const containersToUpdate = containerId ? [containerId] : ['mini-cart-items-container', 'cart-items-container'];

    containersToUpdate.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.classList.toggle(CSS_CLASSES.LOADING, isLoading);
            // می‌توانید یک overlay یا spinner نیز در اینجا اضافه کنید
            if (isLoading) {
                // مثلاً اضافه کردن یک overlay ساده
                let overlay = document.getElementById(`${id}-loading-overlay`);
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.id = `${id}-loading-overlay`;
                    overlay.className = CSS_CLASSES.LOADING_OVERLAY;
                    overlay.innerHTML = '<div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-700"></div>';
                    element.style.position = 'relative'; // اطمینان از اینکه overlay به درستی موقعیت‌بندی شود
                    element.appendChild(overlay);
                }
            } else {
                const overlay = document.getElementById(`${id}-loading-overlay`);
                if (overlay) {
                    overlay.remove();
                }
            }
        }
    });
}
