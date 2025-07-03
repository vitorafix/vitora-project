// resources/js/app.js

import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

/**
 * Displays a temporary message box (toast notification) on the screen.
 * تابع سراسری برای نمایش پیام‌ها (مثل پیام‌های موفقیت، خطا یا اطلاعاتی).
 *
 * @param {string} message - The message to display.
 * @param {string} [type='info'] - The type of message ('success', 'error', 'info'). Affects background color.
 * @param {number} [duration=3000] - The duration (in milliseconds) for which the message is displayed.
 */
window.showMessage = function(message, type = 'info', duration = 3000) {
    // Remove any existing message boxes to prevent stacking
    const existingMessageBox = document.querySelector('.message-box');
    if (existingMessageBox) {
        existingMessageBox.remove();
    }

    const messageBox = document.createElement('div');
    // Tailwind CSS classes for styling the message box
    messageBox.className = `fixed bottom-5 left-1/2 transform -translate-x-1/2 px-6 py-3 rounded-lg shadow-lg text-white z-50 transition-all duration-300 ease-out message-box`;

    // Set background color based on message type
    if (type === 'success') {
        messageBox.classList.add('bg-green-600');
    } else if (type === 'error') {
        messageBox.classList.add('bg-red-600');
    } else {
        messageBox.classList.add('bg-gray-800'); // Default for 'info'
    }

    messageBox.textContent = message; // Set the message text
    document.body.appendChild(messageBox); // Add to the body

    // Fade out and remove after duration
    setTimeout(() => {
        messageBox.classList.add('opacity-0', 'translate-y-full'); // Animate out
        messageBox.addEventListener('transitionend', () => messageBox.remove()); // Remove from DOM after transition
    }, duration);
};

// Import cart logic. This file will handle all AJAX interactions for the shopping cart.
// ایمپورت کردن منطق سبد خرید. این فایل تمامی تعاملات AJAX برای سبد خرید را مدیریت خواهد کرد.
import './cart';

// Import live search logic. This file will handle search functionality.
// ایمپورت کردن منطق جستجوی لایو. این فایل قابلیت جستجو را مدیریت خواهد کرد.
import './search';

// Import jalaali-js for Jalali calendar operations
// ایمپورت کردن کتابخانه jalaali-js برای عملیات تقویم جلالی
import * as jalaali from 'jalaali-js';
window.jalaali = jalaali; // Make jalaali-js globally accessible if needed in other scripts

// Note: The hero-carousel logic was previously in app.blade.php for direct JS,
// but it's generally better practice to move complex JS to dedicated files if needed.
// However, since it's already functional within app.blade.php for this setup,
// we don't need to import a separate hero-carousel.js unless its logic grows complex.
// خط زیر برای hero-carousel دیگر نیاز نیست زیرا منطق آن در app.blade.php مدیریت شده است.
// import './hero-carousel';
