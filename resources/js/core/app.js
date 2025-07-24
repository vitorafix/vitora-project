// resources/js/core/app.js  <-- این فایل باید به این مسیر منتقل شود

import '../vendor/bootstrap'; // مسیر صحیح برای bootstrap.js در فولدر vendor
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// --- Import فایل‌های عمومی و پایه (که همیشه لازم هستند) ---
// این فایل‌ها باید در فولدر 'core' باشند یا مسیرشان به درستی تنظیم شود.
// jalaali-js معمولا از node_modules است، پس import مستقیم آن در اینجا صحیح است
import * as jalaali from 'jalaali-js';
window.jalaali = jalaali;

// API functions - اینها معمولا در تمام صفحات لازم هستند و در فولدر core قرار دارند
import {
    fetchCartContents,
    addToCart,
    updateCartItemQuantity,
    removeCartItem,
    clearCart,
    applyCoupon,
    removeCoupon,
    getJwtToken,
    logoutUser
} from './api.js'; // api.js در همین فولدر core قرار دارد

// فایل‌های `events.js`, `renderer.js`, `axiosInstance.js` در `core` هستند و همیشه لازمند:
import './events.js';
import './renderer.js';
import './axiosInstance.js'; // axiosInstance.js در همین فولدر core قرار دارد

// --- توابع و داده‌های سراسری (Global Data and Functions) ---
// این بخش‌ها می‌توانند در همین فایل باقی بمانند یا به یک فایل `utils.js` منتقل شوند.
window.adminActivityLog = [
    { timestamp: new Date(), username: 'سیستم', action: 'راه‌اندازی پنل', details: 'سیستم آماده کار است.' }
];
window.currentUser = { id: 1, username: 'admin', role: 'مدیر', lastLocation: '192.168.1.100' };

/**
 * Displays a temporary message box (toast notification) on the screen.
 * تابع سراسری برای نمایش پیام‌ها (مثل پیام‌های موفقیت، خطا یا اطلاعاتی).
 * @param {string} message - The message to display.
 * @param {string} [type='info'] - The type of message ('success', 'error', 'info'). Affects background color.
 * @param {number} [duration=3000] - The duration (in milliseconds) for which the message is displayed.
 */
window.showMessage = function(message, type = 'info', duration = 3000) {
    const existingMessageBox = document.querySelector('.message-box');
    if (existingMessageBox) {
        existingMessageBox.remove();
    }

    const messageBox = document.createElement('div');
    messageBox.className = `fixed bottom-5 left-1/2 transform -translate-x-1/2 px-6 py-3 rounded-lg shadow-lg text-white z-50 transition-all duration-300 ease-out message-box`;

    if (type === 'success') {
        messageBox.classList.add('bg-green-600');
    } else if (type === 'error') {
        messageBox.classList.add('bg-red-600');
    } else {
        messageBox.classList.add('bg-gray-800');
    }

    messageBox.textContent = message;
    document.body.appendChild(messageBox);

    setTimeout(() => {
        messageBox.classList.add('opacity-0', 'translate-y-full');
        messageBox.addEventListener('transitionend', () => messageBox.remove());
    }, duration);
};

/**
 * Displays a custom confirmation modal.
 * تابع سراسری برای نمایش مدال تأیید سفارشی.
 * @param {string} title - Title of the confirmation.
 * @param {string} message - Message of the confirmation.
 * @param {function} onConfirm - Callback function to execute on confirmation.
 * @param {function} [onCancel] - Optional callback function to execute on cancellation.
 */
