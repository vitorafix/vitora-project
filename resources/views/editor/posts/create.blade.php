@extends('layouts.editor') {{-- تغییر به layouts.editor برای استفاده از Layout اختصاصی ویرایشگر --}}

@section('title', 'افزودن پست جدید - داشبورد ویرایشگر')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white shadow-lg rounded-lg p-6 mb-8">
        <h1 class="text-3xl font-bold text-green-800 mb-6">
            <i class="fas fa-plus-circle ml-2"></i> افزودن پست جدید
        </h1>

        <form action="{{ route('editor.posts.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label for="title" class="block text-gray-700 text-sm font-bold mb-2">عنوان پست:</label>
                <input type="text" id="title" name="title"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500"
                       placeholder="عنوان پست را وارد کنید" required>
            </div>

            <div class="mb-4">
                <label for="category" class="block text-gray-700 text-sm font-bold mb-2">دسته‌بندی:</label>
                <select id="category" name="category"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500"
                        required>
                    <option value="">انتخاب دسته‌بندی</option>
                    <option value="نوشیدنی‌ها">نوشیدنی‌ها</option>
                    <option value="تاریخچه">تاریخچه</option>
                    <option value="سلامتی">سلامتی</option>
                    <option value="متفرقه">متفرقه</option>
                </select>
            </div>

            <div class="mb-6">
                <label for="content" class="block text-gray-700 text-sm font-bold mb-2">محتوای پست:</label>
                <textarea id="content" name="content" rows="10"
                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500"
                          placeholder="محتوای کامل پست را اینجا بنویسید" required></textarea>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save ml-2"></i> ذخیره پست
                </button>
                <a href="{{ route('editor.posts.index') }}" class="btn-secondary">
                    <i class="fas fa-times ml-2"></i> لغو
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
