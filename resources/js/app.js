// resources/js/app.js

import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Import Chart.js and export functions
import { initializeMonthlySalesChart, updateChartOnResize } from './charts.js';
import { setupExportButtons } from './export.js';

// Import jalaali-js for Jalali calendar operations
import * as jalaali from 'jalaali-js';
window.jalaali = jalaali; // Make jalaali-js globally accessible if needed in other scripts

// Mock Data (can be moved to a separate data.js if it grows larger)
export const users = [
    { id: 1, username: 'admin', role: 'مدیر', lastLocation: '192.168.1.100' },
    { id: 2, username: 'ali.ahmadi', role: 'کاربر', lastLocation: '172.20.10.2' },
    { id: 3, username: 'reza.karimi', role: 'کاربر', lastLocation: '192.168.1.101' },
    { id: 4, username: 'sara.naseri', role: 'کاربر', lastLocation: '10.0.0.5' }
];

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

// Global confirmation modal function - Added to app.js
window.showConfirmationModal = function(title, message, onConfirm, onCancel) {
    const modalOverlay = document.getElementById('confirmation-modal-overlay');
    const modalTitle = document.getElementById('confirmation-modal-title');
    const modalMessage = document.getElementById('confirmation-modal-message');
    const confirmBtn = document.getElementById('confirmation-modal-confirm-btn');
    const cancelBtn = document.getElementById('confirmation-modal-cancel-btn');
    const closeBtn = document.getElementById('confirmation-modal-close-btn');

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
        closeBtn.removeEventListener('click', handleCancel);
    };

    const handleCancel = () => {
        onCancel();
        modalOverlay.classList.add('hidden');
        modalOverlay.classList.remove('active');
        confirmBtn.removeEventListener('click', handleConfirm);
        cancelBtn.removeEventListener('click', handleCancel);
        closeBtn.removeEventListener('click', handleCancel);
    };

    confirmBtn.addEventListener('click', handleConfirm);
    cancelBtn.addEventListener('click', handleCancel);
    closeBtn.addEventListener('click', handleCancel);
};


// Function to log admin actions
export function logAdminAction(username, action, details) {
    const timestamp = new Date();
    adminActivityLog.push({ timestamp, username, action, details });
    renderActivityLog();
}

// Function to render activity log
export function renderActivityLog() {
    const logContainer = document.getElementById('admin-activity-log');
    if (!logContainer) return; // Ensure element exists before trying to manipulate

    logContainer.innerHTML = '';
    adminActivityLog.slice().reverse().forEach(log => { // Show most recent first
        const logEntry = document.createElement('div');
        logEntry.className = 'p-2 bg-gray-50 rounded-md text-sm text-gray-700';
        logEntry.innerHTML = `
            <p><span class="font-semibold">${log.username}</span>: ${log.action} - <span class="text-gray-500">${new Date(log.timestamp).toLocaleString('fa-IR')}</span></p>
            <span class="text-xs text-gray-400">${log.details}</span>
        `;
        logContainer.appendChild(logEntry);
    });
}

// Section switching logic
window.showSection = function(sectionId) {
    const sections = document.querySelectorAll('.section-content');
    sections.forEach(section => {
        section.classList.remove('active');
    });
    const targetSection = document.getElementById(`${sectionId}-content`);
    if (targetSection) {
        targetSection.classList.add('active');
    }

    const titleElement = document.getElementById('current-section-title');
    const navTextElement = document.querySelector(`[onclick="showSection('${sectionId}')"] .nav-text`);
    if (titleElement && navTextElement) {
        titleElement.textContent = navTextElement.textContent;
    }

    // Close dropdowns if any are open
    const notificationDropdown = document.getElementById('notification-dropdown');
    if (notificationDropdown) {
        notificationDropdown.classList.add('hidden');
    }

    const floatingReportToggle = document.getElementById('toggle-report-actions');
    const reportActionsContainer = document.getElementById('report-actions-container');
    if (floatingReportToggle && reportActionsContainer) {
        reportActionsContainer.classList.add('hidden');
        floatingReportToggle.setAttribute('aria-expanded', 'false');
    }

    // If navigating to dashboard, ensure chart is rendered/resized
    if (sectionId === 'dashboard') {
        initializeMonthlySalesChart(); // Will initialize if not already, or do nothing if already initialized
        updateChartOnResize(); // Ensure it's resized in case of sidebar toggle
    }
};

