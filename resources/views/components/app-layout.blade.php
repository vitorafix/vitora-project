{{-- این فایل کامپوننت Blade برای x-app-layout است --}}
{{-- این کامپوننت، لایه‌بندی اصلی (app.blade.php) را extends می‌کند --}}
@extends('layouts.app') {{-- فرض بر این است که app.blade.php شما در resources/views/layouts/app.blade.php قرار دارد --}}

@section('content')
    {{-- در اینجا می‌توانید ساختار ناوبری، سایدبار، یا هر wrapper دیگری را اضافه کنید --}}
    {{-- نوار ناوبری (layouts.navigation) از app.blade.php اصلی اینکلود می‌شود و نیازی به تکرار در اینجا نیست. --}}

    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        {{-- حذف @include('layouts.navigation') از اینجا --}}

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main>
            {{ $slot }} {{-- این $slot محتوایی است که از View فرزند (مثل verify-otp.blade.php) به اینجا ارسال می‌شود --}}
        </main>
    </div>
@endsection
