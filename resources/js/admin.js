// resources/js/admin.js

// Import necessary functions from app.js (if they are global utilities)
// In a real scenario, you might pass showMessage and showConfirmationModal as parameters
// or ensure they are globally accessible via window object as they are now.
// For modularity, it's better to explicitly import or pass them.
import { showMessage, showConfirmationModal, logAdminAction, adminActivityLog, currentUser } from './app.js';
import { initializeMonthlySalesChart, updateChartOnResize } from './charts.js';
import * as jalaali from 'jalaali-js'; // Import jalaali-js for Jalali calendar operations

// --- Mock Data (Simulating Backend Data) ---
// This data is now specific to the admin panel logic.
export let users = [
    { id: 1, username: 'admin', email: 'admin@example.com', role: 'admin', lastLocation: '192.168.1.100', created_at: '2023-01-15T10:00:00Z', status: 'active', phone: '09121234567' },
    { id: 2, username: 'ali.ahmadi', email: 'ali.a@example.com', role: 'user', lastLocation: '172.20.10.2', created_at: '2023-02-20T11:30:00Z', status: 'active', phone: '09122345678' },
    { id: 3, username: 'reza.karimi', email: 'reza.k@example.com', role: 'user', lastLocation: '192.168.1.101', created_at: '2023-03-01T09:00:00Z', status: 'inactive', phone: '09123456789' },
    { id: 4, username: 'sara.naseri', email: 'sara.n@example.com', role: 'editor', lastLocation: '10.0.0.5', created_at: '2023-04-10T14:00:00Z', status: 'active', phone: '09124567890' },
    { id: 5, username: 'mohsen.alavi', email: 'mohsen.a@example.com', role: 'moderator', lastLocation: '192.168.1.102', created_at: '2023-05-05T08:00:00Z', status: 'active', phone: '09125678901' },
    { id: 6, username: 'fatemeh.hasani', email: 'fatemeh.h@example.com', role: 'user', lastLocation: '172.20.10.3', created_at: '2023-06-01T16:00:00Z', status: 'suspended', phone: '09126789012' },
    { id: 7, username: 'javad.zare', email: 'javad.z@example.com', role: 'user', lastLocation: '192.168.1.103', created_at: '2023-07-12T09:45:00Z', status: 'active', phone: '09127890123' },
    { id: 8, username: 'narges.amini', email: 'narges.a@example.com', role: 'editor', lastLocation: '10.0.0.6', created_at: '2023-08-25T13:15:00Z', status: 'active', phone: '09128901234' },
    { id: 9, username: 'amir.rostami', email: 'amir.r@example.com', role: 'user', lastLocation: '192.168.1.104', created_at: '2023-09-03T10:00:00Z', status: 'inactive', phone: '09129012345' },
    { id: 10, username: 'zahra.shahidi', email: 'zahra.sh@example.com', role: 'user', lastLocation: '172.20.10.4', created_at: '2023-10-18T17:00:00Z', status: 'active', phone: '09120123456' },
];

// --- Admin Panel UI Logic ---

/**
 * Renders the admin activity log in the dashboard.
 * تابع برای نمایش لاگ فعالیت‌های ادمین.
 */
export function renderActivityLog() {
    const logContainer = document.getElementById('admin-activity-log');
    if (!logContainer) return;

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

/**
 * Switches between different content sections in the admin panel.
 * تابع برای جابجایی بین بخش‌های مختلف پنل ادمین.
 *
 * @param {string} sectionId - The ID of the section to show (e.g., 'dashboard', 'user-management').
 */
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

    // Specific actions for sections
    if (sectionId === 'dashboard') {
        initializeMonthlySalesChart();
        updateChartOnResize();
    } else if (sectionId === 'user-management') {
        fetchUsers(); // Fetch and render user list when navigating to user management
    }
};

/**
 * Simulates user logout.
 * تابع برای شبیه‌سازی خروج کاربر.
 */
window.logoutUser = function() {
    showMessage('شما از سیستم خارج شدید.', 'info');
    console.log('User logged out.');
    // In a real application, you would redirect to a login page or clear session.
};

// --- User Management Logic ---

