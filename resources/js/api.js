// api.js
// این فایل مسئول برقراری ارتباط با API بک‌اند است.

// تابع کمکی برای دریافت توکن CSRF از تگ meta
function getCsrfToken() {
    const tokenElement = document.querySelector('meta[name="csrf-token"]');
    return tokenElement ? tokenElement.getAttribute('content') : '';
}

/**
 * ارسال درخواست به API برای به‌روزرسانی تعداد آیتم سبد خرید.
 * @param {string} cartItemId - شناسه آیتم سبد خرید.
 * @param {number} quantity - تعداد جدید محصول.
 * @returns {Promise<Object>} پاسخ از سرور.
 */
export async function updateCartItemQuantity(cartItemId, quantity) { // تغییر نام تابع
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        console.error('CSRF token not found. Please ensure <meta name="csrf-token" content="..."> is in your HTML head.');
        throw new Error('CSRF token is missing.');
    }

    try {
        // تغییر URL و متد به POST برای هماهنگی با api.php
        const response = await fetch(`/api/cart/update-quantity/${cartItemId}`, {
            method: 'POST', // تغییر متد از PUT به POST
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ quantity: quantity })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }

        return data;
    } catch (error) {
        console.error('Error in updateCartItemQuantity API call:', error); // تغییر نام تابع در لاگ
        throw error;
    }
}

/**
 * ارسال درخواست به API برای افزودن محصول به سبد خرید.
 * @param {string} productId - شناسه محصول.
 * @param {number} quantity - تعداد محصول.
 * @returns {Promise<Object>} پاسخ از سرور.
 */
export async function addItem(productId, quantity) { // تغییر نام تابع
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        console.error('CSRF token not found. Please ensure <meta name="csrf-token" content="..."> is in your HTML head.');
        throw new Error('CSRF token is missing.');
    }

    try {
        // تغییر URL برای هماهنگی با api.php
        const response = await fetch(`/api/cart/add/${productId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ quantity: quantity })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }

        return data;
    } catch (error) {
        console.error('Error in addItem API call:', error); // تغییر نام تابع در لاگ
        throw error;
    }
}

/**
 * ارسال درخواست به API برای حذف آیتم از سبد خرید.
 * @param {string} cartItemId - شناسه آیتم سبد خرید برای حذف.
 * @returns {Promise<Object>} پاسخ از سرور.
 */
export async function removeCartItem(cartItemId) {
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        console.error('CSRF token not found. Please ensure <meta name="csrf-token" content="..."> is in your HTML head.');
        throw new Error('CSRF token is missing.');
    }

    try {
        // تغییر URL و متد به POST برای هماهنگی با api.php
        const response = await fetch(`/api/cart/remove-item/${cartItemId}`, {
            method: 'POST', // تغییر متد از DELETE به POST
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

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
        // تغییر URL برای هماهنگی با api.php
        const response = await fetch('/api/cart/contents', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }

        return data;
    } catch (error) {
        console.error('Error in fetchCartContents API call:', error);
        throw error;
    }
}
