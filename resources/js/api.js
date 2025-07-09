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
export async function updateCartItem(cartItemId, quantity) {
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        console.error('CSRF token not found. Please ensure <meta name="csrf-token" content="..."> is in your HTML head.');
        // می‌توانید اینجا یک پیام خطا به کاربر نمایش دهید یا عملیات را متوقف کنید.
        throw new Error('CSRF token is missing.');
    }

    try {
        const response = await fetch(`/cart/update/${cartItemId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken, // اضافه کردن توکن CSRF به هدر
                'X-Requested-With': 'XMLHttpRequest' // معمولاً برای تشخیص درخواست‌های AJAX استفاده می‌شود
            },
            body: JSON.stringify({ quantity: quantity })
        });

        const data = await response.json();

        if (!response.ok) {
            // اگر پاسخ موفقیت‌آمیز نبود، یک خطا پرتاب کنید
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }

        return data;
    } catch (error) {
        console.error('Error in updateCartItem API call:', error);
        throw error; // خطا را مجدداً پرتاب کنید تا در CartManager مدیریت شود
    }
}

/**
 * ارسال درخواست به API برای افزودن محصول به سبد خرید.
 * @param {string} productId - شناسه محصول.
 * @param {number} quantity - تعداد محصول.
 * @returns {Promise<Object>} پاسخ از سرور.
 */
export async function addItemToCart(productId, quantity) {
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        console.error('CSRF token not found. Please ensure <meta name="csrf-token" content="..."> is in your HTML head.');
        throw new Error('CSRF token is missing.');
    }

    try {
        const response = await fetch(`/cart/add/${productId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken, // اضافه کردن توکن CSRF به هدر
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
        console.error('Error in addItemToCart API call:', error);
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
        const response = await fetch(`/cart/remove/${cartItemId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken, // اضافه کردن توکن CSRF به هدر
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
        const response = await fetch('/cart/contents', {
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
