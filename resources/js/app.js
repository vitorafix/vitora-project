// resources/js/app.js

import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Import global utilities and setup functions
import { setupExportButtons } from './export.js';
import * as jalaali from 'jalaali-js';
window.jalaali = jalaali;

// Import cart-related API functions from api.js
import {
    fetchCartContents,
    addToCart, // تغییر یافته از addProductToCart
    updateCartItemQuantity,
    removeCartItem,
    clearCart,
    applyCoupon, // تغییر یافته از applyCouponToCart
    removeCoupon, // تغییر یافته از removeCouponFromCart
    getJwtToken,
    logoutUser // NEW: ایمپورت کردن logoutUser از api.js
} from './api.js';

// --- Import other modules ---
// اطمینان حاصل کنید که نام فایل‌ها دقیقاً با فایل‌های موجود در پوشه resources/js مطابقت دارد.
import './cart.js';
import './search.js';
import './auth.js';
// NEW: ایمپورت کردن تابع initializeNavbarAndCart از navbar_new.js
import { initializeNavbarAndCart, updateNavbarUserStatus } from './navbar_new.js'; // مسیر صحیح و اضافه کردن updateNavbarUserStatus

// --- برای اهداف دیباگ: توابع را به صورت گلوبال در دسترس قرار دهید ---
// این کار به شما امکان می‌دهد این توابع را مستقیماً از کنسول فراخوانی کنید.
// در محیط پروداکشن، این خطوط را حذف کنید.
window.initializeNavbarAndCart = initializeNavbarAndCart;
window.updateNavbarUserStatus = updateNavbarUserStatus;
window.logoutUser = logoutUser; // NEW: تابع logoutUser را نیز گلوبال کنید
// --- پایان بخش دیباگ ---


// فعال کردن ایمپورت فایل‌های دیگر که در پوشه شما موجود هستند و ممکن است نیاز باشند:
import './admin.js';
import './charts.js';
import './checkout.js';
import './events.js';
import './hero.js';
import './renderer.js';


// --- Global Data and Functions ---
// این‌ها توابع و داده‌های سراسری هستند که در سراسر برنامه قابل دسترسی خواهند بود.
// Mock Data for admin activity log (can be moved to a shared data file if needed elsewhere)
window.adminActivityLog = [
    { timestamp: new Date(), username: 'سیستم', action: 'راه‌اندازی پنل', details: 'سیستم آماده کار است.' }
];

window.currentUser = { id: 1, username: 'admin', role: 'مدیر', lastLocation: '192.168.1.100' }; // Simulating logged-in admin


/**
 * Displays a temporary message box (toast notification) on the screen.
 * تابع سراسری برای نمایش پیام‌ها (مثل پیام‌های موفقیت، خطا یا اطلاعاتی).
 *
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
 *
 * @param {string} title - Title of the confirmation.
 * @param {string} message - Message of the confirmation.
 * @param {function} onConfirm - Callback function to execute on confirmation.
 * @param {function} [onCancel] - Optional callback function to execute on cancellation.
 */
