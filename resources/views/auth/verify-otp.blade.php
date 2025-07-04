{{-- استفاده از لایه‌بندی اصلی برنامه --}}
<x-app-layout>
    <div class="py-12 bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white dark:bg-gray-800 shadow-xl rounded-lg p-8 sm:p-10 border border-gray-200 dark:border-gray-700" dir="rtl">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-2">
                    {{ __('تأیید کد') }}
                </h2>
                <p class="text-md text-gray-600 dark:text-gray-400">
                    {{ __('کد تأیید به شماره موبایل شما ارسال شد. لطفا کد را وارد کنید.') }}
                </p>
                @if (isset($mobileNumber))
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('شماره موبایل:') }} <span class="font-bold text-gray-800 dark:text-gray-200">{{ $mobileNumber }}</span>
                    </p>
                @endif
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-4 mb-6">
                    {{ __('زمان باقی‌مانده:') }} <span id="countdown-timer" class="font-bold text-red-600 dark:text-red-400">02:00</span>
                </p>
            </div>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('auth.verify-otp') }}" class="space-y-6">
                @csrf

                <input type="hidden" name="mobile_number" value="{{ $mobileNumber ?? '' }}">

                <div>
                    <label for="otp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('کد تأیید') }}
                    </label>
                    <input id="otp" 
                           class="block w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200 ease-in-out text-base placeholder-gray-400 text-center tracking-widest" 
                           type="text" 
                           name="otp" 
                           placeholder="کد ۶ رقمی"
                           maxlength="6"
                           required 
                           autofocus
                           inputmode="numeric"
                           pattern="[0-9]*"
                           value="{{ old('otp') }}"
                           aria-describedby="otp-help otp-error"
                           autocomplete="one-time-code">
                    <span id="otp-help" class="sr-only">{{ __('لطفاً کد تأیید ۶ رقمی که به شماره موبایل شما ارسال شده است را وارد کنید.') }}</span>
                    <x-input-error :messages="$errors->get('otp')" class="mt-2 text-sm" id="otp-error" />
                </div>

                <div class="flex items-center justify-between mt-6">
                    <a href="{{ route('auth.mobile-login-form') }}" 
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg shadow-sm transition-all duration-200 ease-in-out">
                        {{ __('تغییر شماره موبایل') }}
                    </a>

                    <button type="submit" 
                            class="inline-flex items-center justify-center px-6 py-3 text-lg font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300 ease-in-out min-w-[150px]">
                        {{ __('تأیید و ورود') }}
                        <i class="fas fa-check-circle mr-2"></i>
                    </button>
                </div>
            </form>

            <div class="flex items-center justify-center mt-4">
                <form id="resend-otp-form" method="POST" action="{{ route('auth.send-otp') }}">
                    @csrf
                    <input type="hidden" name="mobile_number" value="{{ $mobileNumber ?? '' }}">
                    <button type="submit" id="resend-otp-button"
                            class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-green-600 dark:text-green-400 bg-transparent hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-all duration-200 ease-in-out">
                        {{ __('ارسال مجدد کد') }}
                        <i class="fas fa-redo-alt mr-2"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let timerElement = document.getElementById('countdown-timer');
            let resendButton = document.getElementById('resend-otp-button');
            let resendForm = document.getElementById('resend-otp-form');
            let otpInput = document.getElementById('otp');

            // اصلاح شده: استفاده از config() برای خواندن زمان انقضا از فایل پیکربندی
            let timeLeft = parseInt("{{ (int) config('auth.otp.expiry_minutes', 2) * 60 }}");

            function updateTimer() {
                let minutes = Math.floor(timeLeft / 60);
                let seconds = timeLeft % 60;

                minutes = minutes < 10 ? '0' + minutes : minutes;
                seconds = seconds < 10 ? '0' + seconds : seconds;

                timerElement.textContent = minutes + ':' + seconds;

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    timerElement.textContent = '{{ __("منقضی شد!") }}';
                    resendButton.disabled = false;
                    resendButton.classList.remove('opacity-50', 'cursor-not-allowed');
                } else {
                    timeLeft--;
                    resendButton.disabled = true;
                    resendButton.classList.add('opacity-50', 'cursor-not-allowed');
                }
            }

            let timerInterval = setInterval(updateTimer, 1000);
            updateTimer();

            resendForm.addEventListener('submit', function() {
                resendButton.disabled = true;
                resendButton.classList.add('opacity-50', 'cursor-not-allowed');
                resendButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> {{ __("در حال ارسال...") }}';
            });

            otpInput.addEventListener('input', function() {
                // جایگزینی اعداد فارسی/عربی به انگلیسی
                let persianDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
                let arabicDigits = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];

                let englishValue = this.value.split('').map(function(char) {
                    if (persianDigits.includes(char)) {
                        return persianDigits.indexOf(char);
                    } else if (arabicDigits.includes(char)) {
                        return arabicDigits.indexOf(char);
                    } else {
                        return char;
                    }
                }).join('');

                // فقط اعداد مجاز
                englishValue = englishValue.replace(/[^0-9]/g, '');
                this.value = englishValue;

                if (this.value.length === 6) {
                    document.querySelector('form button[type="submit"]').focus();
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
