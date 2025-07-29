@extends('layouts.app')

@section('title', 'ویرایش محصول - چای ابراهیم')

@section('content')
    <section class="container mx-auto px-4 py-8 md:py-16 max-w-4xl">
        <h1 class="text-4xl font-extrabold text-brown-900 mb-12 text-center">
            <i class="fas fa-edit text-green-700 ml-3"></i>
            ویرایش محصول: {{ $product->title }}
        </h1>

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

        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
            <form action="{{ route('products.update', $product->slug) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT') {{-- Use PUT method for update requests --}}

                {{-- Product Title --}}
                <div class="mb-6">
                    <label for="title" class="block text-gray-700 text-lg font-semibold mb-2">عنوان محصول:</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $product->title) }}"
                           class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-green-700 focus:border-green-700 transition-all duration-200"
                           placeholder="مثال: چای سیاه ممتاز سیلان" required>
                    @error('title')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Product Description --}}
                <div class="mb-6">
                    <label for="description" class="block text-gray-700 text-lg font-semibold mb-2">توضیحات محصول:</label>
                    <textarea id="description" name="description" rows="6"
                              class="form-textarea w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-green-700 focus:border-green-700 transition-all duration-200"
                              placeholder="توضیحات کامل محصول را اینجا بنویسید.">{{ old('description', $product->description) }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Price and Stock --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="price" class="block text-gray-700 text-lg font-semibold mb-2">قیمت (تومان):</label>
                        <input type="number" id="price" name="price" value="{{ old('price', $product->price) }}" step="0.01" min="0"
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-green-700 focus:border-green-700 transition-all duration-200"
                               placeholder="مثال: 150000" required>
                        @error('price')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="stock" class="block text-gray-700 text-lg font-semibold mb-2">موجودی:</label>
                        <input type="number" id="stock" name="stock" value="{{ old('stock', $product->stock) }}" min="0"
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-green-700 focus:border-green-700 transition-all duration-200"
                               placeholder="مثال: 100" required>
                        @error('stock')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Category and Status --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="category_id" class="block text-gray-700 text-lg font-semibold mb-2">دسته‌بندی:</label>
                        <select id="category_id" name="category_id"
                                class="form-select w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-green-700 focus:border-green-700 transition-all duration-200"
                                required>
                            <option value="">انتخاب دسته‌بندی</option>
                            {{-- Assuming $categories is passed from the controller, similar to create --}}
                            @isset($categories)
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            @else
                                {{-- Placeholder options if categories are not passed --}}
                                <option value="1" {{ old('category_id', $product->category_id) == 1 ? 'selected' : '' }}>چای سیاه</option>
                                <option value="2" {{ old('category_id', $product->category_id) == 2 ? 'selected' : '' }}>چای سبز</option>
                                <option value="3" {{ old('category_id', $product->category_id) == 3 ? 'selected' : '' }}>چای سفید</option>
                            @endisset
                        </select>
                        @error('category_id')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="status" class="block text-gray-700 text-lg font-semibold mb-2">وضعیت:</label>
                        <select id="status" name="status"
                                class="form-select w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-green-700 focus:border-green-700 transition-all duration-200"
                                required>
                            <option value="1" {{ old('status', $product->status) == 1 ? 'selected' : '' }}>فعال</option>
                            <option value="0" {{ old('status', $product->status) == 0 ? 'selected' : '' }}>غیرفعال</option>
                        </select>
                        @error('status')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Current Main Product Image --}}
                <div class="mb-6">
                    <label class="block text-gray-700 text-lg font-semibold mb-2">تصویر اصلی فعلی:</label>
                    @if ($product->image)
                        <div class="flex items-center space-x-4 rtl:space-x-reverse mb-4">
                            <img src="{{ $product->image_url }}" alt="تصویر اصلی محصول" class="w-32 h-32 object-cover rounded-lg shadow">
                            <div class="flex items-center">
                                <input type="checkbox" id="remove_image" name="remove_image" value="1"
                                       class="form-checkbox h-5 w-5 text-red-600 rounded focus:ring-red-500">
                                <label for="remove_image" class="ml-2 text-gray-700">حذف تصویر اصلی</label>
                            </div>
                        </div>
                    @else
                        <p class="text-gray-500 mb-4">تصویر اصلی برای این محصول وجود ندارد.</p>
                    @endif
                    <label for="image" class="block text-gray-700 text-lg font-semibold mb-2">آپلود تصویر اصلی جدید (اختیاری):</label>
                    <input type="file" id="image" name="image"
                           class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-green-700 focus:border-green-700 transition-all duration-200 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 cursor-pointer">
                    @error('image')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Current Gallery Images --}}
                <div class="mb-6">
                    <label class="block text-gray-700 text-lg font-semibold mb-2">تصاویر گالری فعلی:</label>
                    @if ($product->images->count() > 0)
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-4" id="current-gallery-images">
                            @foreach ($product->images as $image)
                                <div class="relative group border border-gray-200 rounded-lg overflow-hidden shadow-sm">
                                    <img src="{{ $image->image_url }}" alt="تصویر گالری" class="w-full h-32 object-cover">
                                    <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        <button type="button" class="remove-gallery-image-btn text-white bg-red-600 hover:bg-red-700 rounded-full p-2 text-lg" data-image-id="{{ $image->id }}">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    {{-- Hidden input to mark for deletion --}}
                                    <input type="hidden" name="remove_gallery_images[]" class="remove-image-input" value="">
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 mb-4">هیچ تصویر گالری برای این محصول وجود ندارد.</p>
                    @endif

                    <label for="gallery_images" class="block text-gray-700 text-lg font-semibold mb-2">آپلود تصاویر گالری جدید (اختیاری):</label>
                    <input type="file" id="gallery_images" name="gallery_images[]" multiple
                           class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-green-700 focus:border-green-700 transition-all duration-200 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 cursor-pointer">
                    @error('gallery_images.*')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <div class="flex justify-end mt-8">
                    <button type="submit" class="btn-primary flex items-center justify-center px-8 py-3 text-xl font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                        <i class="fas fa-save ml-3"></i> به‌روزرسانی محصول
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
