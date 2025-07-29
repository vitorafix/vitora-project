// resources/js/auth/jwt_manager.js
console.log('jwt_manager.js loaded and starting...');

const TOKEN_KEY = 'jwe_auth_token'; // Changed to jwe_auth_token for consistency with JWE naming

/**
 * Stores the JWT/JWE token in localStorage.
 * توکن JWT/JWE را در localStorage ذخیره می‌کند.
 * @param {string} token The JWT/JWE token to store.
 */
export const storeJwtToken = (token) => {
    if (token) {
        localStorage.setItem(TOKEN_KEY, token);
        console.log('JWT Manager: Token stored as JWE_AUTH_TOKEN.');
    } else {
        console.warn('JWT Manager: Attempted to store an empty or null token.');
    }
};

/**
 * Retrieves the JWT/JWE token from localStorage.
 * توکن JWT/JWE را از localStorage بازیابی می‌کند.
 * @returns {string|null} The JWT/JWE token, or null if not found.
 */
export const getJwtToken = () => {
    return localStorage.getItem(TOKEN_KEY);
};

/**
 * Clears the JWT/JWE token from localStorage.
 * توکن JWT/JWE را از localStorage حذف می‌کند.
 */
export const clearJwtToken = () => {
    localStorage.removeItem(TOKEN_KEY);
    console.log('JWT Manager: Token cleared from JWE_AUTH_TOKEN.');
};
