@extends('layouts.editor') {{-- تغییر به layouts.editor برای استفاده از Layout اختصاصی ویرایشگر --}}

@section('title', 'مدیریت دسته‌بندی‌ها - داشبورد ویرایشگر')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white shadow-lg rounded-lg p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-green-800">
                <i class="fas fa-tags ml-2"></i> مدیریت دسته‌بندی‌ها
            </h1>
            <button class="btn-primary" id="add-category-btn"> {{-- اضافه کردن id برای مدیریت با JS --}}
                <i class="fas fa-plus ml-2"></i> افزودن دسته‌بندی جدید
            </button>
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
                        <th class="py-3 px-4 text-right">نام دسته‌بندی</th>
                        <th class="py-3 px-4 text-right">توضیحات</th>
                        <th class="py-3 px-4 text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    {{-- مثال برای نمایش دسته‌بندی‌ها. در اینجا باید داده‌های واقعی از کنترلر لود شوند. --}}
                    @forelse ([
                        ['name' => 'نوشیدنی‌ها', 'description' => 'انواع چای و دمنوش‌ها', 'id' => 1],
                        ['name' => 'تاریخچه', 'description' => 'مقالات مربوط به تاریخچه چای', 'id' => 2],
                        ['name' => 'سلامتی', 'description' => 'فواید چای برای سلامتی', 'id' => 3],
                    ] as $category)
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="py-3 px-4">{{ $category['name'] }}</td>
                            <td class="py-3 px-4">{{ $category['description'] }}</td>
                            <td class="py-3 px-4 text-center">
                                <button class="text-blue-600 hover:text-blue-800 mx-1 edit-category-btn" title="ویرایش دسته‌بندی" data-category-id="{{ $category['id'] }}" data-category-name="{{ $category['name'] }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="text-red-600 hover:text-red-800 mx-1 delete-category-btn" title="حذف دسته‌بندی" data-category-id="{{ $category['id'] }}" data-category-name="{{ $category['name'] }}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-4 px-4 text-center text-gray-500">دسته‌بندی برای نمایش وجود ندارد.</td>
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
        // دکمه افزودن دسته‌بندی جدید
        const addCategoryBtn = document.getElementById('add-category-btn');
        if (addCategoryBtn) {
            addCategoryBtn.addEventListener('click', function() {
                window.showMessage('فرم افزودن دسته‌بندی جدید باز شود.', 'info');
                // اینجا می‌توانید منطق باز کردن مدال یا ریدایرکت به صفحه ایجاد را اضافه کنید
                // window.location.href = '{{ route('editor.categories.create') }}';
            });
        }

        // دکمه‌های ویرایش دسته‌بندی
        document.querySelectorAll('.edit-category-btn').forEach(button => {
            button.addEventListener('click', function() {
                const categoryId = this.dataset.categoryId;
                const categoryName = this.dataset.categoryName;
                window.showMessage(`ویرایش دسته‌بندی ${categoryName} (ID: ${categoryId})`, 'info');
                // اینجا می‌توانید منطق باز کردن مدال ویرایش یا ریدایرکت را اضافه کنید
                // window.location.href = `{{ route('editor.categories.edit', '') }}/${categoryId}`;
            });
        });

        // دکمه‌های حذف دسته‌بندی
        document.querySelectorAll('.delete-category-btn').forEach(button => {
            button.addEventListener('click', function() {
                const categoryId = this.dataset.categoryId;
                const categoryName = this.dataset.categoryName;

                // نمایش مدال تایید سفارشی
                window.showConfirmationModal(
                    'حذف دسته‌بندی',
                    `آیا از حذف دسته‌بندی "${categoryName}" مطمئن هستید؟ این عمل غیرقابل بازگشت است.`,
                    function() {
                        // منطق حذف پس از تایید کاربر
                        window.showMessage(`دسته‌بندی ${categoryName} (ID: ${categoryId}) حذف شد.`, 'success');
                        // اینجا می‌توانید درخواست AJAX برای حذف را ارسال کنید
                        // fetch(`/editor/categories/${categoryId}`, {
                        //     method: 'DELETE',
                        //     headers: {
                        //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        //         'Content-Type': 'application/json'
                        //     }
                        // }).then(response => {
                        //     if (response.ok) {
                        //         window.showMessage('دسته‌بندی با موفقیت حذف شد.', 'success');
                        //         // رفرش صفحه یا حذف ردیف از جدول
                        //         location.reload();
                        //     } else {
                        //         window.showMessage('خطا در حذف دسته‌بندی.', 'error');
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
