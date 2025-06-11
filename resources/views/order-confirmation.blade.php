@extends('layouts.app')

@section('title', 'تایید سفارش - چای ابراهیم')

@section('content')
<section class="container mx-auto px-4 py-8 md:py-16 text-center">
    <div class="bg-white rounded-xl shadow-lg p-8 md:p-12 max-w-2xl mx-auto">
        <i class="fas fa-check-circle text-green-500 text-6xl mb-6 animate-bounce"></i>
        <h1 class="text-4xl font-extrabold text-brown-900 mb-4">سفارش شما با موفقیت ثبت شد!</h1>
        <p class="text-gray-700 text-lg mb-8">از خرید شما متشکریم. جزئیات سفارش شما در زیر آمده است.</p>

        @if (isset($order))
            <div class="bg-gray-50 p-6 rounded-lg mb-8 text-right border border-gray-200">
                <h3 class="text-xl font-semibold text-brown-800 mb-4 border-b pb-3">جزئیات سفارش #{{ $order->id }}</h3>
                <p class="text-gray-700 mb-2"><strong>مبلغ کل:</strong> {{ number_format($order->total_amount) }} تومان</p>
                <p class="text-gray-700 mb-2"><strong>وضعیت:</strong> <span class="text-blue-600">{{ $order->status }}</span></p>
                <p class="text-700 mb-2">
                    <strong>آدرس ارسال:</strong>
                    {{ $order->address }}
                    @if ($order->city), {{ $order->city }}@endif
                    @if ($order->province), {{ $order->province }}@endif
                    @if ($order->postal_code), کد پستی: {{ $order->postal_code }}@endif
                </p>
                <p class="text-gray-700 mb-2"><strong>تاریخ ثبت:</strong> {{ $order->created_at->format('Y/m/d H:i') }}</p>
            </div>

            <h3 class="text-xl font-semibold text-brown-900 mb-4 border-b pb-3 text-right">آیتم‌های سفارش:</h3>
            <div class="overflow-x-auto mb-8">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                    <thead>
                        <tr class="bg-gray-100 text-right">
                            <th class="py-3 px-4 text-gray-600 font-semibold text-sm">محصول</th>
                            <th class="py-3 px-4 text-gray-600 font-semibold text-sm text-center">تعداد</th>
                            <th class="py-3 px-4 text-gray-600 font-semibold text-sm text-center">قیمت واحد</th>
                            <th class="py-3 px-4 text-gray-600 font-semibold text-sm text-left">جمع جزء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr class="border-b last:border-b-0 hover:bg-gray-50 text-right">
                                <td class="py-3 px-4 flex items-center">
                                    <img src="{{ $item->product->image ?: 'https://placehold.co/40x40/E5E7EB/4B5563?text=Product' }}" alt="{{ $item->product->title }}" class="w-10 h-10 object-cover rounded-md ml-3">
                                    <a href="{{ route('products.show', $item->product->id) }}" class="text-gray-800 font-semibold hover:text-green-700">{{ $item->product->title }}</a>
                                </td>
                                <td class="py-3 px-4 text-center">{{ number_format($item->quantity) }}</td>
                                <td class="py-3 px-4 text-center">{{ number_format($item->price) }} تومان</td>
                                <td class="py-3 px-4 text-left font-semibold">{{ number_format($item->quantity * $item->price) }} تومان</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @else
            <p class="text-red-500 text-lg">متاسفانه، جزئیات سفارش یافت نشد.</p>
        @endif

        <div class="mt-8 flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4 sm:space-x-reverse">
            <a href="{{ route('home') }}" class="btn-secondary flex items-center justify-center">
                <i class="fas fa-home ml-2"></i>
                بازگشت به صفحه اصلی
            </a>
            <a href="{{ route('products.index') }}" class="btn-primary flex items-center justify-center">
                <i class="fas fa-box-open ml-2"></i>
                مشاهده محصولات بیشتر
            </a>
        </div>
    </div>
</section>
@endsection