// State variables for user list
let currentPage = 1;
let itemsPerPage = 5; // You can adjust this
let currentSortColumn = 'id';
let currentSortDirection = 'asc'; // 'asc' or 'desc'
let currentSearchQuery = '';
let selectedUserIds = new Set(); // To store IDs of selected users for bulk actions

/**
 * Simulates fetching users from a backend API with pagination, sorting, and search.
 * در یک برنامه واقعی، این تابع یک درخواست AJAX به بک‌اند شما ارسال می‌کند.
 *
 * @param {object} params - Parameters for fetching users.
 * @param {number} params.page - Current page number.
 * @param {number} params.per_page - Items per page.
 * @param {string} params.sort_by - Column to sort by.
 * @param {string} params.sort_direction - Sorting direction ('asc' or 'desc').
 * @param {string} params.search - Search query.
 */
async function fetchUsers(page = currentPage, per_page = itemsPerPage, sort_by = currentSortColumn, sort_direction = currentSortDirection, search = currentSearchQuery) {
    const loadingState = document.getElementById('loading-state');
    const userListBody = document.getElementById('user-list-body');
    const noUsersMessage = document.getElementById('no-users-message');

    if (loadingState) loadingState.classList.remove('hidden');
    if (userListBody) userListBody.innerHTML = ''; // Clear current list
    if (noUsersMessage) noUsersMessage.classList.add('hidden');

    try {
        // Simulate API call delay
        await new Promise(resolve => setTimeout(resolve, 500));

        // Filter users based on search query
        const filteredUsers = users.filter(user =>
            user.username.toLowerCase().includes(search.toLowerCase()) ||
            user.email.toLowerCase().includes(search.toLowerCase()) ||
            user.role.toLowerCase().includes(search.toLowerCase())
        );

        // Sort users
        const sortedUsers = [...filteredUsers].sort((a, b) => {
            let valA = a[sort_by];
            let valB = b[sort_by];

            // Handle numeric sorting for ID
            if (sort_by === 'id') {
                valA = parseInt(valA);
                valB = parseInt(valB);
            }
            // Handle date sorting for created_at
            if (sort_by === 'created_at') {
                valA = new Date(valA).getTime();
                valB = new Date(valB).getTime();
            }

            if (valA < valB) return sort_direction === 'asc' ? -1 : 1;
            if (valA > valB) return sort_direction === 'asc' ? 1 : -1;
            return 0;
        });

        // Paginate users
        const totalUsers = sortedUsers.length;
        const totalPages = Math.ceil(totalUsers / per_page);
        const startIndex = (page - 1) * per_page;
        const endIndex = startIndex + per_page;
        const paginatedUsers = sortedUsers.slice(startIndex, endIndex);

        // Update global state
        currentPage = page;
        currentSortColumn = sort_by;
        currentSortDirection = sort_direction;
        currentSearchQuery = search;

        renderUserList(paginatedUsers);
        renderPagination(totalPages, page);
        updateSelectedUsersCount(); // Ensure bulk actions visibility is correct

        if (paginatedUsers.length === 0 && noUsersMessage) {
            noUsersMessage.classList.remove('hidden');
        }

    } catch (error) {
        console.error('Error fetching users:', error);
        showMessage('خطا در بارگذاری لیست کاربران.', 'error');
        if (noUsersMessage) noUsersMessage.classList.remove('hidden');
        if (userListBody) userListBody.innerHTML = `<tr><td colspan="9" class="py-3 px-6 text-center text-red-500">خطا در بارگذاری اطلاعات.</td></tr>`;
    } finally {
        if (loadingState) loadingState.classList.add('hidden');
    }
}

/**
 * Renders the user list table body.
 * تابع برای نمایش لیست کاربران در جدول.
 *
 * @param {Array<Object>} usersToRender - Array of user objects to display.
 */