window.showConfirmationModal = function(title, message, onConfirm, onCancel) {
    const modalOverlay = document.getElementById('confirm-modal-overlay');
    const modalTitle = modalOverlay.querySelector('h3');
    const modalMessage = modalOverlay.querySelector('p#confirm-message');
    const confirmBtn = modalOverlay.querySelector('#confirm-yes');
    const cancelBtn = modalOverlay.querySelector('#confirm-no');

    if (!modalOverlay) {
        console.error("Confirmation modal overlay not found. Ensure 'confirm-modal-overlay' element exists in your layout.");
        return;
    }

    // Reset event listeners to prevent multiple bindings
    confirmBtn.replaceWith(confirmBtn.cloneNode(true));
    cancelBtn.replaceWith(cancelBtn.cloneNode(true));
    const newConfirmBtn = modalOverlay.querySelector('#confirm-yes');
    const newCancelBtn = modalOverlay.querySelector('#confirm-no');


    modalTitle.textContent = title;
    modalMessage.textContent = message;
    modalOverlay.classList.remove('hidden');
    modalOverlay.classList.add('active'); // Activate for transition

    const handleConfirm = () => {
        onConfirm();
        modalOverlay.classList.add('hidden');
        modalOverlay.classList.remove('active');
        // No need to remove listeners here if using replaceWith
    };

    const handleCancel = () => {
        if (onCancel) onCancel();
        modalOverlay.classList.add('hidden');
        modalOverlay.classList.remove('active');
        // No need to remove listeners here if using replaceWith
    };

    newConfirmBtn.addEventListener('click', handleConfirm);
    newCancelBtn.addEventListener('click', handleCancel);
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
    // Assuming renderActivityLog is available in admin.js and will be called there
    // after an action to update the UI.
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


// --- Initial Setup and Event Listeners ---

// IMPORTANT: Initialize guest UUID immediately when the script is parsed,
// NOT inside DOMContentLoaded, to ensure it's available for other modules (like api.js)
window.guest_uuid = initializeGuestUUID();


document.addEventListener('DOMContentLoaded', () => {
    // Setup general export buttons (if any are outside admin panel)
    setupExportButtons();

    // Call the main admin panel setup function ONLY if on an admin page
    if (window.location.pathname.startsWith('/admin/')) {
        if (typeof setupAdminPanelListeners === 'function') {
            setupAdminPanelListeners();
        } else {
            console.error("setupAdminPanelListeners function not found. Ensure admin.js is loaded.");
        }
    }

    // --- Cart Page Specific Logic ---
    if (window.location.pathname === '/cart') {
        const cartItemsContainer = document.getElementById('cart-items-container');
        const cartEmptyMessage = document.getElementById('cart-empty-message');
        const cartSummary = document.getElementById('cart-summary');
        const cartSubtotalPrice = document.getElementById('cart-subtotal-price');
        const cartDiscountPrice = document.getElementById('cart-discount-price');
        const cartShippingPrice = document.getElementById('cart-shipping-price');
        const cartTaxPrice = document.getElementById('cart-tax-price');
        const cartTotalPrice = document.getElementById('cart-total-price');

        function renderCart(cartData) {
            cartItemsContainer.innerHTML = '';

            if (!cartData || !cartData.data || !cartData.data.items || cartData.data.items.length === 0) {
                cartEmptyMessage.classList.remove('hidden');
                cartSummary.classList.add('hidden');
                return;
            }

            cartEmptyMessage.classList.add('hidden');
            cartSummary.classList.remove('hidden');

            cartData.data.items.forEach(item => {
                const itemElement = document.createElement('div');
                itemElement.className = 'flex items-center justify-between border-b border-gray-100 py-4 last:border-b-0';
                // NEW: Add data-cart-item-id and data-unit-price to the itemElement
                itemElement.dataset.cartItemId = item.id;
                const itemPrice = Number(item.price || 0);
                itemElement.dataset.unitPrice = itemPrice;

                const productName = item.product ? item.product.name : 'محصول نامشخص';
                // اصلاح شده: اطمینان از وجود item.product و item.product.image_url
                const productImage = (item.product && item.product.image_url) ? item.product.image_url : 'https://placehold.co/80x80/E2E8F0/64748B?text=Product';

                itemElement.innerHTML = `
                    <div class="flex items-center space-x-4 rtl:space-x-reverse">
                        <img src="${productImage}" alt="${productName}" class="w-20 h-20 rounded-lg object-cover shadow-sm">
                        <div>
                            <h3 class="font-semibold text-gray-800 dark:text-gray-200">${productName}</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">${item.product_variant ? item.product_variant.name : 'بدون واریانت'}</p>
                            <p class="font-bold text-green-700 mt-1">${itemPrice.toLocaleString('fa-IR')} تومان</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4 rtl:space-x-reverse">
                        <div class="flex items-center border border-gray-300 rounded-lg">
                            <button class="quantity-btn p-2 text-gray-600 hover:bg-gray-100 rounded-l-lg" data-action="decrease" data-cart-item-id="${item.id}" data-quantity="${item.quantity}">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="text" value="${item.quantity}" class="w-12 text-center border-x border-gray-300 py-2 focus:outline-none bg-white dark:bg-gray-700 dark:text-white item-quantity" readonly>
                            <button class="quantity-btn p-2 text-gray-600 hover:bg-gray-100 rounded-r-lg" data-action="increase" data-cart-item-id="${item.id}" data-quantity="${item.quantity}">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <button class="remove-item-btn text-red-600 hover:text-red-800 p-2" data-cart-item-id="${item.id}">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                `;
                cartItemsContainer.appendChild(itemElement);
            });

            // Ensure these values are also numbers before calling toLocaleString
            cartSubtotalPrice.textContent = `${Number(cartData.data.subtotal || 0).toLocaleString('fa-IR')} تومان`;
            cartDiscountPrice.textContent = `${Number(cartData.data.discount_amount || 0).toLocaleString('fa-IR')} تومان`;
            cartShippingPrice.textContent = `${Number(cartData.data.shipping_cost || 0).toLocaleString('fa-IR')} تومان`;
            cartTaxPrice.textContent = `${Number(cartData.data.tax_amount || 0).toLocaleString('fa-IR')} تومان`;
            cartTotalPrice.textContent = `${Number(cartData.data.total || 0).toLocaleString('fa-IR')} تومان`;

            attachEventListeners();
        }

        async function loadCart() {
            try {
                const cart = await fetchCartContents();
                renderCart(cart);
            }
            catch (error) {
                console.error('Failed to load cart contents:', error);
                window.showMessage('خطا در بارگذاری سبد خرید.', 'error');
                renderCart(null); // Pass null to renderCart to show empty message
            }
        }

        function attachEventListeners() {
            document.querySelectorAll('.quantity-btn').forEach(button => {
                button.onclick = async (event) => {
                    const cartItemId = event.currentTarget.dataset.cartItemId;
                    // اصلاح شده: اطمینان از اینکه quantity یک عدد است
                    let currentQuantity = parseInt(event.currentTarget.dataset.quantity);

                    // بررسی NaN بودن یا نامعتبر بودن مقدار اولیه
                    if (isNaN(currentQuantity)) {
                        console.error('Initial quantity is NaN or invalid for item:', cartItemId);
                        window.showMessage('خطا: تعداد فعلی محصول نامعتبر است.', 'error');
                        return; // از ادامه اجرای تابع جلوگیری می‌کند
                    }

                    const action = event.currentTarget.dataset.action;

                    if (action === 'increase') {
                        currentQuantity++;
                    } else if (action === 'decrease') {
                        currentQuantity--;
                    }

                    // --- NEW: Immediate UI update for quantity input and buttons ---
                    const itemElement = event.currentTarget.closest('[data-cart-item-id]');
                    if (itemElement) {
                        const quantityInput = itemElement.querySelector('.item-quantity');
                        const increaseBtn = itemElement.querySelector('.quantity-btn[data-action="increase"]');
                        const decreaseBtn = itemElement.querySelector('.quantity-btn[data-action="decrease"]');

                        if (quantityInput) {
                            quantityInput.value = currentQuantity;
                        }
                        if (increaseBtn) {
                            increaseBtn.dataset.quantity = currentQuantity;
                        }
                        if (decreaseBtn) {
                            decreaseBtn.dataset.quantity = currentQuantity;
                        }
                    }
                    // --- END NEW ---

                    if (currentQuantity <= 0) {
                        window.showConfirmationModal(
                            'حذف محصول',
                            'آیا مطمئن هستید که می‌خواهید این محصول را از سبد خرید حذف کنید؟',
                            () => {
                                removeCartItemHandler(cartItemId);
                            }
                        );
                    } else {
                        try {
                            // اطمینان از اینکه currentQuantity یک عدد صحیح و مثبت است
                            if (Number.isInteger(currentQuantity) && currentQuantity > 0) {
                                await updateCartItemQuantity(cartItemId, currentQuantity);
                                window.showMessage('تعداد آیتم به‌روزرسانی شد.', 'success');
                                await loadCart();
                            } else {
                                console.error('Invalid quantity value after operation:', currentQuantity);
                                window.showMessage('خطا: تعداد وارد شده نامعتبر است.', 'error');
                            }
                        } catch (error) {
                            console.error('Error updating quantity:', error);
                            window.showMessage('خطا در به‌روزرسانی تعداد آیتم.', 'error');
                        }
                    }
                };
            });

            document.querySelectorAll('.remove-item-btn').forEach(button => {
                button.onclick = async (event) => {
                    const cartItemId = event.currentTarget.dataset.cartItemId;
                    window.showConfirmationModal(
                        'حذف محصول',
                        'آیا مطمئن هستید که می‌خواهید این محصول را از سبد خرید حذف کنید؟',
                        () => {
                            removeCartItemHandler(cartItemId);
                        }
                    );
                };
            });
        }

        async function removeCartItemHandler(cartItemId) {
            try {
                await removeCartItem(cartItemId);
                window.showMessage('آیتم از سبد خرید حذف شد.', 'success');
                await loadCart();
            } catch (error) {
                console.error('Error removing item:', error);
                window.showMessage('خطا در حذف آیتم از سبد خرید.', 'error');
            }
        }

        loadCart();
    }

    setupProductEditListeners();
    // NEW: فراخوانی تابع راه‌اندازی نوار ناوبری و سبد خرید کوچک
    initializeNavbarAndCart();
});