window.showConfirmationModal = function(title, message, onConfirm, onCancel) {
    const modalOverlay = document.getElementById('confirm-modal-overlay');
    // بررسی وجود modalOverlay
    if (!modalOverlay) {
        console.error("Confirmation modal overlay not found. Ensure 'confirm-modal-overlay' element exists in your layout.");
        return;
    }

    const modalTitle = modalOverlay.querySelector('h3');
    const modalMessage = modalOverlay.querySelector('p#confirm-message');
    let confirmBtn = modalOverlay.querySelector('#confirm-yes');
    let cancelBtn = modalOverlay.querySelector('#confirm-no');

    // Reset event listeners to prevent multiple bindings
    // با استفاده از cloneNode(true) می‌توانیم یک کپی عمیق از دکمه بگیریم
    // و سپس دکمه اصلی را با کپی جایگزین کنیم تا تمام Event Listenerهای قبلی حذف شوند.
    confirmBtn.replaceWith(confirmBtn.cloneNode(true));
    cancelBtn.replaceWith(cancelBtn.cloneNode(true));
    confirmBtn = modalOverlay.querySelector('#confirm-yes'); // ارجاع جدید به دکمه‌های کلون شده
    cancelBtn = modalOverlay.querySelector('#confirm-no');


    modalTitle.textContent = title;
    modalMessage.textContent = message;
    modalOverlay.classList.remove('hidden');
    modalOverlay.classList.add('active'); // Activate for transition

    const handleConfirm = () => {
        onConfirm();
        modalOverlay.classList.add('hidden');
        modalOverlay.classList.remove('active');
    };

    const handleCancel = () => {
        if (onCancel) onCancel();
        modalOverlay.classList.add('hidden');
        modalOverlay.classList.remove('active');
    };

    confirmBtn.addEventListener('click', handleConfirm);
    cancelBtn.addEventListener('click', handleCancel);
};

/**
 * Logs an admin action to the activity log.
 * این تابع به صورت سراسری در دسترس خواهد بود.
 * @param {string} username - The username of the admin performing the action.
 * @param {string} action - The action performed (e.g., 'ویرایش کاربر').
 * @param {string} details - Additional details about the action.
 */
window.logAdminAction = function(username, action, details) {
    window.adminActivityLog.push({
        timestamp: new Date(),
        username: username,
        action: action,
        details: details
    });
};

/**
 * Generates a UUID (Universally Unique Identifier) v4.
 * تولید یک UUID نسخه 4.
 * @returns {string} A UUID string.
 */
function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0,
            v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

/**
 * Sets a cookie with the given name, value, and expiry.
 * یک کوکی با نام، مقدار و تاریخ انقضای مشخص تنظیم می‌کند.
 * @param {string} name - نام کوکی.
 * @param {string} value - مقدار کوکی.
 * @param {number} days - تعداد روزهایی که کوکی معتبر است.
 */
function setCookie(name, value, days) {
    let expires = "";
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "")  + expires + "; path=/";
}

/**
 * Initializes or retrieves the guest_uuid from localStorage and sets it as a cookie.
 * مقداردهی اولیه یا بازیابی guest_uuid از localStorage و تنظیم آن به عنوان کوکی.
 * @returns {string} The guest UUID.
 */
function initializeGuestUUID() {
    let guestUUID = localStorage.getItem('guest_uuid');
    if (!guestUUID) {
        guestUUID = generateUUID();
        localStorage.setItem('guest_uuid', guestUUID);
        console.log('New guest_uuid generated and stored:', guestUUID);
    } else {
        console.log('Existing guest_uuid retrieved:', guestUUID);
    }
    // Always set/refresh the cookie to ensure it's sent with every request
    setCookie('guest_uuid', guestUUID, 365); // Set for 1 year
    return guestUUID;
}

/**
 * Sets up event listeners specific to the product edit page.
 * این تابع برای مدیریت حذف تصاویر گالری در صفحه ویرایش محصول استفاده می‌شود.
 * این تابع به صورت داینامیک لود خواهد شد اگر در صفحه ویرایش محصول باشیم.
 */
