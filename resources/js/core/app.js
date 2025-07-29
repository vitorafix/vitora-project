// resources/js/core/app.js
console.log('app.js loaded and starting...');

import '../vendor/bootstrap'; // Correct path for bootstrap.js in vendor folder
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// --- Import general and base files (always necessary) ---
// These files should be in the 'core' folder or their path should be set correctly.
import * as jalaali from 'jalaali-js';
window.jalaali = jalaali;

// REMOVED: Import the analytics module here. It's now imported by app.tsx which is the main entry.
// import './analytics.js';

// API functions - these are usually needed on all pages and are in the core folder
import {
    fetchCartContents,
    addToCart,
    updateCartItemQuantity,
    removeCartItem,
    clearCart,
    applyCoupon,
    removeCoupon,
    getJwtToken,
    logoutUser // این تابع برای استفاده در navbar_new.js و AppDebugger اکسپوز شده است
} from './api.js'; // api.js is in the same core folder

// `events.js`, `renderer.js`, `axiosInstance.js` files are in `core` and are always necessary:
import './events.js';
import './renderer.js';
import './axiosInstance.js'; // axiosInstance.js is in the same core folder

// --- Global Data and Functions ---
window.adminActivityLog = [
    { timestamp: new Date(), username: 'سیستم', action: 'راه‌اندازی پنل', details: 'سیستم آماده کار است.' }
];

// IMPORTANT: Initialize window.currentUser based on actual authentication status.
// For demonstration, it's null by default. It should be set by your authentication logic
// (e.g., after a successful login, or if user data is passed from Blade).
window.currentUser = null; // Default to null (no authenticated user)
// Example of how you might set it after a successful login:
// window.currentUser = { id: loggedInUserId, username: '...', role: '...' };


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
    // Check for modalOverlay existence
    if (!modalOverlay) {
        console.error("Confirmation modal overlay not found. Ensure 'confirm-modal-overlay' element exists in your layout.");
        return;
    }

    const modalTitle = modalOverlay.querySelector('h3');
    const modalMessage = modalOverlay.querySelector('p#confirm-message');
    let confirmBtn = modalOverlay.querySelector('#confirm-yes');
    let cancelBtn = modalOverlay.querySelector('#confirm-no');

    // Reset event listeners to prevent multiple bindings
    confirmBtn.replaceWith(confirmBtn.cloneNode(true));
    cancelBtn.replaceWith(cancelBtn.cloneNode(true));
    confirmBtn = modalOverlay.querySelector('#confirm-yes'); // New reference to cloned buttons
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
 * @param {string} name - Cookie name.
 * @param {string} value - Cookie value.
 * @param {number} days - Number of days the cookie is valid.
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

    // --- Dynamic Imports based on page path ---

    // Authentication module (auth) and JWT Manager
    if (path.includes('/login') || path.includes('/register') || path.includes('/profile') || path.includes('/dashboard') || path.includes('/mobile-login') || path.includes('/verify-otp-form')) {
        import('../auth/auth.js')
            .then(module => {
                if (module.initAuth) {
                    module.initAuth();
                }
                console.log('Auth module loaded and initialized.');
            })
            .catch(err => console.error('Failed to load auth module:', err));

        import('../auth/jwt_manager.js')
            .then(() => {
                console.log('JWT Manager module loaded.');
            })
            .catch(err => console.error('Failed to load JWT Manager module:', err));
    }

    // Cart module (cart) - REMOVED: React now handles cart functionality
    // If you still need the old cart.js for the main /cart page,
    // you should load it conditionally ONLY on that page, not globally.
    // if (path.includes('/cart')) { // Example: Only load on /cart page
    //     import('../cart/cart.js')
    //         .then(module => {
    //             if (module.initCart) {
    //                 module.initCart();
    //             } else {
    //                 console.warn('Cart module loaded but initCart function not found.');
    //             }
    //             console.log('Cart module loaded and initialized.');
    //         })
    //         .catch(err => console.error('Failed to load cart module:', err));
    // }


    // Search module (search)
    if (path.includes('/search') || path.includes('/products')) {
        import('../ui/search.js') // Corrected path to ui/search.js
            .then(module => {
                if (module.initSearch) {
                    module.initSearch();
                } else {
                    console.warn('Search module loaded but initSearch function not found.');
                }
                console.log('Search module loaded and initialized.');
            })
            .catch(err => console.error('Failed to load search module:', err));
    }

    // Navbar module (navbar_new)
    if (document.querySelector('#main-navbar') || document.querySelector('.main-nav')) {
        import('../ui/navbar_new.js')
            .then(module => {
                if (module.initializeNavbarAndCart) {
                    module.initializeNavbarAndCart();
                } else {
                    console.warn('Navbar module loaded but initializeNavbarAndCart function not found.');
                }
                console.log('Navbar module loaded and initialized.');
            })
            .catch(err => console.error('Failed to load navbar module:', err));
    }

    // Admin panel module (admin)
    if (path.startsWith('/admin/')) {
        import('../admin/admin.js')
            .then(module => {
                if (module.initAdminPanel) {
                    module.initAdminPanel();
                } else {
                    console.warn('Admin module loaded but initAdminPanel function not found.');
                }
                console.log('Admin module loaded and initialized.');
            })
            .catch(err => console.error('Failed to load admin module:', err));
    }

    // Charts module (charts)
    if (path.includes('/dashboard') || path.includes('/admin-reports') || path.includes('/analytics')) {
        import('../admin/charts.js')
            .then(module => {
                if (module.initCharts) {
                    module.initCharts();
                } else {
                    console.warn('Charts module loaded but initCharts function not found.');
                }
                console.log('Charts module loaded and initialized.');
            })
            .catch(err => console.error('Failed to load charts module:', err));
    }

    // Checkout module (checkout)
    if (path.includes('/checkout')) {
        import('../checkout/checkout.js')
            .then(module => {
                if (module.initCheckout) {
                    module.initCheckout();
                } else {
                    console.warn('Checkout module loaded but initCheckout function not found.');
                }
                console.log('Checkout module loaded and initialized.');
            })
            .catch(err => console.error('Failed to load checkout module:', err));
    }

    // Hero module (hero)
    if (path === '/' || path.includes('/home')) {
        import('../ui/hero.js')
            .then(module => {
                if (module.initHeroCarousel) {
                    module.initHeroCarousel();
                } else {
                    console.warn('Hero module loaded but initHeroCarousel function not found.');
                }
                console.log('Hero module loaded and initialized.');
            })
            .catch(err => console.error('Failed to load hero module:', err));
    }

    // Export module (export.js)
    if (document.getElementById('export-excel') || document.getElementById('export-pdf')) {
        import('../cart/export.js')
            .then(module => {
                if (module.setupExportButtons) {
                    module.setupExportButtons();
                } else {
                    console.warn('Export module loaded but setupExportButtons function not found.');
                }
                console.log('Export module loaded and initialized.');
            })
            .catch(err => console.error('Failed to load export module:', err));
    }

    // Calling setupProductEditListeners (if on product edit page)
    if (path.includes('/products/edit') || path.includes('/products/create')) {
        setupProductEditListeners();
        console.log('Product edit listeners setup.');
    }
});

// --- For debugging purposes: make functions globally accessible ---
window.logoutUser = logoutUser;