// Logout function
window.logoutUser = function() {
    window.showMessage('شما از سیستم خارج شدید.', 'info');
    // In a real application, you would redirect to a login page or clear session.
    console.log('User logged out.');
};

// Event Listeners for sidebar, notifications, and clock
document.addEventListener('DOMContentLoaded', () => {
    // Sidebar toggle
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const mainContentWrapper = document.getElementById('main-content-wrapper'); // Use the new wrapper
    const navTexts = document.querySelectorAll('.nav-text');

    if (sidebarToggle && sidebar && mainContentWrapper && navTexts.length > 0) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-expanded');
            sidebar.classList.toggle('sidebar-collapsed');
            mainContentWrapper.classList.toggle('main-content-shifted');
            mainContentWrapper.classList.toggle('main-content-full');

            navTexts.forEach(text => {
                text.classList.toggle('hidden');
            });

            // Adjust chart size on sidebar toggle
            setTimeout(() => {
                updateChartOnResize();
            }, 300); // Small delay to allow CSS transition
        });
    }

    // Notification dropdown
    const notificationButton = document.getElementById('notification-button');
    const notificationDropdown = document.getElementById('notification-dropdown');

    if (notificationButton && notificationDropdown) {
        notificationButton.addEventListener('click', (event) => {
            notificationDropdown.classList.toggle('hidden');
            event.stopPropagation(); // Prevent document click from immediately closing
        });

        document.addEventListener('click', (event) => {
            if (!notificationDropdown.contains(event.target) && !notificationButton.contains(event.target)) {
                notificationDropdown.classList.add('hidden');
            }
        });
    }

    // Report actions dropdown (if exists) - This is specific to the reports section, but its toggle is in the header
    const floatingReportToggle = document.getElementById('toggle-report-actions');
    const reportActionsContainer = document.getElementById('report-actions-container');

    if (floatingReportToggle && reportActionsContainer) {
        floatingReportToggle.addEventListener('click', (event) => {
            event.stopPropagation();
            const isExpanded = floatingReportToggle.getAttribute('aria-expanded') === 'true';
            floatingReportToggle.setAttribute('aria-expanded', String(!isExpanded));
            reportActionsContainer.classList.toggle('hidden');
            reportActionsContainer.classList.toggle('active');
        });

        document.addEventListener('click', (event) => {
            if (!reportActionsContainer.contains(event.target) && !floatingReportToggle.contains(event.target)) {
                reportActionsContainer.classList.add('hidden');
                reportActionsContainer.classList.remove('active');
                floatingReportToggle.setAttribute('aria-expanded', 'false');
            }
            // This condition might be redundant if the above handles it, but keeping for safety
            if (reportActionsContainer.classList.contains('active')) {
                reportActionsContainer.classList.add('hidden');
                reportActionsContainer.classList.remove('active');
                floatingReportToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Close report actions when an action is clicked
        reportActionsContainer.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                reportActionsContainer.classList.add('hidden');
                reportActionsContainer.classList.remove('active'); // Close menu after action
                floatingReportToggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    // Live clock update
    function updateClock() {
        const now = new Date();
        const options = {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        };
        const timeElement = document.getElementById('current-time');
        if (timeElement) {
            timeElement.textContent = now.toLocaleString('fa-IR', options).replace(',', ' -');
        }
    }
    setInterval(updateClock, 1000);
    updateClock(); // Initial call

    // Initialize the dashboard section and activity log on load
    window.onload = () => {
        window.showSection('dashboard'); // Display dashboard initially
        renderActivityLog(); // Initial render of activity log

        // Simulate admin login and check for suspicious activity on load
        const storedAdmin = users.find(u => u.username === currentUser.username);
        if (storedAdmin) {
            logAdminAction(currentUser.username, 'ورود به پنل', 'ورود موفق به سیستم');
        } else {
            window.showMessage('کاربر ادمین شبیه‌سازی شده یافت نشد. به عنوان کاربر پیش‌فرض عمل می‌کنیم.', 'info');
        }
    };
});
