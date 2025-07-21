import axios from 'axios';

// Axios را به صورت سراسری در دسترس قرار دهید
window.axios = axios;

// تنظیم هدر پیش‌فرض برای درخواست‌های AJAX
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// --- تنظیمات Axios برای JWT ---
// این بخش اطمینان می‌دهد که Axios برای ارسال توکن JWT در درخواست‌های API آماده است.
// توکن JWT باید توسط تابع storeJwtToken در api.js ذخیره و تنظیم شود.
// اگر توکن JWT از قبل در localStorage موجود باشد، آن را به هدر Authorization اضافه می‌کند.
const initialToken = localStorage.getItem('jwt_token');
if (initialToken) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${initialToken}`;
    console.log('Axios initialized with existing JWT token from localStorage.');
} else {
    console.log('No existing JWT token found in localStorage for Axios initialization.');
}

// نکته: بخش مربوط به CSRF کوکی Sanctum از اینجا حذف شد،
// زیرا با استفاده از JWT برای احراز هویت API، نیازی به آن نیست.
// اگر بخش‌های سنتی وب‌سایت شما هنوز به CSRF نیاز دارند،
// باید مطمئن شوید که لاراول به طور خودکار توکن را در فرم‌ها یا تگ‌های meta قرار می‌دهد.