function renderUserList(usersToRender) {
    const userListBody = document.getElementById('user-list-body');
    if (!userListBody) return;

    userListBody.innerHTML = '';
    selectedUserIds.clear(); // Clear selections on re-render
    const selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) selectAllCheckbox.checked = false;
    toggleBulkActionsVisibility();

    if (usersToRender.length === 0) {
        document.getElementById('no-users-message')?.classList.remove('hidden');
        return;
    } else {
        document.getElementById('no-users-message')?.classList.add('hidden');
    }

    usersToRender.forEach(user => {
        const row = document.createElement('tr');
        row.className = 'border-b border-gray-200 hover:bg-gray-50';

        // Format created_at to Jalali date
        const createdAtDate = new Date(user.created_at);
        const jalaliDate = jalaali.toJalaali(createdAtDate.getFullYear(), createdAtDate.getMonth() + 1, createdAtDate.getDate());
        const formattedDate = `${jalaliDate.jy}/${String(jalaliDate.jm).padStart(2, '0')}/${String(jalaliDate.jd).padStart(2, '0')}`;

        const statusClass = {
            'active': 'bg-green-100 text-green-800',
            'inactive': 'bg-red-100 text-red-800',
            'suspended': 'bg-yellow-100 text-yellow-800'
        }[user.status] || 'bg-gray-100 text-gray-800'; // Default for unknown status

        const roleClass = {
            'admin': 'bg-red-100 text-red-800',
            'user': 'bg-green-100 text-green-800',
            'editor': 'bg-blue-100 text-blue-800',
            'moderator': 'bg-purple-100 text-purple-800'
        }[user.role] || 'bg-gray-100 text-gray-800'; // Default for unknown role

        row.innerHTML = `
            <td class="py-3 px-6 text-right">
                <input type="checkbox" class="user-checkbox rounded text-green-600 focus:ring-green-500" data-user-id="${user.id}">
            </td>
            <td class="py-3 px-6 text-right whitespace-nowrap">${user.id}</td>
            <td class="py-3 px-6 text-right whitespace-nowrap">${user.username}</td>
            <td class="py-3 px-6 text-right whitespace-nowrap">${user.email}</td>
            <td class="py-3 px-6 text-right whitespace-nowrap">
                <span class="px-2 py-1 font-semibold leading-tight rounded-full ${roleClass}">
                    ${user.role === 'admin' ? 'مدیر' : user.role === 'user' ? 'کاربر' : user.role === 'editor' ? 'ویرایشگر' : user.role === 'moderator' ? 'مدیر محتوا' : user.role}
                </span>
            </td>
            <td class="py-3 px-6 text-right whitespace-nowrap">${user.lastLocation}</td>
            <td class="py-3 px-6 text-right whitespace-nowrap">${formattedDate}</td>
            <td class="py-3 px-6 text-right whitespace-nowrap">
                <span class="px-2 py-1 font-semibold leading-tight rounded-full ${statusClass}">
                    ${user.status === 'active' ? 'فعال' : user.status === 'inactive' ? 'غیرفعال' : user.status === 'suspended' ? 'معلق' : user.status}
                </span>
            </td>
            <td class="py-3 px-6 text-center whitespace-nowrap">
                <div class="flex item-center justify-center space-x-2 space-x-reverse">
                    <button onclick="window.showUserModal(${user.id})" class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center hover:bg-blue-600 transition duration-200" title="ویرایش">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="window.deleteUser(${user.id})" class="w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600 transition duration-200" title="حذف">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </td>
        `;
        userListBody.appendChild(row);
    });

    // Attach event listeners to new checkboxes
    userListBody.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', handleUserCheckboxClick);
    });
}

/**
 * Renders pagination controls.
 * تابع برای نمایش کنترل‌های صفحه‌بندی.
 *
 * @param {number} totalPages - Total number of pages.
 * @param {number} currentPage - Current active page.
 */
