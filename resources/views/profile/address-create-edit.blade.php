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
                    @method('PUT') {{-- For PUT method in edit --}}
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
                            {{-- Changed selected logic for initial load --}}
                            <option value="" disabled {{ old('province', $address->province ?? '') ? '' : 'selected' }}>
                                {{ __('انتخاب استان') }}
                            </option>
                            {{-- Provinces will be populated dynamically by JavaScript --}}
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
                            {{-- Changed selected logic for initial load --}}
                            <option value="" disabled {{ old('city', $address->city ?? '') ? '' : 'selected' }}>
                                {{ __('انتخاب شهر') }}
                            </option>
                            {{-- Cities will be populated dynamically by JavaScript --}}
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
                        <span class="text-red-500 text-lg leading-none align-middle">*</span> {{-- Red asterisk for required field --}}
                    </label>
                    <input id="postal_code"
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400"
                           type="text"
                           name="postal_code"
                           value="{{ old('postal_code', $address->postal_code ?? '') }}"
                           placeholder="1234567890"
                           required {{-- Make the field required --}}
                           minlength="10" {{-- Minimum length of 10 characters --}}
                           maxlength="10" {{-- Maximum length of 10 characters --}}
                           pattern="[0-9]{10}"> {{-- Pattern to ensure exactly 10 digits --}}
                    <span class='help-block text-xs text-gray-500 dark:text-gray-400 mt-1 block'>{{ __('کیبورد را در حالت انگلیسی قرار دهید') }}</span>
                    <x-input-error :messages="$errors->get('postal_code')" class="mt-2 text-sm" />
                </div>

                <!-- Recipient First Name Field -->
                <div>
                    <label for="recipient_first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('نام تحویل گیرنده') }}
                        <span class="text-red-500 text-lg leading-none align-middle">*</span> {{-- Red asterisk for required field --}}
                    </label>
                    <input id="recipient_first_name"
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400"
                           type="text"
                           name="recipient_first_name"
                           value="{{ old('recipient_first_name', $address->recipient_first_name ?? '') }}"
                           placeholder="نام تحویل گیرنده را وارد کنید"
                           required>
                    <x-input-error :messages="$errors->get('recipient_first_name')" class="mt-2 text-sm" />
                </div>

                <!-- Recipient Last Name Field -->
                <div>
                    <label for="recipient_last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('نام خانوادگی تحویل گیرنده') }}
                        <span class="text-red-500 text-lg leading-none align-middle">*</span> {{-- Red asterisk for required field --}}
                    </label>
                    <input id="recipient_last_name"
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400"
                           type="text"
                           name="recipient_last_name"
                           value="{{ old('recipient_last_name', $address->recipient_last_name ?? '') }}"
                           placeholder="نام خانوادگی تحویل گیرنده را وارد کنید"
                           required>
                    <x-input-error :messages="$errors->get('recipient_last_name')" class="mt-2 text-sm" />
                </div>

                <!-- Fixed Phone Number Field -->
                <div>
                    <label for="fixed_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('شماره تلفن ثابت تحویل گیرنده') }}
                        <span class="text-red-500 text-lg leading-none align-middle">*</span> {{-- Red asterisk for required field --}}
                    </label>
                    <input id="fixed_phone"
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400"
                           type="tel" {{-- Use type="tel" for telephone numbers --}}
                           name="fixed_phone"
                           value="{{ old('fixed_phone', $address->fixed_phone ?? '') }}"
                           placeholder="مثال: 02112345678"
                           required {{-- Make the field required --}}
                           pattern="^0[0-9]{10}$" {{-- Pattern for Iranian fixed phone numbers (0xxxxxxxxx or 0xxxxxxxxxx) --}}
                           oninput="this.value = convertToEnglishDigits(this.value);" {{-- Convert Persian digits to English --}}
                           >
                    <span class='help-block text-xs text-gray-500 dark:text-gray-400 mt-1 block'>{{ __('لطفاً شماره تلفن ثابت ۱۱ رقمی را با کد شهر وارد کنید (مثال: 02112345678). می‌توانید از اعداد فارسی یا انگلیسی استفاده کنید.') }}</span>
                    <x-input-error :messages="$errors->get('fixed_phone')" class="mt-2 text-sm" />
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
            // Changed variable name from recipientMobileInput to fixedPhoneInput
            const fixedPhoneInput = document.getElementById('fixed_phone');

            // Load the provinces and cities data from the JSON provided in the Canvas
            const provincesAndCitiesData = [
                {
                    "province": "آذربایجان شرقی",
                    "cities": [
                        "تبریز", "مراغه", "مرند", "اهر", "میانه", "بناب", "سراب", "آذرشهر", "اسکو", "جلفا",
                        "عجب‌شیر", "ملکان", "ورزقان", "هریس", "هشترود", "کلیبر", "بستان‌آباد", "چاراویماق", "هوراند"
                    ]
                },
                {
                    "province": "آذربایجان غربی",
                    "cities": [
                        "ارومیه", "خوی", "بوکان", "مهاباد", "میاندوآب", "سلماس", "پیرانشهر", "نقده", "تکاب", "ماکو",
                        "سردشت", "شاهین‌دژ", "اشنویه", "چایپاره", "پلدشت", "شوط", "چالدران", "باروق", "محمدیار", "سیلوانا"
                    ]
                },
                {
                    "province": "اردبیل",
                    "cities": [
                        "اردبیل", "پارس‌آباد", "مشگین‌شهر", "خلخال", "گرمی", "نمین", "بیله‌سوار", "کوثر", "نیر", "سرعین",
                        "اصلاندوز", "جعفرآباد", "عنبران"
                    ]
                },
                {
                    "province": "اصفهان",
                    "cities": [
                        "اصفهان", "کاشان", "خمینی‌شهر", "نجف‌آباد", "لنجان", "فلاورجان", "شاهین‌شهر و میمه", "شهرضا", "مبارکه",
                        "آران و بیدگل", "گلپایگان", "فریدون‌شهر", "سمیرم", "خوانسار", "نطنز", "اردستان", "نایین",
                        "تیران و کرون", "چادگان", "بوئین و میاندشت", "دهاقان", "خور و بیابانک", "ورزنه"
                    ]
                },
                {
                    "province": "البرز",
                    "cities": [
                        "کرج", "فردیس", "کمال‌شهر", "نظرآباد", "محمدشهر", "ماهدشت", "هشتگرد", "ساوجبلاغ", "چهارباغ",
                        "اشتهارد", "طالقان", "گرمدره", "کوهسار"
                    ]
                },
                {
                    "province": "ایلام",
                    "cities": [
                        "ایلام", "دهلران", "ایوان", "آبدانان", "مهران", "دره‌شهر", "چرداول", "سیروان", "ملکشاهی", "بدره",
                        "هلیلان", "چوار"
                    ]
                },
                {
                    "province": "بوشهر",
                    "cities": [
                        "بوشهر", "برازجان", "گناوه", "کنگان", "جم", "دیر", "دیلم", "عسلویه", "دشتستان", "تنگستان",
                        "دشتی", "خورموج"
                    ]
                },
                {
                    "province": "تهران",
                    "cities": [
                        "تهران", "اسلامشهر", "شهریار", "قدس", "ملارد", "ری", "ورامین", "نسیم‌شهر", "رباط‌کریم",
                        "بهارستان", "پاکدشت", "قرچک", "دماوند", "فیروزکوه", "شمیرانات", "پردیس", "پیشوا"
                    ]
                },
                {
                    "province": "چهارمحال و بختیاری",
                    "cities": [
                        "شهرکرد", "بروجن", "فارسان", "لردگان", "کوهرنگ", "کیار", "اردل", "سامان", "بن", "خانمیرزا",
                        "فلارد"
                    ]
                },
                {
                    "province": "خراسان جنوبی",
                    "cities": [
                        "بیرجند", "قاین", "طبس", "فردوس", "نهبندان", "سربیشه", "سرایان", "بشرویه", "درمیان", "خوسف",
                        "زیرکوه"
                    ]
                },
                {
                    "province": "خراسان رضوی",
                    "cities": [
                        "مشهد", "نیشابور", "سبزوار", "تربت حیدریه", "قوچان", "کاشمر", "تربت جام", "تایباد", "چناران",
                        "سرخس", "گناباد", "فریمان", "خواف", "درگز", "بردسکن", "خلیل‌آباد", "کلات", "رشتخوار",
                        "باخرز", "زاوه", "جغتای", "خوشاب", "فاروج", "داورزن", "ششتمد"
                    ]
                },
                {
                    "province": "خراسان شمالی",
                    "cities": [
                        "بجنورد", "شیروان", "اسفراین", "مانه و سملقان", "جاجرم", "فاروج", "گرمه", "راز و جرگلان"
                    ]
                },
                {
                    "province": "خوزستان",
                    "cities": [
                        "اهواز", "دزفول", "آبادان", "اندیمشک", "خرمشهر", "ایذه", "بهبهان", "مسجدسلیمان", "ماهشهر",
                        "شوشتر", "شوش", "باغ‌ملک", "رامهرمز", "امیدیه", "شادگان", "سوسنگرد", "هندیجان", "گتوند",
                        "لالی", "هویزه", "حمیدیه", "دشت آزادگان", "کارون", "اندیکا", "آغاجاری", "رامشیر", "باوی"
                    ]
                },
                {
                    "province": "زنجان",
                    "cities": [
                        "زنجان", "ابهر", "خرمدره", "قید", "طارم", "ماه‌نشان", "سلطانیه", "ایجرود", "زرین‌رود"
                    ]
                },
                {
                    "province": "سمنان",
                    "cities": [
                        "سمنان", "شاهرود", "دامغان", "گرمسار", "مهدی‌شهر", "میامی", "سرخه", "آرادان", "بسطام"
                    ]
                },
                {
                    "province": "سیستان و بلوچستان",
                    "cities": [
                        "زاهدان", "زابل", "ایرانشهر", "چابهار", "سراوان", "خاش", "کنارک", "نیک‌شهر", "سرباز", "دلگان",
                        "زهک", "سیب و سوران", "مهرستان", "فنوج", "قصرقند", "نیمروز", "هامون", "میرجاوه", "لاشار",
                        "بزمان", "بنت", "دشتیاری", "زرآباد"
                    ]
                },
                {
                    "province": "فارس",
                    "cities": [
                        "شیراز", "مرودشت", "جهرم", "فسا", "کازرون", "داراب", "فیروزآباد", "آباده", "نی‌ریز", "اقلید",
                        "لارستان", "سپیدان", "ممسنی", "کوار", "لامرد", "فراشبند", "زرین‌دشت", "خرم‌بید", "مهر",
                        "گراش", "استهبان", "رستم", "خنج", "بوانات", "قیروکارزین", "سروستان", "پاسارگاد", "ارسنجان",
                        "خرامه", "کازرون", "اوز", "بیضا", "کوهچنار", "سرچهان", "زرقان"
                    ]
                },
                {
                    "province": "قزوین",
                    "cities": [
                        "قزوین", "تاکستان", "الوند", "محمدیه", "آبیک", "بویین‌زهرا", "آوج", "شال", "اسفرورین"
                    ]
                },
                {
                    "province": "قم",
                    "cities": [
                        "قم", "جعفریه", "دستجرد", "کهک", "سلفچگان", "قنوات"
                    ]
                },
                {
                    "province": "کردستان",
                    "cities": [
                        "سنندج", "سقز", "مریوان", "بانه", "قروه", "کامیاران", "دیواندره", "بیجار", "دهگلان", "سروآباد"
                    ]
                },
                {
                    "province": "کرمان",
                    "cities": [
                        "کرمان", "سیرجان", "رفسنجان", "جیرفت", "بم", "کهنوج", "زرند", "بافت", "بردسیر", "راور",
                        "عنبرآباد", "منوجان", "قلعه‌گنج", "ریگان", "فهرج", "رودبار جنوب", "کوهبنان", "ارزوئیه",
                        "نرماشیر", "فاریاب", "رابر", "کرمانشاه"
                    ]
                },
                {
                    "province": "کرمانشاه",
                    "cities": [
                        "کرمانشاه", "اسلام‌آباد غرب", "سنقر", "هرسین", "کنگاور", "جوانرود", "سرپل ذهاب", "گیلانغرب",
                        "صحنه", "قصر شیرین", "روانسر", "ثلاث باباجانی", "پاوه", "دالاهو"
                    ]
                },
                {
                    "province": "کهگیلویه و بویراحمد",
                    "cities": [
                        "یاسوج", "گچساران", "دهدشت", "سی‌سخت", "لیکک", "باشت", "چرام", "لنده", "مارگون"
                    ]
                },
                {
                    "province": "گلستان",
                    "cities": [
                        "گرگان", "گنبد کاووس", "علی‌آباد کتول", "آق‌قلا", "کردکوی", "بندر ترکمن", "آزادشهر", "مینودشت",
                        "رامیان", "کلاله", "گالیکش", "مراوه‌تپه", "گمیشان", "بندر گز", "فاضل‌آباد"
                    ]
                },
                {
                    "province": "گیلان",
                    "cities": [
                        "رشت", "بندر انزلی", "لاهیجان", "لنگرود", "تالش", "رودسر", "صومعه‌سرا", "آستانه اشرفیه", "رودبار",
                        "فومن", "رضوانشهر", "ماسال", "سیاهکل", "املش", "شفت", "خمام", "کوچصفهان", "لشت نشا"
                    ]
                },
                {
                    "province": "لرستان",
                    "cities": [
                        "خرم‌آباد", "بروجرد", "دورود", "کوهدشت", "الیگودرز", "نورآباد", "پلدختر", "ازنا", "الشتر",
                        "چگنی", "رومشکان", "سپیددشت"
                    ]
                },
                {
                    "province": "مازندران",
                    "cities": [
                        "ساری", "بابل", "آمل", "قائم‌شهر", "بهشهر", "تنکابن", "نوشهر", "بابلسر", "نور", "محمودآباد",
                        "رامسر", "فریدونکنار", "چالوس", "جویبار", "نکا", "عباس‌آباد", "گلوگاه", "سوادکوه", "میاندورود",
                        "سیمرغ", "کلاردشت", "سوادکوه شمالی"
                    ]
                },
                {
                    "province": "مرکزی",
                    "cities": [
                        "اراک", "ساوه", "خمین", "محلات", "دلیجان", "شازند", "زرندیه", "فراهان", "آشتیان", "تفرش",
                        "کمیجان", "خنداب"
                    ]
                },
                {
                    "province": "هرمزگان",
                    "cities": [
                        "بندرعباس", "میناب", "رودان", "بندر لنگه", "قشم", "کیش", "پارسیان", "حاجی‌آباد", "بستک",
                        "جاسک", "سیریک", "بشاگرد", "خمیر", "ابوموسی"
                    ]
                },
                {
                    "province": "همدان",
                    "cities": [
                        "همدان", "ملایر", "نهاوند", "تویسرکان", "اسدآباد", "کبودرآهنگ", "بهار", "رزن", "درگزین",
                        "فامنین"
                    ]
                },
                {
                    "province": "یزد",
                    "cities": [
                        "یزد", "میبد", "اردکان", "بافق", "مهریز", "تفت", "ابرکوه", "اشکذر", "بهاباد", "خاتم",
                        "مروست", "عقدا"
                    ]
                }
            ];

            // Function to populate provinces dropdown
            function populateProvinces() {
                provinceSelect.innerHTML = ''; // Clear existing options
                // Add the default "Select Province" option
                const defaultProvinceOption = document.createElement('option');
                defaultProvinceOption.value = "";
                defaultProvinceOption.textContent = "{{ __('انتخاب استان') }}";
                defaultProvinceOption.disabled = true;
                // Set 'selected' based on old value or if no old value exists
                defaultProvinceOption.selected = !("{{ old('province', $address->province ?? '') }}");
                provinceSelect.appendChild(defaultProvinceOption);

                provincesAndCitiesData.forEach(data => {
                    const option = document.createElement('option');
                    option.value = data.province;
                    option.textContent = data.province;
                    provinceSelect.appendChild(option);
                });
            }

            // Function to update cities dropdown based on selected province
            function updateCities() {
                const selectedProvinceName = provinceSelect.value;
                const selectedProvinceData = provincesAndCitiesData.find(data => data.province === selectedProvinceName);
                const cities = selectedProvinceData ? selectedProvinceData.cities : [];

                citySelect.innerHTML = ''; // Clear existing options
                // Add the default "Select City" option
                const defaultCityOption = document.createElement('option');
                defaultCityOption.value = "";
                defaultCityOption.textContent = "{{ __('انتخاب شهر') }}";
                defaultCityOption.disabled = true;
                // Set 'selected' based on old value or if no old value exists
                defaultCityOption.selected = !("{{ old('city', $address->city ?? '') }}");
                citySelect.appendChild(defaultCityOption);

                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
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

                // UX Improvement: Auto-select city if only one option is available
                if (cities.length === 1) {
                    citySelect.value = cities[0];
                }
            }

            // Function to convert Persian digits to English digits
            function convertToEnglishDigits(input) {
                const persianNumbers = [/۰/g, /۱/g, /۲/g, /۳/g, /۴/g, /۵/g, /۶/g, /۷/g, /۸/g, /۹/g];
                const arabicNumbers = [/٠/g, /١/g, /٢/g, /٣/g, /٤/g, /٥/g, /٦/g, /٧/g, /٨/g, /٩/g];

                for (let i = 0; i < 10; i++) {
                    input = input.replace(persianNumbers[i], i).replace(arabicNumbers[i], i);
                }
                return input;
            }

            // Initial population of provinces
            populateProvinces();

            // Set old province value if it exists
            const oldProvince = "{{ old('province', $address->province ?? '') }}";
            if (oldProvince) {
                provinceSelect.value = oldProvince;
                updateCities(); // Also update cities if an old province is set
            }

            // Add event listener for province change
            provinceSelect.addEventListener('change', updateCities);
        });
    </script>
</x-app-layout>
