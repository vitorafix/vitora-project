// api.js
// این ماژول مسئول مدیریت تمامی ارتباطات با سرور برای عملیات سبد خرید است.
// تمام توابع در اینجا async هستند و خطاهای شبکه و سرور را مدیریت می‌کنند.

/**
 * Configuration object for API settings.
 * شیء پیکربندی برای تنظیمات API.
 */
const API_CONFIG = {
    MAX_RETRIES: 3,
    TIMEOUT: 10000, // 10 seconds in milliseconds
    BASE_URL: '/api/cart',
    LOADING_STATES: new Set() // برای ترک کردن وضعیت loading هر درخواست
};

// Map to store cached responses
const responseCache = new Map();

/**
 * Helper function to get CSRF Token from a meta tag in HTML.
 * This token is necessary for the security of POST/PUT/DELETE requests.
 * @returns {string} The CSRF Token.
 */
function getCsrfToken() {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    return csrfMeta ? csrfMeta.getAttribute('content') : '';
}

/**
 * Helper function to get default headers for API requests.
 * @returns {Object} An object containing default headers.
 */
function getDefaultHeaders() {
    return {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken()
    };
}

/**
 * Helper function to preprocess requests before sending.
 * This can be used for adding additional headers, logging, etc.
 * تابع middleware برای پردازش پیش از ارسال درخواست.
 * @param {string} url - آدرس درخواست.
 * @param {Object} options - تنظیمات درخواست.
 * @returns {Object} تنظیمات پردازش شده.
 */
function preprocessRequest(url, options) {
    console.log(`🚀 API Request: ${options.method || 'GET'} ${url}`);
    // Add any global headers or modifications here if needed
    return options;
}

/**
 * Caches GET responses for a limited time.
 * کش کردن پاسخ‌های GET برای مدت محدود.
 * @param {string} url - آدرس درخواست.
 * @param {Object} data - پاسخ دریافتی (parse شده).
 * @param {number} ttl - مدت زمان کش (میلی‌ثانیه).
 */
function cacheResponse(url, data, ttl = 30000) {
    if (ttl > 0) {
        responseCache.set(url, { data: data, expires: Date.now() + ttl });
    }
}

/**
 * Retrieves a cached response if it's still valid.
 * پاسخ کش شده را در صورت معتبر بودن برمی‌گرداند.
 * @param {string} url - آدرس درخواست.
 * @returns {Object | null} Cached data or null if not found or expired.
 */
function getCachedResponse(url) {
    const cached = responseCache.get(url);
    if (cached && Date.now() < cached.expires) {
        return cached.data;
    }
    responseCache.delete(url); // Remove expired cache
    return null;
}

/**
 * Helper function to perform a fetch request with retry logic and a timeout.
 * @param {string} url - The URL to fetch.
 * @param {Object} options - Fetch options (method, headers, body, etc.).
 * @param {number} maxRetries - Maximum number of retries. Defaults to API_CONFIG.MAX_RETRIES.
 * @param {number} timeout - Timeout duration in milliseconds for each attempt. Defaults to API_CONFIG.TIMEOUT.
 * @returns {Promise<Response>} A Promise that resolves to the Response object.
 * @throws {Error} Throws an error if all retries fail or if the request times out.
 */
async function fetchWithRetry(url, options, maxRetries = API_CONFIG.MAX_RETRIES, timeout = API_CONFIG.TIMEOUT) {
    // Preprocess the request
    options = preprocessRequest(url, options);

    // Add URL to loading states
    API_CONFIG.LOADING_STATES.add(url);

    for (let i = 0; i < maxRetries; i++) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout); // Set timeout for each attempt

        try {
            const response = await fetch(url, { ...options, signal: controller.signal });
            clearTimeout(timeoutId); // Clear timeout if fetch completes
            API_CONFIG.LOADING_STATES.delete(url); // Remove from loading states on success
            return response;
        } catch (error) {
            clearTimeout(timeoutId); // Clear timeout on error
            if (error.name === 'AbortError') {
                console.warn(`Request to ${url} timed out or was aborted. Retrying... (${i + 1}/${maxRetries})`);
            } else {
                console.error(`Fetch error for ${url}:`, error);
            }

            if (i === maxRetries - 1) {
                API_CONFIG.LOADING_STATES.delete(url); // Remove from loading states on final failure
                throw error; // Re-throw if it's the last retry
            }
            // Wait before retrying (e.g., exponential backoff)
            await new Promise(resolve => setTimeout(resolve, 1000 * (i + 1))); // 1s, 2s, 3s delay
        }
    }
}


/**
 * Fetches current cart contents from the backend API.
 * @returns {Promise<Object>} A Promise that resolves to the cart data (including items, total quantity, and total price).
 */
