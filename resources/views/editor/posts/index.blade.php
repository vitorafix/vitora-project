@extends('layouts.editor') {{-- تغییر به layouts.editor برای استفاده از Layout اختصاصی ویرایشگر --}}

@section('title', 'لیست پست‌ها - داشبورد ویرایشگر')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white shadow-lg rounded-lg p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-green-800">
                <i class="fas fa-newspaper ml-2"></i> مدیریت پست‌ها
            </h1>
            <a href="{{ route('editor.posts.create') }}" class="btn-primary">
                <i class="fas fa-plus ml-2"></i> افزودن پست جدید
            </a>
        </div>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-lg overflow-hidden">
                <thead class="bg-green-700 text-white">
                    <tr>
                        <th class="py-3 px-4 text-right">عنوان</th>
                        <th class="py-3 px-4 text-right">دسته‌بندی</th>
                        <th class="py-3 px-4 text-right">وضعیت</th>
                        <th class="py-3 px-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    {{-- مثال برای نمایش پست‌ها. در اینجا باید داده‌های واقعی از کنترلر لود شوند. --}}
                    @forelse ([
                        ['title' => 'چگونه چای سبز دم کنیم؟', 'category' => 'نوشیدنی‌ها', 'status' => 'منتشر شده', 'slug' => 'how-to-brew-green-tea'],
                        ['title' => 'تاریخچه چای در ایران', 'category' => 'تاریخچه', 'status' => 'پیش‌نویس', 'slug' => 'history-of-tea-in-iran'],
                        ['title' => 'فواید چای سیاه برای سلامتی', 'category' => 'سلامتی', 'status' => 'منتشر شده', 'slug' => 'benefits-of-black-tea'],
                    ] as $post)
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="py-3 px-4">{{ $post['title'] }}</td>
                            <td class="py-3 px-4">{{ $post['category'] }}</td>
                            <td class="py-3 px-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $post['status'] == 'منتشر شده' ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800' }}">
                                    {{ $post['status'] }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <a href="{{ route('editor.posts.edit', ['post' => $post['slug']]) }}" class="text-blue-600 hover:text-blue-800 mx-1">
                                    <i class="fas fa-edit"></i>
                                </a>
                                {{-- دکمه حذف با استفاده از مدال تایید سفارشی --}}
                                <button type="button" class="text-red-600 hover:text-red-800 mx-1 delete-post-btn" data-post-slug="{{ $post['slug'] }}" data-post-title="{{ $post['title'] }}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 px-4 text-center text-gray-500">پستی برای نمایش وجود ندارد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- اسکریپت برای مدیریت دکمه‌های حذف و نمایش پیام‌ها --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.delete-post-btn').forEach(button => {
            button.addEventListener('click', function() {
                const postSlug = this.dataset.postSlug;
                const postTitle = this.dataset.postTitle;

                // نمایش مدال تایید سفارشی
                window.showConfirmationModal(
                    'حذف پست',
                    `آیا از حذف پست "${postTitle}" مطمئن هستید؟ این عمل غیرقابل بازگشت است.`,
                    function() {
                        // منطق حذف پس از تایید کاربر
                        window.showMessage(`پست "${postTitle}" حذف شد.`, 'success');
                        // اینجا می‌توانید درخواست AJAX برای حذف را ارسال کنید
                        // fetch(`/editor/posts/${postSlug}`, {
                        //     method: 'DELETE',
                        //     headers: {
                        //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        //         'Content-Type': 'application/json'
                        //     }
                        // }).then(response => {
                        //     if (response.ok) {
                        //         window.showMessage('پست با موفقیت حذف شد.', 'success');
                        //         // رفرش صفحه یا حذف ردیف از جدول
                        //         location.reload();
                        //     } else {
                        //         window.showMessage('خطا در حذف پست.', 'error');
                        //     }
                        // }).catch(error => {
                        //     console.error('Error:', error);
                        //     window.showMessage('خطایی رخ داد.', 'error');
                        // });
                    },
                    function() {
                        // منطق لغو عملیات
                        window.showMessage('عملیات حذف لغو شد.', 'info');
                    }
                );
            });
        });
    });
</script>
@endpush
@endsection
