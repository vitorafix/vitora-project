// resources/js/core/api.js
console.log('api.js loaded and starting...');

import axios from './axiosInstance.js'; // مسیر صحیح: axiosInstance.js در همین فولدر core است
// تغییر: مسیر import برای jwt_manager.js به فولدر auth اصلاح شده است.
import { storeJwtToken, clearJwtToken, getJwtToken } from '../auth/jwt_manager.js';

// Export JWT functions directly for use in auth.js
export { storeJwtToken, clearJwtToken, getJwtToken };

// Variable to hold the promise of an active fetchCartContents request
// This prevents sending multiple simultaneous requests.
let cartFetchPromise = null;

/**
 * Sets the X-Guest-UUID header for all subsequent Axios requests.
 * This should be called once when the guestUuid is known (e.g., on CartManager initialization).
 * @param {string|null} guestUuid - The guest UUID to set. If null, the header will be removed.
 */
export const setGuestUuidHeader = (guestUuid) => {
    if (guestUuid) {
        axios.defaults.headers.common['X-Guest-UUID'] = guestUuid;
        console.log('API: X-Guest-UUID header set globally to:', guestUuid);
    } else {
        delete axios.defaults.headers.common['X-Guest-UUID'];
        console.log('API: X-Guest-UUID header removed globally.');
    }
};

/**
 * Sends an OTP to the specified mobile number.
 * این تابع عمدتاً برای جریان **ورود (Login)** کاربران موجود است.
 * اگر شماره در سیستم وجود نداشته باشد، سرور پاسخ با وضعیت (status) مناسب را برمی‌گرداند.
 *
 * @param {string} mobileNumber شماره موبایل برای ارسال OTP.
 * @param {boolean} [isRegistrationAttempt=false] آیا این یک تلاش برای ثبت‌نام است؟ (فقط در موارد خاص استفاده شود)
 * @returns {Promise<object>} داده‌های پاسخ API شامل 'message' و 'status' (مثلاً 'otp_sent' یا 'not_registered').
 */
export const sendOtp = async (mobileNumber, isRegistrationAttempt = false) => {
    try {
        const payload = {
            mobile_number: mobileNumber
        };
        // Add is_registration flag to payload if it's a registration attempt
        // این پرچم به MobileAuthController کمک می‌کند تا بین تلاش برای ورود و ثبت‌نام تمایز قائل شود.
        if (isRegistrationAttempt) {
            payload.is_registration = true;
        }

        const response = await axios.post('/api/auth/send-otp', payload);
        console.log('API: OTP sent successfully (or status received):', response.data);
        // در این حالت، Axios خطایی پرتاب نمی‌کند زیرا پاسخ سرور 2xx است.
        // auth.js باید response.data.status را برای تصمیم‌گیری بررسی کند.
        return response.data;
    } catch (error) {
        console.error('API: Error sending OTP:', error.response?.data || error.message);
        // در اینجا فقط خطاهای واقعی (مانند مشکلات شبکه، خطاهای 5xx سرور، یا خطاهای اعتبارسنجی که توسط سرور به عنوان خطا برگردانده می‌شوند) پرتاب می‌شوند.
        // پاسخ‌های 2xx با status: "not_registered" در اینجا مدیریت نمی‌شوند و به try block می‌روند.
        throw error;
    }
};

/**
 * Requests OTP specifically for the **registration flow** (initial send or resend).
 * این تابع برای ارسال OTP به شماره موبایلی است که هنوز در سیستم ثبت‌نام نکرده است.
 *
 * @param {string} mobileNumber شماره موبایل برای درخواست OTP.
 * @returns {Promise<object>} داده‌های پاسخ API.
 */
export const requestOtpForRegister = async (mobileNumber) => {
    try {
        const response = await axios.post('/api/auth/register/request-otp', {
            mobile_number: mobileNumber
        });
        console.log('API: OTP requested for registration successfully:', response.data);
        return response.data; // This returns the raw response data, auth.js handles success/message
    } catch (error) {
        console.error('API: Error requesting OTP for registration:', error.response?.data || error.message);
        throw error;
    }
};

/**
 * Registers a new user and sends an OTP.
 * This function is specifically for the registration flow.
 * It calls the /api/auth/register endpoint which in turn calls requestOtpForRegister internally.
 *
 * @param {string} mobileNumber The mobile number for registration.
 * @param {string} name The user's first name.
 * @param {string} [lastname=''] The user's last name (optional).
 * @returns {Promise<object>} The API response data.
 */