export async function fetchCartContents() {
    const url = `${API_CONFIG.BASE_URL}/contents`;
    const cachedData = getCachedResponse(url);
    if (cachedData) {
        console.log('Returning cached cart contents for:', url);
        return cachedData;
    }

    try {
        const response = await fetchWithRetry(url, {
            method: 'GET',
            headers: getDefaultHeaders()
        });

        if (!response.ok) {
            // If the response is not successful, try to parse the JSON error message.
            const errorData = await response.json().catch(() => ({ message: 'خطای ناشناخته از سرور.' }));
            throw new Error(errorData.message || 'خطا در دریافت محتویات سبد خرید.');
        }

        const data = await response.json();
        cacheResponse(url, data); // Cache the response
        // window.showMessage('محتویات سبد خرید با موفقیت بارگذاری شد.', 'success'); // Usually, no message is displayed for initial fetch.
        return data;
    } catch (error) {
        console.error('خطا در دریافت محتویات سبد خرید:', error);
        // Display error message to the user.
        if (typeof window.showMessage === 'function') {
            window.showMessage(error.message || 'خطا در بارگذاری سبد خرید.', 'error');
        } else {
            console.warn('تابع window.showMessage یافت نشد. پیام خطا نمایش داده نشد.');
        }
        return { items: [], totalQuantity: 0, totalPrice: 0 }; // Return an empty cart on error.
    }
}

/**
 * Adds a product to the cart or updates its quantity via API.
 * @param {number} productId - The ID of the product.
 * @param {number} quantity - The quantity to add/update.
 * @returns {Promise<Object>} A Promise that resolves to the API response.
 */
export async function addOrUpdateCartItem(productId, quantity = 1) {
    // Parameter validation
    if (typeof productId !== 'number' || productId <= 0 || typeof quantity !== 'number' || quantity <= 0) {
        const errorMessage = 'پارامترهای ورودی برای افزودن/به‌روزرسانی آیتم سبد خرید نامعتبر هستند.';
        console.error(errorMessage, { productId, quantity });
        if (typeof window.showMessage === 'function') {
            window.showMessage(errorMessage, 'error');
        }
        throw new Error(errorMessage);
    }

    try {
        const response = await fetchWithRetry(`${API_CONFIG.BASE_URL}/add`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...getDefaultHeaders() // Use spread operator to merge headers
            },
            body: JSON.stringify({ product_id: productId, quantity: quantity })
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'خطا در افزودن محصول به سبد خرید.');
        }

        // Invalidate cart contents cache on successful modification
        responseCache.delete(`${API_CONFIG.BASE_URL}/contents`);

        // Display success message to the user.
        if (typeof window.showMessage === 'function') {
            window.showMessage(result.message || 'محصول به سبد خرید اضافه شد.', 'success');
        } else {
            console.warn('تابع window.showMessage یافت نشد. پیام موفقیت نمایش داده نشد.');
        }
        return result;
    } catch (error) {
        console.error('خطا در افزودن/به‌روزرسانی آیتم سبد خرید:', error);
        // Display error message to the user.
        if (typeof window.showMessage === 'function') {
            window.showMessage(error.message || 'خطا در افزودن محصول به سبد خرید.', 'error');
        } else {
            console.warn('تابع window.showMessage یافت نشد. پیام خطا نمایش داده نشد.');
        }
        throw error; // Re-throw the error to allow it to be handled elsewhere (e.g., by CartManager).
    }
}

/**
 * Updates the quantity of a specific cart item via API.
 * @param {number} cartItemId - The ID of the cart item.
 * @param {number} newQuantity - The new quantity.
 * @returns {Promise<Object>} A Promise that resolves to the API response.
 */