function setupProductEditListeners() {
    const currentGalleryImagesContainer = document.getElementById('current-gallery-images');

    if (currentGalleryImagesContainer) {
        console.log('Product edit page detected. Setting up gallery image listeners.');
        currentGalleryImagesContainer.addEventListener('click', function(event) {
            if (event.target.closest('.remove-gallery-image-btn')) {
                const button = event.target.closest('.remove-gallery-image-btn');
                const imageId = button.dataset.imageId;
                const imageContainer = button.closest('.relative.group'); // The parent div for the image

                window.showConfirmationModal(
                    'حذف تصویر',
                    'آیا مطمئن هستید که می‌خواهید این تصویر را حذف کنید؟',
                    () => {
                        // Logic to execute if user confirms
                        const hiddenInput = imageContainer.querySelector('.remove-image-input');
                        if (hiddenInput) {
                            hiddenInput.value = imageId; // Set the ID to be removed
                            // Hide the image visually
                            imageContainer.style.display = 'none';
                            window.showMessage('تصویر برای حذف علامت‌گذاری شد.', 'success');
                        }
                    },
                    () => {
                        // Optional: Logic to execute if user cancels
                        window.showMessage('عملیات حذف تصویر لغو شد.', 'info');
                    }
                );
            }
        });
    }
}

// IMPORTANT: Initialize guest UUID immediately when the script is parsed,
// NOT inside DOMContentLoaded, to ensure it's available for other modules (like api.js)
window.guest_uuid = initializeGuestUUID();


