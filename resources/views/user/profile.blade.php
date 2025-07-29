@extends('layouts.app') {{-- Assumes you have a layout named app.blade.php --}}

@section('head')
    {{-- Vazirmatn font and Font Awesome are loaded via app.blade.php, no need to repeat --}}
    {{-- Custom Modal styles are used from app.blade.php. Therefore, duplicate Modal styles have been removed from here. --}}
@endsection

@section('content')
    <div class="container mx-auto p-4 sm:p-6 lg:p-8">
        <div class="max-w-4xl mx-auto bg-white p-6 sm:p-8 rounded-xl shadow-lg border border-gray-200">
            <h2 class="text-3xl font-extrabold text-gray-900 text-center mb-8">پروفایل شخصی</h2>

            {{-- User information section at the top of the page (as per screenshot) --}}
            <div class="flex flex-col items-center justify-center mb-8 pb-8 border-b border-gray-200">
                {{-- User icon instead of profile picture --}}
                <div class="w-24 h-24 rounded-full bg-gray-200 flex items-center justify-center mb-4">
                    <i class="fas fa-user-circle text-6xl text-gray-500"></i>
                </div>
                {{-- Full name of the user --}}
                <h3 class="text-2xl font-bold text-gray-800 mb-1">{{ $user->full_name ?? 'علی احمدی' }}</h3>
                {{-- User's mobile number --}}
                <p class="text-gray-600 mb-2">{{ $user->mobile_number ?? '09123456789' }}</p>
                {{-- User's role --}}
                <span class="bg-green-100 text-green-800 text-sm font-medium px-3 py-1 rounded-full">مشتری عادی</span>
            </div>

            {{-- Main Profile Form for Personal and Contact Information --}}
            <form id="mainProfileForm" action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT') {{-- Assuming a PUT method for profile updates --}}

                {{-- Contact and personal information sections in two columns (as per screenshot) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    {{-- Personal Information Card (moved to the left) --}}
                    <div class="bg-gray-50 p-6 rounded-xl shadow-sm border border-gray-100">
                        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-user ml-2 text-green-600"></i> اطلاعات شخصی
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">نام:</label>
                                <input type="text" id="first_name" name="first_name" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors duration-200" value="{{ explode(' ', $user->full_name ?? 'علی احمدی')[0] ?? 'علی' }}">
                            </div>
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">نام خانوادگی:</label>
                                <input type="text" id="last_name" name="last_name" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors duration-200" value="{{ explode(' ', $user->full_name ?? 'علی احمدی')[1] ?? 'احمدی' }}">
                            </div>
                            <div>
                                <label for="national_code" class="block text-sm font-medium text-gray-700 mb-1">کد ملی:</label>
                                <input type="text" id="national_code" name="national_code" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors duration-200" value="{{ $user->national_code ?? '1234567890' }}">
                            </div>
                            <div>
                                <label for="birthDateInput" class="block text-sm font-medium text-gray-700 mb-1">تاریخ تولد:</label>
                                <div class="flex items-center justify-between">
                                    <input type="text" id="birthDateInput" name="birth_date_display" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors duration-200" value="{{ $user->birth_date ?? '1368/05/15' }}" readonly>
                                    <button type="button" class="text-green-500 hover:text-green-700 mr-2 p-2 rounded-full hover:bg-gray-200 transition-colors duration-200" onclick="showBirthDateModal()">
                                        <i class="fas fa-pencil-alt text-sm"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Contact Information Card (moved to the right) --}}
                    <div class="bg-gray-50 p-6 rounded-xl shadow-sm border border-gray-100">
                        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-phone-alt ml-2 text-green-600"></i> اطلاعات تماس
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label for="mobile_number_display" class="block text-sm font-medium text-gray-700 mb-1">تلفن همراه:</label>
                                <div class="flex items-center justify-between">
                                    <input type="text" id="mobile_number_display" name="mobile_number_display" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors duration-200" value="{{ $user->mobile_number ?? '09123456789' }}" readonly>
                                    <button type="button" class="text-green-500 hover:text-green-700 mr-2 p-2 rounded-full hover:bg-gray-200 transition-colors duration-200" onclick="showMobileEditModal()">
                                        <i class="fas fa-pencil-alt text-sm"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">ایمیل:</label>
                                <input type="email" id="email" name="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors duration-200" value="{{ $user->email ?? 'ali.ahmadi@email.com' }}">
                            </div>
                            <div>
                                <label for="fixed_phone" class="block text-sm font-medium text-gray-700 mb-1">تلفن ثابت:</label>
                                <input type="text" id="fixed_phone" name="fixed_phone" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors duration-200" value="{{ $user->fixed_phone ?? '021-12345678' }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Legal Information Section (as per screenshot) --}}
                <div class="bg-gray-50 p-6 rounded-xl shadow-sm border border-gray-100 mb-8">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-balance-scale ml-2 text-green-600"></i> اطلاعات حقوقی
                    </h3>
                    <p class="text-gray-700 mb-2 text-sm">این گزینه برای کسانی است که نیاز به خرید سازمانی (با فاکتور رسمی) و گواهی ارزش افزوده دارند.</p>
                    {{-- "Register Legal Information" button changed to a link and moved to the end of the sentence --}}
                    <a href="#" class="text-blue-600 hover:underline text-sm" onclick="showLegalInfoModal()">ثبت اطلاعات حقوقی</a>
                </div>

                {{-- "Change Password" and "Delete Account" sections have been removed from here (as per screenshot) --}}

                {{-- Save Information Button --}}
                <div class="flex justify-center mt-8">
                    <button type="submit" class="btn-primary w-full md:w-auto px-8 py-3">
                        ثبت اطلاعات
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Birth Date Modal --}}
    {{-- modal-overlay and modal-content classes have been changed to custom-modal-overlay and custom-modal-content to match app.blade.php. --}}
    <div id="birthDateModal" class="custom-modal-overlay">
        <div class="custom-modal-content">
            <button class="custom-modal-close-btn" onclick="hideBirthDateModal()">
                <i class="fas fa-times"></i>
            </button>
            {{-- Title changed to match the image --}}
            <h3 class="text-2xl font-bold text-gray-800 mb-4 text-center">ثبت اطلاعات شناسایی</h3>
            {{-- Descriptive text added to match the image --}}
            <p class="text-gray-700 mb-6 text-center text-sm">لطفا اطلاعات شناسایی خود را وارد کنید. نام و نام خانوادگی شما باید با اطلاعاتی که وارد می‌کنید همخوانی داشته باشد.</p>

            {{-- The image also shows Name and Last Name fields, but as the request was specifically for the birth date field,
                 and the current modal is only for birth date, I'm keeping the focus on the date fields.
                 If you need to add Name/Last Name fields here, please specify. --}}

            <form id="birthDateForm" class="space-y-4" action="{{ route('profile.birth-date.update') }}" method="POST">
                @csrf
                @method('PUT') {{-- This method is necessary for consistency with Route::put in web.php --}}
                {{-- Hidden input to store the Shamsi date in YYYY-MM-DD format for database submission --}}
                <input type="hidden" id="shamsiFormattedDate" name="shamsi_birth_date_formatted">

                {{-- "تاریخ تولد" heading added to match the image --}}
                <h4 class="text-xl font-bold text-gray-800 mb-4 mt-6">تاریخ تولد:</h4>
                <div class="flex gap-4">
                    <div class="flex-1">
                        <label for="birthYear" class="block text-sm font-medium text-gray-700 mb-2">سال:</label>
                        <select id="birthYear" name="shamsi_birth_year" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors duration-200" required>
                            <option value="">سال</option>
                            {{-- Years are populated by JavaScript --}}
                        </select>
                    </div>
                    <div class="flex-1">
                        <label for="birthMonth" class="block text-sm font-medium text-gray-700 mb-2">ماه:</label>
                        <select id="birthMonth" name="shamsi_birth_month" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors duration-200" required>
                            <option value="">ماه</option>
                            <option value="01">فروردین</option>
                            <option value="02">اردیبهشت</option>
                            <option value="03">خرداد</option>
                            <option value="04">تیر</option>
                            <option value="05">مرداد</option>
                            <option value="06">شهریور</option>
                            <option value="07">مهر</option>
                            <option value="08">آبان</option>
                            <option value="09">آذر</option>
                            <option value="10">دی</option>
                            <option value="11">بهمن</option>
                            <option value="12">اسفند</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label for="birthDay" class="block text-sm font-medium text-gray-700 mb-2">روز:</label>
                        <select id="birthDay" name="shamsi_birth_day" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors duration-200" required>
                            <option value="">روز</option>
                            {{-- Days are populated by JavaScript --}}
                        </select>
                    </div>
                </div>
                {{-- "تایید" button to submit the form --}}
                <button type="submit" class="btn-primary w-full mt-6">تایید</button>
            </form>
        </div>
    </div>

    {{-- Legal Information Modal --}}
    {{-- modal-overlay and modal-content classes have been changed to custom-modal-overlay and custom-modal-content. --}}
    <div id="legalInfoModal" class="custom-modal-overlay">
        <div class="custom-modal-content">
            <button class="custom-modal-close-btn" onclick="hideLegalInfoModal()">
                <i class="fas fa-times"></i>
            </button>
            <h3 class="text-2xl font-bold text-gray-800 mb-6">ویرایش اطلاعات حقوقی</h3>
            <form id="legalInfoForm" class="space-y-4" action="{{ route('profile.legal-info.store') }}" method="POST">
                @csrf
                {{-- @method('PUT') --}} {{-- This line was removed because the route in web.php is defined as POST --}}
                <div>
                    <label for="fullName" class="block text-sm font-medium text-gray-700 mb-2">نام و نام خانوادگی:</label>
                    <input type="text" id="fullName" name="full_name" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors duration-200" value="{{ $user->full_name ?? '' }}" required>
                </div>
                <div>
                    <label for="nationalCode" class="block text-sm font-medium text-gray-700 mb-2">کد ملی:</label>
                    <input type="text" id="nationalCode" name="national_code" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors duration-200" value="{{ $user->national_code ?? '' }}" required>
                </div>
                <div>
                    <label for="shebaNumber" class="block text-sm font-medium text-gray-700 mb-2">شماره شبا:</label>
                    <input type="text" id="shebaNumber" name="sheba_number" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors duration-200" value="{{ $user->sheba_number ?? '' }}">
                </div>
                <div>
                    <label for="cardNumber" class="block text-sm font-medium text-gray-700 mb-2">شماره کارت:</label>
                    <input type="text" id="cardNumber" name="card_number" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors duration-200" value="{{ $user->card_number ?? '' }}">
                </div>
                <div>
                    <label for="province" class="block text-sm font-medium text-gray-700 mb-2">استان:</label>
                    <input type="text" id="province" name="province" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors duration-200" value="{{ $user->province ?? '' }}">
                </div>
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-2">شهر:</label>
                    <input type="text" id="city" name="city" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors duration-200" value="{{ $user->city ?? '' }}">
                </div>
                <div>
                    <label for="postalCode" class="block text-sm font-medium text-gray-700 mb-2">کد پستی:</label>
                    <input type="text" id="postalCode" name="postal_code" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors duration-200" value="{{ $user->postal_code ?? '' }}">
                </div>
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">آدرس کامل:</label>
                    <textarea id="address" name="address" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors duration-200" rows="3">{{ $user->address ?? '' }}</textarea>
                </div>
                <button type="submit" class="btn-primary w-full">ذخیره اطلاعات</button>
            </form>
        </div>
    </div>

    {{-- Mobile Number Edit Modal --}}
    {{-- modal-overlay and modal-content classes have been changed to custom-modal-overlay and custom-modal-content. --}}
    <div id="mobileEditModal" class="custom-modal-overlay">
        <div class="custom-modal-content">
            <button class="custom-modal-close-btn" onclick="hideMobileEditModal()">
                <i class="fas fa-times"></i>
            </button>
            <h3 class="text-2xl font-bold text-gray-800 mb-6">ویرایش شماره موبایل</h3>
            <form id="mobileEditForm" class="space-y-4" action="{{ route('profile.mobile.update') }}" method="POST">
                @csrf
                @method('PUT') {{-- This method is necessary for consistency with Route::put in web.php --}}
                <div>
                    <label for="mobileNumber" class="block text-sm font-medium text-gray-700 mb-2">شماره موبایل جدید:</label>
                    <input type="text" id="mobileNumber" name="mobile_number" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600 transition-colors duration-200" value="{{ $user->mobile_number ?? '' }}" required>
                </div>
                <button type="submit" class="btn-primary w-full">ذخیره شماره موبایل</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Include jalaali-js library --}}
    <script src="https://cdn.jsdelivr.net/npm/jalaali-js@1.2.6/dist/jalaali.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // General function to show modal
            // 'active' class is used instead of 'show' to match app.blade.php styles.
            function showModal(modalId) {
                document.getElementById(modalId).classList.add('active');
            }

            // General function to hide modal
            // 'active' class is used instead of 'show'.
            function hideModal(modalId) {
                document.getElementById(modalId).classList.remove('active');
            }

            // Specific modal functions for calling from HTML
            window.showBirthDateModal = function() {
                showModal('birthDateModal');
            }

            window.hideBirthDateModal = function() {
                hideModal('birthDateModal');
            }

            window.showLegalInfoModal = function() {
                showModal('legalInfoModal');
            }

            window.hideLegalInfoModal = function() {
                hideModal('legalInfoModal');
            }

            window.showMobileEditModal = function() {
                showModal('mobileEditModal');
            }

            window.hideMobileEditModal = function() {
                hideModal('mobileEditModal');
            }

            // Logic for populating date dropdowns
            const birthYearSelect = document.getElementById('birthYear');
            const birthMonthSelect = document.getElementById('birthMonth');
            const birthDaySelect = document.getElementById('birthDay');
            const shamsiFormattedDateInput = document.getElementById('shamsiFormattedDate');
            const birthDateDisplayInput = document.getElementById('birthDateInput'); // The input on the main profile page

            // Populate years (Shamsi years from 1404 down to 80 years before it)
            const currentShamsiYear = 1404; // As requested, up to 1404
            const startShamsiYear = currentShamsiYear - 80; // 80 years before 1404 = 1324

            for (let i = currentShamsiYear; i >= startShamsiYear; i--) {
                const option = document.createElement('option');
                option.value = i; // Store Shamsi year as value
                option.textContent = i; // Display Shamsi year to the user
                birthYearSelect.appendChild(option);
            }

            // Function to populate days based on Shamsi year and month
            function populateDays(shamsiYear, shamsiMonth) {
                birthDaySelect.innerHTML = '<option value="">روز</option>'; // Clear previous days

                if (!shamsiYear || !shamsiMonth) {
                    return; // Don't populate days if year or month are not selected
                }

                let daysInMonth = 31;
                shamsiMonth = parseInt(shamsiMonth); // Ensure month is an integer
                shamsiYear = parseInt(shamsiYear); // Ensure year is an integer

                if (shamsiMonth >= 1 && shamsiMonth <= 6) {
                    daysInMonth = 31;
                } else if (shamsiMonth >= 7 && shamsiMonth <= 11) {
                    daysInMonth = 30;
                } else if (shamsiMonth === 12) {
                    // Use jalaali.isLeapJalaliYear for accurate Esfand days
                    daysInMonth = jalaali.isLeapJalaliYear(shamsiYear) ? 30 : 29;
                }

                for (let i = 1; i <= daysInMonth; i++) {
                    const dayNumber = i.toString().padStart(2, '0');
                    const option = document.createElement('option');
                    option.value = dayNumber;
                    option.textContent = dayNumber;
                    birthDaySelect.appendChild(option);
                }
            }

            // Function to update the hidden Shamsi formatted date input and the display input
            function updateShamsiFormattedDateInput() {
                const shamsiYear = birthYearSelect.value;
                const shamsiMonth = birthMonthSelect.value;
                const shamsiDay = birthDaySelect.value;

                if (shamsiYear && shamsiMonth && shamsiDay) {
                    // Format the selected Shamsi date as YYYY-MM-DD for database submission
                    const formattedDateForDB = `${shamsiYear}-${String(shamsiMonth).padStart(2, '0')}-${String(shamsiDay).padStart(2, '0')}`;
                    shamsiFormattedDateInput.value = formattedDateForDB;

                    // Format the selected Shamsi date as YYYY/MM/DD for display on the main profile page
                    const formattedDateForDisplay = `${shamsiYear}/${String(shamsiMonth).padStart(2, '0')}/${String(shamsiDay).padStart(2, '0')}`;
                    birthDateDisplayInput.value = formattedDateForDisplay;

                } else {
                    shamsiFormattedDateInput.value = ''; // Clear if date is incomplete
                    // Optionally clear or set a default for the display input
                    // birthDateDisplayInput.value = '';
                }
            }

            // Set initial values for dropdowns and hidden input on page load
            // Assuming $user->birth_date from backend is now in Shamsi YYYY-MM-DD format.
            const initialBirthDateFromDB = "{{ $user->birth_date ?? '' }}";

            if (initialBirthDateFromDB) {
                const parts = initialBirthDateFromDB.split('-'); // Assuming YYYY-MM-DD format from DB
                if (parts.length === 3) {
                    const shamsiYearFromDb = parseInt(parts[0]);
                    const shamsiMonthFromDb = parseInt(parts[1]);
                    const shamsiDayFromDb = parseInt(parts[2]);

                    birthYearSelect.value = shamsiYearFromDb;
                    birthMonthSelect.value = String(shamsiMonthFromDb).padStart(2, '0');

                    // Populate days based on the initial Shamsi year and month
                    populateDays(birthYearSelect.value, birthMonthSelect.value);

                    // Set the initial day after days are populated
                    birthDaySelect.value = String(shamsiDayFromDb).padStart(2, '0');

                    // Also set the hidden formatted Shamsi date and the display input
                    updateShamsiFormattedDateInput();
                }
            } else {
                // Initial population if no date exists
                populateDays(birthYearSelect.value, birthMonthSelect.value);
                updateShamsiFormattedDateInput();
            }


            // Event listeners for month, year, and day changes to update formatted Shamsi input and display
            birthYearSelect.addEventListener('change', () => {
                populateDays(birthYearSelect.value, birthMonthSelect.value);
                updateShamsiFormattedDateInput();
            });
            birthMonthSelect.addEventListener('change', () => {
                populateDays(birthYearSelect.value, birthMonthSelect.value);
                updateShamsiFormattedDateInput();
            });
            birthDaySelect.addEventListener('change', () => {
                updateShamsiFormattedDateInput();
            });


            // Form submission functions for AJAX
            document.getElementById('birthDateForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                // Log formData entries for debugging
                for (let [key, value] of formData.entries()) {
                    console.log(`${key}: ${value}`);
                }

                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.showMessage(data.message, 'success');
                        hideBirthDateModal();
                        // Update displayed date on the main profile page with the formatted Shamsi date
                        // Assuming data.birth_date will be the formatted Shamsi date from backend
                        birthDateDisplayInput.value = data.birth_date;
                    } else {
                        window.showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    window.showMessage('خطا در ذخیره تاریخ تولد.', 'error');
                });
            });

            document.getElementById('legalInfoForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.showMessage(data.message, 'success');
                        hideLegalInfoModal();
                        // Optional: update displayed information
                        location.reload(); // Simple refresh to update all information
                    } else {
                        window.showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    window.showMessage('خطا در ذخیره اطلاعات حقوقی.', 'error');
                });
            });

            document.getElementById('mobileEditForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.showMessage(data.message, 'success');
                        hideMobileEditModal();
                        document.getElementById('mobile_number_display').value = data.mobile_number; // Update displayed mobile number
                    } else {
                        window.showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    window.showMessage('خطا در ذخیره شماره موبایل.', 'error');
                });
            });
        });
    </script>
@endpush
