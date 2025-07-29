{{-- resources/views/partials/_user_management.blade.php --}}

<section id="user-management-content" class="section-content" dir="rtl">
    <h2 class="text-2xl font-semibold mb-4 text-brown-900">مدیریت کاربران</h2>
    
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif
    
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
            <h3 class="text-lg font-semibold text-gray-700">لیست کاربران</h3>
            <div class="flex flex-col md:flex-row gap-2 w-full md:w-auto">
                <!-- Search Input -->
                <input type="text" id="user-search" placeholder="جستجو کاربر..." 
                       class="px-3 py-2 border border-gray-300 rounded-md text-sm w-full md:w-64 focus:ring-2 focus:ring-brown-500 focus:border-brown-500">
                <button id="add-user-btn" class="btn-primary flex items-center justify-center w-full md:w-auto">
                    <i class="fas fa-plus ml-2"></i> افزودن کاربر جدید
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loading-state" class="flex justify-center items-center py-8 hidden">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-brown-900"></div>
            <span class="mr-2 text-gray-600">در حال بارگذاری...</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                <thead>
                    <tr class="bg-gray-100 text-right text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-right">
                            <input type="checkbox" id="select-all" class="rounded text-green-600 focus:ring-green-500">
                        </th>
                        <th class="py-3 px-6 text-right cursor-pointer hover:bg-gray-200 transition-colors duration-200" data-sort="id">
                            شناسه <i class="fas fa-sort ml-1"></i>
                        </th>
                        <th class="py-3 px-6 text-right cursor-pointer hover:bg-gray-200 transition-colors duration-200" data-sort="username">
                            نام کاربری <i class="fas fa-sort ml-1"></i>
                        </th>
                        <th class="py-3 px-6 text-right cursor-pointer hover:bg-gray-200 transition-colors duration-200" data-sort="email">
                            ایمیل <i class="fas fa-sort ml-1"></i>
                        </th>
                        <th class="py-3 px-6 text-right cursor-pointer hover:bg-gray-200 transition-colors duration-200" data-sort="role">
                            نقش <i class="fas fa-sort ml-1"></i>
                        </th>
                        <th class="py-3 px-6 text-right">آخرین مکان</th>
                        <th class="py-3 px-6 text-right cursor-pointer hover:bg-gray-200 transition-colors duration-200" data-sort="created_at">
                            تاریخ ایجاد <i class="fas fa-sort ml-1"></i>
                        </th>
                        <th class="py-3 px-6 text-right cursor-pointer hover:bg-gray-200 transition-colors duration-200" data-sort="status">
                            وضعیت <i class="fas fa-sort ml-1"></i>
                        </th>
                        <th class="py-3 px-6 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody id="user-list-body" class="text-gray-600 text-sm font-light">
                    <!-- User rows will be dynamically loaded here by JavaScript -->
                    <tr id="no-users-message" class="hidden">
                        <td class="py-8 px-6 text-center text-gray-500" colspan="9">
                            هیچ کاربری یافت نشد
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="pagination-container" class="mt-4 flex justify-center items-center space-x-2 space-x-reverse">
            <!-- Pagination will be loaded here by JavaScript -->
        </div>

        <!-- Bulk Actions -->
        <div id="bulk-actions" class="mt-4 hidden p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-sm text-gray-700 font-medium">عملیات گروهی:</span>
                <button id="bulk-delete" class="btn-danger text-sm flex items-center">
                    <i class="fas fa-trash-alt ml-1"></i> حذف انتخاب شده
                </button>
                <button id="bulk-activate" class="btn-success text-sm flex items-center">
                    <i class="fas fa-check-circle ml-1"></i> فعال کردن
                </button>
                <button id="bulk-deactivate" class="btn-warning text-sm flex items-center">
                    <i class="fas fa-times-circle ml-1"></i> غیرفعال کردن
                </button>
            </div>
        </div>
    </div>

    <!-- User Edit/Add Modal (Hidden by default) -->
    <div id="user-modal-overlay" class="custom-modal-overlay hidden">
        <div class="custom-modal-content">
            <button id="user-modal-close-btn" class="custom-modal-close-btn">
                <i class="fas fa-times"></i>
            </button>
            <h3 class="text-2xl font-bold text-brown-900 mb-4" id="user-modal-title">ویرایش کاربر</h3>
            
            <!-- Form Error Messages -->
            <div id="form-errors" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul id="error-list" class="list-disc list-inside"></ul>
            </div>

            <form id="user-form" class="space-y-4">
                @csrf {{-- CSRF Token for Laravel forms --}}
                <input type="hidden" id="user-form-method" name="_method" value="POST"> {{-- For PUT/PATCH requests --}}
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="user-id" class="block text-right text-sm font-medium text-gray-700">شناسه کاربر:</label>
                        <input type="text" id="user-id" name="id" 
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-100 cursor-not-allowed" 
                               readonly>
                    </div>
                    <div>
                        <label for="username" class="block text-right text-sm font-medium text-gray-700">
                            نام کاربری: <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="username" name="username" 
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-2 focus:ring-brown-500 focus:border-brown-500" 
                               required>
                        <div class="invalid-feedback text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-right text-sm font-medium text-gray-700">
                            ایمیل: <span class="text-red-500">*</span>
                        </label>
                        <input type="email" id="email" name="email" 
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-2 focus:ring-brown-500 focus:border-brown-500" 
                               required>
                        <div class="invalid-feedback text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                    <div>
                        <label for="role" class="block text-right text-sm font-medium text-gray-700">
                            نقش: <span class="text-red-500">*</span>
                        </label>
                        <select id="role" name="role" 
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-2 focus:ring-brown-500 focus:border-brown-500" 
                                required>
                            <option value="">انتخاب کنید</option>
                            <option value="admin">مدیر</option>
                            <option value="user">کاربر</option>
                            <option value="editor">ویرایشگر</option>
                            <option value="moderator">مدیر محتوا</option>
                        </select>
                        <div class="invalid-feedback text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div id="password-field">
                        <label for="password" class="block text-right text-sm font-medium text-gray-700">
                            رمز عبور: <span class="text-red-500" id="password-required">*</span>
                        </label>
                        <input type="password" id="password" name="password" 
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-2 focus:ring-brown-500 focus:border-brown-500">
                        <div class="invalid-feedback text-red-500 text-sm mt-1 hidden"></div>
                        <small class="text-gray-500">حداقل 8 کاراکتر</small>
                    </div>
                    <div id="password-confirm-field">
                        <label for="password_confirmation" class="block text-right text-sm font-medium text-gray-700">
                            تکرار رمز عبور: <span class="text-red-500" id="password-confirm-required">*</span>
                        </label>
                        <input type="password" id="password_confirmation" name="password_confirmation" 
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-2 focus:ring-brown-500 focus:border-brown-500">
                        <div class="invalid-feedback text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="status" class="block text-right text-sm font-medium text-gray-700">وضعیت:</label>
                        <select id="status" name="status" 
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-2 focus:ring-brown-500 focus:border-brown-500">
                            <option value="active">فعال</option>
                            <option value="inactive">غیرفعال</option>
                            <option value="suspended">معلق</option>
                        </select>
                    </div>
                    <div>
                        <label for="phone" class="block text-right text-sm font-medium text-gray-700">شماره تلفن:</label>
                        <input type="tel" id="phone" name="phone" 
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-2 focus:ring-brown-500 focus:border-brown-500"
                               placeholder="09123456789">
                        <div class="invalid-feedback text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                </div>

                <div class="flex justify-end gap-4 mt-6">
                    <button type="submit" class="btn-primary flex items-center" id="submit-btn">
                        <i class="fas fa-save ml-2"></i>
                        <span>ذخیره تغییرات</span>
                    </button>
                    <button type="button" id="user-form-cancel-btn" class="btn-secondary flex items-center">
                        <i class="fas fa-times ml-2"></i>
                        لغو
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal (for delete/bulk actions) -->
    <div id="confirm-modal-overlay" class="custom-modal-overlay hidden">
        <div class="custom-modal-content max-w-md text-center">
            <h3 class="text-2xl font-bold text-brown-900 mb-4">تایید عملیات</h3>
            <p id="confirm-message" class="text-gray-700 text-lg mb-8"></p>
            <div class="flex justify-center gap-6">
                <button id="confirm-yes" class="btn-danger flex items-center justify-center min-w-[100px]">
                    <i class="fas fa-check ml-2"></i> بله
                </button>
                <button id="confirm-no" class="btn-secondary flex items-center justify-center min-w-[100px]">
                    <i class="fas fa-times ml-2"></i> خیر
                </button>
            </div>
        </div>
    </div>
