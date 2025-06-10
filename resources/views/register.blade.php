@extends('layouts.app')

@section('title', 'ثبت نام - چای ابراهیم')

@section('content')
    <main class="flex-grow flex items-center justify-center p-6">
        <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100 w-full max-w-lg">
            <h2 class="text-4xl font-bold text-center text-brown-900 mb-10 section-heading">ثبت نام کاربر جدید</h2>
            <form id="register-form" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Full Name -->
                    <div>
                        <label for="full-name" class="block text-lg font-semibold text-brown-900 mb-2">نام کامل:</label>
                        <input type="text" id="full-name" name="fullName"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-800 focus:border-transparent text-gray-800"
                               placeholder="نام و نام خانوادگی خود را وارد کنید" required minlength="3">
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-lg font-semibold text-brown-900 mb-2">ایمیل:</label>
                        <input type="email" id="email" name="email"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-800 focus:border-transparent text-gray-800"
                               placeholder="example@example.com" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Password -->
                    <div class="relative">
                        <label for="password" class="block text-lg font-semibold text-brown-900 mb-2">رمز عبور:</label>
                        <input type="password" id="password" name="password"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-800 focus:border-transparent text-gray-800"
                               placeholder="حداقل 8 کاراکتر (حروف و اعداد)" required minlength="8">
                        <span id="toggle-password" class="absolute left-3 top-1/2 transform translate-y-3/4 cursor-pointer text-gray-500">
                            <i class="far fa-eye"></i>
                        </span>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="confirm-password" class="block text-lg font-semibold text-brown-900 mb-2">تأیید رمز عبور:</label>
                        <input type="password" id="confirm-password" name="confirmPassword"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-800 focus:border-transparent text-gray-800"
                               placeholder="رمز عبور خود را دوباره وارد کنید" required>
                    </div>
                </div>

                <!-- Phone Number -->
                <div>
                    <label for="phone-number" class="block text-lg font-semibold text-brown-900 mb-2">شماره تلفن:</label>
                    <input type="tel" id="phone-number" name="phoneNumber"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-800 focus:border-transparent text-gray-800"
                           placeholder="مثال: 09123456789" required pattern="^09[0-9]{9}$">
                    <p class="text-sm text-gray-500 mt-1 text-right">فرمت: ۰۹xxxxxxxxxx</p>
                </div>

                <!-- National Code (کد ملی) -->
                <div>
                    <label for="national-code" class="block text-lg font-semibold text-brown-900 mb-2">کد ملی:</label>
                    <input type="text" id="national-code" name="nationalCode"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-800 focus:border-transparent text-gray-800"
                           placeholder="کد ملی ۱۰ رقمی" required pattern="^[0-9]{10}$" maxlength="10">
                    <p class="text-sm text-gray-500 mt-1 text-right">فقط اعداد (۱۰ رقمی)</p>
                </div>

                <!-- Address -->
                <div class="space-y-4 pt-4 border-t border-gray-200"> {{-- Added top padding and border for visual separation --}}
                    <h4 class="text-xl font-bold text-brown-900 border-b pb-2 mb-4">اطلاعات آدرس</h4>
                    <div>
                        <label for="street-address" class="block text-lg font-semibold text-brown-900 mb-2">آدرس خیابان:</label>
                        <input type="text" id="street-address" name="streetAddress"
                               class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-800 focus:border-transparent text-gray-800"
                               placeholder="مثال: خیابان اصلی، کوچه سحر، پلاک 10" required>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6"> {{-- Group province, city and postal code --}}
                        <!-- Province (استان) -->
                        <div>
                            <label for="province" class="block text-lg font-semibold text-brown-900 mb-2">استان:</label>
                            <input type="text" id="province" name="province"
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-800 focus:border-transparent text-gray-800"
                                   placeholder="مثال: گیلان" required>
                        </div>
                        <div>
                            <label for="city" class="block text-lg font-semibold text-brown-900 mb-2">شهر:</label>
                            <input type="text" id="city" name="city"
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-800 focus:border-transparent text-gray-800"
                                   placeholder="مثال: تهران" required>
                        </div>
                        <div>
                            <label for="postal-code" class="block text-lg font-semibold text-brown-900 mb-2">کد پستی:</label>
                            <input type="text" id="postal-code" name="postalCode"
                                   class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-800 focus:border-transparent text-gray-800"
                                   placeholder="مثال: 1234567890" required pattern="^[0-9]{10}$">
                            <p class="text-sm text-gray-500 mt-1 text-right">کد پستی ۱۰ رقمی</p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="bg-green-800 text-white px-8 py-3 rounded-full text-lg font-semibold hover:bg-green-700 transition-all duration-300 shadow-lg w-full mt-8">
                    ثبت نام
                </button>

                <div class="text-center mt-4">
                    <a href="{{ url('/') }}" class="text-green-800 hover:underline text-base">بازگشت به صفحه اصلی</a>
                </div>
            </form>
        </div>
    </main>
@endsection
