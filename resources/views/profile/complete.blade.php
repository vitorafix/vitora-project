<x-guest-layout>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header Section -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 mb-4 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-3">
                    {{ __('تکمیل اطلاعات پروفایل') }}
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                    {{ __('لطفا اطلاعات پروفایل خود را تکمیل کنید تا بتوانید ادامه دهید.') }}
                </p>
            </div>

            <!-- Main Form Card -->
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
                
                <!-- Session Status -->
                <div class="px-6 lg:px-8 pt-6">
                    <x-auth-session-status class="mb-4" :status="session('status')" />
                </div>

                <form method="POST" action="{{ route('profile.complete.store') }}" class="px-6 lg:px-8 pb-8" novalidate>
                    @csrf

                    <!-- Full Name Field -->
                    <div class="mb-8">
                        <label for="full_name" class="block text-base font-semibold text-gray-800 dark:text-gray-200 mb-2 text-right">
                            {{ __('نام و نام خانوادگی') }}
                            <span class="text-red-500 mr-1" aria-hidden="true">*</span>
                        </label>
                        <div class="relative">
                            <input id="full_name" 
                                   class="block w-full text-base rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 px-4 py-3 placeholder-gray-400" 
                                   type="text" 
                                   name="full_name" 
                                   value="{{ old('full_name', $user->name) }}" 
                                   placeholder="نام و نام خانوادگی خود را وارد کنید"
                                   required 
                                   autofocus
                                   aria-describedby="full_name_error">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none" aria-hidden="true">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('full_name')" class="mt-2 text-sm" id="full_name_error" />
                    </div>

                    <!-- Mobile Number Field (Read-only) -->
                    <div class="mb-8">
                        <label for="mobile_number" class="block text-base font-semibold text-gray-800 dark:text-gray-200 mb-2 text-right">
                            {{ __('موبایل') }}
                            <span class="text-red-500 mr-1" aria-hidden="true">*</span>
                        </label>
                        <div class="relative">
                            <input id="mobile_number" 
                                   class="block w-full text-base rounded-xl border-2 border-gray-300 dark:border-gray-600 shadow-sm px-4 py-3 cursor-not-allowed bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400" 
                                   type="text" 
                                   name="mobile_number" 
                                   value="{{ old('mobile_number', $user->mobile_number) }}" 
                                   readonly 
                                   disabled
                                   aria-readonly="true"
                                   aria-describedby="mobile_number_info">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none" aria-hidden="true">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                        <small id="mobile_number_info" class="text-gray-500 dark:text-gray-400 block mt-1 text-sm">
                            شماره موبایل شما قابل تغییر نیست.
                        </small>
                    </div>

                    <!-- Province & City Fields -->
                    <div class="mb-8">
                        <label class="block text-base font-semibold text-gray-800 dark:text-gray-200 mb-2 text-right">
                            {{ __('استان و شهر') }}
                            <span class="text-red-500 mr-1" aria-hidden="true">*</span>
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="relative">
                                <select id="province" 
                                        name="province" 
                                        class="block w-full text-base rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 px-4 py-3 appearance-none" 
                                        required
                                        aria-describedby="province_error city_error"
                                        aria-required="true">
                                    <option value="" disabled {{ old('province', $user->province) ? '' : 'selected' }}>{{ __('انتخاب استان') }}</option>
                                    <option value="Tehran" {{ old('province', $user->province) == 'Tehran' ? 'selected' : '' }}>{{ __('تهران') }}</option>
                                    <option value="Isfahan" {{ old('province', $user->province) == 'Isfahan' ? 'selected' : '' }}>{{ __('اصفهان') }}</option>
                                    <option value="Mashhad" {{ old('province', $user->province) == 'Mashhad' ? 'selected' : '' }}>{{ __('مشهد') }}</option>
                                    <option value="Shiraz" {{ old('province', $user->province) == 'Shiraz' ? 'selected' : '' }}>{{ __('شیراز') }}</option>
                                    <option value="Tabriz" {{ old('province', $user->province) == 'Tabriz' ? 'selected' : '' }}>{{ __('تبریز') }}</option>
                                </select>
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none" aria-hidden="true">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="relative">
                                <select id="city" 
                                        name="city" 
                                        class="block w-full text-base rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 px-4 py-3 appearance-none" 
                                        required
                                        aria-describedby="city_error"
                                        aria-required="true">
                                    <option value="" disabled {{ old('city', $user->city) ? '' : 'selected' }}>{{ __('انتخاب شهر') }}</option>
                                    <option value="Tehran" {{ old('city', $user->city) == 'Tehran' ? 'selected' : '' }}>{{ __('تهران') }}</option>
                                    <option value="Karaj" {{ old('city', $user->city) == 'Karaj' ? 'selected' : '' }}>{{ __('کرج') }}</option>
                                    <option value="Isfahan" {{ old('city', $user->city) == 'Isfahan' ? 'selected' : '' }}>{{ __('اصفهان') }}</option>
                                    <option value="Mashhad" {{ old('city', $user->city) == 'Mashhad' ? 'selected' : '' }}>{{ __('مشهد') }}</option>
                                </select>
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none" aria-hidden="true">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                            <x-input-error :messages="$errors->get('province')" class="text-sm" id="province_error" />
                            <x-input-error :messages="$errors->get('city')" class="text-sm" id="city_error" />
                        </div>
                    </div>

                    <!-- Address Field -->
                    <div class="mb-8">
                        <label for="address" class="block text-base font-semibold text-gray-800 dark:text-gray-200 mb-2 text-right">
                            {{ __('نشانی دقیق') }}
                            <span class="text-red-500 mr-1" aria-hidden="true">*</span>
                        </label>
                        <div class="relative">
                            <textarea id="address" 
                                   class="block w-full text-base rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 px-4 py-3 placeholder-gray-400 resize-none" 
                                   name="address" 
                                   rows="3"
                                   placeholder="آدرس کامل خود را وارد کنید"
                                   required
                                   aria-describedby="address_error">{{ old('address', $user->address) }}</textarea>
                            <div class="absolute top-3 left-0 pl-3 flex items-start pointer-events-none" aria-hidden="true">
                                <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('address')" class="mt-2 text-sm" id="address_error" />
                    </div>

                    <!-- Postal Code Field -->
                    <div class="mb-8">
                        <label for="postal_code" class="block text-base font-semibold text-gray-800 dark:text-gray-200 mb-2 text-right">
                            {{ __('کد پستی ۱۰ رقمی') }}
                            <span class="text-red-500 mr-1" aria-hidden="true">*</span>
                        </label>
                        <div class="relative">
                            <input id="postal_code" 
                                   class="block w-full text-base rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20 transition-all duration-200 px-4 py-3 placeholder-gray-400 font-mono tracking-wider" 
                                   type="text" 
                                   name="postal_code" 
                                   value="{{ old('postal_code', $user->postal_code) }}" 
                                   placeholder="1234567890"
                                   maxlength="10"
                                   pattern="[0-9]{10}"
                                   required
                                   aria-describedby="postal_code_error">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none" aria-hidden="true">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('postal_code')" class="mt-2 text-sm" id="postal_code_error" />
                    </div>

                    <!-- Email Field (Read-only) -->
                    <div class="mb-10">
                        <label for="email" class="block text-base font-semibold text-gray-800 dark:text-gray-200 mb-2 text-right">
                            {{ __('ایمیل') }}
                        </label>
                        <div class="relative">
                            <input id="email" 
                                   class="block w-full text-base rounded-xl border-2 border-gray-300 dark:border-gray-600 shadow-sm px-4 py-3 cursor-not-allowed bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400" 
                                   type="email" 
                                   name="email" 
                                   value="{{ old('email', $user->email) }}" 
                                   readonly 
                                   disabled
                                   aria-readonly="true">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none" aria-hidden="true">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
                        <div class="flex justify-center">
                            <button type="submit" 
                                    class="group relative inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 focus:outline-none focus:ring-4 focus:ring-blue-500/50 transition-all duration-200 min-w-[240px]">
                                <svg class="w-5 h-5 ml-2 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ __('تکمیل و ذخیره پروفایل') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center">
                <div class="inline-flex items-center justify-center px-4 py-2 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800" role="contentinfo" aria-live="polite">
                    <svg class="w-4 h-4 text-green-600 dark:text-green-400 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span class="text-sm font-medium text-green-700 dark:text-green-300">
                        تمامی اطلاعات شما محفوظ و امن نگهداری می‌شود
                    </span>
                </div>
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
                'Shiraz': [],
                'Tabriz': []
            };

            function updateCities() {
                const selectedProvince = provinceSelect.value;
                const cities = citiesByProvince[selectedProvince] || [];

                // پاک کردن همه گزینه‌ها
                citySelect.innerHTML = '<option value="" disabled selected>انتخاب شهر</option>';

                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city === 'Tehran' ? 'تهران' :
                                         city === 'Karaj' ? 'کرج' :
                                         city === 'Isfahan' ? 'اصفهان' :
                                         city === 'Mashhad' ? 'مشهد' : city;
                    citySelect.appendChild(option);
                });

                // اگر قبلا انتخابی داشت، آن را دوباره انتخاب کن
                const oldCity = "{{ old('city', $user->city) }}";
                if (oldCity && cities.includes(oldCity)) {
                    citySelect.value = oldCity;
                }
            }

            provinceSelect.addEventListener('change', updateCities);

            // فراخوانی اولیه برای تنظیم شهرها هنگام لود صفحه
            updateCities();
        });
    </script>
</x-guest-layout>