</section>

{{-- Inline styles moved to app.css or a dedicated CSS file are preferred --}}
<style>
/* Custom Button Styles (from your app.css, ensuring consistency) */
.btn-primary {
    background-color: #38a169; /* green-700 */
    color: white;
    padding: 0.75rem 1.5rem; /* py-3 px-6 */
    border-radius: 0.5rem; /* rounded-lg */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* shadow-md */
    transition: all 0.3s ease-in-out;
    transform: translateY(0);
}

.btn-primary:hover {
    background-color: #2f855a; /* green-800 */
    transform: translateY(-2px); /* hover:-translate-y-1 */
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.15); /* hover:shadow-lg */
}

.btn-secondary {
    background-color: #ffffff; /* white */
    color: #38a169; /* green-700 */
    border: 1px solid #38a169; /* border border-green-700 */
    font-weight: 600; /* font-semibold */
    padding: 0.75rem 1.5rem; /* py-3 px-6 */
    border-radius: 0.5rem; /* rounded-lg */
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05); /* shadow-sm */
    transition: all 0.3s ease-in-out;
}

.btn-secondary:hover {
    background-color: #f0fdf4; /* green-50 */
}

.btn-danger {
    background-color: #ef4444; /* red-500 */
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    border: none;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-danger:hover {
    background-color: #dc2626; /* red-600 */
}

.btn-success {
    background-color: #22c55e; /* green-500 */
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    border: none;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-success:hover {
    background-color: #16a34a; /* green-600 */
}

.btn-warning {
    background-color: #f59e0b; /* amber-500 */
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    border: none;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-warning:hover {
    background-color: #d97706; /* amber-600 */
}

/* Custom Modal Styles (from app.blade.php / app.css) */
.custom-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
    backdrop-filter: blur(5px);
}
.custom-modal-overlay.active {
    opacity: 1;
    visibility: visible;
}
.custom-modal-content {
    background-color: #fff;
    padding: 3rem;
    border-radius: 1rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    text-align: center;
    max-width: 500px;
    width: 90%;
    position: relative;
    transform: translateY(-20px);
    transition: transform 0.3s ease;
}
.custom-modal-overlay.active .custom-modal-content {
    transform: translateY(0);
}
.custom-modal-close-btn {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6B7280;
    transition: color 0.2s ease;
}
.custom-modal-close-btn:hover {
    color: #EF4444;
}

/* Specific styles for brown color from tailwind.config.js */
.text-brown-900 {
    color: #4a2c2a;
}
.focus\:ring-brown-500:focus {
    --tw-ring-color: #6f4e37; /* brown-800 or brown-500 if defined */
}
.focus\:border-brown-500:focus {
    border-color: #6f4e37; /* brown-800 or brown-500 if defined */
}
</style>