export async function updateCartItemQuantity(cartItemId, newQuantity) {
    // Parameter validation
    if (typeof cartItemId !== 'number' || cartItemId <= 0 || typeof newQuantity !== 'number' || newQuantity < 0) {
        const errorMessage = 'پارامترهای ورودی برای به‌روزرسانی تعداد آیتم سبد خرید نامعتبر هستند.';
        console.error(errorMessage, { cartItemId, newQuantity });
        if (typeof window.showMessage === 'function') {
            window.showMessage(errorMessage, 'error');
        }
        throw new Error(errorMessage);
    }

    try {
        const response = await fetchWithRetry(`${API_CONFIG.BASE_URL}/update/${cartItemId}`, {
            method: 'PUT', // Use PUT for updates
            headers: {
                'Content-Type': 'application/json',
                ...getDefaultHeaders()
            },
            body: JSON.stringify({ quantity: newQuantity })
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'خطا در به‌روزرسانی تعداد محصول.');
        }

        // Invalidate cart contents cache on successful modification
        responseCache.delete(`${API_CONFIG.BASE_URL}/contents`);

        // Display success message to the user.
        if (typeof window.showMessage === 'function') {
            window.showMessage(result.message || 'تعداد محصول به‌روزرسانی شد.', 'success');
        } else {
            console.warn('تابع window.showMessage یافت نشد. پیام موفقیت نمایش داده نشد.');
        }
        return result;
    } catch (error) {
        console.error('خطا در به‌روزرسانی تعداد آیتم سبد خرید:', error);
        // Display error message to the user.
        if (typeof window.showMessage === 'function') {
            window.showMessage(error.message || 'خطا در به‌روزرسانی تعداد محصول.', 'error');
        } else {
            console.warn('تابع window.showMessage یافت نشد. پیام خطا نمایش داده نشد.');
        }
        throw error;
    }
}

/**
 * Removes a specific cart item via API.
 * @param {number} cartItemId - The ID of the cart item to remove.
 * @returns {Promise<Object>} A Promise that resolves to the API response.
 */
export async function removeCartItem(cartItemId) {
    // Parameter validation
    if (typeof cartItemId !== 'number' || cartItemId <= 0) {
        const errorMessage = 'پارامتر ورودی برای حذف آیتم سبد خرید نامعتبر است.';
        console.error(errorMessage, { cartItemId });
        if (typeof window.showMessage === 'function') {
            window.showMessage(errorMessage, 'error');
        }
        throw new Error(errorMessage);
    }

    try {
        const response = await fetchWithRetry(`${API_CONFIG.BASE_URL}/remove/${cartItemId}`, {
            method: 'DELETE', // Use DELETE for removal
            headers: getDefaultHeaders()
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'خطا در حذف محصول از سبد خرید.');
        }

        // Invalidate cart contents cache on successful modification
        responseCache.delete(`${API_CONFIG.BASE_URL}/contents`);

        // Display success message to the user.
        if (typeof window.showMessage === 'function') {
            window.showMessage(result.message || 'محصول از سبد خرید حذف شد.', 'success');
        } else {
            console.warn('تابع window.showMessage یافت نشد. پیام موفقیت نمایش داده نشد.');
        }
        return result;
    } catch (error) {
        console.error('خطا در حذف آیتم سبد خرید:', error);
        // Display error message to the user.
        if (typeof window.showMessage === 'function') {
            window.showMessage(error.message || 'خطا در حذف محصول از سبد خرید.', 'error');
        } else {
            console.warn('تابع window.showMessage یافت نشد. پیام خطا نمایش داده نشد.');
        }
        throw error;
    }
}

/**
 * Clears all items from the cart via API.
 * @returns {Promise<Object>} A Promise that resolves to the API response.
 */
export async function clearCart() {
    try {
        const response = await fetchWithRetry(`${API_CONFIG.BASE_URL}/clear`, {
            method: 'POST', // Or DELETE, depending on your backend implementation.
            headers: getDefaultHeaders()
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'خطا در پاکسازی سبد خرید.');
        }

        // Invalidate cart contents cache on successful modification
        responseCache.delete(`${API_CONFIG.BASE_URL}/contents`);

        // Display success message to the user.
        if (typeof window.showMessage === 'function') {
            window.showMessage(result.message || 'سبد خرید خالی شد.', 'success');
        } else {
            console.warn('تابع window.showMessage یافت نشد. پیام موفقیت نمایش داده نشد.');
        }
        return result;
    } catch (error) {
        console.error('خطا در پاکسازی سبد خرید:', error);
        // Display error message to the user.
        if (typeof window.showMessage === 'function') {
            window.showMessage(error.message || 'خطا در پاکسازی سبد خرید.', 'error');
        } else {
            console.warn('تابع window.showMessage یافت نشد. پیام خطا نمایش داده نشد.');
        }
        throw error;
    }
}

/**
 * Checks if a specific request URL is currently in a loading state.
 * بررسی اینکه آیا درخواست خاصی در حال پردازش است یا خیر.
 * @param {string} url - آدرس درخواست.
 * @returns {boolean} true اگر در حال پردازش باشد.
 */
export function isRequestLoading(url) {
    return API_CONFIG.LOADING_STATES.has(url);
}

/**
 * Checks if no API requests are currently in a loading state.
 * بررسی اینکه آیا هیچ درخواستی در حال پردازش نیست.
 * @returns {boolean} true اگر هیچ درخواستی در حال پردازش نباشد.
 */
export function isApiIdle() {
    return API_CONFIG.LOADING_STATES.size === 0;
}
