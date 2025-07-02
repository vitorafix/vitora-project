{{-- استفاده از لایه‌بندی اصلی برنامه که شامل هدر و فوتر است --}}
<x-app-layout>
    <div class="py-12 bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center">
        <div class="w-full max-w-2xl bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6 sm:p-8 border border-gray-200 dark:border-gray-700" dir="rtl">
            <!-- Header Section -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-2">
                    {{ __('تکمیل اطلاعات پروفایل') }}
                </h1>
                <p class="text-md text-gray-600 dark:text-gray-400">
                    {{ __('برای ارائه بهترین تجربه، لطفا اطلاعات پروفایل خود را تکمیل کنید.') }}
                </p>
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="mb-6" :status="session('status')" />

            <!-- Form Section -->
            <form method="POST" action="{{ route('profile.complete.store') }}" class="space-y-6">
                @csrf

                {{-- نمایش نام و نام خانوادگی کاربر (فقط برای اطلاع، قابل ویرایش نیست) --}}
                <div class="mb-4">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('نام و نام خانوادگی:') }}
                        <span class="font-bold text-gray-800 dark:text-gray-200">
                            {{ $user->name }} {{ $user->lastname }}
                        </span>
                    </p>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('شماره موبایل:') }}
                        <span class="font-bold text-gray-800 dark:text-gray-200">
                            {{ $user->mobile_number }}
                        </span>
                    </p>
                </div>

                <!-- Title Field for Address -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('عنوان آدرس (مثلاً: خانه، محل کار)') }}
                    </label>
                    <input id="title" 
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400" 
                           type="text" 
                           name="title" 
                           value="{{ old('title') }}" 
                           placeholder="نامی برای آدرس خود انتخاب کنید">
                    <x-input-error :messages="$errors->get('title')" class="mt-2 text-sm" />
                </div>

                <!-- Province & City Fields -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="province" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <i title='ضروری' class="fas fa-star-of-life text-red-500 fa-xs ml-1"></i>
                            {{ __('استان') }}
                        </label>
                        <select id="province" 
                                name="province" 
                                class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base appearance-none" 
                                required>
                            <option value="" disabled {{ old('province') ? '' : 'selected' }}>{{ __('انتخاب استان') }}</option>
                            <option value="Tehran" {{ old('province') == 'Tehran' ? 'selected' : '' }}>{{ __('تهران') }}</option>
                            <option value="Isfahan" {{ old('province') == 'Isfahan' ? 'selected' : '' }}>{{ __('اصفهان') }}</option>
                            <option value="Mashhad" {{ old('province') == 'Mashhad' ? 'selected' : '' }}>{{ __('مشهد') }}</option>
                            <option value="Shiraz" {{ old('province') == 'Shiraz' ? 'selected' : '' }}>{{ __('شیراز') }}</option>
                            <option value="Tabriz" {{ old('province') == 'Tabriz' ? 'selected' : '' }}>{{ __('تبریز') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('province')" class="mt-2 text-sm" />
                    </div>
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <i title='ضروری' class="fas fa-star-of-life text-red-500 fa-xs ml-1"></i>
                            {{ __('شهر') }}
                        </label>
                        <select id="city" 
                                name="city" 
                                class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base appearance-none" 
                                required>
                            <option value="" disabled {{ old('city') ? '' : 'selected' }}>{{ __('انتخاب شهر') }}</option>
                            {{-- شهرهای این لیست به صورت داینامیک توسط جاوااسکریپت پر می‌شوند --}}
                        </select>
                        <x-input-error :messages="$errors->get('city')" class="mt-2 text-sm" />
                    </div>
                </div>

                <!-- Address Field -->
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <i title='ضروری' class="fas fa-star-of-life text-red-500 fa-xs ml-1"></i>
                        {{ __('نشانی دقیق') }}
                    </label>
                    <textarea id="address" 
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400 resize-y" 
                           name="address" 
                           rows="3" 
                           placeholder="آدرس کامل خود را وارد کنید"
                           required>{{ old('address') }}</textarea>
                    <x-input-error :messages="$errors->get('address')" class="mt-2 text-sm" />
                </div>

                <!-- Postal Code Field -->
                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('کد پستی ۱۰ رقمی (اختیاری)') }}
                    </label>
                    <input id="postal_code" 
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400" 
                           type="text" 
                           name="postal_code" 
                           value="{{ old('postal_code') }}" 
                           placeholder="1234567890"
                           maxlength="10"
                           pattern="[0-9]{10}">
                    <span class='block text-xs text-gray-500 dark:text-gray-400 mt-1'>{{ __('کیبورد را در حالت انگلیسی قرار دهید') }}</span>
                    <x-input-error :messages="$errors->get('postal_code')" class="mt-2 text-sm" />
                </div>

                <!-- Phone Number Field (Fixed Line) -->
                <div>
                    <label for="phone_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('شماره تلفن ثابت (اختیاری)') }}
                    </label>
                    <input id="phone_number" 
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400" 
                           type="text" 
                           name="phone_number" 
                           value="{{ old('phone_number') }}" 
                           placeholder="مثال: 021XXXXXXXX">
                    <span class='block text-xs text-gray-500 dark:text-gray-400 mt-1'>{{ __('شماره تلفن ثابت با کد شهر (مثال: 021) و ۱۰ رقم بعد از آن') }}</span>
                    <x-input-error :messages="$errors->get('phone_number')" class="mt-2 text-sm" />
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-center mt-6">
                    <button type="submit" 
                            class="inline-flex items-center justify-center px-8 py-3 text-base font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300 ease-in-out">
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ __('تکمیل و ذخیره پروفایل') }}
                    </button>
                </div>
            </form>

            <!-- Footer -->
            <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
                <p>{{ __('تمامی اطلاعات شما محفوظ و امن نگهداری می‌شود') }}</p>
            </div>
        </div>
    </div>

    <!-- Optional: اضافه کردن اسکریپت برای دینامیک کردن شهرها بر اساس استان -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const provinceSelect = document.getElementById('province');
            const citySelect = document.getElementById('city');

            const citiesByProvince = {
                'Tehran': ['Tehran', 'Karaj'],
                'Isfahan': ['Isfahan'],
                'Mashhad': ['Mashhad'],
                'Shiraz': ['Shiraz'],
                'Tabriz': ['Tabriz']
            };

            function updateCities() {
                const selectedProvince = provinceSelect.value;
                const cities = citiesByProvince[selectedProvince] || [];

                // پاک کردن همه گزینه‌ها
                citySelect.innerHTML = '<option value="" disabled selected>{{ __("انتخاب شهر") }}</option>';

                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city === 'Tehran' ? 'تهران' :
                                         city === 'Karaj' ? 'کرج' :
                                         city === 'Isfahan' ? 'اصفهان' :
                                         city === 'Mashhad' ? 'مشهد' :
                                         city === 'Shiraz' ? 'شیراز' :
                                         city === 'Tabriz' ? 'تبریز' : city;
                    citySelect.appendChild(option);
                });

                // اگر قبلا انتخابی داشت، آن را دوباره انتخاب کن
                // از old('city') برای بازیابی مقدار پس از خطای اعتبارسنجی استفاده می‌کنیم
                const oldCity = "{{ old('city') }}";
                if (oldCity && cities.includes(oldCity)) {
                    citySelect.value = oldCity;
                }
            }

            provinceSelect.addEventListener('change', updateCities);

            // فراخوانی اولیه برای تنظیم شهرها هنگام لود صفحه
            updateCities();
        });
    </script>
</x-app-layout>
