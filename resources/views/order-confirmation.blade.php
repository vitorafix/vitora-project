@extends('layouts.app')

@section('title', 'تایید سفارش - چای ابراهیم')

@section('content')
<section class="container mx-auto px-4 py-8 md:py-16 max-w-4xl">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 md:p-8 text-center rtl:text-right">
        <i class="fas fa-check-circle text-green-600 text-6xl mb-6 animate-bounce"></i>
        <h1 class="text-4xl font-extrabold text-brown-900 mb-4">سفارش شما با موفقیت ثبت شد!</h1>
        <p class="text-gray-700 text-lg mb-8">از خرید شما متشکریم. جزئیات سفارش شما در زیر آمده است.</p>

        <div class="bg-gray-50 rounded-xl p-6 mb-8 text-right border border-gray-200">
            <h2 class="text-2xl font-bold text-brown-900 mb-4 flex items-center">
                <i class="fas fa-info-circle ml-2 text-green-700"></i>
                اطلاعات سفارش
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
                <p><strong>کد سفارش:</strong> <span class="text-green-700 font-semibold">{{ $order->id }}</span></p>
                {{-- استفاده از DateHelper برای نمایش تاریخ شمسی --}}
                <p><strong>تاریخ سفارش:</strong> {{ App\Helpers\DateHelper::toJalali($order->created_at, 'Y/m/d - H:i') }}</p>
                <p><strong>وضعیت:</strong> <span class="font-semibold text-orange-600">{{ __($order->status) }}</span></p> {{-- Translate status --}}
                <p><strong>مبلغ کل:</strong> <span class="font-bold text-brown-900">{{ number_format($order->total_amount) }} تومان</span></p>
            </div>
        </div>

        <div class="bg-gray-50 rounded-xl p-6 mb-8 text-right border border-gray-200">
            <h2 class="text-2xl font-bold text-brown-900 mb-4 flex items-center">
                <i class="fas fa-map-marker-alt ml-2 text-green-700"></i>
                اطلاعات ارسال
            </h2>
            <div class="text-gray-700 leading-relaxed">
                @if($order->user) {{-- اگر کاربر احراز هویت شده باشد و اطلاعات در مدل User باشد --}}
                    <p>{{ $order->user->name }}</p>
                    <p>{{ $order->user->mobile_number ?? 'شماره تماس ثبت نشده' }}</p>
                @endif
                <p>{{ $order->address }}</p>
                <p>{{ $order->city }}, {{ $order->province }} - {{ $order->postal_code }}</p>
                {{-- می‌توانید فیلدهای نام و نام خانوادگی را نیز از مدل Order دریافت کنید --}}
                {{-- <p>{{ $order->first_name }} {{ $order->last_name }}</p> --}}
            </div>
        </div>


        <div class="bg-gray-50 rounded-xl p-6 mb-8 text-right border border-gray-200">
            <h2 class="text-2xl font-bold text-brown-900 mb-4 flex items-center">
                <i class="fas fa-boxes ml-2 text-green-700"></i>
                اقلام سفارش
            </h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white rounded-lg overflow-hidden shadow-sm">
                    <thead class="bg-gray-200 text-gray-700 uppercase text-sm leading-normal">
                        <tr>
                            <th class="py-3 px-6 text-right">محصول</th>
                            <th class="py-3 px-6 text-center">تعداد</th>
                            <th class="py-3 px-6 text-center">قیمت واحد</th>
                            <th class="py-3 px-6 text-center">مجموع</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm font-light">
                        @foreach($order->items as $item)
                        <tr class="border-b last:border-b-0 hover:bg-gray-100">
                            <td class="py-3 px-6 text-right whitespace-nowrap">
                                <div class="flex items-center">
                                    <img src="{{ $item->product->image ?: 'https://placehold.co/50x50/E5E7EB/4B5563?text=P' }}"
                                         onerror="this.onerror=null;this.src='https://placehold.co/50x50/E5E7EB/4B5563?text=P';"
                                         alt="{{ $item->product->title }}" class="w-10 h-10 rounded-md object-cover ml-3">
                                    <span>{{ $item->product->title }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-6 text-center">{{ $item->quantity }}</td>
                            <td class="py-3 px-6 text-center">{{ number_format($item->price) }} تومان</td>
                            <td class="py-3 px-6 text-center font-bold text-brown-800">{{ number_format($item->quantity * $item->price) }} تومان</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-green-700 text-white font-bold text-lg">
                        <tr>
                            <td colspan="3" class="py-3 px-6 text-right">جمع کل سفارش:</td>
                            <td class="py-3 px-6 text-center">{{ number_format($order->total_amount) }} تومان</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="mt-8 flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4 sm:space-x-reverse">
            <a href="{{ route('home') }}" class="btn-primary inline-flex items-center">
                <i class="fas fa-home ml-2"></i>
                بازگشت به صفحه اصلی
            </a>
            {{--
            <a href="{{ route('orders.show', $order->id) }}" class="btn-secondary inline-flex items-center ml-4">
                <i class="fas fa-file-invoice ml-2"></i>
                مشاهده فاکتور
            </a>
            --}}
        </div>
    </div>
</section>
@endsection
