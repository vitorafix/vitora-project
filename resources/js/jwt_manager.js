// resources/js/jwt_manager.js
console.log('jwt_manager.js loaded and starting...');

/**
 * Stores the JWT token in localStorage.
 * توکن JWT را در localStorage ذخیره می‌کند.
 * @param {string} token The JWT token to store.
 */
export const storeJwtToken = (token) => {
    localStorage.setItem('jwt_token', token);
    console.log('JWT Manager: Token stored.');
};

/**
 * Retrieves the JWT token from localStorage.
 * توکن JWT را از localStorage بازیابی می‌کند.
 * @returns {string|null} The JWT token, or null if not found.
 */
export const getJwtToken = () => {
    return localStorage.getItem('jwt_token');
};

/**
 * Clears the JWT token from localStorage.
 * توکن JWT را از localStorage حذف می‌کند.
 */
export const clearJwtToken = () => {
    localStorage.removeItem('jwt_token');
    console.log('JWT Manager: Token cleared.');
};
