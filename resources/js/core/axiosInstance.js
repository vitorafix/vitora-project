// axiosInstance.js
import axios from 'axios';

// ایجاد یک نمونه از axios با تنظیمات اولیه
const axiosInstance = axios.create({
    baseURL: '/', // آدرس پایه درخواست‌ها (در صورت نیاز تنظیم شود)
});

// گرفتن JWT از localStorage
const jwtToken = localStorage.getItem('jwt_token');

// افزودن هدر Authorization اگر JWT وجود دارد (این بخش فقط در زمان بارگذاری اولیه فایل اجرا می‌شود)
if (jwtToken) {
    axiosInstance.defaults.headers.common['Authorization'] = `Bearer ${jwtToken}`;
}

// اگر بخواهید برای همه درخواست‌ها بررسی کنید
axiosInstance.interceptors.request.use((config) => {
    const token = localStorage.getItem('jwt_token');
    // --- شروع بخش اشکال‌زدایی ---
    console.log('DEBUG: Axios Interceptor - Checking for JWT Token. Token found:', token ? 'Yes' : 'No');
    if (token) {
        console.log('DEBUG: Axios Interceptor - Setting Authorization header with token:', token.substring(0, 30) + '...'); // نمایش بخشی از توکن
    }
    // --- پایان بخش اشکال‌زدایی ---
    if (token) {
        config.headers['Authorization'] = `Bearer ${token}`;
    } else {
        // اطمینان حاصل کنید که اگر توکن وجود ندارد، هدر Authorization حذف شود.
        delete config.headers['Authorization'];
    }
    return config;
}, (error) => {
    return Promise.reject(error);
});

export default axiosInstance;
