@extends('layouts.app')

@section('title', 'ثبت‌نام کاربر جدید - چای ابراهیم')

@section('content')
    <section class="container mx-auto px-4 py-8 md:py-16 max-w-md">
        <div class="w-full bg-white dark:bg-gray-800 shadow-xl rounded-lg p-8 sm:p-10 border border-gray-200 dark:border-gray-700" dir="rtl">
            <div class="text-center mb-8">
                <h2 class="2xl font-extrabold text-gray-900 dark:text-white mb-2">
                    ثبت‌نام کاربر جدید
                </h2>
                <p class="text-md text-gray-600 dark:text-gray-400">
                    لطفاً اطلاعات خود را برای ثبت‌نام وارد کنید.
                </p>
                {{-- نمایش شماره موبایل در صورتی که از MobileAuthController به اینجا هدایت شده باشد --}}
                @if (isset($mobileNumber) && !empty($mobileNumber))
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{-- Displaying verified mobile number if redirected from MobileAuthController --}}
                        شماره موبایل تأیید شده: <span class="font-bold text-gray-800 dark:text-gray-200">{{ $mobileNumber }}</span>
                    </p>
                @endif
            </div>

            {{-- نمایش پیام‌های وضعیت از سشن (مانند ثبت‌نام موفق) --}}
            @if (session('status'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">موفقیت!</strong>
                    <span class="block sm:inline">{{ session('status') }}</span>
                </div>
            @endif

            {{-- نمایش خطاهای اعتبارسنجی از سرور --}}
            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">خطا!</strong>
                    <ul class="mt-3 list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- اکشن فرم به مسیر صحیح برای درخواست POST بر اساس web.php --}}
            <form id="register-form" class="space-y-6"> {{-- Removed method="POST" action="..." --}}
                @csrf {{-- توکن CSRF برای فرم‌های Laravel --}}

                <!-- Name Input -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        نام
                    </label>
                    <input id="name"
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400"
                           type="text"
                           name="name"
                           value="{{ old('name') }}"
                           required
                           autofocus
                           placeholder="نام خود را وارد کنید">
                    {{-- نمایش خطای اعتبارسنجی برای فیلد نام --}}
                    @error('name')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Last Name Input -->
                <div>
                    <label for="lastname" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        نام خانوادگی (اختیاری)
                    </label>
                    <input id="lastname"
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400"
                           type="text"
                           name="lastname"
                           value="{{ old('lastname') }}"
                           placeholder="نام خانوادگی خود را وارد کنید">
                    {{-- نمایش خطای اعتبارسنجی برای فیلد نام خانوادگی --}}
                    @error('lastname')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Mobile Number Input -->
                <div>
                    <label for="mobile_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        شماره موبایل
                    </label>
                    <input id="mobile_number"
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400"
                           type="text"
                           name="mobile_number"
                           value="{{ $mobileNumber ?? old('mobile_number') }}" {{-- Pre-fill if redirected from login --}}
                           required
                           placeholder="مثال: 09123456789">
                    {{-- نمایش خطای اعتبارسنجی برای فیلد شماره موبایل --}}
                    @error('mobile_number')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-center mt-6">
                    <button type="button" id="register-button" {{-- Changed type to button and added ID --}}
                            class="inline-flex items-center justify-center px-6 py-3 text-lg font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300 ease-in-out min-w-[180px]">
                        ثبت‌نام و دریافت کد
                        <i class="fas fa-paper-plane mr-2"></i> {{-- آیکون ارسال --}}
                    </button>
                </div>

                <div class="flex items-center justify-center mt-4">
                    {{-- لینک به‌روز شده به مسیر صحیح برای صفحه ورود بر اساس web.php --}}
                    <a href="{{ route('auth.mobile-login-form') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900/20 rounded-lg transition-all duration-200 ease-in-out">
                        بازگشت به صفحه ورود
                    </a>
                </div>
            </form>
        </div>
    </section>
@endsection

@push('scripts')
    <script type="module">
        // Import API functions from api.js
        import { sendOtp } from '{{ asset('js/api.js') }}'; // Adjust path if needed

        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('name');
            const lastnameInput = document.getElementById('lastname');
            const mobileNumberInput = document.getElementById('mobile_number');
            const registerButton = document.getElementById('register-button');
            const registerButtonOriginalText = registerButton.innerHTML; // Store original text

            // Function to convert Persian/Arabic digits to English and remove non-digits
            const convertAndFilterDigits = (value) => {
                const persianToEnglishMap = {
                    '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
                    '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
                    '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
                    '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9'
                };
                let convertedValue = '';
                for (let i = 0; i < value.length; i++) {
                    const char = value[i];
                    convertedValue += persianToEnglishMap[char] || char;
                }
                // Remove any non-digit characters after conversion
                return convertedValue.replace(/\D/g, '');
            };

            // Apply digit conversion and filtering to mobile number input
            if (mobileNumberInput) {
                mobileNumberInput.addEventListener('input', function(event) {
                    event.target.value = convertAndFilterDigits(event.target.value);
                });
            }

            // Apply digit conversion and filtering to name and lastname inputs (if needed, though usually not digits)
            // This is generally for consistency if you expect mixed input.
            if (nameInput) {
                nameInput.addEventListener('input', function(event) {
                    // Example: Only allow Persian/English letters and spaces for names
                    event.target.value = event.target.value.replace(/[^a-zA-Z\u0600-\u06FF\s]/g, '');
                });
            }
            if (lastnameInput) {
                lastnameInput.addEventListener('input', function(event) {
                    event.target.value = event.target.value.replace(/[^a-zA-Z\u0600-\u06FF\s]/g, '');
                });
            }


            if (registerButton) {
                registerButton.addEventListener('click', async function() {
                    const name = nameInput.value;
                    const lastname = lastnameInput.value;
                    const mobileNumber = mobileNumberInput.value;

                    // Basic client-side validation
                    if (!name || name.trim() === '') {
                        window.showMessage('لطفاً نام خود را وارد کنید.', 'error');
                        return;
                    }
                    if (!mobileNumber || !/^09\d{9}$/.test(mobileNumber)) {
                        window.showMessage('لطفاً یک شماره موبایل معتبر وارد کنید (مثال: 09123456789).', 'error');
                        return;
                    }

                    // Show loading state
                    registerButton.disabled = true;
                    registerButton.classList.add('opacity-50', 'cursor-not-allowed');
                    registerButton.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال ثبت‌نام...';

                    try {
                        // Send registration data to the backend.
                        // Assuming sendOtp function in api.js can handle additional registration data
                        // or you might need a separate register API call in api.js if your backend
                        // has a dedicated register endpoint that takes name/lastname.
                        // For now, we'll assume sendOtp is smart enough to handle new user registration
                        // based on the MobileAuthController@sendOtp logic.
                        const response = await sendOtp(mobileNumber, {
                            name: name,
                            lastname: lastname,
                            // mobile_number is already passed as first argument
                        });

                        window.showMessage(response.message || 'ثبت‌نام با موفقیت انجام شد. کد تأیید ارسال شد.', 'success');

                        // Redirect to OTP verification page
                        window.location.href = `{{ route('auth.verify-otp-form') }}?mobile_number=${mobileNumber}`;

                    } catch (error) {
                        const errorMessage = error.response?.data?.message || 'خطا در ثبت‌نام. لطفاً دوباره تلاش کنید.';
                        window.showMessage(errorMessage, 'error');
                        console.error('Error during registration:', error);
                        // If there are specific error fields, you might want to highlight them
                        // For example: if (error.response?.data?.error_field === 'mobile_number') { /* highlight input */ }
                    } finally {
                        // Hide loading state
                        registerButton.disabled = false;
                        registerButton.classList.remove('opacity-50', 'cursor-not-allowed');
                        registerButton.innerHTML = registerButtonOriginalText;
                    }
                });
            }
        });
    </script>
@endpush
