// resources/js/admin.js

// Import necessary modules from app.js and charts.js
// فرض بر این است که این فایل‌ها در کنار admin.js قرار دارند و توابع مورد نیاز را export می‌کنند.
import { showMessage, showConfirmationModal, logAdminAction, adminActivityLog, currentUser } from './app.js';
import { initializeMonthlySalesChart, updateChartOnResize } from './charts.js';
import * as jalaali from 'jalaali-js'; // برای تبدیل تاریخ میلادی به شمسی

// Simulated user data
// این آرایه شامل اطلاعات کاربران نمونه است. در یک سیستم واقعی، این اطلاعات از یک پایگاه داده بارگذاری می‌شوند.
let users = [
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

// Current state for user management table
let currentPage = 1;
let itemsPerPage = 5;
let currentSortColumn = 'id';
let currentSortDirection = 'asc';
let currentSearchQuery = '';
let selectedUserIds = new Set(); // برای نگهداری شناسه‌های کاربران انتخاب شده جهت عملیات گروهی

// Utility function to map user roles and statuses to Tailwind CSS classes
const statusClasses = {
    'active': 'bg-green-100 text-green-800',
    'inactive': 'bg-red-100 text-red-800',
    'suspended': 'bg-yellow-100 text-yellow-800'
};

const roleClasses = {
    'admin': 'bg-red-100 text-red-800',
    'user': 'bg-green-100 text-green-800',
    'editor': 'bg-blue-100 text-blue-800',
    'moderator': 'bg-purple-100 text-purple-800'
};

const roleDisplayNames = {
    'admin': 'مدیر',
    'user': 'کاربر',
    'editor': 'ویرایشگر',
    'moderator': 'مدیر محتوا'
};

/**
 * Renders the admin activity log.
 * فعالیت‌های اخیر ادمین را در پنل نمایش می‌دهد.
 */
export function renderActivityLog() {
    const logContainer = document.getElementById('admin-activity-log');
    if (!logContainer) return;

    logContainer.innerHTML = '';
    // نمایش جدیدترین فعالیت‌ها در بالا
    adminActivityLog.slice().reverse().forEach(log => {
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
 * Shows a specific section of the admin panel and updates the title.
 * بخش مورد نظر از پنل ادمین را نمایش داده و عنوان آن را به‌روزرسانی می‌کند.
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
    // پیدا کردن عنصر ناوبری مرتبط برای دریافت متن عنوان
    const navTextElement = document.querySelector(`[onclick="showSection('${sectionId}')"] .nav-text`);
    if (titleElement && navTextElement) {
        titleElement.textContent = navTextElement.textContent;
    }

    // بستن دراپ‌داون نوتیفیکیشن در صورت باز بودن
    const notificationDropdown = document.getElementById('notification-dropdown');
    if (notificationDropdown) {
        notificationDropdown.classList.add('hidden');
    }

    // بستن اکشن‌های گزارش شناور در صورت باز بودن
    const floatingReportToggle = document.getElementById('toggle-report-actions');
    const reportActionsContainer = document.getElementById('report-actions-container');
    if (floatingReportToggle && reportActionsContainer) {
        reportActionsContainer.classList.add('hidden');
        floatingReportToggle.setAttribute('aria-expanded', 'false');
    }

    // اجرای توابع خاص برای هر بخش
    if (sectionId === 'dashboard') {
        initializeMonthlySalesChart();
        updateChartOnResize(); // اطمینان از رندر صحیح چارت پس از تغییر اندازه
    } else if (sectionId === 'user-management') {
        fetchUsers(); // بارگذاری کاربران هنگام ورود به بخش مدیریت کاربران
    }
};

/**
 * Simulates user logout.
 * خروج کاربر از سیستم را شبیه‌سازی می‌کند.
 */
window.logoutUser = function() {
    showMessage('شما از سیستم خارج شدید.', 'info');
    logAdminAction(currentUser.username, 'خروج از پنل', 'خروج موفق از سیستم');
    console.log('User logged out.');
    // در یک سیستم واقعی، اینجا به صفحه ورود هدایت می‌شوید یا توکن احراز هویت حذف می‌شود.
};

/**
 * Fetches and renders user data with pagination, sorting, and searching.
 * داده‌های کاربران را با قابلیت صفحه‌بندی، مرتب‌سازی و جستجو بارگذاری و نمایش می‌دهد.
 * @param {number} page - The current page number.
 * @param {number} per_page - Number of items per page.
 * @param {string} sort_by - Column to sort by.
 * @param {string} sort_direction - Sorting direction ('asc' or 'desc').
 * @param {string} search - Search query string.
 */
async function fetchUsers(page = currentPage, per_page = itemsPerPage, sort_by = currentSortColumn, sort_direction = currentSortDirection, search = currentSearchQuery) {
    // Cache DOM elements for better performance
    const loadingState = document.getElementById('loading-state');
    const userListBody = document.getElementById('user-list-body');
    const noUsersMessage = document.getElementById('no-users-message');
    const selectAllCheckbox = document.getElementById('select-all');

    if (loadingState) loadingState.classList.remove('hidden');
    if (userListBody) userListBody.innerHTML = ''; // Clear previous list
    if (noUsersMessage) noUsersMessage.classList.add('hidden'); // Hide no users message initially

    try {
        // Simulate API call delay
        await new Promise(resolve => setTimeout(resolve, 500));

        // Filter users based on search query
        const filteredUsers = users.filter(user =>
            user.username.toLowerCase().includes(search.toLowerCase()) ||
            user.email.toLowerCase().includes(search.toLowerCase()) ||
            user.role.toLowerCase().includes(search.toLowerCase())
        );

        // Sort users based on selected column and direction
        const sortedUsers = [...filteredUsers].sort((a, b) => {
            let valA = a[sort_by];
            let valB = b[sort_by];

            // Handle specific column types for sorting
            if (sort_by === 'id') {
                valA = parseInt(valA);
                valB = parseInt(valB);
            } else if (sort_by === 'created_at') {
                valA = new Date(valA).getTime();
                valB = new Date(valB).getTime();
            }

            if (valA < valB) return sort_direction === 'asc' ? -1 : 1;
            if (valA > valB) return sort_direction === 'asc' ? 1 : -1;
            return 0;
        });

        const totalUsers = sortedUsers.length;
        const totalPages = Math.ceil(totalUsers / per_page);
        const startIndex = (page - 1) * per_page;
        const endIndex = startIndex + per_page;
        const paginatedUsers = sortedUsers.slice(startIndex, endIndex);

        // Update current state variables
        currentPage = page;
        currentSortColumn = sort_by;
        currentSortDirection = sort_direction;
        currentSearchQuery = search;

        renderUserList(paginatedUsers);
        renderPagination(totalPages, page);
        updateSelectedUsersCount(); // Update count and bulk action visibility

        // Show message if no users are found after filtering/searching
        if (paginatedUsers.length === 0 && noUsersMessage) {
            noUsersMessage.classList.remove('hidden');
        }

    } catch (error) {
        console.error('Error fetching users:', error);
        showMessage('خطا در بارگذاری لیست کاربران.', 'error');
        if (noUsersMessage) noUsersMessage.classList.remove('hidden');
        if (userListBody) userListBody.innerHTML = `<tr><td colspan="9" class="py-3 px-6 text-center text-red-500">خطا در بارگذاری اطلاعات.</td></tr>`;
    } finally {
        if (loadingState) loadingState.classList.add('hidden'); // Hide loading state
    }
}

/**
 * Renders the list of users in the table body.
 * لیست کاربران را در بدنه جدول نمایش می‌دهد.
 * @param {Array<Object>} usersToRender - Array of user objects to render.
 */
function renderUserList(usersToRender) {
    const userListBody = document.getElementById('user-list-body');
    if (!userListBody) return;

    userListBody.innerHTML = ''; // Clear existing rows
    selectedUserIds.clear(); // Clear selected users for new render
    const selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) selectAllCheckbox.checked = false; // Uncheck select all
    toggleBulkActionsVisibility(); // Hide bulk actions initially

    if (usersToRender.length === 0) {
        document.getElementById('no-users-message')?.classList.remove('hidden');
        return;
    } else {
        document.getElementById('no-users-message')?.classList.add('hidden');
    }

    // Use a DocumentFragment for efficient DOM manipulation
    const fragment = document.createDocumentFragment();

    usersToRender.forEach(user => {
        const row = document.createElement('tr');
        row.className = 'border-b border-gray-200 hover:bg-gray-50';

        // Format creation date to Jalaali calendar
        let formattedDate = 'نامشخص';
        if (user.created_at) {
            const createdAtDate = new Date(user.created_at);
            const jalaliDate = jalaali.toJalaali(createdAtDate.getFullYear(), createdAtDate.getMonth() + 1, createdAtDate.getDate());
            formattedDate = `${jalaliDate.jy}/${String(jalaliDate.jm).padStart(2, '0')}/${String(jalaliDate.jd).padStart(2, '0')}`;
        }

        const statusClass = statusClasses[user.status] || 'bg-gray-100 text-gray-800';
        const roleClass = roleClasses[user.role] || 'bg-gray-100 text-gray-800';
        const roleDisplayName = roleDisplayNames[user.role] || user.role;

        row.innerHTML = `
            <td class="py-3 px-6 text-right">
                <input type="checkbox" class="user-checkbox rounded text-green-600 focus:ring-green-500" data-user-id="${user.id}" ${selectedUserIds.has(user.id) ? 'checked' : ''}>
            </td>
            <td class="py-3 px-6 text-right whitespace-nowrap">${user.id}</td>
            <td class="py-3 px-6 text-right whitespace-nowrap">${user.username}</td>
            <td class="py-3 px-6 text-right whitespace-nowrap">${user.email}</td>
            <td class="py-3 px-6 text-right whitespace-nowrap">
                <span class="px-2 py-1 font-semibold leading-tight rounded-full ${roleClass}">
                    ${roleDisplayName}
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
        fragment.appendChild(row);
    });
    userListBody.appendChild(fragment);

    // Event delegation for user checkboxes (attaching to parent table body)
    userListBody.addEventListener('change', (event) => {
        if (event.target.classList.contains('user-checkbox')) {
            handleUserCheckboxClick(event);
        }
    });
}

/**
 * Renders the pagination controls.
 * کنترل‌های صفحه‌بندی را نمایش می‌دهد.
 * @param {number} totalPages - Total number of pages.
 * @param {number} currentPage - The current active page.
 */
function renderPagination(totalPages, currentPage) {
    const paginationContainer = document.getElementById('pagination-container');
    if (!paginationContainer) return;

    paginationContainer.innerHTML = ''; // Clear existing pagination

    if (totalPages <= 1) return; // No pagination needed for 1 or less pages

    // Previous button
    const prevButton = document.createElement('button');
    prevButton.className = `px-3 py-1 rounded-md border border-gray-300 ${currentPage === 1 ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-100'}`;
    prevButton.innerHTML = `<i class="fas fa-chevron-right"></i>`; // Icon for right arrow (for RTL)
    prevButton.disabled = currentPage === 1;
    prevButton.addEventListener('click', () => fetchUsers(currentPage - 1));
    paginationContainer.appendChild(prevButton);

    // Page number buttons
    const maxPagesToShow = 5; // Max number of page buttons to display
    let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

    // Adjust startPage if not enough pages to fill maxPagesToShow from current position
    if (endPage - startPage + 1 < maxPagesToShow) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        const pageButton = document.createElement('button');
        pageButton.className = `px-3 py-1 rounded-md border border-gray-300 mx-1 ${i === currentPage ? 'bg-brown-900 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'}`;
        pageButton.textContent = i.toLocaleString('fa-IR'); // Display page numbers in Persian
        pageButton.addEventListener('click', () => fetchUsers(i));
        paginationContainer.appendChild(pageButton);
    }

    // Next button
    const nextButton = document.createElement('button');
    nextButton.className = `px-3 py-1 rounded-md border border-gray-300 ${currentPage === totalPages ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-100'}`;
    nextButton.innerHTML = `<i class="fas fa-chevron-left"></i>`; // Icon for left arrow (for RTL)
    nextButton.disabled = currentPage === totalPages;
    nextButton.addEventListener('click', () => fetchUsers(currentPage + 1));
    paginationContainer.appendChild(nextButton);
}

/**
 * Displays the user creation/edit modal and populates it with user data if editing.
 * مودال ایجاد/ویرایش کاربر را نمایش می‌دهد و در صورت ویرایش، اطلاعات کاربر را پر می‌کند.
 * @param {number|null} userId - The ID of the user to edit, or null for a new user.
 */
window.showUserModal = function(userId = null) {
    // Cache modal elements
    const modalOverlay = document.getElementById('user-modal-overlay');
    const modalTitle = document.getElementById('user-modal-title');
    const userIdInput = document.getElementById('user-id');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const roleSelect = document.getElementById('role');
    const statusSelect = document.getElementById('status');
    const phoneInput = document.getElementById('phone');
    const passwordInput = document.getElementById('password');
    const passwordRequired = document.getElementById('password-required');
    const passwordConfirmationInput = document.getElementById('password_confirmation');
    const passwordConfirmRequired = document.getElementById('password-confirm-required');
    const formMethodInput = document.getElementById('user-form-method');

    resetUserForm(); // Reset form fields and validation messages

    if (userId) {
        // Editing an existing user
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
        phoneInput.value = user.phone || ''; // Ensure phone is not null/undefined

        // Passwords are not required for editing unless explicitly changed
        passwordInput.removeAttribute('required');
        passwordConfirmationInput.removeAttribute('required');
        if (passwordRequired) passwordRequired.classList.add('hidden');
        if (passwordConfirmRequired) passwordConfirmRequired.classList.add('hidden');

        formMethodInput.value = 'PUT'; // Indicate update operation
    } else {
        // Adding a new user
        modalTitle.textContent = 'افزودن کاربر جدید';
        userIdInput.value = ''; // Clear user ID for new user

        // Passwords are required for new users
        passwordInput.setAttribute('required', 'required');
        passwordConfirmationInput.setAttribute('required', 'required');
        if (passwordRequired) passwordRequired.classList.remove('hidden');
        if (passwordConfirmRequired) passwordConfirmRequired.classList.remove('hidden');

        formMethodInput.value = 'POST'; // Indicate create operation
    }

    modalOverlay.classList.remove('hidden');
    modalOverlay.classList.add('active'); // Show the modal
};

/**
 * Resets the user form, clears input values, and hides validation messages.
 * فرم کاربر را ریست کرده، مقادیر ورودی را پاک می‌کند و پیام‌های اعتبارسنجی را پنهان می‌کند.
 */
function resetUserForm() {
    const userForm = document.getElementById('user-form');
    if (userForm) {
        userForm.reset(); // Reset all form fields
        userForm.querySelectorAll('.invalid-feedback').forEach(el => el.classList.add('hidden')); // Hide all error messages
        userForm.querySelectorAll('input, select').forEach(el => el.classList.remove('border-red-500')); // Remove error borders
        document.getElementById('form-errors')?.classList.add('hidden'); // Hide general form errors
        document.getElementById('error-list')?.innerHTML = ''; // Clear error list
    }
}

/**
 * Validates the user form inputs.
 * ورودی‌های فرم کاربر را اعتبارسنجی می‌کند.
 * @param {HTMLFormElement} form - The form element to validate.
 * @returns {boolean} - True if the form is valid, false otherwise.
 */
function validateUserForm(form) {
    let isValid = true;
    const errorList = document.getElementById('error-list');
    const formErrorsDiv = document.getElementById('form-errors');

    // Clear previous errors
    errorList.innerHTML = '';
    formErrorsDiv.classList.add('hidden');
    form.querySelectorAll('.invalid-feedback').forEach(el => el.classList.add('hidden'));
    form.querySelectorAll('input, select').forEach(el => el.classList.remove('border-red-500'));

    // Cache input elements
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const roleSelect = document.getElementById('role');
    const passwordInput = document.getElementById('password');
    const passwordConfirmationInput = document.getElementById('password_confirmation');
    const phoneInput = document.getElementById('phone');

    // Validate username
    if (usernameInput.value.trim() === '') {
        displayFieldError(usernameInput, 'نام کاربری نمی‌تواند خالی باشد.');
        isValid = false;
    }

    // Validate email
    if (emailInput.value.trim() === '' || !/\S+@\S+\.\S+/.test(emailInput.value)) {
        displayFieldError(emailInput, 'ایمیل نامعتبر است.');
        isValid = false;
    }

    // Validate role
    if (roleSelect.value === '') {
        displayFieldError(roleSelect, 'نقش کاربر را انتخاب کنید.');
        isValid = false;
    }

    // Validate password for new users or if password fields are not empty for existing users
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

    // Validate phone number (optional but good to have)
    if (phoneInput.value.trim() !== '' && !/^09\d{9}$/.test(phoneInput.value)) {
        displayFieldError(phoneInput, 'شماره تلفن نامعتبر است. (مثال: 09123456789)');
        isValid = false;
    }

    // Show general form errors if any field is invalid
    if (!isValid) {
        formErrorsDiv.classList.remove('hidden');
    }
    return isValid;
}

/**
 * Displays a validation error message for a specific input field.
 * پیام خطای اعتبارسنجی را برای یک فیلد ورودی خاص نمایش می‌دهد.
 * @param {HTMLElement} inputElement - The input element that has the error.
 * @param {string} message - The error message to display.
 */
function displayFieldError(inputElement, message) {
    const errorDiv = inputElement.nextElementSibling;
    if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
        errorDiv.textContent = message;
        errorDiv.classList.remove('hidden');
    }
    inputElement.classList.add('border-red-500'); // Add red border to indicate error

    const errorList = document.getElementById('error-list');
    if (errorList) {
        const li = document.createElement('li');
        li.textContent = message;
        errorList.appendChild(li); // Add error to the general error list
    }
}

/**
 * Handles the submission of the user form (add/edit user).
 * ارسال فرم کاربر (افزودن/ویرایش کاربر) را مدیریت می‌کند.
 * @param {Event} event - The form submission event.
 */
async function handleUserFormSubmit(event) {
    event.preventDefault(); // Prevent default form submission
    const form = event.target;

    if (!validateUserForm(form)) {
        showMessage('لطفاً خطاهای فرم را برطرف کنید.', 'error');
        return;
    }

    // Get form data
    const userId = document.getElementById('user-id').value;
    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const role = document.getElementById('role').value;
    const status = document.getElementById('status').value;
    const phone = document.getElementById('phone').value;
    const password = document.getElementById('password').value; // Only if provided

    const userData = { username, email, role, status, phone };
    if (password) {
        userData.password = password;
        userData.password_confirmation = document.getElementById('password_confirmation').value;
    }

    const submitBtn = document.getElementById('submit-btn');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true; // Disable button during submission
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i><span>در حال ذخیره...</span>'; // Show loading spinner

    try {
        // Simulate API call delay for saving data
        await new Promise(resolve => setTimeout(resolve, 800));

        if (userId) {
            // Update existing user
            const userIndex = users.findIndex(u => u.id === parseInt(userId));
            if (userIndex !== -1) {
                users[userIndex] = { ...users[userIndex], ...userData }; // Merge existing data with new
                showMessage('کاربر با موفقیت ویرایش شد.', 'success');
                logAdminAction(currentUser.username, 'ویرایش کاربر', `کاربر ${username} (شناسه: ${userId}) ویرایش شد.`);
            } else {
                showMessage('کاربر برای ویرایش یافت نشد.', 'error');
            }
        } else {
            // Add new user
            const newId = users.length > 0 ? Math.max(...users.map(u => u.id)) + 1 : 1; // Generate new ID
            const newUser = {
                id: newId,
                username: username,
                email: email,
                role: role,
                lastLocation: 'N/A', // Placeholder for new user
                created_at: new Date().toISOString(), // Set current date
                status: status,
                phone: phone
            };
            users.push(newUser);
            showMessage('کاربر جدید با موفقیت اضافه شد.', 'success');
            logAdminAction(currentUser.username, 'افزودن کاربر', `کاربر ${username} اضافه شد.`);
        }

        // Close modal and refresh user list
        document.getElementById('user-modal-overlay').classList.add('hidden');
        document.getElementById('user-modal-overlay').classList.remove('active');
        fetchUsers();
    } catch (error) {
        console.error('Error saving user:', error);
        showMessage('خطا در ذخیره کاربر: ' + error.message, 'error');
    } finally {
        submitBtn.disabled = false; // Re-enable button
        submitBtn.innerHTML = originalBtnText; // Restore original button text
    }
}

/**
 * Deletes a user after confirmation.
 * یک کاربر را پس از تایید حذف می‌کند.
 * @param {number} userId - The ID of the user to delete.
 */
window.deleteUser = function(userId) {
    const userToDelete = users.find(u => u.id === userId);
    if (!userToDelete) {
        showMessage('کاربر یافت نشد.', 'error');
        return;
    }

    showConfirmationModal(
        'تایید حذف کاربر',
        `آیا از حذف کاربر "${userToDelete.username}" مطمئن هستید؟ این عملیات قابل بازگشت نیست.`,
        async () => { // On confirm
            try {
                // Simulate API call delay for deletion
                await new Promise(resolve => setTimeout(resolve, 500));
                const initialLength = users.length;
                users = users.filter(user => !selectedUserIds.has(user.id)); // Remove user from array

                if (users.length < initialLength) {
                    showMessage('کاربر با موفقیت حذف شد.', 'success');
                    logAdminAction(currentUser.username, 'حذف کاربر', `کاربر ${userToDelete.username} (شناسه: ${userId}) حذف شد.`);
                } else {
                    showMessage('خطا در حذف کاربر.', 'error');
                }
                fetchUsers(); // Refresh user list
            } catch (error) {
                console.error('Error deleting user:', error);
                showMessage('خطا در حذف کاربر: ' + error.message, 'error');
            }
        },
        () => { // On cancel
            showMessage('عملیات حذف لغو شد.', 'info');
        }
    );
};

/**
 * Toggles the visibility of bulk action buttons based on selected users.
 * نمایش دکمه‌های عملیات گروهی را بر اساس تعداد کاربران انتخاب شده تغییر می‌دهد.
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
 * Updates the state of the "select all" checkbox and bulk action visibility.
 * وضعیت چک‌باکس "انتخاب همه" و نمایش عملیات گروهی را به‌روزرسانی می‌کند.
 */
function updateSelectedUsersCount() {
    const selectAllCheckbox = document.getElementById('select-all');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');

    // Check if all displayed users are selected
    if (userCheckboxes.length > 0 && selectedUserIds.size === userCheckboxes.length) {
        if (selectAllCheckbox) selectAllCheckbox.checked = true;
    } else {
        if (selectAllCheckbox) selectAllCheckbox.checked = false;
    }

    toggleBulkActionsVisibility();
}

/**
 * Handles the click event for the "select all" checkbox.
 * رویداد کلیک برای چک‌باکس "انتخاب همه" را مدیریت می‌کند.
 * @param {Event} event - The change event.
 */
function handleSelectAllClick(event) {
    const isChecked = event.target.checked;
    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.checked = isChecked; // Set individual checkboxes
        const userId = parseInt(checkbox.dataset.userId);
        if (isChecked) {
            selectedUserIds.add(userId);
        } else {
            selectedUserIds.delete(userId);
        }
    });
    updateSelectedUsersCount(); // Update count and visibility
}

/**
 * Handles the click event for individual user checkboxes.
 * رویداد کلیک برای چک‌باکس‌های کاربران را مدیریت می‌کند.
 * @param {Event} event - The change event.
 */
function handleUserCheckboxClick(event) {
    const userId = parseInt(event.target.dataset.userId);
    if (event.target.checked) {
        selectedUserIds.add(userId);
    } else {
        selectedUserIds.delete(userId);
    }
    updateSelectedUsersCount(); // Update count and visibility
}

/**
 * Performs bulk actions (delete, activate, deactivate) on selected users.
 * عملیات گروهی (حذف، فعال‌سازی، غیرفعال‌سازی) را روی کاربران انتخاب شده انجام می‌دهد.
 * @param {string} actionType - The type of action ('delete', 'activate', 'deactivate').
 */
async function handleBulkAction(actionType) {
    if (selectedUserIds.size === 0) {
        showMessage('هیچ کاربری انتخاب نشده است.', 'info');
        return;
    }

    // Get usernames for confirmation message
    const usersToActOn = Array.from(selectedUserIds).map(id => users.find(u => u.id === id)).filter(Boolean);
    const usernames = usersToActOn.map(u => u.username).join(', ');

    let confirmMessage = '';
    let successMessage = '';
    let logAction = '';

    // Set messages based on action type
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
        return; // Invalid action type
    }

    showConfirmationModal(
        'تایید عملیات گروهی',
        confirmMessage,
        async () => { // On confirm
            try {
                // Simulate API call delay
                await new Promise(resolve => setTimeout(resolve, 800));

                if (actionType === 'delete') {
                    users = users.filter(user => !selectedUserIds.has(user.id)); // Remove selected users
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
                fetchUsers(); // Refresh user list
            } catch (error) {
                console.error('Error performing bulk action:', error);
                showMessage('خطا در انجام عملیات گروهی: ' + error.message, 'error');
            }
        },
        () => { // On cancel
            showMessage('عملیات گروهی لغو شد.', 'info');
        }
    );
}

/**
 * Sets up event listeners for the sidebar toggle.
 * شنونده‌های رویداد برای دکمه باز و بسته کردن سایدبار را تنظیم می‌کند.
 */
function setupSidebarToggle() {
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

            // Update chart on resize after sidebar transition
            setTimeout(() => {
                updateChartOnResize();
            }, 300);
        });
    }
}

