// resources/js/admin/admin.js

// Import functions from other modules (assuming their new paths)
// charts.js is in resources/js/admin/
import { initializeMonthlySalesChart, updateChartOnResize } from '../admin/charts.js';
import * as jalaali from 'jalaali-js'; // jalaali-js is usually from node_modules, so this path is fine

// The hardcoded user array has been removed as requested.
// The user list will now be initially empty and should be populated from a data source like an API.
export let users = []; // This should ideally be managed via an API call

export function renderActivityLog() {
    const logContainer = document.getElementById('admin-activity-log');
    if (!logContainer) return;

    logContainer.innerHTML = '';
    // adminActivityLog is now accessible via window.adminActivityLog
    if (window.adminActivityLog) { // Ensure window.adminActivityLog exists
        window.adminActivityLog.slice().reverse().forEach(log => {
            const logEntry = document.createElement('div');
            logEntry.className = 'p-2 bg-gray-50 rounded-md text-sm text-gray-700';
            logEntry.innerHTML = `
                <p><span class="font-semibold">${log.username}</span>: ${log.action} - <span class="text-gray-500">${new Date(log.timestamp).toLocaleString('fa-IR')}</span></p>
                <span class="text-xs text-gray-400">${log.details}</span>
            `;
            logContainer.appendChild(logEntry);
        });
    }
}

// window.showSection is defined in app.js, so we call it directly via window
// window.showSection = function(sectionId) { ... }; // This definition is removed from here

let currentPage = 1;
let itemsPerPage = 5;
let currentSortColumn = 'id';
let currentSortDirection = 'asc';
let currentSearchQuery = '';
let selectedUserIds = new Set();

