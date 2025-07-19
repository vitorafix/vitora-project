// api.js
// این فایل مسئول برقراری ارتباط با API بک‌اند است.

// تابع کمکی برای دریافت توکن CSRF از تگ meta
function getCsrfToken() {
    const tokenElement = document.querySelector('meta[name="csrf-token"]');
    return tokenElement ? tokenElement.getAttribute('content') : '';
}

// تنظیمات مشترک برای هدرها و credentials
const commonHeaders = {
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
};

/**
 * تابع کمکی برای ساخت گزینه‌های fetch با CSRF، Credentials و Guest UUID.
 * @param {string} method - متد HTTP (مثلاً 'GET', 'POST').
 * @param {Object|null} body - بدنه درخواست برای متدهای POST/PUT.
 * @returns {Object} گزینه‌های fetch.
 */
function getFetchOptions(method, body = null) {
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        console.error('CSRF token not found. Please ensure <meta name="csrf-token" content="..."> is in your HTML head.');
        throw new Error('CSRF token is missing.');
    }

    // دریافت guest_uuid از window.guest_uuid که در app.js تنظیم شده است
    const guestUuid = window.guest_uuid || null;

    // --- DEBUG LOG: بررسی مقدار guestUuid قبل از ارسال ---
    console.log('getFetchOptions: guestUuid being sent:', guestUuid);
    // --- پایان DEBUG LOG ---

    const headers = {
        ...commonHeaders,
        'X-CSRF-TOKEN': csrfToken, // اضافه کردن هدر CSRF
    };

    // اضافه کردن Guest UUID به هدرها در صورت وجود
    if (guestUuid) {
        headers['X-Guest-UUID'] = guestUuid;
    }

    const options = {
        method: method,
        headers: headers,
        credentials: 'include' // این خط برای ارسال کوکی‌ها در درخواست‌های Cross-Origin حیاتی است
    };

    if (body) {
        options.body = JSON.stringify(body);
    }

    return options;
}


/**
 * ارسال درخواست به API برای به‌روزرسانی تعداد آیتم سبد خرید.
 * @param {string} cartItemId - شناسه آیتم سبد خرید.
 * @param {number} quantity - تعداد جدید محصول.
 * @returns {Promise<Object>} پاسخ از سرور.
 */
export async function updateCartItemQuantity(cartItemId, quantity) {
    try {
        const response = await fetch(`/api/cart/update-quantity/${cartItemId}`, getFetchOptions('POST', { quantity: quantity }));
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }
        return data;
    } catch (error) {
        console.error('Error in updateCartItemQuantity API call:', error);
        throw error;
    }
}

/**
 * ارسال درخواست به API برای افزودن محصول به سبد خرید.
 * @param {string} productId - شناسه محصول.
 * @param {number} quantity - تعداد محصول.
 * @returns {Promise<Object>} پاسخ از سرور.
 */
export async function addItem(productId, quantity) {
    try {
        const response = await fetch(`/api/cart/add/${productId}`, getFetchOptions('POST', { quantity: quantity }));
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }
        return data;
    } catch (error) {
        console.error('Error in addItem API call:', error);
        throw error;
    }
}

/**
 * ارسال درخواست به API برای حذف آیتم از سبد خرید.
 * @param {string} cartItemId - شناسه آیتم سبد خرید برای حذف.
 * @returns {Promise<Object>} پاسخ از سرور.
 */
export async function removeCartItem(cartItemId) {
    try {
        const response = await fetch(`/api/cart/remove-item/${cartItemId}`, getFetchOptions('POST')); // POST برای حذف
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }
        return data;
    } catch (error) {
        console.error('Error in removeCartItem API call:', error);
        throw error;
    }
}

/**
 * ارسال درخواست به API برای دریافت محتویات سبد خرید.
 * @returns {Promise<Object>} پاسخ از سرور.
 */
export async function fetchCartContents() {
    try {
        const response = await fetch('/api/cart/contents', getFetchOptions('GET'));
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }
        // اطمینان حاصل کنید که 'items' همیشه یک آرایه است
        if (data && data.data && !Array.isArray(data.data.items)) {
            console.warn("API response for cart contents did not contain 'items' as an array. Defaulting to empty array.");
            data.data.items = [];
        }
        return data;
    } catch (error) {
        console.error('Error in fetchCartContents API call:', error);
        throw error;
    }
}

/**
 * ارسال درخواست به API برای پاک کردن کامل سبد خرید.
 * @returns {Promise<Object>} پاسخ از سرور.
 */
export async function clearCart() {
    try {
        const response = await fetch('/api/cart/clear', getFetchOptions('POST'));
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }
        return data;
    } catch (error) {
        console.error('Error in clearCart API call:', error);
        throw error;
    }
}

/**
 * ارسال درخواست به API برای اعمال کد تخفیف.
 * @param {string} couponCode - کد تخفیف.
 * @returns {Promise<Object>} پاسخ از سرور.
 */
export async function applyCoupon(couponCode) {
    try {
        const response = await fetch('/api/cart/apply-coupon', getFetchOptions('POST', { coupon_code: couponCode }));
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }
        return data;
    } catch (error) {
        console.error('Error in applyCoupon API call:', error);
        throw error;
    }
}

/**
 * ارسال درخواست به API برای حذف کد تخفیف.
 * @returns {Promise<Object>} پاسخ از سرور.
 */
export async function removeCoupon() {
    try {
        const response = await fetch('/api/cart/remove-coupon', getFetchOptions('POST'));
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }
        return data;
    } catch (error) {
        console.error('Error in removeCoupon API call:', error);
        throw error;
    }
}
