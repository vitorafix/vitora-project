{{-- این هدر می‌تواند در یک layout مخصوص editor (مثلاً layouts/editor.blade.php) استفاده شود --}}
{{-- یا در هر ویوی editor که نیاز به هدر دارد، include شود. --}}

<header class="bg-white shadow-md p-4 flex justify-between items-center border-b border-gray-200">
    <div class="text-xl font-semibold text-brown-900">
        @yield('title') {{-- عنوان صفحه از بخش title در ویوهای فرزند گرفته می‌شود --}}
    </div>
    <div class="flex items-center space-x-reverse space-x-4">
        {{-- اطلاعات کاربر لاگین شده --}}
        @auth
            <div class="flex items-center">
                <span class="text-gray-700 text-sm ml-2">
                    {{ Auth::user()->name ?? 'کاربر' }}
                </span>
                <div class="w-8 h-8 bg-amber-400 rounded-full flex items-center justify-center text-green-800 font-bold text-sm">
                    {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                </div>
            </div>
        @endauth
        {{-- دکمه منو موبایل یا سایر آیکون‌ها --}}
        {{-- این بخش می‌تواند شامل دکمه باز کردن سایدبار در حالت موبایل باشد --}}
        <button class="text-gray-600 hover:text-green-800 focus:outline-none lg:hidden">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>
</header>
