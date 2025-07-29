@extends('layouts.app')

@section('title', 'ایجاد محصول جدید - چای ابراهیم')

@section('content')
    <section class="container mx-auto px-4 py-8 md:py-16 max-w-4xl">
        <h1 class="text-4xl font-extrabold text-brown-900 mb-12 text-center">
            <i class="fas fa-plus-circle text-green-700 ml-3"></i>
            ایجاد محصول جدید
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
            <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Product Title --}}
                <div class="mb-6">
                    <label for="title" class="block text-gray-700 text-lg font-semibold mb-2">عنوان محصول:</label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}"
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
                              placeholder="توضیحات کامل محصول را اینجا بنویسید.">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Price and Stock --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="price" class="block text-gray-700 text-lg font-semibold mb-2">قیمت (تومان):</label>
                        <input type="number" id="price" name="price" value="{{ old('price') }}" step="0.01" min="0"
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-green-700 focus:border-green-700 transition-all duration-200"
                               placeholder="مثال: 150000" required>
                        @error('price')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="stock" class="block text-gray-700 text-lg font-semibold mb-2">موجودی:</label>
                        <input type="number" id="stock" name="stock" value="{{ old('stock') }}" min="0"
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
                            {{-- Assuming $categories is passed from the controller --}}
                            @isset($categories)
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            @else
                                {{-- Placeholder option if categories are not passed --}}
                                <option value="1" {{ old('category_id') == 1 ? 'selected' : '' }}>چای سیاه</option>
                                <option value="2" {{ old('category_id') == 2 ? 'selected' : '' }}>چای سبز</option>
                                <option value="3" {{ old('category_id') == 3 ? 'selected' : '' }}>چای سفید</option>
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
                            <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>فعال</option>
                            <option value="0" {{ old('status', 1) == 0 ? 'selected' : '' }}>غیرفعال</option>
                        </select>
                        @error('status')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Main Product Image --}}
                <div class="mb-6">
                    <label for="image" class="block text-gray-700 text-lg font-semibold mb-2">تصویر اصلی محصول:</label>
                    <input type="file" id="image" name="image"
                           class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-green-700 focus:border-green-700 transition-all duration-200 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 cursor-pointer">
                    @error('image')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Gallery Images --}}
                <div class="mb-6">
                    <label for="gallery_images" class="block text-gray-700 text-lg font-semibold mb-2">تصاویر گالری (چندگانه):</label>
                    <input type="file" id="gallery_images" name="gallery_images[]" multiple
                           class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-green-700 focus:border-green-700 transition-all duration-200 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 cursor-pointer">
                    @error('gallery_images.*')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <div class="flex justify-end mt-8">
                    <button type="submit" class="btn-primary flex items-center justify-center px-8 py-3 text-xl font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                        <i class="fas fa-save ml-3"></i> ذخیره محصول
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
