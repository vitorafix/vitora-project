{{-- این سایدبار می‌تواند در یک layout مخصوص editor (مثلاً layouts/editor.blade.php) استفاده شود --}}
{{-- یا در هر ویوی editor که نیاز به سایدبار دارد، include شود. --}}

<div class="w-64 bg-green-900 text-white h-full p-6 shadow-xl">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-amber-400">پنل ویرایشگر</h2>
        <p class="text-sm text-gray-300">مدیریت محتوا</p>
    </div>

    <nav class="space-y-4">
        <a href="{{ route('editor.dashboard') }}" class="flex items-center space-x-reverse space-x-3 p-3 rounded-lg hover:bg-green-700 transition-colors duration-200 {{ request()->routeIs('editor.dashboard') ? 'bg-green-700 font-semibold' : '' }}">
            <i class="fas fa-tachometer-alt text-lg"></i>
            <span>داشبورد</span>
        </a>
        <a href="{{ route('editor.posts.index') }}" class="flex items-center space-x-reverse space-x-3 p-3 rounded-lg hover:bg-green-700 transition-colors duration-200 {{ request()->routeIs('editor.posts.*') ? 'bg-green-700 font-semibold' : '' }}">
            <i class="fas fa-newspaper text-lg"></i>
            <span>پست‌ها</span>
        </a>
        <a href="{{ route('editor.comments.index') }}" class="flex items-center space-x-reverse space-x-3 p-3 rounded-lg hover:bg-green-700 transition-colors duration-200 {{ request()->routeIs('editor.comments.*') ? 'bg-green-700 font-semibold' : '' }}">
            <i class="fas fa-comments text-lg"></i>
            <span>دیدگاه‌ها</span>
        </a>
        <a href="{{ route('editor.categories.index') }}" class="flex items-center space-x-reverse space-x-3 p-3 rounded-lg hover:bg-green-700 transition-colors duration-200 {{ request()->routeIs('editor.categories.*') ? 'bg-green-700 font-semibold' : '' }}">
            <i class="fas fa-tags text-lg"></i>
            <span>دسته‌بندی‌ها</span>
        </a>
        {{-- می‌توانید لینک‌های بیشتری اضافه کنید --}}
    </nav>

    <div class="mt-8 pt-4 border-t border-green-700">
        <form method="POST" action="{{ route('auth.logout') }}">
            @csrf
            <button type="submit" class="flex items-center space-x-reverse space-x-3 p-3 rounded-lg hover:bg-red-700 transition-colors duration-200 w-full text-right text-red-300">
                <i class="fas fa-sign-out-alt text-lg"></i>
                <span>خروج</span>
            </button>
        </form>
    </div>
</div>