export const registerUserAndSendOtp = async (mobileNumber, name, lastname = '') => {
    try {
        const response = await axios.post('/api/auth/register', {
            mobile_number: mobileNumber,
            name: name,
            lastname: lastname
        });
        console.log('API: User registered and OTP sent successfully:', response.data);
        return response.data; // This returns the raw response data, auth.js handles success/message
    } catch (error) {
        console.error('API: Error during registration and OTP send:', error.response?.data || error.message);
        throw error;
    }
};


/**
 * Verifies the OTP and attempts to log in the user.
 *
 * @param {string} mobileNumber The mobile number.
 * @param {string} otp The OTP entered by the user.
 * @returns {Promise<object>} The API response data, including JWT token on success.
 */
export const verifyOtpAndLogin = async (mobileNumber, otp) => {
    try {
        // guestUuid is now handled by the global setGuestUuidHeader
        const response = await axios.post('/api/auth/verify-otp', {
            mobile_number: mobileNumber,
            otp: otp
        });

        console.log('API: OTP verified and login successful:', response.data);

        if (response.data.access_token) {
            storeJwtToken(response.data.access_token);
            // Optionally remove guest_uuid after successful login and cart merge
            // This is handled by CartService in backend, but good to clear client-side too.
            localStorage.removeItem('guest_uuid');
            setGuestUuidHeader(null); // Clear the header after login/merge
        }

        return response.data; // This returns the raw response data, auth.js handles success/message
    } catch (error) {
        console.error('API: Error verifying OTP and logging in:', error.response?.data || error.message);
        throw error;
    }
};

/**
 * Logs out the currently authenticated user.
 * @returns {Promise<object>} The API response data.
 */
export const logoutUser = async () => {
    try {
        // Send API request to server to invalidate token/session
        await axios.post('/api/auth/logout', {}, {
            headers: {
                // Changed to use getJwtToken() for consistency with jwt_manager.js
                'Authorization': 'Bearer ' + getJwtToken()
            }
        });

        clearJwtToken(); // Clear JWT token from localStorage on logout
        console.log('API: User logged out successfully.'); // Log message after clearing token

        // Redirect to login page or home page after successful logout
        window.location.href = '/'; // Redirect to the home page as requested
    } catch (error) {
        console.error('API: Error logging out:', error.response?.data || error.message);
        throw error;
    }
};

/**
 * Fetches cart contents for the current user or guest.
 * @returns {Promise<object>} The API response data.
 *
 * CHANGED: Implemented a robust caching mechanism to prevent multiple simultaneous requests.
 * CHANGED: Now resolves with `response.data` directly, assuming it contains `items` and `summary`.
 */
export const fetchCartContents = async () => {
    // If a fetchCartContents request is already in progress, return the same Promise.
    if (cartFetchPromise) {
        console.warn('API: fetchCartContents request already in progress. Returning existing promise.');
        return cartFetchPromise;
    }

    // Create a new Promise and store it in cartFetchPromise.
    cartFetchPromise = new Promise(async (resolve, reject) => {
        try {
            console.log('API: Initiating new fetchCartContents request.');
            const response = await axios.get('/api/cart/contents');
            console.log('API: fetchCartContents response received:', response.data); // لاگ کردن پاسخ کامل
            // Resolve directly with response.data, assuming it contains 'items' and 'summary'
            resolve(response.data); // Removed { success: true, data: response.data, message: ... }
        } catch (error) {
            console.error('API: Error fetching cart contents:', error.response?.data || error.message);
            reject({ success: false, message: error.response?.data?.message || 'خطا در دریافت محتویات سبد خرید.' });
        } finally {
            cartFetchPromise = null; // Reset the Promise after it settles (success or failure)
        }
    });
    return cartFetchPromise;
};

/**
 * Adds a product to the cart.
 * @param {number} productId The ID of the product to add.
 * @param {number} quantity The quantity to add.
 * @param {number|null} productVariantId - شناسه واریانت محصول (اختیاری).
 * @returns {Promise<object>} The API response data with success flag and message.
 *
 * CHANGED: Ensured the response format includes 'success' and 'message' properties for consistency.
 */
export const addToCart = async (productId, quantity = 1, productVariantId = null) => {
    try {
        console.log(`API: Adding product ${productId} to cart with quantity ${quantity}.`);
        const response = await axios.post(`/api/cart/add/${productId}`, {
            quantity: quantity,
            product_variant_id: productVariantId // Ensure variant ID is passed if available
        });
        console.log('API: Product added to cart:', response.data);
        // Assuming backend response for add to cart is { success: true, message: "..." }
        return { success: true, message: response.data.message || 'محصول با موفقیت به سبد خرید اضافه شد.', data: response.data };
    } catch (error) {
        console.error('API: Error adding to cart:', error.response?.data || error.message);
        throw { success: false, message: error.response?.data?.message || 'خطا در افزودن محصول به سبد خرید.' };
    }
};

