@extends('layouts.app')

@section('title', $product->title . ' - چای ابراهیم')

@section('content')
<section class="container mx-auto px-4 py-8 md:py-16">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden md:flex md:items-center">
        {{-- Product Image Section --}}
        <div class="md:w-1/2 p-6 md:p-8 flex justify-center items-center">
            <img src="{{ $product->image ?: 'https://placehold.co/600x600/E5E7EB/4B5563?text=Product' }}" alt="{{ $product->title }}" class="w-full max-w-md h-auto rounded-lg shadow-md object-cover transition-transform duration-300 hover:scale-105">
        </div>

        {{-- Product Details Section --}}
        <div class="md:w-1/2 p-6 md:p-8 text-right flex flex-col justify-center">
            <h1 class="text-4xl font-extrabold text-brown-900 mb-4">{{ $product->title }}</h1>
            <p class="text-green-700 text-2xl font-bold mb-6">{{ number_format($product->price) }} تومان</p>

            <div class="mb-6">
                <h3 class="text-xl font-semibold text-brown-900 mb-2">توضیحات محصول:</h3>
                <p class="text-gray-700 leading-relaxed">{{ $product->description }}</p>
            </div>

            <div class="mb-6 text-gray-800">
                <p class="mb-2"><span class="font-semibold">دسته‌بندی:</span> {{ $product->category->name }}</p>
                <p><span class="font-semibold">موجودی:</span>
                    @if ($product->stock > 0)
                        <span class="text-green-600">{{ $product->stock }} عدد موجود</span>
                    @else
                        <span class="text-red-600">ناموجود</span>
                    @endif
                </p>
            </div>

            {{-- Add to Cart Section (React Component) --}}
            <div class="flex items-center justify-end space-x-4 space-x-reverse mt-6">
                <input type="number" id="product-quantity" value="1" min="1" max="{{ $product->stock }}" class="w-20 p-2 border border-gray-300 rounded-md text-center focus:outline-none focus:ring-2 focus:ring-green-700">
                @if ($product->stock > 0)
                    {{-- نقطه اتصال برای دکمه افزودن به سبد خرید React --}}
                    <div id="add-to-cart-root-{{ $product->id }}"
                         data-product-name="{{ $product->title }}"
                         data-product-price="{{ $product->price }}">
                    </div>
                @else
                    <p class="text-red-500 text-sm mt-4 text-right" id="out-of-stock-message">این محصول در حال حاضر ناموجود است.</p>
                @endif
            </div>

            {{-- Back button --}}
            <div class="mt-8 text-right">
                <a href="{{ url('/products') }}" class="text-green-800 hover:underline flex items-center justify-end">
                    <i class="fas fa-arrow-right ml-2"></i>
                    بازگشت به لیست محصولات
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