function renderPagination(totalPages, currentPage) {
    const paginationContainer = document.getElementById('pagination-container');
    if (!paginationContainer) return;

    paginationContainer.innerHTML = ''; // Clear existing pagination

    if (totalPages <= 1) return;

    // Previous button
    const prevButton = document.createElement('button');
    prevButton.className = `px-3 py-1 rounded-md border border-gray-300 ${currentPage === 1 ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-100'}`;
    prevButton.innerHTML = `<i class="fas fa-chevron-right"></i>`; // RTL arrow
    prevButton.disabled = currentPage === 1;
    prevButton.addEventListener('click', () => fetchUsers(currentPage - 1));
    paginationContainer.appendChild(prevButton);

    // Page numbers
    const maxPagesToShow = 5; // Max number of page buttons to display
    let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

    if (endPage - startPage + 1 < maxPagesToShow) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        const pageButton = document.createElement('button');
        pageButton.className = `px-3 py-1 rounded-md border border-gray-300 mx-1 ${i === currentPage ? 'bg-brown-900 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'}`;
        pageButton.textContent = i.toLocaleString('fa-IR'); // Format page number to Persian
        pageButton.addEventListener('click', () => fetchUsers(i));
        paginationContainer.appendChild(pageButton);
    }

    // Next button
    const nextButton = document.createElement('button');
    nextButton.className = `px-3 py-1 rounded-md border border-gray-300 ${currentPage === totalPages ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-100'}`;
    nextButton.innerHTML = `<i class="fas fa-chevron-left"></i>`; // RTL arrow
    nextButton.disabled = currentPage === totalPages;
    nextButton.addEventListener('click', () => fetchUsers(currentPage + 1));
    paginationContainer.appendChild(nextButton);
}

/**
 * Shows the user add/edit modal.
 * تابع برای نمایش مدال افزودن/ویرایش کاربر.
 *
 * @param {number|null} userId - The ID of the user to edit, or null for a new user.
 */
window.showUserModal = function(userId = null) {
    const modalOverlay = document.getElementById('user-modal-overlay');
    const modalTitle = document.getElementById('user-modal-title');
    const userIdInput = document.getElementById('user-id');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const roleSelect = document.getElementById('role');
    const statusSelect = document.getElementById('status');
    const phoneInput = document.getElementById('phone');
    const passwordField = document.getElementById('password-field');
    const passwordInput = document.getElementById('password');
    const passwordRequired = document.getElementById('password-required');
    const passwordConfirmField = document.getElementById('password-confirm-field');
    const passwordConfirmationInput = document.getElementById('password_confirmation');
    const passwordConfirmRequired = document.getElementById('password-confirm-required');
    const formMethodInput = document.getElementById('user-form-method');

    resetUserForm(); // Clear any previous errors or values

    if (userId) {
        // Edit mode
        const user = users.find(u => u.id === userId);
        if (!user) {
            showMessage('کاربر یافت نشد.', 'error');
            return;
        }
        modalTitle.textContent = 'ویرایش کاربر';
        userIdInput.value = user.id;
        usernameInput.value = user.username;
        emailInput.value = user.email;
        roleSelect.value = user.role;
        statusSelect.value = user.status;
        phoneInput.value = user.phone || ''; // Handle potential null phone
        
        // Passwords are not required for editing unless explicitly changed
        passwordInput.removeAttribute('required');
        passwordConfirmationInput.removeAttribute('required');
        if (passwordRequired) passwordRequired.classList.add('hidden');
        if (passwordConfirmRequired) passwordConfirmRequired.classList.add('hidden');
        
        formMethodInput.value = 'PUT'; // For Laravel PUT requests
    } else {
        // Add mode
        modalTitle.textContent = 'افزودن کاربر جدید';
        userIdInput.value = ''; // Ensure it's empty for new user
        
        // Passwords are required for new users
        passwordInput.setAttribute('required', 'required');
        passwordConfirmationInput.setAttribute('required', 'required');
        if (passwordRequired) passwordRequired.classList.remove('hidden');
        if (passwordConfirmRequired) passwordConfirmRequired.classList.remove('hidden');

        formMethodInput.value = 'POST'; // For Laravel POST requests
    }

    modalOverlay.classList.remove('hidden');
    modalOverlay.classList.add('active');
}

/**
 * Resets the user form and clears validation messages.
 * تابع برای ریست کردن فرم کاربر و پاک کردن پیام‌های اعتبارسنجی.
 */
function resetUserForm() {
    const userForm = document.getElementById('user-form');
    if (userForm) {
        userForm.reset();
        userForm.querySelectorAll('.invalid-feedback').forEach(el => el.classList.add('hidden'));
        userForm.querySelectorAll('input, select').forEach(el => el.classList.remove('border-red-500'));
        document.getElementById('form-errors')?.classList.add('hidden');
        document.getElementById('error-list')?.innerHTML = '';
    }
}