/**
 * Updates the quantity of a product in the cart.
 * @param {string} cartItemId The ID of the cart item to update.
 * @param {number} quantity The new quantity.
 * @returns {Promise<object>} The API response data with success flag and message.
 *
 * CHANGED: Ensured the response format includes 'success' and 'message' properties for consistency.
 */
export const updateCartItemQuantity = async (cartItemId, quantity) => {
    try {
        console.log(`API: Updating cart item ${cartItemId} quantity to ${quantity}.`);
        const response = await axios.put(`/api/cart/update/${cartItemId}`, {
            quantity: quantity
        });
        console.log('API: Cart item quantity updated:', response.data);
        return { success: true, message: response.data.message || 'تعداد محصول در سبد خرید به‌روز شد.', data: response.data };
    } catch (error) {
        console.error('API: Error updating cart item quantity:', error.response?.data || error.message);
        throw { success: false, message: error.response?.data?.message || 'خطا در به‌روزرسانی تعداد محصول در سبد خرید.' };
    }
};

/**
 * Removes a product from the cart.
 * @param {string} cartItemId The ID of the cart item to remove.
 * @returns {Promise<object>} The API response data with success flag and message.
 *
 * CHANGED: Ensured the response format includes 'success' and 'message' properties for consistency.
 */
export const removeCartItem = async (cartItemId) => {
    try {
        console.log(`API: Removing cart item ${cartItemId}.`);
        const response = await axios.delete(`/api/cart/remove/${cartItemId}`);
        console.log('API: Cart item removed:', response.data);
        return { success: true, message: response.data.message || 'محصول از سبد خرید حذف شد.', data: response.data };
    } catch (error) {
        console.error('API: Error removing cart item:', error.response?.data || error.message);
        throw { success: false, message: error.response?.data?.message || 'خطا در حذف محصول از سبد خرید.' };
    }
};

/**
 * Clears all items from the cart.
 * @returns {Promise<object>} The API response data with success flag and message.
 *
 * CHANGED: Ensured the response format includes 'success' and 'message' properties for consistency.
 */
export const clearCart = async () => {
    try {
        console.log('API: Clearing cart.');
        const response = await axios.post('/api/cart/clear', {});
        console.log('API: Cart cleared:', response.data);
        return { success: true, message: response.data.message || 'سبد خرید با موفقیت پاک شد.', data: response.data };
    } catch (error) {
        console.error('API: Error clearing cart:', error.response?.data || error.message);
        throw { success: false, message: error.response?.data?.message || 'خطا در پاک کردن سبد خرید.' };
    }
};

/**
 * Applies a coupon to the cart.
 * @param {string} couponCode The coupon code to apply.
 * @returns {Promise<object>} The API response data with success flag and message.
 *
 * CHANGED: Ensured the response format includes 'success' and 'message' properties for consistency.
 */
export const applyCoupon = async (couponCode) => {
    try {
        console.log(`API: Applying coupon code: ${couponCode}.`);
        const response = await axios.post('/api/cart/apply-coupon', {
            coupon_code: couponCode
        });
        console.log('API: Coupon applied successfully:', response.data);
        return { success: true, message: response.data.message || 'کد تخفیف با موفقیت اعمال شد.', data: response.data };
    } catch (error) {
        console.error('API: Error applying coupon:', error.response?.data || error.message);
        throw { success: false, message: error.response?.data?.message || 'خطا در اعمال کد تخفیف.' };
    }
};

/**
 * Removes the applied coupon from the cart.
 * @returns {Promise<object>} The API response data with success flag and message.
 *
 * CHANGED: Ensured the response format includes 'success' and 'message' properties for consistency.
 */
export const removeCoupon = async () => {
    try {
        console.log('API: Removing coupon.');
        const response = await axios.post('/api/cart/remove-coupon', {});
        console.log('API: Coupon removed successfully:', response.data);
        return { success: true, message: response.data.message || 'کد تخفیف با موفقیت حذف شد.', data: response.data };
    } catch (error) {
        console.error('API: Error removing coupon:', error.response?.data || error.message);
        throw { success: false, message: error.response?.data?.message || 'خطا در حذف کد تخفیف.' };
    }
};


/**
 * Fetches user data from the authenticated endpoint.
 * @returns {Promise<object>} The user data.
 */
export const fetchUserData = async () => {
    try {
        const response = await axios.get('/api/user');
        console.log('API: User data fetched:', response.data);
        return response.data; // This returns the raw response data, navbar_new.js handles success/message
    } catch (error) {
        console.error('API: Error fetching user data:', error.response?.data || error.message);
        clearJwtToken(); // اگر توکن نامعتبر بود، آن را پاک کن
        throw error;
    }
};
