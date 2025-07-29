// resources/js/core/axiosInstance.js

import axios from 'axios';

// ایجاد یک نمونه Axios با تنظیمات اولیه
const instance = axios.create({
    baseURL: '/', // آدرس پایه درخواست‌ها (در صورت نیاز تنظیم شود)
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    withCredentials: true, // برای ارسال کوکی‌ها و اطلاعات سشن (CSRF token)
});

// تنظیم CSRF Token برای Axios
// این توکن برای محافظت از درخواست‌های POST، PUT، DELETE در Laravel استفاده می‌شود.
let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    instance.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Interceptor برای افزودن توکن JWT به درخواست‌ها
// این بخش تضمین می‌کند که توکن JWT ذخیره شده، به صورت خودکار به هدر Authorization تمام درخواست‌های API اضافه شود.
instance.interceptors.request.use((config) => {
    // تغییر: اکنون به دنبال 'jwt_token' می‌گردیم
    const jwtToken = localStorage.getItem('jwt_token'); 
    // --- شروع بخش اشکال‌زدایی ---
    console.log('DEBUG: Axios Interceptor - Checking for JWT Token. Token found:', jwtToken ? 'Yes' : 'No');
    if (jwtToken) {
        console.log('DEBUG: Axios Interceptor - Setting Authorization header with token:', jwtToken.substring(0, 30) + '...'); // نمایش بخشی از توکن
    }
    // --- پایان بخش اشکال‌زدایی ---
    if (jwtToken) {
        config.headers['Authorization'] = `Bearer ${jwtToken}`;
    } else {
        // اطمینان حاصل کنید که اگر توکن وجود ندارد، هدر Authorization حذف شود.
        delete config.headers['Authorization'];
    }
    return config;
}, (error) => {
    return Promise.reject(error);
});

// Interceptor برای مدیریت خطاهای پاسخ API (به خصوص 401 Unauthorized)
instance.interceptors.response.use(
    (response) => response,
    (error) => {
        // اگر پاسخ 401 Unauthorized بود، توکن را پاک کرده و به صفحه ورود هدایت کنید.
        if (error.response && error.response.status === 401) {
            console.warn('Unauthorized API response (401). Clearing token and redirecting to login.');
            // تغییر: اکنون 'jwt_token' را پاک می‌کنیم
            localStorage.removeItem('jwt_token'); 
            // Optionally, redirect to login page. Ensure this doesn't cause infinite loops.
            // window.location.href = '/auth/mobile-login'; // می‌توانید این خط را فعال کنید
        }
        return Promise.reject(error);
    }
);

console.log('axiosInstance.js loaded and Axios instance configured.');

export default instance; // اکسپورت کردن نمونه Axios