/**
 * Handles client-side form validation.
 * تابع برای اعتبارسنجی فرم سمت کلاینت.
 *
 * @param {HTMLFormElement} form - The form element to validate.
 * @returns {boolean} - True if form is valid, false otherwise.
 */
function validateUserForm(form) {
    let isValid = true;
    const errorList = document.getElementById('error-list');
    const formErrorsDiv = document.getElementById('form-errors');
    errorList.innerHTML = '';
    formErrorsDiv.classList.add('hidden');

    form.querySelectorAll('.invalid-feedback').forEach(el => el.classList.add('hidden'));
    form.querySelectorAll('input, select').forEach(el => el.classList.remove('border-red-500'));

    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const roleSelect = document.getElementById('role');
    const passwordInput = document.getElementById('password');
    const passwordConfirmationInput = document.getElementById('password_confirmation');
    const phoneInput = document.getElementById('phone');

    // Basic validation checks
    if (usernameInput.value.trim() === '') {
        displayFieldError(usernameInput, 'نام کاربری نمی‌تواند خالی باشد.');
        isValid = false;
    }
    if (emailInput.value.trim() === '' || !/\S+@\S+\.\S+/.test(emailInput.value)) {
        displayFieldError(emailInput, 'ایمیل نامعتبر است.');
        isValid = false;
    }
    if (roleSelect.value === '') {
        displayFieldError(roleSelect, 'نقش کاربر را انتخاب کنید.');
        isValid = false;
    }

    // Password validation (only if required or if fields are not empty)
    const isNewUser = !document.getElementById('user-id').value;
    if (isNewUser || (passwordInput.value !== '' || passwordConfirmationInput.value !== '')) {
        if (passwordInput.value.length < 8) {
            displayFieldError(passwordInput, 'رمز عبور باید حداقل 8 کاراکتر باشد.');
            isValid = false;
        }
        if (passwordInput.value !== passwordConfirmationInput.value) {
            displayFieldError(passwordConfirmationInput, 'رمز عبور و تکرار آن مطابقت ندارند.');
            isValid = false;
        }
    }

    // Phone validation (optional, basic regex)
    if (phoneInput.value.trim() !== '' && !/^09\d{9}$/.test(phoneInput.value)) {
        displayFieldError(phoneInput, 'شماره تلفن نامعتبر است. (مثال: 09123456789)');
        isValid = false;
    }

    if (!isValid) {
        formErrorsDiv.classList.remove('hidden');
    }
    return isValid;
}

/**
 * Displays an error message for a specific form field.
 * تابع برای نمایش پیام خطا برای یک فیلد فرم خاص.
 *
 * @param {HTMLElement} inputElement - The input or select element.
 * @param {string} message - The error message.
 */
function displayFieldError(inputElement, message) {
    const errorDiv = inputElement.nextElementSibling; // Assuming invalid-feedback is next sibling
    if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
        errorDiv.textContent = message;
        errorDiv.classList.remove('hidden');
    }
    inputElement.classList.add('border-red-500');
    
    // Add to general error list
    const errorList = document.getElementById('error-list');
    if (errorList) {
        const li = document.createElement('li');
        li.textContent = message;
        errorList.appendChild(li);
    }
}


/**
 * Handles the submission of the user add/edit form.
 * تابع برای مدیریت ارسال فرم افزودن/ویرایش کاربر.
 *
 * @param {Event} event - The form submission event.
 */
