@extends('layouts.app')

@section('title', 'محصولات - چای ابراهیم')

@section('content')
<section class="container mx-auto px-4 py-8 md:py-16">
    <h1 class="text-4xl font-extrabold text-brown-900 mb-12 text-center">
        <i class="fas fa-box-open text-green-700 ml-3"></i>
        لیست کامل محصولات
    </h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
        @forelse ($products as $product)
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 transform transition-transform duration-300 hover:scale-105 hover:shadow-xl group">
                <div class="relative overflow-hidden">
                    <img src="{{ $product->image ?: 'https://placehold.co/400x400/E5E7EB/4B5563?text=Product' }}" alt="{{ $product->title }}" class="w-full h-64 object-cover transition-transform duration-300 group-hover:scale-110">
                    <div class="absolute inset-0 bg-black bg-opacity-20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <a href="{{ url('/products/' . $product->id) }}" class="btn-primary-outline text-white border-white">
                            مشاهده جزئیات
                        </a>
                    </div>
                </div>
                <div class="p-6 text-right">
                    <h3 class="text-xl font-semibold text-brown-900 mb-2">{{ $product->title }}</h3>
                    <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ $product->description }}</p>
                    <div class="flex justify-between items-center mt-4">
                        <span class="text-green-700 text-2xl font-bold">{{ number_format($product->price) }} تومان</span>
                        <button class="btn-primary add-to-cart-btn flex items-center"
                                data-product-id="{{ $product->id }}"
                                data-product-title="{{ $product->title }}"
                                data-product-price="{{ $product->price }}"
                                data-product-image="{{ $product->image ?: 'https://placehold.co/400x400/E5E7EB/4B5563?text=Product' }}">
                            <i class="fas fa-cart-plus ml-2"></i>
                            افزودن به سبد
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-center text-gray-600 col-span-full">هیچ محصولی برای نمایش وجود ندارد.</p>
        @endforelse
    </div>
</section>
@endsection
