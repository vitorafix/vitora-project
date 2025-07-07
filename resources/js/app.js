// resources/js/app.js

import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Import global utilities and setup functions
import { setupExportButtons } from './export.js'; // Export functions are general purpose
import * as jalaali from 'jalaali-js';
window.jalaali = jalaali;

// Import admin panel specific logic
// This import ensures admin.js code is bundled, but its execution is conditional
import { setupAdminPanelListeners, logAdminAction, renderActivityLog } from './admin.js';

// --- Global Data and Functions ---
// These are general-purpose or shared across different parts of the application.
// Mock Data for admin activity log (can be moved to a shared data file if needed elsewhere)
export const adminActivityLog = [
    { timestamp: new Date(), username: 'سیستم', action: 'راه‌اندازی پنل', details: 'سیستم آماده کار است.' }
];

export let currentUser = { id: 1, username: 'admin', role: 'مدیر', lastLocation: '192.168.1.100' }; // Simulating logged-in admin


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
 * @param {function} onCancel - Callback function to execute on cancellation.
 */
window.showConfirmationModal = function(title, message, onConfirm, onCancel) {
    const modalOverlay = document.getElementById('confirm-modal-overlay');
    const modalTitle = modalOverlay.querySelector('h3');
    const modalMessage = modalOverlay.querySelector('p#confirm-message');
    const confirmBtn = modalOverlay.querySelector('#confirm-yes');
    const cancelBtn = modalOverlay.querySelector('#confirm-no');

    if (!modalOverlay) {
        console.error("Confirmation modal overlay not found.");
        return;
    }

    modalTitle.textContent = title;
    modalMessage.textContent = message;
    modalOverlay.classList.remove('hidden');
    modalOverlay.classList.add('active'); // Activate for transition

    const handleConfirm = () => {
        onConfirm();
        modalOverlay.classList.add('hidden');
        modalOverlay.classList.remove('active');
        confirmBtn.removeEventListener('click', handleConfirm);
        cancelBtn.removeEventListener('click', handleCancel);
    };

    const handleCancel = () => {
        if (onCancel) onCancel();
        modalOverlay.classList.add('hidden');
        modalOverlay.classList.remove('active');
        confirmBtn.removeEventListener('click', handleConfirm);
        cancelBtn.removeEventListener('click', handleCancel);
    };

    confirmBtn.addEventListener('click', handleConfirm);
    cancelBtn.addEventListener('click', handleCancel);
};


// --- Initial Setup and Event Listeners ---
document.addEventListener('DOMContentLoaded', () => {
    // Setup general export buttons (if any are outside admin panel)
    setupExportButtons();

    // Call the main admin panel setup function ONLY if on an admin page
    // This function now contains the path check internally as well, but this outer check
    // prevents unnecessary execution for public pages.
    if (window.location.pathname.startsWith('/admin/')) {
        setupAdminPanelListeners();
    }
});

// Note: The hero-carousel logic was previously in app.blade.php for direct JS,
// but it's generally better practice to move complex JS to dedicated files if needed.
// However, since it's already functional within app.blade.php for this setup,
// we don't need to import a separate hero-carousel.js unless its logic grows complex.
// خط زیر برای hero-carousel دیگر نیاز نیست زیرا منطق آن در app.blade.php مدیریت شده است.
// Also, cart and search imports are left here as they might be for the public-facing site.
// ایمپورت کردن منطق سبد خرید. این فایل تمامی تعاملات AJAX برای سبد خرید را مدیریت خواهد کرد.
import './cart';
// ایمپورت کردن منطق جستجوی لایو. این فایل قابلیت جستجو را مدیریت خواهد کرد.
import './search';