async function fetchUsers(page = currentPage, per_page = itemsPerPage, sort_by = currentSortColumn, sort_direction = currentSortDirection, search = currentSearchQuery) {
    const loadingState = document.getElementById('loading-state');
    const userListBody = document.getElementById('user-list-body');
    const noUsersMessage = document.getElementById('no-users-message');

    if (loadingState) loadingState.classList.remove('hidden');
    if (userListBody) userListBody.innerHTML = '';
    if (noUsersMessage) noUsersMessage.classList.add('hidden');

    try {
        // This is a simulation. In a real application, you would fetch users from an API.
        await new Promise(resolve => setTimeout(resolve, 500));

        const filteredUsers = users.filter(user =>
            user.username.toLowerCase().includes(search.toLowerCase()) ||
            user.email.toLowerCase().includes(search.toLowerCase()) ||
            user.role.toLowerCase().includes(search.toLowerCase())
        );

        const sortedUsers = [...filteredUsers].sort((a, b) => {
            let valA = a[sort_by];
            let valB = b[sort_by];

            if (sort_by === 'id') {
                valA = parseInt(valA);
                valB = parseInt(valB);
            }
            if (sort_by === 'created_at') {
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

        currentPage = page;
        currentSortColumn = sort_by;
        currentSortDirection = sort_direction;
        currentSearchQuery = search;

        renderUserList(paginatedUsers);
        renderPagination(totalPages, currentPage);
        updateSelectedUsersCount();

        if (paginatedUsers.length === 0 && noUsersMessage) {
            noUsersMessage.classList.remove('hidden');
        }

    } catch (error) {
        console.error('Error fetching users:', error);
        window.showMessage('خطا در بارگذاری لیست کاربران.', 'error');
        if (noUsersMessage) noUsersMessage.classList.remove('hidden');
        if (userListBody) userListBody.innerHTML = `<tr><td colspan="9" class="py-3 px-6 text-center text-red-500">خطا در بارگذاری اطلاعات.</td></tr>`;
    } finally {
        if (loadingState) loadingState.classList.add('hidden');
    }
}

function renderUserList(usersToRender) {
    const userListBody = document.getElementById('user-list-body');
    if (!userListBody) return;

    userListBody.innerHTML = '';
    selectedUserIds.clear();
    const selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) selectAllCheckbox.checked = false;
    toggleBulkActionsVisibility();

    if (usersToRender.length === 0) {
        const noUsersMessage = document.getElementById('no-users-message');
        if (noUsersMessage) {
            noUsersMessage.classList.remove('hidden');
        }
        return;
    } else {
        const noUsersMessage = document.getElementById('no-users-message');
        if (noUsersMessage) {
            noUsersMessage.classList.add('hidden');
        }
    }

    usersToRender.forEach(user => {
        const row = document.createElement('tr');
        row.className = 'border-b border-gray-200 hover:bg-gray-50';

        const createdAtDate = new Date(user.created_at);
        const jalaliDate = jalaali.toJalaali(createdAtDate.getFullYear(), createdAtDate.getMonth() + 1, createdAtDate.getDate());
        const formattedDate = `${jalaliDate.jy}/${String(jalaliDate.jm).padStart(2, '0')}/${String(jalaliDate.jd).padStart(2, '0')}`;

        const statusClass = {
            'active': 'bg-green-100 text-green-800',
            'inactive': 'bg-red-100 text-red-800',
            'suspended': 'bg-yellow-100 text-yellow-800'
        }[user.status] || 'bg-gray-100 text-gray-800';

        const roleClass = {
            'admin': 'bg-red-100 text-red-800',
            'user': 'bg-green-100 text-green-800',
            'editor': 'bg-blue-100 text-blue-800',
            'moderator': 'bg-purple-100 text-purple-800'
        }[user.role] || 'bg-gray-100 text-gray-800';

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
                    ${user.status === 'active' ? 'فعال' : user.status === 'inactive' ? 'غیرفعال' : user.status}
                </span>
            </td>
            <td class="py-3 px-6 text-center whitespace-nowrap">
                <div class="flex item-center justify-center space-x-2 space-x-reverse">
                    <button class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center hover:bg-blue-600 transition duration-200 edit-user-btn" data-user-id="${user.id}" title="ویرایش">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600 transition duration-200 delete-user-btn" data-user-id="${user.id}" title="حذف">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </td>
        `;
        userListBody.appendChild(row);
    });

    userListBody.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', handleUserCheckboxClick);
    });

    // Attach listeners for edit/delete buttons using event delegation
    userListBody.querySelectorAll('.edit-user-btn').forEach(button => {
        button.addEventListener('click', (event) => window.showUserModal(parseInt(event.currentTarget.dataset.userId)));
    });
    userListBody.querySelectorAll('.delete-user-btn').forEach(button => {
        button.addEventListener('click', (event) => window.deleteUser(parseInt(event.currentTarget.dataset.userId)));
    });
}

function renderPagination(totalPages, currentPage) {
    const paginationContainer = document.getElementById('pagination-container');
    if (!paginationContainer) return;

    paginationContainer.innerHTML = '';

    if (totalPages <= 1) return;

    const prevButton = document.createElement('button');
    prevButton.className = `px-3 py-1 rounded-md border border-gray-300 ${currentPage === 1 ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-100'}`;
    prevButton.innerHTML = `<i class="fas fa-chevron-right"></i>`;
    prevButton.disabled = currentPage === 1;
    prevButton.addEventListener('click', () => fetchUsers(currentPage - 1));
    paginationContainer.appendChild(prevButton);

    const maxPagesToShow = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

    if (endPage - startPage + 1 < maxPagesToShow) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        const pageButton = document.createElement('button');
        pageButton.className = `px-3 py-1 rounded-md border border-gray-300 mx-1 ${i === currentPage ? 'bg-brown-900 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'}`;
        pageButton.textContent = i.toLocaleString('fa-IR');
        pageButton.addEventListener('click', () => fetchUsers(i));
        paginationContainer.appendChild(pageButton);
    }

    const nextButton = document.createElement('button');
    nextButton.className = `px-3 py-1 rounded-md border border-gray-300 ${currentPage === totalPages ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-100'}`;
    nextButton.innerHTML = `<i class="fas fa-chevron-left"></i>`;
    nextButton.disabled = currentPage === totalPages;
    nextButton.addEventListener('click', () => fetchUsers(currentPage + 1));
    paginationContainer.appendChild(nextButton);
}

window.showUserModal = function(userId = null) {
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

    // بررسی وجود elements ضروری
    if (!modalOverlay || !modalTitle || !passwordInput || !passwordConfirmationInput) {
        console.error('Required modal elements not found');
        window.showMessage('خطا در بارگذاری فرم کاربر.', 'error');
        return;
    }

    resetUserForm();

    if (userId) {
        const user = users.find(u => u.id === userId);
        if (!user) {
            window.showMessage('کاربر یافت نشد.', 'error');
            return;
        }

        modalTitle.textContent = 'ویرایش کاربر';

        // تنظیم مقادیر با بررسی وجود elements
        if (userIdInput) userIdInput.value = user.id;
        if (usernameInput) usernameInput.value = user.username;
        if (emailInput) emailInput.value = user.email;
        if (roleSelect) roleSelect.value = user.role;
        if (statusSelect) statusSelect.value = user.status;
        if (phoneInput) phoneInput.value = user.phone || '';

        console.log('User ID exists. Setting password fields to not required.');

        // اصلاح اصلی - بررسی وجود element قبل از تنظیم required
        if (passwordInput) {
            passwordInput.required = false;
            console.log('passwordInput.required set to false.');
        } else {
            console.error('passwordInput element not found');
        }

        if (passwordConfirmationInput) {
            passwordConfirmationInput.required = false;
            console.log('passwordConfirmationInput.required set to false.');
        } else {
            console.error('passwordConfirmationInput element not found');
        }

        if (passwordRequired) {
            console.log('passwordRequired element found. Hiding it.');
            passwordRequired.classList.add('hidden');
        } else {
            console.log('passwordRequired element NOT found.');
        }

        if (passwordConfirmRequired) {
            console.log('passwordConfirmRequired element found. Hiding it.');
            passwordConfirmRequired.classList.add('hidden');
        } else {
            console.log('passwordConfirmRequired element NOT found.');
        }

        if (formMethodInput) formMethodInput.value = 'PUT';

    } else {
        modalTitle.textContent = 'افزودن کاربر جدید';
        if (userIdInput) userIdInput.value = '';

        console.log('New user. Setting password fields to required.');

        // اصلاح اصلی - بررسی وجود element قبل از تنظیم required
        if (passwordInput) {
            console.log('Attempting to set passwordInput.required = true;');
            passwordInput.required = true;
            console.log('passwordInput.required set to true.');
        } else {
            console.error('passwordInput element not found');
        }

        if (passwordConfirmationInput) {
            console.log('Attempting to set passwordConfirmationInput.required = true;');
            passwordConfirmationInput.required = true;
            console.log('passwordConfirmationInput.required set to true.');
        } else {
            console.error('passwordConfirmationInput element not found');
        }

        if (passwordRequired) {
            console.log('passwordRequired element found. Showing it.');
            passwordRequired.classList.remove('hidden');
        } else {
            console.log('passwordRequired element NOT found.');
        }

        if (passwordConfirmRequired) {
            console.log('passwordConfirmRequired element found. Showing it.');
            passwordConfirmRequired.classList.remove('hidden');
        } else {
            console.log('passwordConfirmRequired element NOT found.');
        }

        if (formMethodInput) formMethodInput.value = 'POST';
    }

    modalOverlay.classList.remove('hidden');
    modalOverlay.classList.add('active');
}

function resetUserForm() {
    const userForm = document.getElementById('user-form');
    if (userForm) {
        userForm.reset();
        userForm.querySelectorAll('.invalid-feedback').forEach(el => el.classList.add('hidden'));
        userForm.querySelectorAll('input, select').forEach(el => el.classList.remove('border-red-500'));
        const formErrorsDiv = document.getElementById('form-errors');
        if (formErrorsDiv) {
            formErrorsDiv.classList.add('hidden');
        }
        const errorList = document.getElementById('error-list');
        if (errorList) { // Fix applied here
            errorList.innerHTML = '';
        }
    }
}

function validateUserForm(form) {
    if (!form) {
        console.error('Form element is null');
        return false;
    }

    let isValid = true;
    const errorList = document.getElementById('error-list');
    const formErrorsDiv = document.getElementById('form-errors');

    if (errorList) {
        errorList.innerHTML = '';
    }
    if (formErrorsDiv) {
        formErrorsDiv.classList.add('hidden');
    }

    // پاک کردن خطاهای قبلی با بررسی وجود elements
    const invalidFeedbacks = form.querySelectorAll('.invalid-feedback');
    invalidFeedbacks.forEach(el => {
        if (el && el.classList) {
            el.classList.add('hidden');
        }
    });

    const formInputs = form.querySelectorAll('input, select');
    formInputs.forEach(el => {
        if (el && el.classList) {
            el.classList.remove('border-red-500');
        }
    });

    // بررسی وجود inputs
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const roleSelect = document.getElementById('role');
    const passwordInput = document.getElementById('password');
    const passwordConfirmationInput = document.getElementById('password_confirmation');
    const phoneInput = document.getElementById('phone');

    if (usernameInput && usernameInput.value.trim() === '') {
        displayFieldError(usernameInput, 'نام کاربری نمی‌تواند خالی باشد.');
        isValid = false;
    }

    if (emailInput && (emailInput.value.trim() === '' || !/\S+@\S+\.\S+/.test(emailInput.value))) {
        displayFieldError(emailInput, 'ایمیل نامعتبر است.');
        isValid = false;
    }

    if (roleSelect && roleSelect.value === '') {
        displayFieldError(roleSelect, 'نقش کاربر را انتخاب کنید.');
        isValid = false;
    }

    const userIdInput = document.getElementById('user-id');
    const isNewUser = !userIdInput || !userIdInput.value;

    if (passwordInput && passwordConfirmationInput) {
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
    }

    if (phoneInput && phoneInput.value.trim() !== '' && !/^09\d{9}$/.test(phoneInput.value)) {
        displayFieldError(phoneInput, 'شماره تلفن نامعتبر است. (مثال: 09123456789)');
        isValid = false;
    }

    if (!isValid && formErrorsDiv) {
        formErrorsDiv.classList.remove('hidden');
    }

    return isValid;
}

function displayFieldError(inputElement, message) {
    // بررسی وجود inputElement
    if (!inputElement) {
        console.error('inputElement is null or undefined');
        return;
    }

    const errorDiv = inputElement.nextElementSibling;
    if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
        errorDiv.textContent = message;
        errorDiv.classList.remove('hidden');
    }

    // اضافه کردن کلاس error با بررسی وجود classList
    if (inputElement.classList) {
        inputElement.classList.add('border-red-500');
    }

    const errorList = document.getElementById('error-list');
    if (errorList) {
        const li = document.createElement('li');
        if (li) {
            li.textContent = message;
            errorList.appendChild(li);
        }
    }
}


async function handleUserFormSubmit(event) {
    event.preventDefault();
    const form = event.target;

    if (!validateUserForm(form)) {
        window.showMessage('لطفاً خطاهای فرم را برطرف کنید.', 'error');
        return;
    }

    // بررسی وجود elements قبل از استفاده
    const userIdInput = document.getElementById('user-id');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const roleSelect = document.getElementById('role');
    const statusSelect = document.getElementById('status');
    const phoneInput = document.getElementById('phone');
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirmation');

    if (!usernameInput || !emailInput || !roleSelect || !statusSelect) {
        window.showMessage('خطا در بارگذاری فرم. لطفاً صفحه را reload کنید.', 'error');
        return;
    }

    const userId = userIdInput ? userIdInput.value : '';
    const username = usernameInput.value;
    const email = emailInput.value;
    const role = roleSelect.value;
    const status = statusSelect.value;
    const phone = phoneInput ? phoneInput.value : '';
    const password = passwordInput ? passwordInput.value : '';

    const userData = { username, email, role, status, phone };
    if (password) {
        userData.password = password;
        if (passwordConfirmInput) {
            userData.password_confirmation = passwordConfirmInput.value;
        }
    }

    const submitBtn = document.getElementById('submit-btn');
    if (!submitBtn) {
        console.error('Submit button not found');
        return;
    }

    const originalBtnText = submitBtn.innerHTML;

    // اصلاح اصلی - بررسی وجود submitBtn قبل از تنظیم properties
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i><span>در حال ذخیره...</span>';
    }


    try {
        await new Promise(resolve => setTimeout(resolve, 800));

        if (userId) {
            const userIndex = users.findIndex(u => u.id === parseInt(userId));
            if (userIndex !== -1) {
                users[userIndex] = { ...users[userIndex], ...userData };
                window.showMessage('کاربر با موفقیت ویرایش شد.', 'success');
                window.logAdminAction(window.currentUser.username, 'ویرایش کاربر', `کاربر ${username} (شناسه: ${userId}) ویرایش شد.`);
            } else {
                window.showMessage('کاربر برای ویرایش یافت نشد.', 'error');
            }
        } else {
            const newId = users.length > 0 ? Math.max(...users.map(u => u.id)) + 1 : 1;
            const newUser = {
                id: newId,
                username: username,
                email: email,
                role: role,
                lastLocation: 'N/A',
                created_at: new Date().toISOString(),
                status: status,
                phone: phone
            };
            users.push(newUser);
            window.showMessage('کاربر جدید با موفقیت اضافه شد.', 'success');
            window.logAdminAction(window.currentUser.username, 'افزودن کاربر', `کاربر ${username} اضافه شد.`);
        }

        const userModalOverlay = document.getElementById('user-modal-overlay');
        if (userModalOverlay) {
            userModalOverlay.classList.add('hidden');
            userModalOverlay.classList.remove('active');
        }

        fetchUsers();

    } catch (error) {
        console.error('Error saving user:', error);
        window.showMessage('خطا در ذخیره کاربر: ' + error.message, 'error');
    } finally {
        // بررسی وجود submitBtn قبل از reset کردن
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    }
}

window.deleteUser = function(userId) {
    const userToDelete = users.find(u => u.id === userId);
    if (!userToDelete) {
        window.showMessage('کاربر یافت نشد.', 'error');
        return;
    }

    // showConfirmationModal is now directly accessible via window
    window.showConfirmationModal(
        'تایید حذف کاربر',
        `آیا از حذف کاربر "${userToDelete.username}" مطمئن هستید؟ این عملیات قابل بازگشت نیست.`,
        async () => {
            try {
                await new Promise(resolve => setTimeout(resolve, 500));

                const userIndex = users.findIndex(user => user.id === userId);

                if (userIndex > -1) {
                    users.splice(userIndex, 1);
                    window.showMessage('کاربر با موفقیت حذف شد.', 'success');
                    window.logAdminAction(window.currentUser.username, 'حذف کاربر', `کاربر ${userToDelete.username} (شناسه: ${userId}) حذف شد.`);
                } else {
                    window.showMessage('خطا در حذف کاربر. کاربر یافت نشد.', 'error');
                }

                fetchUsers(currentPage);
            } catch (error) {
                console.error('Error deleting user:', error);
                window.showMessage('خطا در حذف کاربر: ' + error.message, 'error');
            }
        },
        () => {
            window.showMessage('عملیات حذف کاربر لغو شد.', 'info');
        }
    );
};

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

function updateSelectedUsersCount() {
    const selectAllCheckbox = document.getElementById('select-all');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');

    if (userCheckboxes.length > 0 && selectedUserIds.size === userCheckboxes.length && selectedUserIds.size > 0) {
        if (selectAllCheckbox) selectAllCheckbox.checked = true;
    } else {
        if (selectAllCheckbox) selectAllCheckbox.checked = false;
    }

    toggleBulkActionsVisibility();
}

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

function handleUserCheckboxClick(event) {
    const userId = parseInt(event.target.dataset.userId);
    if (event.target.checked) {
        selectedUserIds.add(userId);
    } else {
        selectedUserIds.delete(userId);
    }
    updateSelectedUsersCount();
}

async function handleBulkAction(actionType) {
    if (selectedUserIds.size === 0) {
        window.showMessage('هیچ کاربری انتخاب نشده است.', 'info');
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

    // showConfirmationModal is now directly accessible via window
    window.showConfirmationModal(
        'تایید عملیات گروهی',
        confirmMessage,
        async () => {
            try {
                await new Promise(resolve => setTimeout(resolve, 800));

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

                window.showMessage(successMessage, 'success');
                window.logAdminAction(window.currentUser.username, logAction, `عملیات روی کاربران: ${usernames}`);
                selectedUserIds.clear();
                fetchUsers();
            } catch (error) {
                console.error('Error performing bulk action:', error);
                window.showMessage('خطا در انجام عملیات گروهی: ' + error.message, 'error');
            }
        },
        () => {
            window.showMessage('عملیات گروهی لغو شد.', 'info');
        }
    );
}

// Main initialization function for the admin panel
export function initAdminPanel() {
    console.log("Admin module initializing...");

    // Initial setup (previously in window.onload)
    if (window.location.pathname.startsWith('/admin/')) {
        window.showSection('dashboard'); // Assuming showSection is a global function from app.js
        renderActivityLog();

        const storedAdmin = users.find(u => u.username === window.currentUser.username);
        if (storedAdmin) {
            window.logAdminAction(window.currentUser.username, 'ورود به پنل', 'ورود موفق به سیستم');
        } else {
            window.showMessage('کاربر ادمین شبیه‌سازی شده یافت نشد. به عنوان کاربر پیش‌فرض عمل می‌کنیم.', 'info');
        }
    }

    // Setup event listeners for the admin panel UI
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
        });

        reportActionsContainer.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                reportActionsContainer.classList.add('hidden');
                reportActionsContainer.classList.remove('active');
                floatingReportToggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

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

    const userSearchInput = document.getElementById('user-search');
    if (userSearchInput) {
        let searchTimeout;
        userSearchInput.addEventListener('keyup', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchUsers(1, itemsPerPage, currentSortColumn, currentSortDirection, userSearchInput.value.trim());
            }, 300);
        });
    }

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

    const addUserButton = document.getElementById('add-user-btn');
    if (addUserButton) {
        addUserButton.addEventListener('click', () => window.showUserModal(null));
    }

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

    const userForm = document.getElementById('user-form');
    if (userForm) {
        userForm.addEventListener('submit', handleUserFormSubmit);
    }

    const selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', handleSelectAllClick);
    }

    document.getElementById('bulk-delete')?.addEventListener('click', () => handleBulkAction('delete'));
    document.getElementById('bulk-activate')?.addEventListener('click', () => handleBulkAction('activate'));
    document.getElementById('bulk-deactivate')?.addEventListener('click', () => handleBulkAction('deactivate'));

    console.log("Admin module initialized successfully.");
}

// Export the initAdminPanel function so app.js can call it
// The previous window.onload block is removed as app.js will handle the loading.