/**
 * Sets up event listeners for the notification dropdown.
 * شنونده‌های رویداد برای دراپ‌داون نوتیفیکیشن را تنظیم می‌کند.
 */
function setupNotificationDropdown() {
    const notificationButton = document.getElementById('notification-button');
    const notificationDropdown = document.getElementById('notification-dropdown');

    if (notificationButton && notificationDropdown) {
        notificationButton.addEventListener('click', (event) => {
            notificationDropdown.classList.toggle('hidden');
            event.stopPropagation(); // Prevent document click from closing it immediately
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (event) => {
            if (!notificationDropdown.contains(event.target) && !notificationButton.contains(event.target)) {
                notificationDropdown.classList.add('hidden');
            }
        });
    }
}

/**
 * Sets up event listeners for the floating report actions.
 * شنونده‌های رویداد برای اکشن‌های گزارش شناور را تنظیم می‌کند.
 */
function setupReportActions() {
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

        // Close report actions when clicking outside
        document.addEventListener('click', (event) => {
            if (!reportActionsContainer.contains(event.target) && !floatingReportToggle.contains(event.target)) {
                reportActionsContainer.classList.add('hidden');
                reportActionsContainer.classList.remove('active');
                floatingReportToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Close report actions when clicking on a link inside
        reportActionsContainer.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                reportActionsContainer.classList.add('hidden');
                reportActionsContainer.classList.remove('active');
                floatingReportToggle.setAttribute('aria-expanded', 'false');
            });
        });
    }
}

