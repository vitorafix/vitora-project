// resources/js/api.js
import axios from 'axios';

// URL پایه برای API شما.
// این باید با پیشوند مسیرهای API شما در Laravel مطابقت داشته باشد (معمولاً /api).
const API_BASE_URL = '/api'; 

// تابع کمکی برای دریافت توکن CSRF از تگ meta
function getCsrfToken() {
    const tokenElement = document.querySelector('meta[name="csrf-token"]');
    return tokenElement ? tokenElement.getAttribute('content') : '';
}

// تنظیمات پیش‌فرض برای هدرهای Axios
axios.defaults.headers.common['Content-Type'] = 'application/json';
axios.defaults.headers.common['Accept'] = 'application/json';
// اضافه کردن هدر CSRF برای تمام درخواست‌های POST/PUT/DELETE
// لاراول به طور پیش‌فرض این را در bootstrap.js برای Axios تنظیم می‌کند، اما برای اطمینان اینجا هم اضافه می‌کنیم.
axios.defaults.headers.common['X-CSRF-TOKEN'] = getCsrfToken();


// نکته مهم: اگر فرانت‌اند و بک‌اند روی دامنه‌های متفاوتی هستند (CORS)،
// باید withCredentials را true تنظیم کنید تا کوکی‌ها (مانند guest_uuid) ارسال شوند
// و کوکی‌های HttpOnly (مانند laravel_session اگر هنوز در بخش‌هایی استفاده می‌کنید) دریافت شوند.
// اگر روی یک دامنه/ساب‌دامنه هستند، این خط ممکن است ضروری نباشد اما یک روش خوب است.
axios.defaults.withCredentials = true;


// --- مدیریت توکن JWT ---

/**
 * توکن JWT را در localStorage ذخیره می‌کند.
 * @param {string} token - توکن JWT برای ذخیره.
 */
export const storeJwtToken = (token) => { // Changed to named export
    if (token) {
        localStorage.setItem('jwt_token', token);
        // هدر Authorization را برای تمام درخواست‌های آتی Axios تنظیم می‌کند.
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        console.log('API: JWT Token stored and Axios header set.');
    } else {
        console.warn('API: Attempted to store an empty or null JWT token.');
    }
};

/**
 * توکن JWT را از localStorage بازیابی می‌کند.
 * @returns {string|null} توکن JWT یا null اگر یافت نشود.
 */
export const getJwtToken = () => { // Changed to named export
    return localStorage.getItem('jwt_token');
};

/**
 * توکن JWT را از localStorage حذف می‌کند.
 */
export const clearJwtToken = () => { // Changed to named export
    localStorage.removeItem('jwt_token');
    delete axios.defaults.headers.common['Authorization'];
    console.log('API: JWT Token removed from localStorage and Axios headers.');
};


