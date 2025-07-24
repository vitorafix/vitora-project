// resources/js/core/api.js

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
 * This function is primarily for the login flow (existing users).
 * It can also be used for registration flow if `isRegistrationAttempt` is true.
 *
 * @param {string} mobileNumber The mobile number to send OTP to.
 * @param {boolean} [isRegistrationAttempt=false] Indicates if this is a registration attempt.
 * @returns {Promise<object>} The API response data.
 */
export const sendOtp = async (mobileNumber, isRegistrationAttempt = false) => {
    try {
        const payload = {
            mobile_number: mobileNumber
        };
        // Add is_registration flag to payload if it's a registration attempt
        if (isRegistrationAttempt) {
            payload.is_registration = true;
        }

        const response = await axios.post('/api/auth/send-otp', payload);
        console.log('API: OTP sent successfully:', response.data);
        return response.data;
    } catch (error) {
        console.error('API: Error sending OTP:', error.response?.data || error.message);
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
        return response.data;
    } catch (error) {
        console.error('API: Error during registration and OTP send:', error.response?.data || error.message);
        throw error;
    }
};

/**
 * Requests OTP for registration flow (initial send or resend).
 * This function calls the dedicated registration OTP endpoint.
 *
 * @param {string} mobileNumber The mobile number to request OTP for.
 * @returns {Promise<object>} The API response data.
 */
export const requestOtpForRegister = async (mobileNumber) => {
    try {
        const response = await axios.post('/api/auth/register/request-otp', {
            mobile_number: mobileNumber
        });
        console.log('API: OTP requested for registration successfully:', response.data);
        return response.data;
    } catch (error) {
        console.error('API: Error requesting OTP for registration:', error.response?.data || error.message);
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

        return response.data;
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
                'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
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
 * CHANGED: Added caching mechanism to prevent multiple simultaneous requests.
 */
export const fetchCartContents = async () => {
    // If a fetchCartContents request is already in progress, return the same Promise.
    if (cartFetchPromise) {
        console.log('API: fetchCartContents request already in progress. Returning existing promise.');
        return cartFetchPromise;
    }

    // guestUuid is now handled by the global setGuestUuidHeader
    // No need for local guestUuid and headers here.

    // Create a new Promise and store it in cartFetchPromise.
    cartFetchPromise = axios.get('/api/cart/contents') // Remove { headers } here
        .finally(() => {
            // After the request is settled (success or failure), clear the Promise so subsequent requests can execute.
            cartFetchPromise = null;
        });

    console.log('API: Initiating new fetchCartContents request.');
    return cartFetchPromise;
};

/**
 * Adds a product to the cart.
 * @param {number} productId The ID of the product to add.
 * @param {number} quantity The quantity to add.
 * @returns {Promise<object>} The API response data.
 */
export const addToCart = async (productId, quantity = 1) => {
    try {
        // guestUuid is now handled by the global setGuestUuidHeader
        // No need for local guestUuid and headers here.

        // CHANGED: Construct the URL to include productId as a path parameter
        const response = await axios.post(`/api/cart/add/${productId}`, {
            quantity: quantity // Only quantity is needed in the body now
        }); // Remove { headers } here
        console.log('API: Product added to cart:', response.data);
        return response.data;
    } catch (error) {
        console.error('API: Error adding to cart:', error.response?.data || error.message);
        throw error;
    }
};

/**
 * Updates the quantity of a product in the cart.
 * @param {number} productId The ID of the product to update.
 * @param {number} quantity The new quantity.
 * @returns {Promise<object>} The API response data.
 */
export const updateCartItemQuantity = async (productId, quantity) => {
    try {
        // guestUuid is now handled by the global setGuestUuidHeader
        // No need for local guestUuid and headers here.

        const response = await axios.put(`/api/cart/update/${productId}`, {
            quantity: quantity
        }); // Remove { headers } here
        console.log('API: Cart item quantity updated:', response.data);
        return response.data;
    } catch (error) {
        console.error('API: Error updating cart item quantity:', error.response?.data || error.message);
        throw error;
    }
};

/**
 * Removes a product from the cart.
 * @param {number} productId The ID of the product to remove.
 * @returns {Promise<object>} The API response data.
 */
export const removeCartItem = async (productId) => {
    try {
        // guestUuid is now handled by the global setGuestUuidHeader
        // No need for local guestUuid and headers here.

        const response = await axios.delete(`/api/cart/remove/${productId}`); // Remove { headers } here
        console.log('API: Cart item removed:', response.data);
        return response.data;
    } catch (error) {
        console.error('API: Error removing cart item:', error.response?.data || error.message);
        throw error;
    }
};

/**
 * Clears all items from the cart.
 * @returns {Promise<object>} The API response data.
 */
export const clearCart = async () => {
    try {
        // guestUuid is now handled by the global setGuestUuidHeader
        // No need for local guestUuid and headers here.

        const response = await axios.post('/api/cart/clear', {}); // Remove { headers } here
        console.log('API: Cart cleared:', response.data);
        return response.data;
    } catch (error) {
        console.error('API: Error clearing cart:', error.response?.data || error.message);
        throw error;
    }
};

/**
 * Applies a coupon to the cart.
 * @param {string} couponCode The coupon code to apply.
 * @returns {Promise<object>} The API response data.
 */
export const applyCoupon = async (couponCode) => {
    try {
        // guestUuid is now handled by the global setGuestUuidHeader
        // No need for local guestUuid and headers here.

        const response = await axios.post('/api/cart/apply-coupon', {
            coupon_code: couponCode
        }); // Remove { headers } here
        console.log('API: Coupon applied successfully:', response.data);
        return response.data;
    } catch (error) {
        console.error('API: Error applying coupon:', error.response?.data || error.message);
        throw error;
    }
};

/**
 * Removes the applied coupon from the cart.
 * @returns {Promise<object>} The API response data.
 */
export const removeCoupon = async () => {
    try {
        // guestUuid is now handled by the global setGuestUuidHeader
        // No need for local guestUuid and headers here.

        const response = await axios.post('/api/cart/remove-coupon', {}); // Remove { headers } here
        console.log('API: Coupon removed successfully:', response.data);
        return response.data;
    } catch (error) {
        console.error('API: Error removing coupon:', error.response?.data || error.message);
        throw error;
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
        return response.data;
    } catch (error) {
        console.error('API: Error fetching user data:', error.response?.data || error.message);
        throw error;
    }
};
