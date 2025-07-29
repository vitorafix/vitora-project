// resources/js/vendor/bootstrap.js

// تغییر: مسیر ایمپورت کردن axiosInstance به فولدر core اصلاح شد.
import axiosInstance from '../core/axiosInstance.js';

// Axios را به صورت سراسری در دسترس قرار دهید و از axiosInstance استفاده کنید
window.axios = axiosInstance; // تغییر کلیدی: اختصاص axiosInstance به window.axios

// تنظیم هدر پیش‌فرض برای درخواست‌های AJAX
// این خط ممکن است اضافی باشد اگر در axiosInstance.js تنظیم شده باشد، اما برای اطمینان نگه داشته می‌شود.
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// --- تنظیمات Axios برای JWT ---
// این بخش اکنون توسط axiosInstance.js و api.js مدیریت می‌شود و دیگر نیازی به آن در اینجا نیست.
// axiosInstance.js خودش JWT را از localStorage می‌خواند و تنظیم می‌کند.
// بنابراین، بخش زیر را حذف می‌کنیم یا به عنوان کامنت نگه می‌داریم.
/*
const initialToken = localStorage.getItem('jwt_token');
if (initialToken) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${initialToken}`;
    console.log('Axios initialized with existing JWT token from localStorage.');
} else {
    console.log('No existing JWT token found in localStorage for Axios initialization.');
}
*/

// NEW: اضافه کردن guest_uuid به عنوان هدر پیش‌فرض برای تمام درخواست‌های Axios
// این بخش نیز توسط api.js (که از axiosInstance استفاده می‌کند) مدیریت می‌شود.
// بنابراین، بخش زیر را نیز حذف می‌کنیم یا به عنوان کامنت نگه می‌داریم.
/*
if (typeof window.guest_uuid !== 'undefined' && window.guest_uuid !== null) {
    window.axios.defaults.headers.common['X-Guest-UUID'] = window.guest_uuid;
    console.log('Axios initialized with X-Guest-UUID header:', window.guest_uuid);
} else {
    console.warn('window.guest_uuid is not defined when initializing Axios. Guest cart functionality might be affected.');
    // Fallback: Try to get it again if it's not set yet (though it should be by app.js)
    if (localStorage.getItem('guest_uuid')) {
        window.axios.defaults.headers.common['X-Guest-UUID'] = localStorage.getItem('guest_uuid');
        console.log('Axios initialized with X-Guest-UUID from localStorage fallback:', localStorage.getItem('guest_uuid'));
    }
}
*/

// نکته: بخش مربوط به CSRF کوکی Sanctum از اینجا حذف شد،
// زیرا با استفاده از JWT برای احراز هویت API، نیازی به آن نیست.
// اگر بخش‌های سنتی وب‌سایت شما هنوز به CSRF نیاز دارند،
// باید مطمئن شوید که لاراول به طور خودکار توکن را در فرم‌ها یا تگ‌های meta قرار می‌دهد.
