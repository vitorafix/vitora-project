@extends('layouts.app')

@section('title', 'جزئیات محصول - چای ابراهیم')

@section('content')
    {{-- Top Promotional Banner --}}
    div class=bg-gradient-to-r from-green-700 to-green-900 text-white p-4 text-center text-sm font-semibold rounded-lg shadow-md mb-8
        p🎁 ارسال رایگان برای خرید بالای span class=font-bold۱ میلیون تومانspan!  بعد از ثبت نام span class=font-bold۵ درصد تخفیفspan بگیر!p
    div

    section class=my-8 p-8
        div class=container mx-auto
            {{-- Breadcrumbs --}}
            nav class=text-gray-600 text-sm mb-6 flex items-center justify-start aria-label=Breadcrumb
                ol class=list-none p-0 inline-flex items-center
                    li class=flex items-center
                        a href={{ url('') }} class=text-green-800 hovertext-green-900خانهa
                        i class=fas fa-chevron-left text-xs mx-2i
                    li
                    li class=flex items-center
                        a href={{ url('products') }} class=text-green-800 hovertext-green-900انواع چایa
                        i class=fas fa-chevron-left text-xs mx-2i
                    li
                    li class=flex items-center
                        span id=breadcrumb-category class=text-gray-600دسته بندی محصولspan {{-- Dynamic Category --}}
                        i class=fas fa-chevron-left text-xs mx-2i
                    li
                    li class=flex items-center
                        span id=breadcrumb-product-name class=text-brown-900 font-semiboldنام محصولspan {{-- Dynamic Product Name --}}
                    li
                ol
            nav

            div id=product-detail-content class=bg-white rounded-xl shadow-lg border border-gray-100 p-8 grid grid-cols-1 lggrid-cols-3 xlgrid-cols-4 gap-8 lggap-12 items-start
                
                {{-- Product Details - Left Column (lgcol-span-2  xlcol-span-2) --}}
                div class=lgcol-span-2 xlcol-span-2 order-2 lgorder-1 text-right
                    h1 id=product-detail-name class=text-4xl font-bold text-brown-900 mb-4 leading-tightنام محصولh1
                    
                    p id=product-detail-description class=text-gray-700 text-lg leading-relaxed mb-6 border-b border-gray-200 pb-6
                        توضیحات محصول به صورت کامل در اینجا قرار می‌گیرد. این توضیحات می‌تواند شامل جزئیات طعم، روش تولید، فواید و هر اطلاعات دیگری باشد که مشتری نیاز دارد بداند.
                    p

                    div class=text-gray-700 text-base mb-6 grid grid-cols-1 mdgrid-cols-2 gap-y-2 gap-x-4
                        pspan class=font-semibold text-brown-900وزنspan span id=product-detail-weightspan گرمp
                        pspan class=font-semibold text-brown-900نوع چایspan span id=product-detail-tea-typespanp
                        pspan class=font-semibold text-brown-900فصل برداشتspan span id=product-detail-harvest-seasonspanp
                        pspan class=font-semibold text-brown-900خاستگاهspan span id=product-detail-originspanp
                        pspan class=font-semibold text-brown-900برداشتspan span id=product-detail-harvesting-methodspanp
                        pspan class=font-semibold text-brown-900ترکیبspan span id=product-detail-blend-infospanp
                        pspan class=font-semibold text-brown-900چای خالص (فاقد هر گونه افزودنی)span span id=product-detail-pure-teaspanp
                        pspan class=font-semibold text-brown-900کد کالاspan span id=product-detail-codespanp
                    div

                    div class=flex flex-col smflex-row items-center smjustify-start justify-center gap-6 mt-8
                        div class=flex items-center border border-gray-300 rounded-lg p-1 w-full smw-auto justify-between
                            button id=decrease-quantity class=p-2 text-gray-700 hoverbg-gray-200 rounded-md transition-colors duration-200i class=fas fa-minusibutton
                            input type=number id=product-quantity value=1 min=1 class=w-16 text-center border-none focusring-0 text-2xl font-semibold text-brown-900 bg-transparent readonly
                            button id=increase-quantity class=p-2 text-gray-700 hoverbg-gray-200 rounded-md transition-colors duration-200i class=fas fa-plusibutton
                        div
                        div class=flex items-center gap-2 w-full smw-auto justify-end
                            span id=product-detail-price class=text-green-800 text-4xl font-bold۰ تومانspan
                        div
                    div
                    button id=add-to-cart-detail-page class=bg-green-800 text-white px-8 py-3 rounded-xl text-xl font-semibold hoverbg-green-700 transition-all duration-300 shadow-lg w-full mt-6 flex items-center justify-center
                        i class=fas fa-shopping-basket ml-3i افزودن به سبد
                    button
                div

                {{-- Product Image Gallery - Right Column (lgcol-span-1  xlcol-span-2) --}}
                div class=lgcol-span-1 xlcol-span-2 order-1 lgorder-2 flex flex-col items-center
                    div class=w-full relative rounded-xl shadow-lg border border-gray-200 overflow-hidden
                        img id=product-detail-main-image src=httpsplacehold.co600x450E0E0E04A4A4Atext=No+Image alt=تصویر اصلی محصول class=w-full h-auto object-cover rounded-xl
                        {{-- Zoom icon (if needed) --}}
                        {{-- button class=absolute top-4 left-4 bg-white70 rounded-full p-2 text-gray-700 hoverbg-white transition-colorsi class=fas fa-search-plusibutton --}}
                    div
                    div id=product-thumbnails class=flex flex-row justify-center lgflex-col gap-3 mt-6 w-full lgw-24
                        {{-- Thumbnails will be loaded here by JavaScript --}}
                        {{-- Example Thumbnail (will be dynamic) --}}
                        {{-- img src=httpsplacehold.co100x75E0E0E04A4A4Atext=Thumb1 class=w-20 h-auto rounded-lg border border-gray-300 cursor-pointer hoverborder-green-800 transition-all duration-200 alt=Thumbnail 1 --}}
                    div
                div

                {{-- Additional Info Blocks (below main content on smaller screens, or float on larger) --}}
                div class=lgcol-span-3 xlcol-span-4 mt-8 grid grid-cols-1 mdgrid-cols-2 gap-6
                    div class=bg-gray-100 p-6 rounded-xl shadow-md border border-gray-200 text-center
                        h4 class=text-xl font-bold text-brown-900 mb-3طعم span id=product-detail-tastespanh4
                        p class=text-gray-700 text-basespan id=product-detail-taste-descriptionspanp
                    div
                    div class=bg-gray-100 p-6 rounded-xl shadow-md border border-gray-200 text-center
                        h4 class=text-xl font-bold text-brown-900 mb-3طعم باد span id=product-detail-aromaspanh4
                        p class=text-gray-700 text-basespan id=product-detail-aroma-descriptionspanp
                    div
                div

            div

            div id=product-not-found class=hidden text-center bg-white p-8 rounded-xl shadow-lg border border-gray-100 mt-8
                i class=fas fa-exclamation-circle text-6xl text-red-500 mb-4i
                h2 class=text-3xl font-bold text-brown-900 mb-4محصول مورد نظر یافت نشد.h2
                p class=text-gray-700 text-lg mb-6متاسفانه، محصولی با این مشخصات پیدا نشد. لطفاً از a href={{ url('products') }} class=text-green-800 hoverunderlineصفحه محصولاتa دیدن فرمایید.p
            div
        div
    section
@endsection