async function handleUserFormSubmit(event) {
    event.preventDefault();
    const form = event.target;

    if (!validateUserForm(form)) {
        showMessage('لطفاً خطاهای فرم را برطرف کنید.', 'error');
        return;
    }

    const userId = document.getElementById('user-id').value;
    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const role = document.getElementById('role').value;
    const status = document.getElementById('status').value;
    const phone = document.getElementById('phone').value;
    const password = document.getElementById('password').value;

    const userData = { username, email, role, status, phone };
    if (password) { // Only include password if it's provided (for new user or if changed)
        userData.password = password;
        userData.password_confirmation = document.getElementById('password_confirmation').value;
    }

    // Simulate API call
    const submitBtn = document.getElementById('submit-btn');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i><span>در حال ذخیره...</span>';

    try {
        await new Promise(resolve => setTimeout(resolve, 800)); // Simulate network delay

        if (userId) {
            // Simulate PUT request for editing
            const userIndex = users.findIndex(u => u.id === parseInt(userId));
            if (userIndex !== -1) {
                users[userIndex] = { ...users[userIndex], ...userData };
                showMessage('کاربر با موفقیت ویرایش شد.', 'success');
                logAdminAction(currentUser.username, 'ویرایش کاربر', `کاربر ${username} (شناسه: ${userId}) ویرایش شد.`);
            } else {
                showMessage('کاربر برای ویرایش یافت نشد.', 'error');
            }
        } else {
            // Simulate POST request for adding
            const newId = users.length > 0 ? Math.max(...users.map(u => u.id)) + 1 : 1;
            const newUser = {
                id: newId,
                username: username,
                email: email,
                role: role,
                lastLocation: 'N/A', // Mock value
                created_at: new Date().toISOString(),
                status: status,
                phone: phone
            };
            users.push(newUser);
            showMessage('کاربر جدید با موفقیت اضافه شد.', 'success');
            logAdminAction(currentUser.username, 'افزودن کاربر', `کاربر ${username} اضافه شد.`);
        }
        
        document.getElementById('user-modal-overlay').classList.add('hidden');
        document.getElementById('user-modal-overlay').classList.remove('active');
        fetchUsers(); // Re-fetch and re-render the list to show changes
    } catch (error) {
        console.error('Error saving user:', error);
        showMessage('خطا در ذخیره کاربر: ' + error.message, 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
}

/**
 * Handles single user deletion.
 * تابع برای مدیریت حذف تک کاربر.
 *
 * @param {number} userId - The ID of the user to delete.
 */
window.deleteUser = function(userId) {
    const userToDelete = users.find(u => u.id === userId);
    if (!userToDelete) {
        showMessage('کاربر یافت نشد.', 'error');
        return;
    }

    showConfirmationModal( // Using imported showConfirmationModal
        'تایید حذف کاربر',
        `آیا از حذف کاربر "${userToDelete.username}" مطمئن هستید؟ این عملیات قابل بازگشت نیست.`,
        async () => {
            try {
                await new Promise(resolve => setTimeout(resolve, 500)); // Simulate API call delay
                const initialLength = users.length;
                users = users.filter(user => user.id !== userId); // Update mock data

                if (users.length < initialLength) {
                    showMessage('کاربر با موفقیت حذف شد.', 'success');
                    logAdminAction(currentUser.username, 'حذف کاربر', `کاربر ${userToDelete.username} (شناسه: ${userId}) حذف شد.`);
                } else {
                    showMessage('خطا در حذف کاربر.', 'error');
                }
                fetchUsers(); // Re-fetch and re-render the list
            } catch (error) {
                console.error('Error deleting user:', error);
                showMessage('خطا در حذف کاربر: ' + error.message, 'error');
            }
        },
        () => {
            showMessage('عملیات حذف کاربر لغو شد.', 'info');
        }
    );
};

/**
 * Toggles the visibility of the bulk actions container based on selected users.
 * تابع برای نمایش/پنهان کردن بخش عملیات گروهی.
 */
function toggleBulkActionsVisibility() {
    const bulkActionsContainer = document.getElementById('bulk-actions');
    if (bulkActionsContainer) {
        if (selectedUserIds.size > 0) {
            bulkActionsContainer.classList.remove('hidden');
        } else {
            bulkActionsContainer.classList.add('hidden');
        }
    }
}

/**
 * Updates the count of selected users and enables/disables bulk action buttons.
 * تابع برای به‌روزرسانی تعداد کاربران انتخاب شده.
 */
function updateSelectedUsersCount() {
    const selectAllCheckbox = document.getElementById('select-all');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    
    // Update select-all checkbox state
    if (userCheckboxes.length > 0 && selectedUserIds.size === userCheckboxes.length) {
        if (selectAllCheckbox) selectAllCheckbox.checked = true;
    } else {
        if (selectAllCheckbox) selectAllCheckbox.checked = false;
    }

    toggleBulkActionsVisibility();
}

/**
 * Handles the click event for the "select all" checkbox.
 * تابع برای مدیریت کلیک روی چک‌باکس "انتخاب همه".
 *
 * @param {Event} event - The click event.
 */
function handleSelectAllClick(event) {
    const isChecked = event.target.checked;
    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.checked = isChecked;
        const userId = parseInt(checkbox.dataset.userId);
        if (isChecked) {
            selectedUserIds.add(userId);
        } else {
            selectedUserIds.delete(userId);
        }
    });
    updateSelectedUsersCount();
}

