<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('برای ورود یا ثبت‌نام، شماره موبایل خود را وارد کنید.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('auth.send-otp') }}">
        @csrf

        <!-- Mobile Number -->
        <div>
            <x-input-label for="mobile_number" :value="__('شماره موبایل')" />
            <x-text-input id="mobile_number" class="block mt-1 w-full" type="text" name="mobile_number" :value="old('mobile_number')" required autofocus />
            <x-input-error :messages="$errors->get('mobile_number')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-3">
                {{ __('ارسال کد تأیید') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
