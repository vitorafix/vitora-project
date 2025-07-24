@extends('layouts.app')

@section('title', 'محصولات ما - چای ابراهیم')

@section('content')
    <section class="container mx-auto px-4 py-8 md:py-16 max-w-6xl">
        <h1 class="text-4xl font-extrabold text-brown-900 mb-12 text-center">
            <i class="fas fa-box-open text-green-700 ml-3"></i>
            محصولات ما
        </h1>

        {{-- Displaying session messages --}}
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">موفقیت!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">خطا!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 gap-8">
            @forelse ($products as $product)
                <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 transform transition-transform duration-300 hover:scale-105 hover:shadow-xl group">
                    <div class="relative overflow-hidden">
                        <img src="{{ $product->image_url }}" alt="{{ $product->title }}" class="w-full h-64 object-cover transition-transform duration-300 group-hover:scale-110"
                             onerror="this.onerror=null;this.src='https://placehold.co/400x400/E5E7EB/4B5563?text=Product';">
                        <div class="absolute inset-0 bg-black bg-opacity-20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <a href="{{ route('products.show', $product->id) }}" class="btn-primary-outline text-white border-white">
                                مشاهده جزئیات
                            </a>
                        </div>
                    </div>
                    <div class="p-6 text-right">
                        <h3 class="text-xl font-semibold text-brown-900 mb-2">{{ $product->title }}</h3>
                        <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ $product->description }}</p>
                        <div class="flex justify-between items-center mt-4">
                            {{-- قیمت محصول --}}
                            <span class="text-green-700 text-2xl font-bold">{{ number_format($product->price) }} تومان</span>
                            {{-- نقطه اتصال برای دکمه افزودن به سبد خرید React --}}
                            @if ($product->stock > 0)
                                <div id="add-to-cart-root-{{ $product->id }}"
                                     data-product-name="{{ $product->title }}"
                                     data-product-price="{{ $product->price }}">
                                </div>
                            @else
                                <button class="btn-disabled w-full py-2 flex items-center justify-center text-lg" disabled>
                                    <i class="fas fa-ban ml-2"></i>
                                    ناموجود
                                </button>
                            @endif
                        </div>
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
