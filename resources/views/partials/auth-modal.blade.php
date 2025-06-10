{{-- Auth Modal Overlay --}}
<div id="auth-modal-overlay" class="auth-modal-overlay">
    <div class="auth-modal-content">
        <button id="auth-modal-close-btn" class="auth-modal-close-btn">
            <i class="fas fa-times"></i>
        </button>

        {{-- Mobile Login/Registration Step --}}
        <form id="mobile-login-step" class="auth-form">
            <h3>ورود / ثبت نام</h3>
            <div class="auth-form-group">
                <label for="mobile-number">شماره موبایل:</label>
                <input type="tel" id="mobile-number" name="mobile_number" placeholder="مثال: 09123456789" required pattern="^09[0-9]{9}$" maxlength="11">
                <p class="text-sm text-gray-500 mt-1 text-right">شماره موبایل خود را وارد کنید.</p>
            </div>
            <button type="submit" class="auth-submit-btn" id="get-otp-btn">دریافت کد تایید</button>
            <div class="auth-switch-link hidden"> {{-- این لینک اکنون پنهان است، ثبت نام ابتدا با موبایل شروع می‌شود --}}
                هنوز حساب کاربری ندارید؟ <a href="{{ url('/register') }}" class="text-green-800 font-semibold hover:underline">ثبت نام</a>
            </div>
        </form>

        {{-- SMS Verification Step (Hidden by default) --}}
        <form id="sms-verify-step" class="auth-form hidden">
            <h3>تایید شماره موبایل</h3>
            <div class="auth-form-group">
                <label for="otp-code">کد تایید (پیامک شده به <span id="display-mobile-number" class="font-bold text-green-800"></span>):</label>
                <input type="text" id="otp-code" name="otp_code" placeholder="کد 4 رقمی" required pattern="[0-9]{4}" maxlength="4">
            </div>
            <button type="submit" class="auth-submit-btn" id="verify-otp-btn">تایید و ورود</button>
            <div class="auth-switch-link mt-4">
                <button type="button" id="resend-otp-btn" class="text-green-800 font-semibold hover:underline">ارسال مجدد کد</button>
            </div>
            <div class="auth-switch-link mt-2">
                <button type="button" id="change-mobile-btn" class="text-red-600 font-semibold hover:underline">تغییر شماره موبایل</button>
            </div>
        </form>
    </div>
</div>
