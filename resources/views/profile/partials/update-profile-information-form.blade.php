<section>
    <header class="mb-6 text-center">
        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-2">
            {{ __('اطلاعات پروفایل') }}
        </h2>

        <p class="text-md text-gray-600 dark:text-gray-400">
            {{ __("اطلاعات حساب کاربری و آدرس ایمیل خود را به‌روزرسانی کنید.") }}
        </p>
    </header>

    {{-- فرم send-verification حذف شده است زیرا مسیر verification.send وجود ندارد و تأیید هویت با موبایل انجام می‌شود --}}

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" dir="rtl">
        @csrf
        @method('patch')

        <!-- Name Field -->
        <div>
            <x-input-label for="name" :value="__('نام')" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"/>
            <x-text-input id="name" 
                          name="name" 
                          type="text" 
                          class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400" 
                          :value="old('name', $user->name)" 
                          required 
                          autofocus 
                          autocomplete="name" />
            <x-input-error class="mt-2 text-sm" :messages="$errors->get('name')" />
        </div>

        <!-- Email Field -->
        <div>
            <x-input-label for="email" :value="__('ایمیل')" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"/>
            <x-text-input id="email" 
                          name="email" 
                          type="email" 
                          class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400" 
                          :value="old('email', $user->email)" 
                          required 
                          autocomplete="username" />
            <x-input-error class="mt-2 text-sm" :messages="$errors->get('email')" />

            {{-- بخش مربوط به تأیید ایمیل حذف شده است --}}
        </div>

        <div class="flex items-center gap-4 mt-6">
            <button type="submit" 
                    class="inline-flex items-center justify-center px-6 py-3 text-lg font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300 ease-in-out min-w-[150px]">
                {{ __('ذخیره') }}
                <i class="fas fa-check-circle ml-2"></i> {{-- آیکون تأیید --}}
            </button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" 
                   x-show="show" 
                   x-transition 
                   x-init="setTimeout(() => show = false, 2000)" 
                   class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('ذخیره شد.') }}
                </p>
            @endif
        </div>
    </form>
</section>
