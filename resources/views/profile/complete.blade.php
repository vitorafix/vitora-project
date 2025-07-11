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
                            {{-- Changed selected logic for initial load --}}
                            <option value="" disabled {{ old('province') ? '' : 'selected' }}>
                                {{ __('انتخاب استان') }}
                            </option>
                            {{-- Provinces will be populated dynamically by JavaScript --}}
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
                            {{-- Changed selected logic for initial load --}}
                            <option value="" disabled {{ old('city') ? '' : 'selected' }}>
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
                        {{ __('کد پستی ۱۰ رقمی ') }}
                        <i title='ضروری' class="fas fa-star-of-life text-red-500 fa-xs ml-1"></i> {{-- Added required indicator --}}
                    </label>
                    <input id="postal_code"
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400"
                           type="text"
                           name="postal_code"
                           value="{{ old('postal_code') }}"
                           placeholder="1234567890"
                           maxlength="10"
                           pattern="[0-9]{10}"
                           required> {{-- Added 'required' attribute here --}}
                    <span class='block text-xs text-gray-500 dark:text-gray-400 mt-1'>{{ __('کیبورد را در حالت انگلیسی قرار دهید') }}</span>
                    <x-input-error :messages="$errors->get('postal_code')" class="mt-2 text-sm" />
                </div>

                <!-- Email Address Field (Moved here and made optional) -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('آدرس ایمیل ') }}
                    </label>
                    <input id="email"
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400"
                           type="email"
                           name="email"
                           value="{{ old('email', $user->email ?? '') }}"
                           placeholder="example@example.com"> {{-- Removed 'required' attribute --}}
                    <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm" />
                </div>

                <!-- Fixed Phone Number Field (Fixed Line) -->
                <div>
                    <label for="fixed_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('شماره تلفن ثابت ') }}
                    </label>
                    <input id="fixed_phone"
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400"
                           type="text"
                           name="fixed_phone"
                           value="{{ old('fixed_phone') }}"
                           placeholder="مثال: 021XXXXXXXX"
                           maxlength="11" {{-- حداکثر طول 11 رقم برای شماره تلفن ثابت --}}
                           pattern="^0[0-9]{10}$" {{-- الگوی regex برای شماره تلفن ثابت: شروع با 0 و 10 رقم دیگر --}}
                           title="مثال: 02112345678" {{-- توضیح برای کاربر در صورت عدم تطابق الگو --}}
                           >
                    <span class='block text-xs text-gray-500 dark:text-gray-400 mt-1'>{{ __('شماره تلفن ثابت با کد شهر (مثال: 021) و ۱۰ رقم بعد از آن') }}</span>
                    <x-input-error :messages="$errors->get('fixed_phone')" class="mt-2 text-sm" />
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

    <!-- Script for Province-City dynamic dropdown -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const provinceSelect = document.getElementById('province');
            const citySelect = document.getElementById('city');

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

            // تابع برای پر کردن لیست کشویی استان‌ها
            function populateProvinces() {
                provinceSelect.innerHTML = ''; // پاک کردن گزینه‌های موجود
                // اضافه کردن گزینه پیش‌فرض "انتخاب استان"
                const defaultProvinceOption = document.createElement('option');
                defaultProvinceOption.value = "";
                defaultProvinceOption.textContent = "{{ __('انتخاب استان') }}";
                defaultProvinceOption.disabled = true;
                // تنظیم 'selected' بر اساس مقدار قبلی یا اگر هیچ مقدار قبلی وجود ندارد
                defaultProvinceOption.selected = !("{{ old('province') }}");
                provinceSelect.appendChild(defaultProvinceOption);

                provincesAndCitiesData.forEach(data => {
                    const option = document.createElement('option');
                    option.value = data.province;
                    option.textContent = data.province;
                    provinceSelect.appendChild(option);
                });
            }

            // تابع برای به‌روزرسانی لیست کشویی شهرها بر اساس استان انتخاب شده
            function updateCities() {
                const selectedProvinceName = provinceSelect.value;
                const selectedProvinceData = provincesAndCitiesData.find(data => data.province === selectedProvinceName);
                const cities = selectedProvinceData ? selectedProvinceData.cities : [];

                citySelect.innerHTML = ''; // پاک کردن گزینه‌های موجود
                // اضافه کردن گزینه پیش‌فرض "انتخاب شهر"
                const defaultCityOption = document.createElement('option');
                defaultCityOption.value = "";
                defaultCityOption.textContent = "{{ __('انتخاب شهر') }}";
                defaultCityOption.disabled = true;
                // تنظیم 'selected' بر اساس مقدار قبلی یا اگر هیچ مقدار قبلی وجود ندارد
                defaultCityOption.selected = !("{{ old('city') }}");
                citySelect.appendChild(defaultCityOption);

                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });

                // تنظیم مقدار شهر قبلی اگر وجود دارد و در شهرهای استان فعلی است
                const oldCity = "{{ old('city') }}";
                if (oldCity && cities.includes(oldCity)) {
                    citySelect.value = oldCity;
                } else {
                    // اگر شهر قبلی در لیست جدید نیست، یا آدرس جدید است، به گزینه پیش‌فرض بازنشانی شود
                    citySelect.value = "";
                }

                // بهبود UX: انتخاب خودکار شهر اگر فقط یک گزینه موجود باشد
                if (cities.length === 1) {
                    citySelect.value = cities[0];
                }
            }

            // پر کردن اولیه استان‌ها
            populateProvinces();

            // تنظیم مقدار استان قبلی اگر وجود دارد
            const oldProvince = "{{ old('province') }}";
            if (oldProvince) {
                provinceSelect.value = oldProvince;
                updateCities(); // همچنین شهرها را در صورت تنظیم استان قبلی به‌روزرسانی کنید
            }

            // اضافه کردن شنونده رویداد برای تغییر استان
            provinceSelect.addEventListener('change', updateCities);
        });
    </script>
</x-app-layout>
