<footer class="bg-brown-900 text-gray-200 p-8 rounded-t-3xl shadow-inner">
    {{-- اضافه کردن یک div والد برای محدود کردن عرض کل محتوای فوتر به max-w-6xl --}}
    <div class="container mx-auto max-w-6xl px-4 sm:px-6 lg:px-8"> {{-- Added responsive padding --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8 gap-y-4">
            <div>
                <h3 class="text-2xl font-bold mb-4 text-white">چای ابراهیم</h3>
                <p class="text-sm leading-relaxed">ما با عشق و دقت، بهترین چای‌ها را برای شما فراهم می‌کنیم تا هر لحظه شما را با طعم و عطر بی‌نظیر چای، خاطره‌انگیز کنیم.</p>
                <div class="flex space-x-5 space-x-reverse mt-6">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
                        <i class="fab fa-instagram text-3xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
                        <i class="fas fa-envelope text-3xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-300">
                        <i class="fab fa-twitter text-3xl"></i>
                    </a>
                </div>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-4 text-white">دسترسی سریع</h3>
                <ul class="space-y-3">
                    <li><a href="{{ url('/') }}" class="text-gray-400 hover:text-white transition-colors duration-300">خانه</a></li>
                    <li><a href="{{ url('/products') }}" class="text-gray-400 hover:text-white transition-colors duration-300">محصولات</a></li>
                    <li><a href="{{ url('/blog') }}" class="text-gray-400 hover:text-white transition-colors duration-300">بلاگ</a></li>
                    <li><a href="{{ url('/faq') }}" class="text-gray-400 hover:text-white transition-colors duration-300">سوالات متداول</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-4 text-white">تماس با ما</h3>
                <p class="text-sm mb-3">📍 دفتر بازرگانی: تهران، بزرگراه اشرفی اصفهانی، کوچه طباطبایی، ساختمان رویال</p>
                <p class="text-sm mb-3">🏭 کارخانه: لاهیجان، دو راهی بام سبز، خیابان سعدی، روستای کتشال، کارخانه چای سازی پیمان سبز لاهیج</p>
                <p class="text-sm mb-3">📞 تلفن: ۰۹۱۱۴۳۴۹۸۶۰</p>
                <p class="text-sm">✉️ ایمیل: peymansabzlahijteafactory@gmail.com</p>
            </div>
        </div>

        {{-- تغییر max-w-4xl به max-w-6xl برای هماهنگی با عرض کلی سایت --}}
        <div class="bg-brown-800 py-12 px-8 rounded-2xl text-white text-center shadow-lg mt-8 mx-auto w-full max-w-6xl">
            <h2 class="text-3xl font-bold mb-6">در خبرنامه ما عضو شوید</h2>
            <p class="text-gray-200 leading-loose max-w-2xl mx-auto mb-8 text-base">
                جدیدترین اخبار، تخفیف‌ها و مقالات جذاب چای را مستقیماً در ایمیل خود دریافت کنید.
            </p>
            <form class="max-w-xl mx-auto flex flex-col md:flex-row gap-4 items-center">
                <input type="email" placeholder="ایمیل خود را وارد کنید..." class="flex-grow p-3 rounded-full text-gray-800 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-700 transition-all duration-300 shadow-md w-full md:w-auto" required>
                <button type="submit" class="bg-green-700 text-white px-7 py-3 rounded-full text-base font-semibold hover:bg-green-800 transition-all duration-300 shadow-xl transform hover:scale-105 w-full md:w-auto">
                    عضویت
                </button>
            </form>
        </div>

        <div class="text-center mt-12 pt-6 border-t border-gray-700">
            <p>&copy; 2023 چای ابراهیم. تمامی حقوق محفوظ است.</p>
        </div>
    </div>
</footer>