/**
 * Handles the change event for individual user checkboxes.
 * تابع برای مدیریت تغییر وضعیت چک‌باکس‌های تک کاربر.
 *
 * @param {Event} event - The change event.
 */
function handleUserCheckboxClick(event) {
    const userId = parseInt(event.target.dataset.userId);
    if (event.target.checked) {
        selectedUserIds.add(userId);
    } else {
        selectedUserIds.delete(userId);
    }
    updateSelectedUsersCount();
}

/**
 * Handles bulk actions (delete, activate, deactivate).
 * تابع برای مدیریت عملیات گروهی.
 *
 * @param {string} actionType - Type of bulk action ('delete', 'activate', 'deactivate').
 */
async function handleBulkAction(actionType) {
    if (selectedUserIds.size === 0) {
        showMessage('هیچ کاربری انتخاب نشده است.', 'info');
        return;
    }

    const usersToActOn = Array.from(selectedUserIds).map(id => users.find(u => u.id === id)).filter(Boolean);
    const usernames = usersToActOn.map(u => u.username).join(', ');
    let confirmMessage = '';
    let successMessage = '';
    let logAction = '';

    if (actionType === 'delete') {
        confirmMessage = `آیا از حذف ${selectedUserIds.size} کاربر انتخاب شده (${usernames}) مطمئن هستید؟ این عملیات قابل بازگشت نیست.`;
        successMessage = 'کاربران انتخاب شده با موفقیت حذف شدند.';
        logAction = 'حذف گروهی کاربران';
    } else if (actionType === 'activate') {
        confirmMessage = `آیا از فعال کردن ${selectedUserIds.size} کاربر انتخاب شده (${usernames}) مطمئن هستید؟`;
        successMessage = 'کاربران انتخاب شده با موفقیت فعال شدند.';
        logAction = 'فعال‌سازی گروهی کاربران';
    } else if (actionType === 'deactivate') {
        confirmMessage = `آیا از غیرفعال کردن ${selectedUserIds.size} کاربر انتخاب شده (${usernames}) مطمئن هستید؟`;
        successMessage = 'کاربران انتخاب شده با موفقیت غیرفعال شدند.';
        logAction = 'غیرفعال‌سازی گروهی کاربران';
    } else {
        return;
    }

    showConfirmationModal( // Using imported showConfirmationModal
        'تایید عملیات گروهی',
        confirmMessage,
        async () => {
            try {
                await new Promise(resolve => setTimeout(resolve, 800)); // Simulate API call delay

                if (actionType === 'delete') {
                    users = users.filter(user => !selectedUserIds.has(user.id));
                } else {
                    users.forEach(user => {
                        if (selectedUserIds.has(user.id)) {
                            if (actionType === 'activate') user.status = 'active';
                            if (actionType === 'deactivate') user.status = 'inactive';
                        }
                    });
                }
                
                showMessage(successMessage, 'success');
                logAdminAction(currentUser.username, logAction, `عملیات روی کاربران: ${usernames}`);
                selectedUserIds.clear(); // Clear selection after action
                fetchUsers(); // Re-fetch and re-render the list
            } catch (error) {
                console.error('Error performing bulk action:', error);
                showMessage('خطا در انجام عملیات گروهی: ' + error.message, 'error');
            }
        },
        () => {
            showMessage('عملیات گروهی لغو شد.', 'info');
        }
    );
}