// Axios را با توکن موجود (اگر در بارگذاری صفحه موجود باشد) مقداردهی اولیه می‌کند.
const initialToken = getJwtToken();
if (initialToken) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${initialToken}`;
    console.log('Axios initialized with existing JWT token.');
}


// --- توابع تعامل با API ---

/**
 * درخواست تأیید OTP را ارسال کرده و توکن JWT را مدیریت می‌کند.
 * @param {string} mobileNumber - شماره موبایل کاربر.
 * @param {string} otp - کد OTP وارد شده توسط کاربر.
 * @returns {Promise<Object>} یک Promise که با داده‌های پاسخ API حل می‌شود.
 */
export async function verifyOtpAndLogin(mobileNumber, otp) { // Changed to named export
    try {
        const response = await axios.post(`${API_BASE_URL}/auth/verify-otp`, {
            mobile_number: mobileNumber,
            otp: otp
        });

        if (response.data.token) {
            storeJwtToken(response.data.token);
            console.log('Login successful! JWT token received and stored.');
        } else {
            console.warn('Login successful, but no JWT token received.');
        }

        return response.data;
    } catch (error) {
        console.error('Error during OTP verification and login:', error.response ? error.response.data : error.message);
        clearJwtToken(); // اطمینان از پاک شدن توکن در صورت شکست لاگین
        throw error; // برای اینکه کامپوننت فراخواننده بتواند خطا را مدیریت کند.
    }
}

/**
 * تابع ارسال OTP به شماره موبایل.
 * @param {string} mobileNumber - شماره موبایل کاربر.
 * @param {object} [extraData={}] - داده‌های اضافی مانند نام و نام خانوادگی برای ثبت‌نام.
 * @returns {Promise<Object>} یک Promise که با داده‌های پاسخ API حل می‌شود.
 */
export async function sendOtp(mobileNumber, extraData = {}) { // Changed to named export
    try {
        const payload = {
            mobile_number: mobileNumber,
            ...extraData // اضافه کردن داده‌های اضافی به payload
        };
        const response = await axios.post(`${API_BASE_URL}/auth/send-otp`, payload);
        console.log('OTP sent:', response.data);
        return response.data;
    } catch (error) {
        console.error('Error sending OTP:', error.response ? error.response.data : error.message);
        throw error;
    }
}


/**
 * محتویات سبد خرید را واکشی می‌کند.
 * این درخواست به طور خودکار توکن JWT (اگر موجود باشد) و کوکی guest_uuid را شامل می‌شود.
 * @returns {Promise<Object>} یک Promise که با داده‌های سبد خرید حل می‌شود.
 */
export async function fetchCartContents() { // Changed to named export
    try {
        const response = await axios.get(`${API_BASE_URL}/cart/contents`);
        console.log('Cart contents fetched:', response.data);
        // اطمینان حاصل کنید که 'items' همیشه یک آرایه است
        if (response.data && response.data.data && !Array.isArray(response.data.data.items)) {
            console.warn("API response for cart contents did not contain 'items' as an array. Defaulting to empty array.");
            response.data.data.items = [];
        }
        return response.data;
    } catch (error) {
        console.error('Error fetching cart contents:', error.response ? error.response.data : error.message);
        throw error;
    }
}

/**
 * محصولی را به سبد خرید اضافه می‌کند.
 * این درخواست به طور خودکار توکن JWT (اگر موجود باشد) و کوکی guest_uuid را شامل می‌شود.
 * @param {number} productId - شناسه محصول برای اضافه کردن.
 * @param {number} quantity - تعداد برای اضافه کردن.
 * @returns {Promise<Object>} یک Promise که با داده‌های به‌روز شده سبد خرید حل می‌شود.
 */
export async function addItem(productId, quantity = 1) { // Changed to named export
    try {
        const response = await axios.post(`${API_BASE_URL}/cart/add/${productId}`, {
            quantity: quantity
        });
        console.log('Product added to cart:', response.data);
        return response.data;
    } catch (error) {
        console.error('Error adding product to cart:', error.response ? error.response.data : error.message);
        throw error;
    }
}

/**
 * تعداد آیتم سبد خرید را به‌روزرسانی می‌کند.
 * این درخواست به طور خودکار توکن JWT (اگر موجود باشد) و کوکی guest_uuid را شامل می‌شود.
 * @param {string} cartItemId - شناسه آیتم سبد خرید.
 * @param {number} quantity - تعداد جدید محصول.
 * @returns {Promise<Object>} یک Promise که با پاسخ از سرور حل می‌شود.
 */
export async function updateCartItemQuantity(cartItemId, quantity) { // Changed to named export
    try {
        const response = await axios.post(`${API_BASE_URL}/cart/update-quantity/${cartItemId}`, { quantity: quantity });
        console.log('Cart item quantity updated:', response.data);
        return response.data;
    } catch (error) {
        console.error('Error in updateCartItemQuantity API call:', error.response ? error.response.data : error.message);
        throw error;
    }
}

/**
 * آیتمی را از سبد خرید حذف می‌کند.
 * این درخواست به طور خودکار توکن JWT (اگر موجود باشد) و کوکی guest_uuid را شامل می‌شود.
 * @param {string} cartItemId - شناسه آیتم سبد خرید برای حذف.
 * @returns {Promise<Object>} یک Promise که با پاسخ از سرور حل می‌شود.
 */
export async function removeCartItem(cartItemId) { // Changed to named export
    try {
        const response = await axios.post(`${API_BASE_URL}/cart/remove-item/${cartItemId}`); // POST برای حذف
        console.log('Cart item removed:', response.data);
        return response.data;
    } catch (error) {
        console.error('Error in removeCartItem API call:', error.response ? error.response.data : error.message);
        throw error;
    }
}

/**
 * سبد خرید را به طور کامل پاک می‌کند.
 * این درخواست به طور خودکار توکن JWT (اگر موجود باشد) و کوکی guest_uuid را شامل می‌شود.
 * @returns {Promise<Object>} یک Promise که با پاسخ از سرور حل می‌شود.
 */
export async function clearCart() { // Changed to named export
    try {
        const response = await axios.post(`${API_BASE_URL}/cart/clear`);
        console.log('Cart cleared:', response.data);
        return response.data;
    } catch (error) {
        console.error('Error in clearCart API call:', error.response ? error.response.data : error.message);
        throw error;
    }
}


/**
 * کوپن تخفیف را به سبد خرید اعمال می‌کند. نیاز به احراز هویت دارد.
 * @param {string} couponCode - کد کوپن برای اعمال.
 * @returns {Promise<Object>} یک Promise که با داده‌های به‌روز شده سبد خرید حل می‌شود.
 */
export async function applyCouponToCart(couponCode) { // Changed to named export
    try {
        const response = await axios.post(`${API_BASE_URL}/cart/apply-coupon`, {
            coupon_code: couponCode
        });
        console.log('Coupon applied:', response.data);
        return response.data;
    } catch (error) {
        console.error('Error applying coupon:', error.response ? error.response.data : error.message);
        throw error;
    }
}

/**
 * کوپن تخفیف را از سبد خرید حذف می‌کند. نیاز به احراز هویت دارد.
 * @returns {Promise<Object>} یک Promise که با پاسخ از سرور حل می‌شود.
 */
export async function removeCouponFromCart() { // Changed to named export
    try {
        const response = await axios.post(`${API_BASE_URL}/cart/remove-coupon`);
        console.log('Coupon removed:', response.data);
        return response.data;
    } catch (error) {
        console.error('Error removing coupon:', error.response ? error.response.data : error.message);
        throw error;
    }
}

/**
 * کاربر را از سیستم خارج می‌کند (توکن JWT را باطل می‌کند).
 * @returns {Promise<Object>} یک Promise که با پیام خروج حل می‌شود.
 */
export async function logoutUser() { // Changed to named export
    try {
        const response = await axios.post(`${API_BASE_URL}/auth/logout`);
        clearJwtToken(); // توکن را از سمت کلاینت در صورت خروج موفق پاک می‌کند.
        console.log('Logout successful:', response.data);
        return response.data;
    } catch (error) {
        console.error('Error during logout:', error.response ? error.response.data : error.message);
        // حتی در صورت خطا در API logout، توکن را از کلاینت حذف کنید تا کاربر بتواند دوباره وارد شود.
        clearJwtToken();
        throw error;
    }
}

// توابع اصلی را برای استفاده در سایر بخش‌های برنامه فرانت‌اند شما export می‌کند.
// این بخش نیازی به تغییر ندارد زیرا توابع اکنون به صورت مستقیم export شده‌اند.
// export {
//     verifyOtpAndLogin,
//     sendOtp,
//     fetchCartContents,
//     addItem,
//     updateCartItemQuantity,
//     removeCartItem,
//     clearCart,
//     applyCouponToCart,
//     removeCouponFromCart,
//     logoutUser,
//     getJwtToken,
//     clearJwtToken, // clearJwtToken is now directly exported
// };

// export aliases به صورت جداگانه برای سازگاری بهتر با Vite و خوانایی.
export const addProductToCart = addItem;
export const applyCoupon = applyCouponToCart;
export const removeCoupon = removeCouponFromCart; // اضافه شد: export کردن removeCouponFromCart با نام removeCoupon
