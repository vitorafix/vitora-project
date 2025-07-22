// resources/js/api.js

import axios from './axiosInstance.js'; // Ensure this path is correct for your project setup
import { storeJwtToken, clearJwtToken, getJwtToken } from './jwt_manager.js'; // Import JWT functions

// Export JWT functions directly for use in auth.js
export { storeJwtToken, clearJwtToken, getJwtToken };

/**
 * Sends an OTP to the specified mobile number.
 * This function is primarily for the login flow (existing users).
 *
 * @param {string} mobileNumber The mobile number to send OTP to.
 * @returns {Promise<object>} The API response data.
 */
export const sendOtp = async (mobileNumber) => {
    try {
        const response = await axios.post('/api/auth/send-otp', {
            mobile_number: mobileNumber
        });
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
 * Verifies the OTP and attempts to log in the user.
 *
 * @param {string} mobileNumber The mobile number.
 * @param {string} otp The OTP entered by the user.
 * @returns {Promise<object>} The API response data, including JWT token on success.
 */
export const verifyOtpAndLogin = async (mobileNumber, otp) => {
    try {
        const guestUuid = localStorage.getItem('guest_uuid'); // Retrieve guest_uuid
        const headers = guestUuid ? { 'X-Guest-UUID': guestUuid } : {};

        const response = await axios.post('/api/auth/verify-otp', {
            mobile_number: mobileNumber,
            otp: otp
        }, { headers }); // Pass headers here

        console.log('API: OTP verified and login successful:', response.data);

        if (response.data.access_token) {
            storeJwtToken(response.data.access_token);
            // Optionally remove guest_uuid after successful login and cart merge
            localStorage.removeItem('guest_uuid');
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
        window.location.href = '/'; // CHANGED: Redirect to the home page
    } catch (error) {
        console.error('API: Error logging out:', error.response?.data || error.message);
        throw error;
    }
};

/**
 * Fetches cart contents for the current user or guest.
 * @returns {Promise<object>} The API response data.
 */
export const fetchCartContents = async () => {
    try {
        const guestUuid = localStorage.getItem('guest_uuid');
        const headers = guestUuid ? { 'X-Guest-UUID': guestUuid } : {};

        const response = await axios.get('/api/cart/contents', { headers });
        console.log('API: Cart contents fetched:', response.data);
        return response.data;
    } catch (error) {
        console.error('API: Error fetching cart contents:', error.response?.data || error.message);
        throw error;
    }
};

/**
 * Adds a product to the cart.
 * @param {number} productId The ID of the product to add.
 * @param {number} quantity The quantity to add.
 * @returns {Promise<object>} The API response data.
 */
export const addToCart = async (productId, quantity = 1) => {
    try {
        const guestUuid = localStorage.getItem('guest_uuid');
        const headers = guestUuid ? { 'X-Guest-UUID': guestUuid } : {};

        const response = await axios.post('/api/cart/add', {
            product_id: productId,
            quantity: quantity
        }, { headers });
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
export const updateCartItemQuantity = async (productId, quantity) => { // Changed from updateCartItem to updateCartItemQuantity for consistency
    try {
        const guestUuid = localStorage.getItem('guest_uuid');
        const headers = guestUuid ? { 'X-Guest-UUID': guestUuid } : {};

        const response = await axios.put(`/api/cart/update/${productId}`, {
            quantity: quantity
        }, { headers });
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
        const guestUuid = localStorage.getItem('guest_uuid');
        const headers = guestUuid ? { 'X-Guest-UUID': guestUuid } : {};

        const response = await axios.delete(`/api/cart/remove/${productId}`, { headers });
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
        const guestUuid = localStorage.getItem('guest_uuid');
        const headers = guestUuid ? { 'X-Guest-UUID': guestUuid } : {};

        const response = await axios.post('/api/cart/clear', {}, { headers });
        console.log('API: Cart cleared:', response.data);
        return response.data;
    } catch (error) {
        console.error('API: Error clearing cart:', error.response?.data || error.message);
        throw error;
    }
};

/**
 * Applies a coupon code to the cart.
 * @param {string} couponCode The coupon code to apply.
 * @returns {Promise<object>} The API response data.
 */
export const applyCoupon = async (couponCode) => {
    try {
        const guestUuid = localStorage.getItem('guest_uuid');
        const headers = guestUuid ? { 'X-Guest-UUID': guestUuid } : {};

        const response = await axios.post('/api/cart/apply-coupon', {
            coupon_code: couponCode
        }, { headers });
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
        const guestUuid = localStorage.getItem('guest_uuid');
        const headers = guestUuid ? { 'X-Guest-UUID': guestUuid } : {};

        const response = await axios.post('/api/cart/remove-coupon', {}, { headers });
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
