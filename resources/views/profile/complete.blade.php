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

                <!-- Full Name Field -->
                <div class="form-group row">
                    <label for="fullName" class="control-label col-lg-4 col-md-4 col-sm-4 col-xs-12 text-right">
                        <i title='ضروری' class="fas fa-star-of-life text-red-500 fa-xs ml-1"></i>
                        {{ __('نام و نام خانوادگی') }}
                    </label>
                    <div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">
                        <input id="fullName" 
                               class="form-control block w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400" 
                               type="text" 
                               name="fullName" 
                               value="{{ old('fullName', $user->name) }}" 
                               placeholder="نام کامل خود را وارد کنید"
                               required 
                               autofocus>
                        <x-input-error :messages="$errors->get('fullName')" class="mt-2 text-sm" />
                    </div>
                </div>

                <!-- Mobile Number Field (Read-only) -->
                <div class="form-group row">
                    <label for="mobile_number" class="control-label col-lg-4 col-md-4 col-sm-4 col-xs-12 text-right">
                        <i title='ضروری' class="fas fa-star-of-life text-red-500 fa-xs ml-1"></i>
                        {{ __('موبایل') }}
                    </label>
                    <div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">
                        <input id="mobile_number" 
                               class="form-control block w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 shadow-sm text-base cursor-not-allowed bg-gray-100 dark:bg-gray-700/30 text-gray-600 dark:text-gray-400" 
                               type="text" 
                               name="mobile_number" 
                               value="{{ old('mobile_number', $user->mobile_number) }}" 
                               readonly 
                               disabled>
                        <span class='help-block text-xs text-gray-500 dark:text-gray-400 mt-1 block'>{{ __('شماره موبایل شما قابل تغییر نیست.') }}</span>
                    </div>
                </div>

                <!-- Province & City Fields -->
                <div class="form-group row">
                    <label class="control-label col-lg-4 col-md-4 col-sm-4 col-xs-12 text-right">
                        <i title='ضروری' class="fas fa-star-of-life text-red-500 fa-xs ml-1"></i>
                        {{ __('استان و شهر') }}
                    </label>
                    <div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <select id="province" 
                                    name="province" 
                                    class="form-control block w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base appearance-none" 
                                    required>
                                <option value="" disabled {{ old('province', $user->province) ? '' : 'selected' }}>{{ __('انتخاب استان') }}</option>
                                <option value="Tehran" {{ old('province', $user->province) == 'Tehran' ? 'selected' : '' }}>{{ __('تهران') }}</option>
                                <option value="Isfahan" {{ old('province', $user->province) == 'Isfahan' ? 'selected' : '' }}>{{ __('اصفهان') }}</option>
                                <option value="Mashhad" {{ old('province', $user->province) == 'Mashhad' ? 'selected' : '' }}>{{ __('مشهد') }}</option>
                                <option value="Shiraz" {{ old('province', $user->province) == 'Shiraz' ? 'selected' : '' }}>{{ __('شیراز') }}</option>
                                <option value="Tabriz" {{ old('province', $user->province) == 'Tabriz' ? 'selected' : '' }}>{{ __('تبریز') }}</option>
                            </select>
                            <select id="city" 
                                    name="city" 
                                    class="form-control block w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base appearance-none" 
                                    required>
                                <option value="" disabled {{ old('city', $user->city) ? '' : 'selected' }}>{{ __('انتخاب شهر') }}</option>
                                <option value="Tehran" {{ old('city', $user->city) == 'Tehran' ? 'selected' : '' }}>{{ __('تهران') }}</option>
                                <option value="Karaj" {{ old('city', $user->city) == 'Karaj' ? 'selected' : '' }}>{{ __('کرج') }}</option>
                                <option value="Isfahan" {{ old('city', $user->city) == 'Isfahan' ? 'selected' : '' }}>{{ __('اصفهان') }}</option>
                                <option value="Mashhad" {{ old('city', $user->city) == 'Mashhad' ? 'selected' : '' }}>{{ __('مشهد') }}</option>
                            </select>
                        </div>
                        <x-input-error :messages="$errors->get('province')" class="mt-2 text-sm" />
                        <x-input-error :messages="$errors->get('city')" class="mt-2 text-sm" />
                    </div>
                </div>

                <!-- Address Field -->
                <div class="form-group row">
                    <label for="address" class="control-label col-lg-4 col-md-4 col-sm-4 col-xs-12 text-right">
                        <i title='ضروری' class="fas fa-star-of-life text-red-500 fa-xs ml-1"></i>
                        {{ __('نشانی دقیق') }}
                    </label>
                    <div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">
                        <textarea id="address" 
                               class="form-control block w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400 resize-y" 
                               name="address" 
                               rows="2" 
                               placeholder="آدرس کامل خود را وارد کنید"
                               required>{{ old('address', $user->address) }}</textarea>
                        <x-input-error :messages="$errors->get('address')" class="mt-2 text-sm" />
                    </div>
                </div>

                <!-- Postal Code Field -->
                <div class="form-group row">
                    <label for="postalCode" class="control-label col-lg-4 col-md-4 col-sm-4 col-xs-12 text-right">
                        {{ __('کد پستی ۱۰ رقمی') }}
                    </label>
                    <div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">
                        <input id="postalCode" 
                               class="form-control block w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400" 
                               type="text" 
                               name="postalCode" 
                               value="{{ old('postalCode', $user->postal_code) }}" 
                               placeholder="1234567890"
                               maxlength="10"
                               pattern="[0-9]{10}"
                               required>
                        <span class='help-block text-xs text-gray-500 dark:text-gray-400 mt-1 block'>{{ __('کیبورد را در حالت انگلیسی قرار دهید') }}</span>
                        <x-input-error :messages="$errors->get('postalCode')" class="mt-2 text-sm" />
                    </div>
                </div>

                <!-- Email Field -->
                <div class="form-group row">
                    <label for="email" class="control-label col-lg-4 col-md-4 col-sm-4 col-xs-12 text-right">
                        {{ __('ایمیل') }}
                    </label>
                    <div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">
                        <input id="email" 
                               class="form-control block w-full px-3 py-2 rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400" 
                               type="email" 
                               name="email" 
                               value="{{ old('email', $user->email) }}" 
                               placeholder="example@example.com">
                        <span class='help-block text-xs text-gray-500 dark:text-gray-400 mt-1 block'>{{ __('کیبورد را در حالت انگلیسی قرار دهید') }}</span>
                        <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm" />
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="form-group row tr_submit form-actions text-center mt-6">
                    <div class="col-md-12">
                        <button type="submit" 
                                class="btn btn-success btn-lg inline-flex items-center justify-center px-8 py-3 text-base font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300 ease-in-out">
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ __('تکمیل و ذخیره پروفایل') }}
                        </button>
                    </div>
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
</x-app-layout>
