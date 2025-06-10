@extends('layouts.app')

@section('title', 'سبد خرید - چای ابراهیم')

@section('content')
    <section class="my-16 p-8">
        <h1 class="text-4xl font-bold text-center text-brown-900 mb-10 section-heading">سبد خرید شما</h1>

        <div id="cart-content"> {{-- این div همیشه شامل جدول و خلاصه سفارش خواهد بود --}}
            <div class="overflow-x-auto bg-white rounded-xl shadow-lg border border-gray-100">
                <table class="w-full cart-table text-gray-700">
                    <thead>
                        <tr>
                            {{-- عنوان محصول: تراز به راست --}}
                            <th class="py-4 px-3 text-right rounded-tr-xl">عنوان محصول</th>
                            {{-- قیمت واحد: تراز به راست --}}
                            <th class="py-4 px-3 text-right">قیمت واحد</th>
                            {{-- تعداد / واحد: تراز به مرکز --}}
                            <th class="py-4 px-3 text-center">تعداد / واحد</th>
                            {{-- جمع جزء: تراز به راست --}}
                            <th class="py-4 px-3 text-right">جمع جزء</th>
                            {{-- حذف: تراز به مرکز --}}
                            <th class="py-4 px-3 text-center rounded-tl-xl">حذف</th>
                        </tr>
                    </thead>
                    <tbody id="cart-items-container">
                        <!-- آیتم‌های سبد خرید توسط جاوااسکریپت اینجا بارگذاری می‌شوند -->
                    </tbody>
                    <tfoot id="cart-total-row" class="hidden"> {{-- سطر مجموع کل در footer جدول، در صورت خالی بودن سبد پنهان است --}}
                        <tr>
                            <td colspan="3" class="py-4 px-3 text-left font-bold text-brown-900 text-xl">مجموع کل:</td>
                            <td colspan="2" class="py-4 px-3 text-right font-bold text-green-800 text-2xl" id="cart-total-price-footer">۰ تومان</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- کانتینر برای کد سفارش و دکمه‌ها --}}
            {{-- با استفاده از flex-row و justify-between، محتوا به صورت افقی چیده شده و به طرفین فشرده می‌شود --}}
            <div class="flex flex-col md:flex-row justify-between items-center mt-8 p-6 bg-green-50 rounded-xl shadow-md border border-green-100">
                {{-- کد سفارش --}}
                <div class="text-brown-900 font-bold text-2xl mb-4 md:mb-0"> {{-- در موبایل مارجین پایین دارد، در دسکتاپ ندارد --}}
                    کد سفارش شما: <span id="cart-order-id" class="text-green-800 text-3xl ltr-text">۹۸۷۶۵۴۳۲۱۰</span>
                </div>
                {{-- دکمه‌ها --}}
                <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto">
                    {{-- دکمه تکمیل خرید با فونت بولد، حاشیه سبز مستطیلی، بگراند سبز و متن "تایید سفارش" --}}
                    <a href="{{ url('/checkout') }}" class="btn-primary w-full sm:w-auto text-center font-bold !rounded-none border border-green-800 !bg-green-800 !text-white">
                        تایید سفارش
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
