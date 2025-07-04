@extends('layouts.app')

@section('title', 'تکمیل و ثبت سفارش - چای ابراهیم')

@section('content')
<section class="container mx-auto px-4 py-8 md:py-16">
    <h1 class="text-4xl font-extrabold text-brown-900 mb-12 text-center">
        <i class="fas fa-clipboard-check text-green-700 ml-3"></i>
        تکمیل و ثبت سفارش
    </h1>

    {{-- فرم اصلی ثبت سفارش که شامل تمامی بخش‌ها می‌شود --}}
    {{-- اضافه شدن method="POST" برای fail-safe بودن فرم در صورت عدم اجرای جاوااسکریپت --}}
    <form id="place-order-form" class="space-y-6" method="POST" novalidate role="form" aria-label="فرم ثبت سفارش"
          data-addresses="{{ json_encode($addresses ?? []) }}"
          data-default-address="{{ json_encode($defaultAddress ?? null) }}">
        {{-- CSRF Token برای امنیت فرم‌های لاراول --}}
        @csrf

        {{-- نوار پیشرفت (Progress Bar) --}}
        <div class="w-full bg-gray-200 rounded-full h-2.5 mb-6 overflow-hidden">
            <div id="progress-bar" class="bg-green-600 h-2.5 rounded-full transition-all duration-500 ease-out" style="width: 0%;"></div>
        </div>

        {{-- Live Region برای اعلان خطاهای کلی فرم (مخصوص Screen Readers) --}}
        <div id="form-errors-live-region" class="sr-only" aria-live="polite" aria-atomic="true"></div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 p-6 md:p-8 flex flex-col gap-8">
            {{-- بخش اطلاعات ارسال و خلاصه سبد خرید در دو ستون (در دسکتاپ) --}}
            <div class="flex flex-col md:flex-row gap-8 w-full">
                {{-- بخش اطلاعات ارسال --}}
                {{-- این بخش اطلاعات آدرس و تماس مشتری را دریافت می‌کند. --}}
                <div class="md:w-1/2">
                    <h2 class="text-2xl font-semibold text-brown-900 mb-6 flex items-center">
                        <i class="fas fa-map-marker-alt ml-3 text-red-500"></i>
                        اطلاعات ارسال
                    </h2>

                    {{-- بخش انتخاب آدرس (اگر کاربر آدرس ثبت کرده باشد) --}}
                    @if ($addresses->isNotEmpty())
                    <div class="mb-6 border border-gray-200 rounded-lg p-4 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">انتخاب آدرس موجود:</h3>
                        <div class="space-y-3" id="address-selection-radios">
                            @foreach ($addresses as $address)
                                <div class="flex items-start p-3 border rounded-md cursor-pointer hover:bg-gray-100 transition-colors duration-200
                                    @if ($address->is_default) border-green-500 bg-green-50 @else border-gray-200 @endif">
                                    <input type="radio" id="address_{{ $address->id }}" name="selected_address_id"
                                           value="{{ $address->id }}" class="form-radio h-5 w-5 text-green-700 mt-1 cursor-pointer"
                                           @if ($address->is_default) checked @endif>
                                    <label for="address_{{ $address->id }}" class="mr-3 flex-1 cursor-pointer">
                                        <span class="font-medium text-gray-800">{{ $address->title ?: 'آدرس بدون عنوان' }}</span>
                                        <p class="text-gray-600 text-sm">{{ $address->address }}</p>
                                        <p class="text-gray-600 text-sm">{{ $address->city }}, {{ $address->province }} - {{ $address->postal_code }}</p>
                                        <p class="text-gray-600 text-sm">تلفن: {{ $address->phone_number }}</p>
                                    </label>
                                    @if ($address->is_default)
                                        <span class="bg-amber-500 text-white text-xs font-bold px-2 py-1 rounded-full mr-2">پیش‌فرض</span>
                                    @endif
                                </div>
                            @endforeach
                            {{-- گزینه برای وارد کردن آدرس جدید --}}
                            <div class="flex items-start p-3 border border-gray-200 rounded-md cursor-pointer hover:bg-gray-100 transition-colors duration-200">
                                <input type="radio" id="address_new" name="selected_address_id" value="new" class="form-radio h-5 w-5 text-green-700 mt-1 cursor-pointer"
                                    @if (!$defaultAddress) checked @endif> {{-- اگر آدرس پیش‌فرضی نبود، این گزینه انتخاب شود --}}
                                <label for="address_new" class="mr-3 flex-1 cursor-pointer">
                                    <span class="font-medium text-gray-800">افزودن آدرس جدید</span>
                                    <p class="text-gray-600 text-sm">فیلدهای زیر را برای وارد کردن آدرس جدید پر کنید.</p>
                                </label>
                            </div>
                        </div>
                    </div>
                    @else
                        <p class="text-gray-600 text-sm mb-4">شما هنوز آدرسی ثبت نکرده‌اید. لطفاً فیلدهای زیر را برای آدرس جدید پر کنید.</p>
                    @endif

                    {{-- فیلدهای اطلاعات آدرس که توسط JS پر می‌شوند یا دستی وارد می‌شوند --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- نام --}}
                        <div>
                            <label for="first_name" class="block text-gray-700 text-sm font-bold mb-2">نام:</label>
                            <input type="text" id="first_name" name="first_name" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-700 focus:border-green-700 p-2.5" placeholder="نام کوچک شما" required aria-describedby="first_name-error">
                            <div id="first_name-error" class="text-red-500 text-sm mt-1 hidden" role="alert"></div>
                        </div>
                        {{-- نام خانوادگی --}}
                        <div>
                            <label for="last_name" class="block text-gray-700 text-sm font-bold mb-2">نام خانوادگی:</label>
                            <input type="text" id="last_name" name="last_name" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-700 focus:border-green-700 p-2.5" placeholder="نام خانوادگی شما" required aria-describedby="last_name-error">
                            <div id="last_name-error" class="text-red-500 text-sm mt-1 hidden" role="alert"></div>
                        </div>
                        {{-- شماره تلفن --}}
                        <div class="md:col-span-2"> {{-- این فیلد تمام عرض را اشغال کند --}}
                            <label for="phone_number" class="block text-gray-700 text-sm font-bold mb-2">شماره تلفن:</label>
                            <input type="tel" id="phone_number" name="phone_number" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-700 focus:border-green-700 p-2.5" placeholder="مثال: 09123456789" required aria-describedby="phone_number-error">
                            <div id="phone_number-error" class="text-red-500 text-sm mt-1 hidden" role="alert"></div>
                        </div>
                        {{-- آدرس کامل --}}
                        <div class="md:col-span-2"> {{-- این فیلد تمام عرض را اشغال کند --}}
                            <label for="address" class="block text-gray-700 text-sm font-bold mb-2">آدرس کامل:</label>
                            <textarea id="address" name="address" rows="3" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-700 focus:border-green-700 p-2.5" placeholder="مثال: تهران، خیابان آزادی، کوچه اول، پلاک ۱۰" required aria-describedby="address-error"></textarea>
                            <div id="address-error" class="text-red-500 text-sm mt-1 hidden" role="alert"></div>
                        </div>
                        {{-- استان --}}
                        <div>
                            <label for="province" class="block text-gray-700 text-sm font-bold mb-2">استان:</label>
                            <input type="text" id="province" name="province" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-700 focus:border-green-700 p-2.5" placeholder="مثال: تهران" required aria-describedby="province-error">
                            <div id="province-error" class="text-red-500 text-sm mt-1 hidden" role="alert"></div>
                        </div>
                        {{-- شهر --}}
                        <div>
                            <label for="city" class="block text-gray-700 text-sm font-bold mb-2">شهر:</label>
                            <input type="text" id="city" name="city" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-700 focus:border-green-700 p-2.5" placeholder="مثال: تهران" required aria-describedby="city-error">
                            <div id="city-error" class="text-red-500 text-sm mt-1 hidden" role="alert"></div>
                        </div>
                        {{-- کد پستی --}}
                        <div class="md:col-span-2"> {{-- این فیلد تمام عرض را اشغال کند --}}
                            <label for="postal_code" class="block text-gray-700 text-sm font-bold mb-2">کد پستی:</label>
                            <input type="text" id="postal_code" name="postal_code" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-700 focus:border-green-700 p-2.5" placeholder="مثال: ۱۲۳۴۵۶۷۸۹۰" required aria-describedby="postal_code-error">
                            <div id="postal_code-error" class="text-red-500 text-sm mt-1 hidden" role="alert"></div>
                        </div>
                    </div>
                </div>

                {{-- بخش خلاصه سبد خرید --}}
                {{-- این بخش جزئیات محصولات موجود در سبد خرید و جمع کل را نمایش می‌دهد. --}}
                <div class="md:w-1/2 bg-gray-50 p-6 rounded-lg shadow-inner">
                    <h2 class="text-2xl font-semibold text-brown-900 mb-6 flex items-center justify-end">
                        خلاصه سبد خرید شما
                        <i class="fas fa-shopping-basket ml-3 text-orange-500"></i>
                    </h2>
                    <div class="space-y-4">
                        @forelse ($cartItems as $item)
                            <div class="flex justify-between items-center border-b pb-4 last:border-b-0 last:pb-0">
                                <div class="flex items-center">
                                    {{-- نمایش تصویر محصول یا یک تصویر placeholder --}}
                                    <img src="{{ $item->product->image ?: 'https://placehold.co/60x60/E5E7EB/4B5563?text=Product' }}" alt="{{ $item->product->title }}" class="w-16 h-16 object-cover rounded-lg ml-3">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800">{{ $item->product->title }}</h3>
                                        <p class="text-gray-600 text-sm">{{ number_format($item->quantity) }} عدد</p>
                                    </div>
                                </div>
                                {{-- نمایش قیمت کل برای هر آیتم --}}
                                <span class="text-green-700 font-bold text-lg">{{ number_format($item->price * $item->quantity) }} تومان</span>
                            </div>
                        @empty
                            {{-- پیام در صورت خالی بودن سبد خرید --}}
                            <p class="text-center text-gray-600 py-10">سبد خرید شما خالی است.</p>
                        @endforelse
                    </div>
                    @if (!$cartItems->isEmpty())
                        {{-- نمایش جمع کل سبد خرید --}}
                        <div class="border-t border-gray-200 pt-4 mt-6 flex justify-between items-center text-xl font-bold text-brown-900">
                            <span>جمع کل:</span>
                            <span class="text-green-700">{{ number_format($cart->getTotalPrice()) }} تومان</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- بخش انتخاب روش ارسال --}}
            {{-- این بخش به کاربر اجازه می‌دهد روش ارسال مورد نظر خود را انتخاب کند. --}}
            <div class="w-full mt-8 pt-8 border-t border-gray-200">
                <h2 class="text-2xl font-semibold text-brown-900 mb-6 flex items-center">
                    <i class="fas fa-shipping-fast ml-3 text-purple-600"></i>
                    انتخاب روش ارسال
                </h2>
                <div class="space-y-4">
                    {{-- گزینه پست پیشتاز --}}
                    <div class="flex items-center">
                        <input type="radio" id="shipping_post" name="shipping_method" value="post" class="form-radio h-5 w-5 text-green-700 focus:ring-green-700" checked required aria-describedby="shipping_method-error">
                        <label for="shipping_post" class="mr-3 text-gray-800 text-lg font-medium">پست پیشتاز</label>
                    </div>
                    <p class="text-gray-600 text-sm mr-8">تحویل ۲-۵ روز کاری. هزینه: ۱۵,۰۰۰ تومان</p>

                    {{-- گزینه پیک موتوری (فقط برای شهرهای خاص) --}}
                    <div class="flex items-center mt-4">
                        <input type="radio" id="shipping_courier" name="shipping_method" value="courier" class="form-radio h-5 w-5 text-green-700 focus:ring-green-700" required aria-describedby="shipping_method-error">
                        <label for="shipping_courier" class="mr-3 text-gray-800 text-lg font-medium">پیک موتوری</label>
                    </div>
                    <p class="text-gray-600 text-sm mr-8">تحویل ۱ روز کاری (فقط تهران). هزینه: ۳۰,۰۰۰ تومان</p>
                    <div id="shipping_method-error" class="text-red-500 text-sm mt-1 hidden" role="alert"></div>

                    {{-- امکان افزودن یادداشت برای پیک --}}
                    <div class="mt-6">
                        <label for="delivery_notes" class="block text-gray-700 text-sm font-bold mb-2">یادداشت برای پیک (اختیاری):</label>
                        <textarea id="delivery_notes" name="delivery_notes" rows="2" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring-green-700 focus:border-green-700 p-2.5" placeholder="مثلاً: تحویل به همسایه طبقه پایین در صورت عدم حضور"></textarea>
                    </div>
                </div>
            </div>

            {{-- بخش روش پرداخت --}}
            {{-- این بخش به کاربر اجازه می‌دهد روش پرداخت مورد نظر خود را انتخاب کند. --}}
            <div class="w-full mt-8 pt-8 border-t border-gray-200">
                <h2 class="text-2xl font-semibold text-brown-900 mb-6 flex items-center">
                    <i class="fas fa-wallet ml-3 text-blue-500"></i>
                    روش پرداخت
                </h2>
                <div class="space-y-4">
                    {{-- گزینه پرداخت آنلاین --}}
                    <div class="flex items-center">
                        <input type="radio" id="payment_online" name="payment_method" value="online" class="form-radio h-5 w-5 text-green-700 focus:ring-green-700" checked required aria-describedby="payment_method-error">
                        <label for="payment_online" class="mr-3 text-gray-800 text-lg font-medium">پرداخت آنلاین</label>
                    </div>
                    <p class="text-gray-600 text-sm mr-8">پرداخت از طریق درگاه‌های بانکی امن.</p>

                    {{-- گزینه پرداخت در محل --}}
                    <div class="flex items-center mt-4">
                        <input type="radio" id="payment_cod" name="payment_method" value="cod" class="form-radio h-5 w-5 text-green-700 focus:ring-green-700" required aria-describedby="payment_method-error">
                        <label for="payment_cod" class="mr-3 text-gray-800 text-lg font-medium">پرداخت در محل</label>
                    </div>
                    <p class="text-gray-600 text-sm mr-8">پرداخت نقدی یا با کارتخوان هنگام تحویل سفارش.</p>
                    <div id="payment_method-error" class="text-red-500 text-sm mt-1 hidden" role="alert"></div>
                </div>
            </div>

            {{-- بخش تأیید نهایی و دکمه ثبت سفارش --}}
            {{-- شامل چک‌باکس پذیرش قوانین و دکمه نهایی ثبت سفارش. --}}
            <div class="w-full mt-8 pt-6 border-t border-gray-200">
                <div class="flex items-center mb-6">
                    <input type="checkbox" id="terms_agree" name="terms_agree" class="form-checkbox h-5 w-5 text-green-700 rounded focus:ring-green-700" required aria-describedby="terms_agree-error">
                    <label for="terms_agree" class="mr-3 text-gray-700 text-base">
                        <a href="{{ route('rules') }}" class="text-blue-600 hover:underline" target="_blank">قوانین و مقررات</a> را مطالعه کرده و می‌پذیرم.
                    </label>
                </div>
                <div id="terms_agree-error" class="text-red-500 text-sm mt-1 hidden" role="alert"></div>
                {{-- دکمه ثبت سفارش --}}
                {{-- از کلاس btn-primary استفاده شده که فرض می‌شود در فایل CSS اصلی تعریف شده است. --}}
                <button type="submit" id="place-order-btn" class="btn-primary w-full flex items-center justify-center" aria-label="ثبت سفارش و پرداخت">
                    ثبت سفارش و پرداخت فرضی
                    <i class="fas fa-credit-card mr-2"></i>
                </button>
            </div>
        </div>
    </form>
</section>
@endsection

@push('scripts')
<script src="{{ asset('js/checkout.js') }}"></script>
@endpush