/**
 * Sets up and updates the real-time clock in the header.
 * ساعت بلادرنگ در هدر را تنظیم و به‌روزرسانی می‌کند.
 */
function setupClock() {
    const timeElement = document.getElementById('current-time');
    if (!timeElement) return;

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
        timeElement.textContent = now.toLocaleString('fa-IR', options).replace(',', ' -');
    }
    setInterval(updateClock, 1000); // Update every second
    updateClock(); // Initial call
}

/**
 * Sets up the user search input functionality.
 * قابلیت جستجوی کاربران را تنظیم می‌کند.
 */
function setupUserSearch() {
    const userSearchInput = document.getElementById('user-search');
    if (userSearchInput) {
        let searchTimeout;
        userSearchInput.addEventListener('keyup', () => {
            clearTimeout(searchTimeout); // Clear previous timeout
            searchTimeout = setTimeout(() => {
                fetchUsers(1, itemsPerPage, currentSortColumn, currentSortDirection, userSearchInput.value.trim());
            }, 300); // Debounce search input
        });
    }
}

/**
 * Sets up sorting functionality for the user table headers.
 * قابلیت مرتب‌سازی را برای هدرهای جدول کاربران را تنظیم می‌کند.
 */
function setupUserTableSorting() {
    document.querySelectorAll('#user-management-content th[data-sort]').forEach(header => {
        header.addEventListener('click', () => {
            const sortColumn = header.dataset.sort;
            let sortDirection = 'asc';
            // Toggle sort direction if clicking on the same column
            if (currentSortColumn === sortColumn) {
                sortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
            }
            fetchUsers(1, itemsPerPage, sortColumn, sortDirection, currentSearchQuery);
        });
    });
}

