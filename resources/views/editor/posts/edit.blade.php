@extends('layouts.editor') {{-- تغییر به layouts.editor برای استفاده از Layout اختصاصی ویرایشگر --}}

@section('title', 'ویرایش پست - داشبورد ویرایشگر')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white shadow-lg rounded-lg p-6 mb-8">
        <h1 class="text-3xl font-bold text-green-800 mb-6">
            <i class="fas fa-edit ml-2"></i> ویرایش پست: {{ $post->title ?? 'عنوان پست' }}
        </h1>

        <form action="{{ route('editor.posts.update', ['post' => $post->slug ?? 'post-slug']) }}" method="POST">
            @csrf
            @method('PUT') {{-- برای متد PUT در لاراول --}}

            <div class="mb-4">
                <label for="title" class="block text-gray-700 text-sm font-bold mb-2">عنوان پست:</label>
                <input type="text" id="title" name="title"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500"
                       value="{{ $post->title ?? 'عنوان نمونه' }}" required>
            </div>

            <div class="mb-4">
                <label for="category" class="block text-gray-700 text-sm font-bold mb-2">دسته‌بندی:</label>
                <select id="category" name="category"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500"
                        required>
                    <option value="نوشیدنی‌ها" {{ (isset($post->category) && $post->category == 'نوشیدنی‌ها') ? 'selected' : '' }}>نوشیدنی‌ها</option>
                    <option value="تاریخچه" {{ (isset($post->category) && $post->category == 'تاریخچه') ? 'selected' : '' }}>تاریخچه</option>
                    <option value="سلامتی" {{ (isset($post->category) && $post->category == 'سلامتی') ? 'selected' : '' }}>سلامتی</option>
                    <option value="متفرقه" {{ (isset($post->category) && $post->category == 'متفرقه') ? 'selected' : '' }}>متفرقه</option>
                </select>
            </div>

            <div class="mb-6">
                <label for="content" class="block text-gray-700 text-sm font-bold mb-2">محتوای پست:</label>
                <textarea id="content" name="content" rows="10"
                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500"
                          required>{{ $post->content ?? 'محتوای نمونه پست.' }}</textarea>
            </div>

            <div class="mb-6">
                <label for="status" class="block text-gray-700 text-sm font-bold mb-2">وضعیت:</label>
                <select id="status" name="status"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-green-500"
                        required>
                    <option value="draft" {{ (isset($post->status) && $post->status == 'draft') ? 'selected' : '' }}>پیش‌نویس</option>
                    <option value="published" {{ (isset($post->status) && $post->status == 'published') ? 'selected' : '' }}>منتشر شده</option>
                </select>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save ml-2"></i> به‌روزرسانی پست
                </button>
                <a href="{{ route('editor.posts.index') }}" class="btn-secondary">
                    <i class="fas fa-times ml-2"></i> لغو
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
