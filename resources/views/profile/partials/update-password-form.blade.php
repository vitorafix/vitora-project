<section>
    <header class="mb-6 text-center">
        <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-2">
            {{ __('به‌روزرسانی رمز عبور') }}
        </h2>

        <p class="text-md text-gray-600 dark:text-gray-400">
            {{ __('مطمئن شوید حساب شما از یک رمز عبور طولانی و تصادفی برای امنیت استفاده می‌کند.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6" dir="rtl">
        @csrf
        @method('put')

        <!-- Current Password Field -->
        <div>
            <x-input-label for="current_password" :value="__('رمز عبور فعلی')" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"/>
            <x-text-input id="current_password" 
                          name="current_password" 
                          type="password" 
                          class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400" 
                          autocomplete="current-password" />
            <x-input-error :messages="$errors->get('current_password')" name="current_password" class="mt-2 text-sm" />
        </div>

        <!-- New Password Field -->
        <div>
            <x-input-label for="password" :value="__('رمز عبور جدید')" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"/>
            <x-text-input id="password" 
                          name="password" 
                          type="password" 
                          class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400" 
                          autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" name="password" class="mt-2 text-sm" />
        </div>

        <!-- Confirm New Password Field -->
        <div>
            <x-input-label for="password_confirmation" :value="__('تأیید رمز عبور جدید')" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"/>
            <x-text-input id="password_confirmation" 
                          name="password_confirmation" 
                          type="password" 
                          class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400" 
                          autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" name="password_confirmation" class="mt-2 text-sm" />
        </div>

        <div class="flex items-center gap-4 mt-6">
            <button type="submit" 
                    class="inline-flex items-center justify-center px-6 py-3 text-lg font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300 ease-in-out min-w-[150px]">
                {{ __('ذخیره') }}
                <i class="fas fa-check-circle ml-2"></i> {{-- آیکون تأیید --}}
            </button>

            @if (session('status') === 'password-updated')
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
