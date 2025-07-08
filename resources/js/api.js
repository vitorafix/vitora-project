// api.js
// این فایل مسئول ارتباط با API بک‌اند برای عملیات سبد خرید است.
// این یک ساختار فرضی است و شما باید آن را با منطق واقعی API خود جایگزین کنید.

// آدرس پایه برای نقاط پایانی API سبد خرید.
// تغییر به '/api/cart' برای یکپارچگی با تعریف مسیرها در routes/api.php
const API_BASE_URL = '/api/cart'; 

/**
 * دریافت محتویات فعلی سبد خرید از API.
 * @returns {Promise<Object>} داده‌های سبد خرید شامل آیتم‌ها، تعداد کل و قیمت کل.
 */
export async function fetchCartContents() {
    console.log('🚀 API Request: GET ' + API_BASE_URL + '/contents');
    try {
        // استفاده از API_BASE_URL برای یکپارچگی
        const response = await fetch(`${API_BASE_URL}/contents`, {
            credentials: 'same-origin' // اضافه شده: برای ارسال کوکی‌های سشن
        });
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to fetch cart contents.');
        }
        return await response.json();
    } catch (error) {
        console.error('API Error (fetchCartContents):', error);
        throw error;
    }
}

/**
 * افزودن یک محصول به سبد خرید.
 * @param {string} productId - شناسه محصول.
 * @param {number} quantity - تعداد محصول.
 * @returns {Promise<Object>} پاسخ API.
 */
export async function addItemToCart(productId, quantity) {
    // استفاده از API_BASE_URL برای یکپارچگی
    console.log(`🚀 API Request: POST ${API_BASE_URL}/add/${productId}`, { quantity });
    try {
        const response = await fetch(`${API_BASE_URL}/add/${productId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            // product_id اکنون در URL است، فقط quantity در body ارسال می‌شود.
            body: JSON.stringify({ quantity: quantity }), 
            credentials: 'same-origin' // اضافه شده: برای ارسال کوکی‌های سشن
        });
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to add item to cart.');
        }
        return await response.json();
    } catch (error) {
        console.error('API Error (addItemToCart):', error);
        throw error;
    }
}

/**
 * به‌روزرسانی تعداد یک آیتم در سبد خرید.
 * @param {string} productId - شناسه محصول.
 * @param {number} quantity - تعداد جدید محصول.
 * @returns {Promise<Object>} پاسخ API.
 */
export async function updateCartItem(productId, quantity) {
    // استفاده از API_BASE_URL برای یکپارچگی
    console.log(`🚀 API Request: PUT ${API_BASE_URL}/update/${productId}`, { quantity });
    try {
        const response = await fetch(`${API_BASE_URL}/update/${productId}`, {
            method: 'PUT', // یا PATCH
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            // product_id اکنون در URL است، فقط quantity در body ارسال می‌شود.
            body: JSON.stringify({ quantity: quantity }), 
            credentials: 'same-origin' // اضافه شده: برای ارسال کوکی‌های سشن
        });
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to update cart item.');
        }
        return await response.json();
    } catch (error) {
        console.error('API Error (updateCartItem):', error);
        throw error;
    }
}

/**
 * حذف یک آیتم از سبد خرید.
 * @param {string} productId - شناسه محصول.
 * @returns {Promise<Object>} پاسخ API.
 */
export async function removeCartItem(productId) {
    // استفاده از API_BASE_URL برای یکپارچگی
    console.log(`🚀 API Request: DELETE ${API_BASE_URL}/remove/${productId}`, { productId });
    try {
        const response = await fetch(`${API_BASE_URL}/remove/${productId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            // product_id اکنون در URL است، body می‌تواند خالی باشد یا فقط یک پیام.
            body: JSON.stringify({ product_id: productId }), // می توانید این را حذف کنید اگر بک‌اند نیازی به آن ندارد
            credentials: 'same-origin' // اضافه شده: برای ارسال کوکی‌های سشن
        });
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to remove item from cart.');
        }
        return await response.json();
    } catch (error) {
        console.error('API Error (removeCartItem):', error);
        throw error;
    }
}

// اگر متدهای دیگری برای API سبد خرید دارید (مانند clearCart, applyCoupon, removeCoupon)،
// باید credentials: 'same-origin' را به آن‌ها نیز اضافه کنید.
