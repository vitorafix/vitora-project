@extends('layouts.app')

@section('title', 'جزئیات محصول - چای ابراهیم') {{-- عنوان صفحه را عمومی‌تر کردم --}}

@section('content')
    {{-- Top Promotional Banner --}}
    <div class="bg-gradient-to-r from-green-700 to-green-900 text-white p-4 text-center text-sm font-semibold rounded-lg shadow-md mb-8">
        <p>🎁 ارسال رایگان برای خرید بالای <span class="font-bold">۱ میلیون تومان</span>! بعد از ثبت نام <span class="font-bold">۵ درصد تخفیف</span> بگیر!</p>
    </div>

    <section class="my-8 p-8">
        <div class="container mx-auto">
            {{-- Breadcrumbs --}}
            <nav class="text-gray-600 text-sm mb-6 flex items-center justify-start" aria-label="Breadcrumb">
                <ol class="list-none p-0 inline-flex items-center">
                    <li class="flex items-center">
                        <a href="{{ url('/') }}" class="text-green-800 hover:text-green-900">خانه</a>
                        <i class="fas fa-chevron-left text-xs mx-2"></i>
                    </li>
                    <li class="flex items-center">
                        <a href="{{ url('products') }}" class="text-green-800 hover:text-green-900">انواع چای</a>
                        <i class="fas fa-chevron-left text-xs mx-2"></i>
                    </li>
                    <li class="flex items-center">
                        <span id="breadcrumb-category" class="text-gray-600">{{ $product->category->name ?? 'دسته بندی محصول' }}</span> {{-- Dynamic Category --}}
                        <i class="fas fa-chevron-left text-xs mx-2"></i>
                    </li>
                    <li class="flex items-center">
                        <span id="breadcrumb-product-name" class="text-brown-900 font-semibold">{{ $product->title }}</span> {{-- Dynamic Product Name --}}
                    </li>
                </ol>
            </nav>

            <div id="product-detail-content" class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 grid grid-cols-1 lg:grid-cols-3 xl:grid-cols-4 gap-8 lg:gap-12 items-start">

                {{-- Product Details - Left Column (lg:col-span-2 xl:col-span-2) --}}
                <div class="lg:col-span-2 xl:col-span-2 order-2 lg:order-1 text-right">
                    <h1 id="product-detail-name" class="text-4xl font-bold text-brown-900 mb-4 leading-tight">{{ $product->title }}</h1>

                    <p id="product-detail-description" class="text-gray-700 text-lg leading-relaxed mb-6 border-b border-gray-200 pb-6">
                        {{ $product->description }}
                    </p>

                    <div class="text-gray-700 text-base mb-6 grid grid-cols-1 md:grid-cols-2 gap-y-2 gap-x-4">
                        <p><span class="font-semibold text-brown-900">وزن:</span> <span id="product-detail-weight">N/A</span> گرم</p> {{-- Placeholder, as weight is not in Product model --}}
                        <p><span class="font-semibold text-brown-900">نوع چای:</span> <span id="product-detail-tea-type">N/A</span></p>
                        <p><span class="font-semibold text-brown-900">فصل برداشت:</span> <span id="product-detail-harvest-season">N/A</span></p>
                        <p><span class="font-semibold text-brown-900">خاستگاه:</span> <span id="product-detail-origin">N/A</span></p>
                        <p><span class="font-semibold text-brown-900">برداشت:</span> <span id="product-detail-harvesting-method">N/A</span></p>
                        <p><span class="font-semibold text-brown-900">ترکیب:</span> <span id="product-detail-blend-info">N/A</span></p>
                        <p><span class="font-semibold text-brown-900">چای خالص (فاقد هر گونه افزودنی):</span> <span id="product-detail-pure-tea">N/A</span></p>
                        <p><span class="font-semibold text-brown-900">کد کالا:</span> <span id="product-detail-code">N/A</span></p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center sm:justify-start justify-center gap-6 mt-8">
                        <div class="flex items-center border border-gray-300 rounded-lg p-1 w-full sm:w-auto justify-between">
                            <button id="decrease-quantity" class="p-2 text-gray-700 hover:bg-gray-200 rounded-md transition-colors duration-200"><i class="fas fa-minus"></i></button>
                            <input type="number" id="product-quantity" value="1" min="1" class="w-16 text-center border-none focus:ring-0 text-2xl font-semibold text-brown-900 bg-transparent" readonly>
                            <button id="increase-quantity" class="p-2 text-gray-700 hover:bg-gray-200 rounded-md transition-colors duration-200"><i class="fas fa-plus"></i></button>
                        </div>
                        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                            <span id="product-detail-price" class="text-green-800 text-4xl font-bold">{{ $product->formatted_price }}</span>
                        </div>
                    </div>
                    @if ($product->stock > 0)
                        <button id="add-to-cart-detail-page"
                                class="bg-green-800 text-white px-8 py-3 rounded-xl text-xl font-semibold hover:bg-green-700 transition-all duration-300 shadow-lg w-full mt-6 flex items-center justify-center"
                                data-product-id="{{ $product->id }}"
                                data-product-title="{{ $product->title }}"
                                data-product-price="{{ $product->price }}"
                                data-product-stock="{{ $product->stock }}"> {{-- Add product stock for client-side validation --}}
                            <i class="fas fa-shopping-basket ml-3"></i> افزودن به سبد
                        </button>
                    @else
                        <button class="bg-gray-400 text-white px-8 py-3 rounded-xl text-xl font-semibold cursor-not-allowed w-full mt-6 flex items-center justify-center" disabled>
                            <i class="fas fa-ban ml-3"></i> ناموجود
                        </button>
                    @endif
                </div>

                {{-- Product Image Gallery - Right Column (lg:col-span-1 xl:col-span-2) --}}
                <div class="lg:col-span-1 xl:col-span-2 order-1 lg:order-2 flex flex-col items-center">
                    <div class="w-full relative rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                        {{-- Main image display --}}
                        <img id="product-detail-main-image"
                             src="{{ $product->images->first()->image_url ?? $product->image_url }}"
                             alt="{{ $product->title }}"
                             class="w-full h-auto object-cover rounded-xl">
                        {{-- Zoom icon (if needed) --}}
                        {{-- <button class="absolute top-4 left-4 bg-white/70 rounded-full p-2 text-gray-700 hover:bg-white transition-colors"><i class="fas fa-search-plus"></i></button> --}}
                    </div>
                    <div id="product-thumbnails" class="flex flex-row justify-center lg:flex-col gap-3 mt-6 w-full lg:w-24">
                        {{-- Display all product images as thumbnails --}}
                        @forelse ($product->images as $image)
                            <img src="{{ $image->image_url }}"
                                 class="w-20 h-auto rounded-lg border border-gray-300 cursor-pointer hover:border-green-800 transition-all duration-200 thumbnail-image {{ $loop->first ? 'active-thumbnail border-green-800' : '' }}"
                                 alt="Thumbnail {{ $loop->index + 1 }}"
                                 data-main-image-src="{{ $image->image_url }}">
                        @empty
                            {{-- If no gallery images, display the main product image as a single thumbnail --}}
                            <img src="{{ $product->image_url }}"
                                 class="w-20 h-auto rounded-lg border border-gray-300 cursor-pointer hover:border-green-800 transition-all duration-200 thumbnail-image active-thumbnail border-green-800"
                                 alt="Thumbnail 1"
                                 data-main-image-src="{{ $product->image_url }}">
                        @endforelse
                    </div>
                </div>

                {{-- Additional Info Blocks (below main content on smaller screens, or float on larger) --}}
                <div class="lg:col-span-3 xl:col-span-4 mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gray-100 p-6 rounded-xl shadow-md border border-gray-200 text-center">
                        <h4 class="text-xl font-bold text-brown-900 mb-3">طعم: <span id="product-detail-taste">N/A</span></h4>
                        <p class="text-gray-700 text-base"><span id="product-detail-taste-description">N/A</span></p>
                    </div>
                    <div class="bg-gray-100 p-6 rounded-xl shadow-md border border-gray-200 text-center">
                        <h4 class="text-xl font-bold text-brown-900 mb-3">طعم باد: <span id="product-detail-aroma">N/A</span></h4>
                        <p class="text-gray-700 text-base"><span id="product-detail-aroma-description">N/A</span></p>
                    </div>
                </div>
            </div>

            <div id="product-not-found" class="hidden text-center bg-white p-8 rounded-xl shadow-lg border border-gray-100 mt-8">
                <i class="fas fa-exclamation-circle text-6xl text-red-500 mb-4"></i>
                <h2 class="text-3xl font-bold text-brown-900 mb-4">محصول مورد نظر یافت نشد.</h2>
                <p class="text-gray-700 text-lg mb-6">متاسفانه، محصولی با این مشخصات پیدا نشد. لطفاً از <a href="{{ url('products') }}" class="text-green-800 hover:underline">صفحه محصولات</a> دیدن فرمایید.</p>
            </div>
        </div>
    </section>
@endsection
