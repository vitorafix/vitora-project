{{-- Auth Modal Overlay --}}
<div id="auth-modal-overlay" class="auth-modal-overlay">
    <div class="auth-modal-content">
        <button id="auth-modal-close-btn" class="auth-modal-close-btn">
            <i class="fas fa-times"></i>
        </button>

        {{-- Login Form --}}
        <form id="login-form" class="auth-form">
            <h3>ورود</h3>
            <div class="auth-form-group">
                <label for="login-username">نام کاربری / ایمیل:</label>
                <input type="text" id="login-username" name="username" required>
            </div>
            <div class="auth-form-group">
                <label for="login-password">رمز عبور:</label>
                <input type="password" id="login-password" name="password" required>
            </div>
            <button type="submit" class="auth-submit-btn">ورود</button>
            <div class="auth-switch-link">
                هنوز حساب کاربری ندارید؟ <a href="{{ url('/register') }}" class="text-green-800 font-semibold hover:underline">ثبت نام</a>
            </div>
        </form>
    </div>
</div>
