@extends('layouts.app')

@section('title', 'محصولات ما - چای ابراهیم')

@section('content')
    <section class="container mx-auto px-4 py-8 md:py-16 max-w-6xl">
        <h1 class="text-4xl font-extrabold text-brown-900 mb-12 text-center">
            <i class="fas fa-box-open text-green-700 ml-3"></i>
            محصولات ما
        </h1>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @forelse ($products as $product)
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 card-hover-effect">
                    <a href="{{ route('products.show', $product->id) }}">
                        <img src="{{ $product->image ? asset($product->image) : 'https://placehold.co/400x400/E5E7EB/4B5563?text=Product' }}"
                             onerror="this.onerror=null;this.src='https://placehold.co/400x400/E5E7EB/4B5563?text=Product';"
                             alt="{{ $product->title }}"
                             class="w-full h-48 object-cover transition-transform duration-300 hover:scale-105">
                    </a>
                    <div class="p-5 text-center rtl:text-right">
                        <h3 class="text-xl font-semibold text-brown-900 mb-2 truncate">
                            <a href="{{ route('products.show', $product->id) }}" class="hover:text-green-700 transition-colors duration-200">
                                {{ $product->title }}
                            </a>
                        </h3>
                        @if ($product->category)
                            <p class="text-sm text-gray-500 mb-2">دسته‌بندی: {{ $product->category->name }}</p>
                        @endif
                        <p class="text-green-700 font-bold text-2xl mb-4">
                            {{ number_format($product->price) }} تومان
                        </p>
                        @if ($product->stock > 0)
                            <button class="add-to-cart-btn btn-primary w-full py-2 flex items-center justify-center text-lg"
                                    data-product-id="{{ $product->id }}"
                                    data-product-title="{{ $product->title }}"
                                    data-product-price="{{ $product->price }}">
                                <i class="fas fa-cart-plus ml-2"></i>
                                افزودن به سبد
                            </button>
                        @else
                            <button class="btn-disabled w-full py-2 flex items-center justify-center text-lg" disabled>
                                <i class="fas fa-ban ml-2"></i>
                                ناموجود
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-10">
                    <p class="text-gray-600 text-lg">
                        متاسفانه هیچ محصولی برای نمایش یافت نشد.
                    </p>
                    <a href="{{ route('home') }}" class="mt-4 inline-block btn-secondary">بازگشت به صفحه اصلی</a>
                </div>
            @endforelse
        </div>

        {{-- Pagination Links --}}
        <div class="mt-10 flex justify-center">
            {{ $products->links() }}
        </div>
    </section>
@endsection
