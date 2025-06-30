<x-app-layout>
    <div class="py-12 bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center">
        <div class="w-full max-w-2xl bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6 sm:p-8 border border-gray-200 dark:border-gray-700" dir="rtl">
            <!-- Header Section -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-2">
                    {{ isset($address) ? __('ویرایش آدرس') : __('افزودن آدرس جدید') }}
                </h1>
                <p class="text-md text-gray-600 dark:text-gray-400">
                    {{ isset($address) ? __('اطلاعات آدرس خود را به‌روزرسانی کنید.') : __('اطلاعات آدرس جدید را وارد کنید.') }}
                </p>
            </div>

            <!-- Session Status Message -->
            @if (session('status'))
                <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/20 p-3 rounded-lg flex items-center">
                    <i class="fas fa-check-circle ml-2"></i>
                    {{ session('status') }}
                </div>
            @endif

            <!-- Form Section -->
            <form method="POST" action="{{ isset($address) ? route('profile.addresses.update', $address->id) : route('profile.addresses.store') }}" class="space-y-6">
                @csrf
                @if (isset($address))
                    @method('PUT') {{-- برای متد PUT در ویرایش --}}
                @endif

                <!-- Title Field -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('عنوان آدرس (مثال: خانه، محل کار)') }}
                    </label>
                    <input id="title" 
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400" 
                           type="text" 
                           name="title" 
                           value="{{ old('title', $address->title ?? '') }}" 
                           placeholder="نامی برای آدرس خود انتخاب کنید">
                    <x-input-error :messages="$errors->get('title')" class="mt-2 text-sm" />
                </div>

                <!-- Province & City Fields -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="province" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('استان') }}
                        </label>
                        <select id="province" 
                                name="province" 
                                class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base appearance-none" 
                                required>
                            <option value="" disabled selected>{{ __('انتخاب استان') }}</option>
                            <option value="Tehran" {{ old('province', $address->province ?? '') == 'Tehran' ? 'selected' : '' }}>{{ __('تهران') }}</option>
                            <option value="Isfahan" {{ old('province', $address->province ?? '') == 'Isfahan' ? 'selected' : '' }}>{{ __('اصفهان') }}</option>
                            <option value="Mashhad" {{ old('province', $address->province ?? '') == 'Mashhad' ? 'selected' : '' }}>{{ __('مشهد') }}</option>
                            <option value="Shiraz" {{ old('province', $address->province ?? '') == 'Shiraz' ? 'selected' : '' }}>{{ __('شیراز') }}</option>
                            <option value="Tabriz" {{ old('province', $address->province ?? '') == 'Tabriz' ? 'selected' : '' }}>{{ __('تبریز') }}</option>
                            {{-- می‌توانید شهرهای بیشتری را اینجا اضافه کنید --}}
                        </select>
                        <x-input-error :messages="$errors->get('province')" class="mt-2 text-sm" />
                    </div>
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('شهر') }}
                        </label>
                        <select id="city" 
                                name="city" 
                                class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base appearance-none" 
                                required>
                            <option value="" disabled selected>{{ __('انتخاب شهر') }}</option>
                            {{-- شهرهای مربوط به استان انتخاب شده با جاوااسکریپت پر می‌شوند --}}
                            <option value="Tehran" {{ old('city', $address->city ?? '') == 'Tehran' ? 'selected' : '' }}>{{ __('تهران') }}</option>
                            <option value="Karaj" {{ old('city', $address->city ?? '') == 'Karaj' ? 'selected' : '' }}>{{ __('کرج') }}</option>
                            <option value="Isfahan" {{ old('city', $address->city ?? '') == 'Isfahan' ? 'selected' : '' }}>{{ __('اصفهان') }}</option>
                            <option value="Mashhad" {{ old('city', $address->city ?? '') == 'Mashhad' ? 'selected' : '' }}>{{ __('مشهد') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('city')" class="mt-2 text-sm" />
                    </div>
                </div>

                <!-- Address Field -->
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('نشانی دقیق') }}
                    </label>
                    <textarea id="address" 
                              class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400 resize-y" 
                              name="address" 
                              rows="3" 
                              placeholder="خیابان، کوچه، پلاک، واحد...">{{ old('address', $address->address ?? '') }}</textarea>
                    <x-input-error :messages="$errors->get('address')" class="mt-2 text-sm" />
                </div>

                <!-- Postal Code Field -->
                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('کد پستی ۱۰ رقمی') }}
                    </label>
                    <input id="postal_code" 
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400" 
                           type="text" 
                           name="postal_code" 
                           value="{{ old('postal_code', $address->postal_code ?? '') }}" 
                           placeholder="1234567890"
                           maxlength="10"
                           pattern="[0-9]{10}">
                    <span class='help-block text-xs text-gray-500 dark:text-gray-400 mt-1 block'>{{ __('کیبورد را در حالت انگلیسی قرار دهید') }}</span>
                    <x-input-error :messages="$errors->get('postal_code')" class="mt-2 text-sm" />
                </div>

                <!-- Phone Number Field (Optional for address, if different from user's mobile) -->
                <div>
                    <label for="phone_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('شماره تماس (اختیاری)') }}
                    </label>
                    <input id="phone_number" 
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400" 
                           type="text" 
                           name="phone_number" 
                           value="{{ old('phone_number', $address->phone_number ?? '') }}" 
                           placeholder="09123456789"
                           maxlength="11"
                           pattern="^09[0-9]{9}$">
                    <span class='help-block text-xs text-gray-500 dark:text-gray-400 mt-1 block'>{{ __('شماره موبایل ۱۰ یا ۱۱ رقمی. مثال: 09123456789') }}</span>
                    <x-input-error :messages="$errors->get('phone_number')" class="mt-2 text-sm" />
                </div>

                <!-- Is Default Checkbox -->
                <div>
                    <label for="is_default" class="inline-flex items-center">
                        <input id="is_default" 
                               type="checkbox" 
                               name="is_default" 
                               value="1" 
                               class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600" 
                               {{ old('is_default', $address->is_default ?? false) ? 'checked' : '' }}>
                        <span class="ms-2 text-sm text-gray-700 dark:text-gray-300">{{ __('این آدرس پیش‌فرض من است') }}</span>
                    </label>
                </div>

                <div class="flex items-center justify-end mt-6">
                    <a href="{{ route('profile.addresses.index') }}" 
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg shadow-sm transition-all duration-200 ease-in-out ml-3">
                        {{ __('انصراف') }}
                    </a>
                    <button type="submit" 
                            class="inline-flex items-center justify-center px-6 py-3 text-lg font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300 ease-in-out min-w-[150px]">
                        {{ isset($address) ? __('به‌روزرسانی آدرس') : __('ذخیره آدرس') }}
                        <i class="fas fa-check-circle mr-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script for Province-City dynamic dropdown -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const provinceSelect = document.getElementById('province');
            const citySelect = document.getElementById('city');

            const citiesByProvince = {
                'Tehran': ['Tehran', 'Karaj'],
                'Isfahan': ['Isfahan'],
                'Mashhad': ['Mashhad'],
                'Shiraz': ['Shiraz'],
                'Tabriz': ['Tabriz'],
                // Add more provinces and their cities as needed
            };

            function updateCities() {
                const selectedProvince = provinceSelect.value;
                const cities = citiesByProvince[selectedProvince] || [];

                citySelect.innerHTML = '<option value="" disabled selected>{{ __("انتخاب شهر") }}</option>';

                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    // Translate city names for display
                    option.textContent = city === 'Tehran' ? 'تهران' :
                                         city === 'Karaj' ? 'کرج' :
                                         city === 'Isfahan' ? 'اصفهان' :
                                         city === 'Mashhad' ? 'مشهد' :
                                         city === 'Shiraz' ? 'شیراز' :
                                         city === 'Tabriz' ? 'تبریز' : city;
                    citySelect.appendChild(option);
                });

                // Set old city value if it exists and is in the current province's cities
                const oldCity = "{{ old('city', $address->city ?? '') }}";
                if (oldCity && cities.includes(oldCity)) {
                    citySelect.value = oldCity;
                } else {
                    // If old city is not in the new list, or it's a new address, reset to default option
                    citySelect.value = "";
                }
            }

            provinceSelect.addEventListener('change', updateCities);

            // Initial call to populate cities if a province is already selected (e.g., on edit or old input)
            if (provinceSelect.value) {
                updateCities();
            }
        });
    </script>
</x-app-layout>