/**
 * Sets up all event listeners related to admin panel UI and user management.
 * تابع برای تنظیم تمام EventListenerهای مربوط به رابط کاربری پنل ادمین و مدیریت کاربران.
 */
export function setupAdminPanelListeners() {
    // Sidebar toggle
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const mainContentWrapper = document.getElementById('main-content-wrapper');
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

            setTimeout(() => {
                updateChartOnResize();
            }, 300);
        });
    }

    // Notification dropdown
    const notificationButton = document.getElementById('notification-button');
    const notificationDropdown = document.getElementById('notification-dropdown');

    if (notificationButton && notificationDropdown) {
        notificationButton.addEventListener('click', (event) => {
            notificationDropdown.classList.toggle('hidden');
            event.stopPropagation();
        });

        document.addEventListener('click', (event) => {
            if (!notificationDropdown.contains(event.target) && !notificationButton.contains(event.target)) {
                notificationDropdown.classList.add('hidden');
            }
        });
    }

    // Report actions dropdown
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
            if (reportActionsContainer.classList.contains('active')) {
                reportActionsContainer.classList.add('hidden');
                reportActionsContainer.classList.remove('active');
                floatingReportToggle.setAttribute('aria-expanded', 'false');
            }
        });

        reportActionsContainer.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                reportActionsContainer.classList.add('hidden');
                reportActionsContainer.classList.remove('active');
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
    updateClock();

    // --- User Management Specific Listeners ---
    // Search input
    const userSearchInput = document.getElementById('user-search');
    if (userSearchInput) {
        let searchTimeout;
        userSearchInput.addEventListener('keyup', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchUsers(1, itemsPerPage, currentSortColumn, currentSortDirection, userSearchInput.value.trim());
            }, 300); // Debounce search input
        });
    }

    // Sortable table headers
    document.querySelectorAll('#user-management-content th[data-sort]').forEach(header => {
        header.addEventListener('click', () => {
            const sortColumn = header.dataset.sort;
            let sortDirection = 'asc';
            if (currentSortColumn === sortColumn) {
                sortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
            }
            fetchUsers(1, itemsPerPage, sortColumn, sortDirection, currentSearchQuery);
        });
    });

    // Add User button
    const addUserButton = document.getElementById('add-user-btn');
    if (addUserButton) {
        addUserButton.addEventListener('click', () => window.showUserModal(null));
    }

    // User Modal close buttons
    const userModalCloseBtn = document.getElementById('user-modal-close-btn');
    const userFormCancelBtn = document.getElementById('user-form-cancel-btn');
    const userModalOverlay = document.getElementById('user-modal-overlay');

    if (userModalCloseBtn) {
        userModalCloseBtn.addEventListener('click', () => {
            userModalOverlay.classList.add('hidden');
            userModalOverlay.classList.remove('active');
        });
    }
    if (userFormCancelBtn) {
        userFormCancelBtn.addEventListener('click', () => {
            userModalOverlay.classList.add('hidden');
            userModalOverlay.classList.remove('active');
        });
    }

    // User Form submission
    const userForm = document.getElementById('user-form');
    if (userForm) {
        userForm.addEventListener('submit', handleUserFormSubmit);
    }

    // Select All checkbox
    const selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', handleSelectAllClick);
    }

    // Bulk action buttons
    document.getElementById('bulk-delete')?.addEventListener('click', () => handleBulkAction('delete'));
    document.getElementById('bulk-activate')?.addEventListener('click', () => handleBulkAction('activate'));
    document.getElementById('bulk-deactivate')?.addEventListener('click', () => handleBulkAction('deactivate'));
}

// Initial setup on window load
window.onload = () => {
    window.showSection('dashboard'); // Display dashboard initially
    renderActivityLog(); // Initial render of activity log

    const storedAdmin = users.find(u => u.username === currentUser.username);
    if (storedAdmin) {
        logAdminAction(currentUser.username, 'ورود به پنل', 'ورود موفق به سیستم');
    } else {
        showMessage('کاربر ادمین شبیه‌سازی شده یافت نشد. به عنوان کاربر پیش‌فرض عمل می‌کنیم.', 'info');
    }

    setupAdminPanelListeners(); // Setup all admin panel specific listeners
};
