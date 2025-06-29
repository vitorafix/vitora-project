@extends('layouts.app')

@section('title', $product->title . ' - چای ابراهیم')

@section('content')
    <section class="container mx-auto px-4 py-8 md:py-16 max-w-6xl">
        <nav class="text-sm text-gray-500 mb-6 rtl:text-right" aria-label="breadcrumb">
            <ol class="list-none p-0 inline-flex">
                <li class="flex items-center">
                    <a href="{{ route('home') }}" class="text-green-700 hover:text-green-900">خانه</a>
                    <i class="fas fa-angle-left mx-2"></i>
                </li>
                <li class="flex items-center">
                    <a href="{{ route('products.index') }}" class="text-green-700 hover:text-green-900">محصولات</a>
                    <i class="fas fa-angle-left mx-2"></i>
                </li>
                <li class="flex items-center">
                    <span class="text-gray-900">{{ $product->title }}</span>
                </li>
            </ol>
        </nav>

        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 md:p-8 flex flex-col lg:flex-row gap-8 lg:gap-12 items-start">
            {{-- Product Image Gallery (Simple for now) --}}
            <div class="lg:w-1/2 w-full flex justify-center items-center rounded-xl overflow-hidden shadow-md">
                <img src="{{ $product->image ? asset($product->image) : 'https://placehold.co/600x600/E5E7EB/4B5563?text=No+Image' }}"
                     onerror="this.onerror=null;this.src='https://placehold.co/600x600/E5E7EB/4B5563?text=No+Image';"
                     alt="{{ $product->title }}"
                     class="w-full h-auto max-h-[500px] object-contain rounded-xl">
            </div>

            {{-- Product Details --}}
            <div class="lg:w-1/2 w-full space-y-6 rtl:text-right">
                <h1 class="text-4xl font-extrabold text-brown-900 leading-tight">{{ $product->title }}</h1>
                
                @if ($product->category)
                    <p class="text-sm text-gray-600">
                        دسته‌بندی: <a href="#" class="text-green-700 hover:underline font-semibold">{{ $product->category->name }}</a>
                    </p>
                @endif

                <div class="flex items-center text-3xl font-bold text-green-700">
                    <span class="ml-2">{{ number_format($product->price) }}</span>
                    <span>تومان</span>
                </div>

                <p class="text-gray-700 leading-relaxed text-base">
                    {{ $product->description }}
                </p>

                {{-- Stock Status --}}
                <div class="text-lg font-semibold flex items-center">
                    @if ($product->stock > 0)
                        <span class="text-green-600 flex items-center">
                            <i class="fas fa-check-circle ml-2"></i>
                            موجود در انبار: {{ number_format($product->stock) }} عدد
                        </span>
                    @else
                        <span class="text-red-600 flex items-center">
                            <i class="fas fa-times-circle ml-2"></i>
                            ناموجود
                        </span>
                    @endif
                </div>

                {{-- Add to Cart Button --}}
                <div class="mt-8">
                    @if ($product->stock > 0)
                        <button class="add-to-cart-btn btn-primary w-full flex items-center justify-center py-3 text-lg"
                                data-product-id="{{ $product->id }}"
                                data-product-title="{{ $product->title }}"
                                data-product-price="{{ $product->price }}">
                            <i class="fas fa-cart-plus ml-3"></i>
                            افزودن به سبد خرید
                        </button>
                    @else
                        <button class="btn-disabled w-full flex items-center justify-center py-3 text-lg" disabled>
                            <i class="fas fa-ban ml-3"></i>
                            ناموجود
                        </button>
                    @endif
                </div>

                {{-- Reviews Section (Placeholder) --}}
                <div class="mt-12 border-t pt-8 border-gray-200">
                    <h2 class="text-2xl font-bold text-brown-900 mb-6">دیدگاه‌ها</h2>
                    <p class="text-gray-600">هنوز دیدگاهی برای این محصول ثبت نشده است. اولین دیدگاه را شما بنویسید!</p>
                    {{-- Form for adding reviews would go here --}}
                </div>
            </div>
        </div>

        {{-- Related Products Section --}}
        @if ($relatedProducts->isNotEmpty()) {{-- فقط در صورتی که محصولات مرتبطی وجود دارند، این بخش را نمایش بده --}}
            <div class="mt-16">
                <h2 class="text-3xl font-bold text-brown-900 text-center mb-8">محصولات مرتبط</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach ($relatedProducts as $relatedProduct)
                        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 p-4 text-center card-hover-effect">
                            <a href="{{ route('products.show', $relatedProduct->id) }}">
                                <img src="{{ $relatedProduct->image ?: 'https://placehold.co/300x300/E5E7EB/4B5563?text=Related+Product' }}"
                                     onerror="this.onerror=null;this.src='https://placehold.co/300x300/E5E7EB/4B5563?text=Related+Product';"
                                     alt="{{ $relatedProduct->title }}"
                                     class="w-full h-40 object-cover mb-4 rounded-lg transition-transform duration-300 hover:scale-105">
                            </a>
                            <h3 class="text-lg font-semibold text-brown-900 mb-1 truncate">
                                <a href="{{ route('products.show', $relatedProduct->id) }}" class="hover:text-green-700 transition-colors duration-200">
                                    {{ $relatedProduct->title }}
                                </a>
                            </h3>
                            <p class="text-green-700 font-bold">{{ number_format($relatedProduct->price) }} تومان</p>
                            <button class="add-to-cart-btn btn-secondary text-sm mt-3 flex items-center justify-center w-full"
                                    data-product-id="{{ $relatedProduct->id }}"
                                    data-product-title="{{ $relatedProduct->title }}"
                                    data-product-price="{{ $relatedProduct->price }}">
                                <i class="fas fa-cart-plus ml-1"></i>
                                افزودن به سبد
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
@endsection
