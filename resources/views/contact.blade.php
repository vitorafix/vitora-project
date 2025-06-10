@extends('layouts.app')

@section('title', 'تماس با ما - چای ابراهیم')

@section('content')
    <section class="my-16 p-8">
        <h2 class="text-4xl font-bold text-center text-brown-900 mb-10 section-heading">تماس با ما</h2>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
            <!-- Contact Form Section -->
            <div class="bg-gray-50 p-8 rounded-xl shadow-lg border border-gray-100 card-hover-effect">
                <h3 class="text-3xl font-semibold text-brown-900 mb-6 text-center">ارسال پیام</h3>
                <form class="space-y-6">
                    <div>
                        <label for="name" class="block text-gray-700 text-lg font-medium mb-2">نام و نام خانوادگی:</label>
                        <input type="text" id="name" name="name" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-800 transition-all duration-300 text-gray-800" placeholder="نام کامل شما" required>
                    </div>
                    <div>
                        <label for="email" class="block text-gray-700 text-lg font-medium mb-2">ایمیل:</label>
                        <input type="email" id="email" name="email" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-800 transition-all duration-300 text-gray-800" placeholder="example@gmail.com" required>
                    </div>
                    <div>
                        <label for="subject" class="block text-gray-700 text-lg font-medium mb-2">موضوع:</label>
                        <input type="text" id="subject" name="subject" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-800 transition-all duration-300 text-gray-800" placeholder="موضوع پیام" required>
                    </div>
                    <div>
                        <label for="message" class="block text-gray-700 text-lg font-medium mb-2">پیام شما:</label>
                        <textarea id="message" name="message" rows="6" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-800 transition-all duration-300 text-gray-800 resize-y" placeholder="پیام خود را اینجا بنویسید..." required></textarea>
                    </div>
                    <button type="submit" class="bg-green-800 text-white px-8 py-3 rounded-full text-lg font-semibold hover:bg-green-700 transition-all duration-300 shadow-xl transform hover:scale-105 w-full">
                        ارسال پیام
                    </button>
                </form>
            </div>

            <!-- Address Details and Map Section -->
            <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-100 flex flex-col justify-between card-hover-effect">
                <div>
                    <h3 class="text-3xl font-semibold text-brown-900 mb-6 text-center lg:text-right">اطلاعات تماس</h3>
                    <div class="space-y-6 text-lg text-gray-700">
                        <p class="flex items-center lg:justify-end">
                            <i class="fas fa-map-marker-alt text-green-800 text-2xl ml-4"></i>
                            <span>دفتر بازرگانی: تهران، بزرگراه اشرفی اصفهانی، کوچه طباطبایی، ساختمان رویال</span>
                        </p>
                        <p class="flex items-center lg:justify-end">
                            <i class="fas fa-industry text-green-800 text-2xl ml-4"></i>
                            <span>کارخانه: لاهیجان، دو راهی بام سبز، خیابان سعدی، روستای کتشال، کارخانه چای سازی پیمان سبز لاهیج</span>
                        </p>
                        <p class="flex items-center lg:justify-end">
                            <i class="fas fa-phone-alt text-green-800 text-2xl ml-4"></i>
                            <span>تلفن: ۰۹۱۱۴۳۴۹۸۶۰</span>
                        </p>
                        <p class="flex items-center lg:justify-end">
                            <i class="fas fa-envelope text-green-800 text-2xl ml-4"></i>
                            <span>ایمیل: peymansabzlahijteafactory@gmail.com</span>
                        </p>
                    </div>
                </div>
                <div class="mt-8">
                     <h3 class="text-3xl font-semibold text-brown-900 mb-6 text-center lg:text-right">نقشه</h3>
                     <div class="w-full h-64 bg-gray-200 rounded-lg overflow-hidden shadow-md border border-gray-300 flex items-center justify-center">
                        <!-- Google Maps Embed for the Factory Address -->
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1671.0772271879793!2d50.01525979505052!3d37.20233481285273!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3f9fe5a0d5c0b0f7%3A0x6a0f0a8d4a9d7b4d!2sLahijan%2C%20Kachal%2C%20Saadi%20St%2C%20Peyman%20Sabz%20Lahij%20Tea%20Factory!5e0!3m2!1sen!2scom!4v1717838568779!5m2!1sen!2scom"
                            width="100%"
                            height="100%"
                            style="border:0;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                        ></iframe>
                     </div>
                </div>
            </div>
        </div>
    </section>
@endsection
