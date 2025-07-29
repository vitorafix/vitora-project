@extends('layouts.editor') {{-- تغییر به layouts.editor برای استفاده از Layout اختصاصی ویرایشگر --}}

@section('title', 'مدیریت دیدگاه‌ها - داشبورد ویرایشگر')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white shadow-lg rounded-lg p-6 mb-8">
        <h1 class="text-3xl font-bold text-green-800 mb-6">
            <i class="fas fa-comments ml-2"></i> مدیریت دیدگاه‌ها
        </h1>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-lg overflow-hidden">
                <thead class="bg-green-700 text-white">
                    <tr>
                        <th class="py-3 px-4 text-right">نویسنده</th>
                        <th class="py-3 px-4 text-right">محتوای دیدگاه</th>
                        <th class="py-3 px-4 text-right">پست مربوطه</th>
                        <th class="py-3 px-4 text-right">وضعیت</th>
                        <th class="py-3 px-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    {{-- مثال برای نمایش دیدگاه‌ها. در اینجا باید داده‌های واقعی از کنترلر لود شوند. --}}
                    @forelse ([
                        ['author' => 'علی احمدی', 'content' => 'مطلب بسیار خوبی بود، ممنون!', 'post_title' => 'چگونه چای سبز دم کنیم؟', 'status' => 'تایید شده', 'id' => 1],
                        ['author' => 'فاطمه کریمی', 'content' => 'چای سیاه شما عالیه!', 'post_title' => 'فواید چای سیاه برای سلامتی', 'status' => 'در انتظار تایید', 'id' => 2],
                    ] as $comment)
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="py-3 px-4">{{ $comment['author'] }}</td>
                            <td class="py-3 px-4">{{ Str::limit($comment['content'], 50) }}</td>
                            <td class="py-3 px-4">{{ $comment['post_title'] }}</td>
                            <td class="py-3 px-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $comment['status'] == 'تایید شده' ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800' }}">
                                    {{ $comment['status'] }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if ($comment['status'] == 'در انتظار تایید')
                                    <button class="text-green-600 hover:text-green-800 mx-1 approve-comment-btn" title="تایید دیدگاه" data-comment-id="{{ $comment['id'] }}">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                @endif
                                <button class="text-red-600 hover:text-red-800 mx-1 delete-comment-btn" title="حذف دیدگاه" data-comment-id="{{ $comment['id'] }}" data-comment-author="{{ $comment['author'] }}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 px-4 text-center text-gray-500">دیدگاهی برای نمایش وجود ندارد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- اسکریپت برای مدیریت دکمه‌ها و نمایش پیام‌ها --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // دکمه‌های تایید دیدگاه
        document.querySelectorAll('.approve-comment-btn').forEach(button => {
            button.addEventListener('click', function() {
                const commentId = this.dataset.commentId;
                window.showMessage(`دیدگاه با شناسه ${commentId} تایید شد.`, 'success');
                // اینجا می‌توانید درخواست AJAX برای تایید دیدگاه را ارسال کنید
                // fetch(`/editor/comments/${commentId}/approve`, {
                //     method: 'POST',
                //     headers: {
                //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                //         'Content-Type': 'application/json'
                //     }
                // }).then(response => {
                //     if (response.ok) {
                //         window.showMessage('دیدگاه با موفقیت تایید شد.', 'success');
                //         location.reload();
                //     } else {
                //         window.showMessage('خطا در تایید دیدگاه.', 'error');
                //     }
                // }).catch(error => {
                //     console.error('Error:', error);
                //     window.showMessage('خطایی رخ داد.', 'error');
                // });
            });
        });

        // دکمه‌های حذف دیدگاه
        document.querySelectorAll('.delete-comment-btn').forEach(button => {
            button.addEventListener('click', function() {
                const commentId = this.dataset.commentId;
                const commentAuthor = this.dataset.commentAuthor;

                // نمایش مدال تایید سفارشی
                window.showConfirmationModal(
                    'حذف دیدگاه',
                    `آیا از حذف دیدگاه از "${commentAuthor}" (شناسه: ${commentId}) مطمئن هستید؟ این عمل غیرقابل بازگشت است.`,
                    function() {
                        // منطق حذف پس از تایید کاربر
                        window.showMessage(`دیدگاه با شناسه ${commentId} حذف شد.`, 'success');
                        // اینجا می‌توانید درخواست AJAX برای حذف را ارسال کنید
                        // fetch(`/editor/comments/${commentId}`, {
                        //     method: 'DELETE',
                        //     headers: {
                        //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        //         'Content-Type': 'application/json'
                        //     }
                        // }).then(response => {
                        //     if (response.ok) {
                        //         window.showMessage('دیدگاه با موفقیت حذف شد.', 'success');
                        //         location.reload();
                        //     } else {
                        //         window.showMessage('خطا در حذف دیدگاه.', 'error');
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