/**
 * Sets up event listeners for user modal related buttons.
 * شنونده‌های رویداد برای دکمه‌های مربوط به مودال کاربر را تنظیم می‌کند.
 */
function setupUserModalButtons() {
    const addUserButton = document.getElementById('add-user-btn');
    if (addUserButton) {
        addUserButton.addEventListener('click', () => window.showUserModal(null)); // Open modal for new user
    }

    const userModalCloseBtn = document.getElementById('user-modal-close-btn');
    const userFormCancelBtn = document.getElementById('user-form-cancel-btn');
    const userModalOverlay = document.getElementById('user-modal-overlay');

    // Close modal listeners
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
}

/**
 * Sets up the event listener for user form submission.
 * شنونده رویداد برای ارسال فرم کاربر را تنظیم می‌کند.
 */
function setupUserFormSubmission() {
    const userForm = document.getElementById('user-form');
    if (userForm) {
        userForm.addEventListener('submit', handleUserFormSubmit);
    }
}

/**
 * Sets up event listeners for bulk actions (select all, delete, activate, deactivate).
 * شنونده‌های رویداد برای عملیات گروهی (انتخاب همه، حذف، فعال‌سازی، غیرفعال‌سازی) را تنظیم می‌کند.
 */
function setupBulkActions() {
    const selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', handleSelectAllClick);
    }

    document.getElementById('bulk-delete')?.addEventListener('click', () => handleBulkAction('delete'));
    document.getElementById('bulk-activate')?.addEventListener('click', () => handleBulkAction('activate'));
    document.getElementById('bulk-deactivate')?.addEventListener('click', () => handleBulkAction('deactivate'));
}

