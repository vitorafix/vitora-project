import axios from 'axios';

// Axios را به صورت سراسری در دسترس قرار دهید
window.axios = axios;

// تنظیم هدر پیش‌فرض برای درخواست‌های AJAX
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// --- تنظیمات Sanctum CSRF ---
// این بخش اطمینان می‌دهد که کوکی CSRF از لاراول دریافت شده و هدر X-CSRF-TOKEN برای Axios تنظیم می‌شود.
// این برای احراز هویت مبتنی بر سشن در APIهای SPA با Sanctum ضروری است.
document.addEventListener('DOMContentLoaded', () => {
    // دریافت توکن CSRF از متا تگ
    const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenElement ? csrfTokenElement.getAttribute('content') : null;

    if (csrfToken) {
        // تنظیم هدر X-CSRF-TOKEN برای تمامی درخواست‌های Axios
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
        console.log('Axios X-CSRF-TOKEN header set.');
    } else {
        console.error('CSRF token not found in meta tag. Please ensure <meta name="csrf-token" content="..."> is present in your HTML head.');
    }

    // ارسال درخواست به مسیر Sanctum CSRF-cookie برای اطمینان از تنظیم کوکی XSRF-TOKEN
    // این کوکی برای Sanctum جهت تأیید درخواست‌های stateful ضروری است.
    fetch('/sanctum/csrf-cookie')
        .then(response => {
            if (!response.ok) {
                console.error('Failed to fetch Sanctum CSRF cookie:', response.status, response.statusText);
            } else {
                console.log('Sanctum CSRF cookie fetched successfully.');
            }
        })
        .catch(error => {
            console.error('Error fetching Sanctum CSRF cookie:', error);
        });
});
