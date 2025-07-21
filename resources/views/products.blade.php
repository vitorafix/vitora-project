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

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @forelse ($products as $product)
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 card-hover-effect">
                    {{-- Link to product single page, now using product ID as per route definition --}}
                    <a href="{{ route('products.show', $product->id) }}">
                        {{-- Display the product image using the image_url accessor --}}
                        <img src="{{ $product->image_url }}"
                             alt="{{ $product->title }}"
                             class="w-full h-48 object-cover transition-transform duration-300 hover:scale-105"
                             onerror="this.onerror=null;this.src='https://placehold.co/400x400/E5E7EB/4B5563?text=Product';">
                    </a>

                    <div class="p-5 text-center rtl:text-right">
                        <h3 class="text-xl font-semibold text-brown-900 mb-2 truncate">
                            {{-- Link to product single page, now using product ID --}}
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
                        @if ($product->stock > 0 && $product->is_active) {{-- اضافه شدن شرط is_active --}}
                            <button class="add-to-cart-btn btn-primary w-full py-2 flex items-center justify-center text-lg"
                                    data-product-id="{{ $product->id }}"
                                    data-product-title="{{ $product->title }}"
                                    data-product-price="{{ $product->price }}"
                                    data-product-image="{{ $product->image_url }}">
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

@push('scripts')
    <script type="module">
        // Import API functions from api.js
        import { addProductToCart } from '{{ asset('js/api.js') }}'; // Adjust path if needed

        document.addEventListener('DOMContentLoaded', function() {
            const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');

            addToCartButtons.forEach(button => {
                const originalButtonText = button.innerHTML; // Store original text for each button

                button.addEventListener('click', async function() {
                    const productId = this.dataset.productId;
                    const quantity = 1; // Default quantity for products list page

                    // Show loading state
                    this.disabled = true;
                    this.classList.add('opacity-50', 'cursor-not-allowed');
                    this.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال افزودن...';

                    try {
                        const response = await addProductToCart(productId, quantity);
                        window.showMessage(response.message || 'محصول با موفقیت به سبد خرید اضافه شد.', 'success');
                        // Optionally, update cart icon count in navigation here if needed
                    } catch (error) {
                        const errorMessage = error.response?.data?.message || 'خطا در افزودن محصول به سبد خرید. لطفاً دوباره تلاش کنید.';
                        window.showMessage(errorMessage, 'error');
                        console.error('Error adding product to cart:', error);
                    } finally {
                        // Hide loading state
                        this.disabled = false;
                        this.classList.remove('opacity-50', 'cursor-not-allowed');
                        this.innerHTML = originalButtonText;
                    }
                });
            });
        });
    </script>
@endpush
