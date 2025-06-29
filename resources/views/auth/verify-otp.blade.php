<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('کد تأیید به شماره موبایل شما ارسال شد. لطفا کد را وارد کنید.') }}
        @if (session('mobile_number'))
            <p class="mt-2 text-sm text-gray-600">
                {{ __('شماره موبایل:') }} <span class="font-bold">{{ session('mobile_number') }}</span>
            </p>
        @endif
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('auth.verify-otp') }}">
        @csrf

        <!-- Mobile Number (Hidden field to pass mobile_number) -->
        <input type="hidden" name="mobile_number" value="{{ session('mobile_number') }}">

        <!-- OTP -->
        <div>
            <x-input-label for="otp" :value="__('کد تأیید')" />
            <x-text-input id="otp" class="block mt-1 w-full" type="text" name="otp" required autofocus />
            <x-input-error :messages="$errors->get('otp')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <a href="{{ route('auth.mobile-login-form') }}" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ __('تغییر شماره موبایل') }}
            </a>

            <x-primary-button class="ms-3">
                {{ __('تأیید و ورود') }}
            </x-primary-button>
        </div>
        
        <div class="flex items-center justify-end mt-4">
            <form method="POST" action="{{ route('auth.send-otp') }}">
                @csrf
                <input type="hidden" name="mobile_number" value="{{ session('mobile_number') }}">
                <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ __('ارسال مجدد کد') }}
                </button>
            </form>
        </div>
    </form>
</x-guest-layout>