/**
 * Initializes all event listeners and functionalities for the admin panel.
 * تمامی شنونده‌های رویداد و قابلیت‌های پنل ادمین را مقداردهی اولیه می‌کند.
 */
export function setupAdminPanelListeners() {
    // Only set up listeners if we are on an admin path
    if (!window.location.pathname.startsWith('/admin/')) {
        console.log("Not on admin page, skipping admin panel listener setup.");
        return;
    }
    console.log("On admin page, setting up admin panel listeners.");

    setupSidebarToggle();
    setupNotificationDropdown();
    setupReportActions();
    setupClock();
    setupUserSearch();
    setupUserTableSorting();
    setupUserModalButtons();
    setupUserFormSubmission();
    setupBulkActions();
}

// Ensure DOM is fully loaded before running initial setup
window.onload = () => {
    // Check if we are on an admin-related path
    if (window.location.pathname.startsWith('/admin/')) {
        window.showSection('dashboard'); // Default to dashboard on load
        renderActivityLog(); // Render activity log

        // Simulate admin login logging
        const storedAdmin = users.find(u => u.username === currentUser.username);
        if (storedAdmin) {
            logAdminAction(currentUser.username, 'ورود به پنل', 'ورود موفق به سیستم');
        } else {
            showMessage('کاربر ادمین شبیه‌سازی شده یافت نشد. به عنوان کاربر پیش‌فرض عمل می‌کنیم.', 'info');
        }
    }
    setupAdminPanelListeners(); // Setup all general listeners
};