document.addEventListener('DOMContentLoaded', () => {
    const path = window.location.pathname;

    // --- Dynamic Imports بر اساس مسیر صفحه ---

    // ماژول احراز هویت (auth) و JWT Manager
    // این ماژول‌ها در صفحات ورود، ثبت‌نام، پروفایل و داشبورد نیاز هستند.
    if (path.includes('/login') || path.includes('/register') || path.includes('/profile') || path.includes('/dashboard')) {
        import('../auth/auth.js')
            .then(module => {
                if (module.initAuth) { // تابع initAuth در auth.js
                    module.initAuth();
                }
                console.log('Auth module loaded and initialized.');
            })
            .catch(err => console.error('Failed to load auth module:', err));

        // jwt_manager.js نیازی به init خاصی ندارد، فقط import شود تا توابعش در دسترس باشند.
        import('../auth/jwt_manager.js')
            .then(() => {
                console.log('JWT Manager module loaded.');
            })
            .catch(err => console.error('Failed to load JWT Manager module:', err));
    }

    // ماژول سبد خرید (cart)
    if (path.includes('/cart')) {
        import('../cart/cart.js')
            .then(module => {
                if (module.initCart) { // تابع initCart در cart.js
                    module.initCart();
                } else {
                    console.warn('Cart module loaded but initCart function not found.');
                }
                console.log('Cart module loaded and initialized.');
            })
            .catch(err => console.error('Failed to load cart module:', err));
    }

    // ماژول جستجو (search)
    if (path.includes('/search') || path.includes('/products')) {
        import('../search/search.js')
            .then(module => {
                if (module.initSearch) { // تابع initSearch در search.js
                    module.initSearch();
                } else {
                    console.warn('Search module loaded but initSearch function not found.');
                }
                console.log('Search module loaded and initialized.');
            })
            .catch(err => console.error('Failed to load search module:', err));
    }

    // ماژول نوار ناوبری (navbar_new)
    // این ماژول در اکثر صفحات نیاز است، بنابراین شرط آن می‌تواند بر اساس وجود عناصر DOM باشد.
    if (document.querySelector('#main-navbar') || document.querySelector('.main-nav')) {
        import('../ui/navbar_new.js')
            .then(module => {
                if (module.initializeNavbarAndCart) { // تابع initializeNavbarAndCart در navbar_new.js
                    module.initializeNavbarAndCart();
                } else {
                    console.warn('Navbar module loaded but initializeNavbarAndCart function not found.');
                }
                console.log('Navbar module loaded and initialized.');
            })
            .catch(err => console.error('Failed to load navbar module:', err));
    }

    // ماژول پنل مدیریت (admin)
    if (path.startsWith('/admin/')) {
        import('../admin/admin.js')
            .then(module => {
                if (module.initAdminPanel) { // تابع initAdminPanel در admin.js
                    module.initAdminPanel();
                } else {
                    console.warn('Admin module loaded but initAdminPanel function not found.');
                }
                console.log('Admin module loaded and initialized.');
            })
            .catch(err => console.error('Failed to load admin module:', err));
    }

    // ماژول نمودارها (charts)
    // این ماژول معمولاً در صفحات داشبورد یا گزارشات ادمین استفاده می‌شود.
    if (path.includes('/dashboard') || path.includes('/admin-reports') || path.includes('/analytics')) {
        import('../admin/charts.js')
            .then(module => {
                if (module.initCharts) { // تابع initCharts در charts.js
                    module.initCharts();
                } else {
                    console.warn('Charts module loaded but initCharts function not found.');
                }
                console.log('Charts module loaded and initialized.');
            })
            .catch(err => console.error('Failed to load charts module:', err));
    }

    // ماژول پرداخت (checkout)
    if (path.includes('/checkout')) {
        import('../checkout/checkout.js')
            .then(module => {
                if (module.initCheckout) { // تابع initCheckout در checkout.js
                    module.initCheckout();
                } else {
                    console.warn('Checkout module loaded but initCheckout function not found.');
                }
                console.log('Checkout module loaded and initialized.');
            })
            .catch(err => console.error('Failed to load checkout module:', err));
    }

    // ماژول هیرو (hero)
    // این ماژول معمولاً فقط در صفحه اصلی یا صفحات خاصی که کاروسل هیرو دارند استفاده می‌شود.
    if (path === '/' || path.includes('/home')) {
        import('../ui/hero.js')
            .then(module => {
                if (module.initHeroCarousel) { // تابع initHeroCarousel در hero.js
                    module.initHeroCarousel();
                } else {
                    console.warn('Hero module loaded but initHeroCarousel function not found.');
                }
                console.log('Hero module loaded and initialized.');
            })
            .catch(err => console.error('Failed to load hero module:', err));
    }

    // ماژول Export (export.js)
    // این ماژول معمولاً در صفحات گزارشات یا مدیریت استفاده می‌شود.
    if (document.getElementById('export-excel') || document.getElementById('export-pdf')) { // اگر دکمه‌های export وجود دارند
        import('../cart/export.js') // مسیر صحیح به فایل export.js
            .then(module => {
                if (module.setupExportButtons) { // تابع setupExportButtons در export.js
                    module.setupExportButtons();
                } else {
                    console.warn('Export module loaded but setupExportButtons function not found.');
                }
                console.log('Export module loaded and initialized.');
            })
            .catch(err => console.error('Failed to load export module:', err));
    }

    // فراخوانی setupProductEditListeners (اگر در صفحه ویرایش محصول هستیم)
    // این تابع در همین فایل app.js تعریف شده است.
    if (path.includes('/products/edit') || path.includes('/products/create')) {
        setupProductEditListeners();
        console.log('Product edit listeners setup.');
    }
});

// --- برای اهداف دیباگ: توابع را به صورت گلوبال در دسترس قرار دهید ---
// این خطوط را در محیط پروداکشن حذف کنید.
// توجه: این توابع از ماژول‌های داینامیک لود شده می‌آیند و ممکن است در زمان بارگذاری اولیه undefined باشند.
// بهتر است برای دیباگ، مستقیماً به module.functionName در کنسول دسترسی پیدا کنید
// یا اینها را فقط برای توابع واقعاً گلوبال که در همین فایل تعریف شده‌اند، نگه دارید.
// window.initializeNavbarAndCart = initializeNavbarAndCart; // این تابع از navbar_new.js می‌آید
// window.updateNavbarUserStatus = updateNavbarUserStatus; // این تابع از navbar_new.js می‌آید
window.logoutUser = logoutUser; // این تابع از api.js می‌آید و در اینجا import شده است.
// --- پایان بخش دیباگ ---
