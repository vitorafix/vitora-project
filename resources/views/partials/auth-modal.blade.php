{{-- Auth Modal Overlay --}}
{{-- اضافه شدن کلاس hidden به صورت پیش‌فرض --}}
<div id="auth-modal-overlay" class="auth-modal-overlay hidden">
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
            <button type="button" id="back-to-mobile-btn" class="text-gray-500 hover:underline mt-4">ویرایش شماره موبایل</button>
        </form>

        {{-- پیام‌ها و اسپینر --}}
        <div id="auth-message" class="mt-4 text-center text-sm font-semibold"></div>
        <div id="auth-spinner" class="mt-4 hidden">
            <i class="fas fa-spinner fa-spin text-green-700 text-2xl"></i>
        </div>
    </div>
</div>

<style>
    /* Auth Modal Styles */
    .auth-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .auth-modal-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    /* Styles for the modal content (no changes needed here related to default visibility) */
    .auth-modal-content {
        background-color: #fff;
        padding: 2.5rem;
        border-radius: 1rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        max-width: 450px;
        width: 90%;
        text-align: center;
        position: relative;
        transform: translateY(-20px);
        opacity: 0;
        transition: transform 0.3s ease, opacity 0.3s ease;
    }

    .auth-modal-overlay.show .auth-modal-content {
        transform: translateY(0);
        opacity: 1;
    }

    .auth-modal-close-btn {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #777;
        cursor: pointer;
        transition: color 0.2s ease;
    }

    .auth-modal-close-btn:hover {
        color: #333;
    }

    .auth-form h3 {
        font-size: 1.875rem; /* text-3xl */
        font-weight: 800; /* font-extrabold */
        color: #4A2D2A; /* brown-900 */
        margin-bottom: 1.5rem;
    }

    .auth-form-group {
        margin-bottom: 1.25rem;
        text-align: right;
    }

    .auth-form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #333;
    }

    .auth-form-group input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #ccc;
        border-radius: 0.5rem;
        font-size: 1rem;
        outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .auth-form-group input:focus {
        border-color: #2F855A; /* green-700 */
        box-shadow: 0 0 0 3px rgba(47, 133, 90, 0.2);
    }

    .auth-submit-btn {
        width: 100%;
        padding: 0.75rem 1rem;
        background-color: #2F855A; /* green-700 */
        color: white;
        border: none;
        border-radius: 0.5rem;
        font-size: 1.125rem;
        font-weight: 700;
        cursor: pointer;
        transition: background-color 0.2s ease, transform 0.2s ease;
    }

    .auth-submit-btn:hover {
        background-color: #2C7A52; /* darker green */
        transform: translateY(-2px);
    }

    .auth-switch-link {
        margin-top: 1rem;
        font-size: 0.875rem;
        color: #555;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const authModalOverlay = document.getElementById('auth-modal-overlay');
        const authModalCloseBtn = document.getElementById('auth-modal-close-btn');
        const mobileLoginStep = document.getElementById('mobile-login-step');
        const smsVerifyStep = document.getElementById('sms-verify-step');
        const getOtpBtn = document.getElementById('get-otp-btn');
        const verifyOtpBtn = document.getElementById('verify-otp-btn');
        const resendOtpBtn = document.getElementById('resend-otp-btn');
        const backToMobileBtn = document.getElementById('back-to-mobile-btn');
        const mobileNumberInput = document.getElementById('mobile-number');
        const otpCodeInput = document.getElementById('otp-code');
        const displayMobileNumber = document.getElementById('display-mobile-number');
        const authMessage = document.getElementById('auth-message');
        const authSpinner = document.getElementById('auth-spinner');
        const loginRegisterBtn = document.getElementById('login-register-btn'); // Assuming this button exists in your nav

        function showModal() {
            authModalOverlay.classList.remove('hidden'); // حذف کلاس hidden
            authModalOverlay.classList.add('show');
        }

        function hideModal() {
            authModalOverlay.classList.remove('show');
            authModalOverlay.classList.add('hidden'); // اضافه کردن کلاس hidden
            // Reset to mobile login step when closing
            mobileLoginStep.classList.remove('hidden');
            smsVerifyStep.classList.add('hidden');
            authMessage.textContent = '';
        }

        // Event listener for opening the modal (e.g., from a login button in the nav)
        if (loginRegisterBtn) {
            loginRegisterBtn.addEventListener('click', showModal);
        }

        authModalCloseBtn.addEventListener('click', hideModal);

        // Close modal when clicking outside content
        authModalOverlay.addEventListener('click', function(event) {
            if (event.target === authModalOverlay) {
                hideModal();
            }
        });

        // Handle mobile login step (get OTP)
        mobileLoginStep.addEventListener('submit', async function(event) {
            event.preventDefault();
            authMessage.textContent = '';
            authSpinner.classList.remove('hidden');
            getOtpBtn.disabled = true;

            const mobileNumber = mobileNumberInput.value;

            try {
                const response = await fetch('/api/get-otp', { // Make sure this route exists
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ mobile_number: mobileNumber })
                });
                const result = await response.json();

                if (response.ok) {
                    authMessage.textContent = result.message || 'کد تایید ارسال شد.';
                    authMessage.style.color = 'green';
                    displayMobileNumber.textContent = mobileNumber; // نمایش شماره موبایل
                    mobileLoginStep.classList.add('hidden');
                    smsVerifyStep.classList.remove('hidden');
                    otpCodeInput.focus(); // فوکوس بر روی فیلد کد تایید
                } else {
                    authMessage.textContent = result.message || 'خطا در ارسال کد تایید.';
                    authMessage.style.color = 'red';
                }
            } catch (error) {
                console.error('Error fetching OTP:', error);
                authMessage.textContent = 'خطا در ارتباط با سرور. لطفا اتصال اینترنت خود را بررسی کنید.';
                authMessage.style.color = 'red';
            } finally {
                authSpinner.classList.add('hidden');
                getOtpBtn.disabled = false;
            }
        });

        // Handle SMS verification step (verify OTP)
        smsVerifyStep.addEventListener('submit', async function(event) {
            event.preventDefault();
            authMessage.textContent = '';
            authSpinner.classList.remove('hidden');
            verifyOtpBtn.disabled = true;

            const mobileNumber = displayMobileNumber.textContent; // استفاده از شماره موبایل نمایش داده شده
            const otpCode = otpCodeInput.value;

            try {
                const response = await fetch('/api/verify-otp', { // Make sure this route exists
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ mobile_number: mobileNumber, otp_code: otpCode })
                });
                const result = await response.json();

                if (response.ok) {
                    authMessage.textContent = result.message || 'ورود موفقیت‌آمیز بود! در حال انتقال...';
                    authMessage.style.color = 'green';
                    // Optional: Redirect or refresh page after successful login
                    if (result.redirect) {
                        window.location.href = result.redirect;
                    } else {
                        window.location.reload(); // Refresh the page to show authenticated state
                    }
                } else {
                    authMessage.textContent = result.message || 'کد تایید اشتباه است. دوباره تلاش کنید.';
                    authMessage.style.color = 'red';
                }
            } catch (error) {
                console.error('Error verifying OTP:', error);
                authMessage.textContent = 'خطا در ارتباط با سرور. لطفا اتصال اینترنت خود را بررسی کنید.';
                authMessage.style.color = 'red';
            } finally {
                authSpinner.classList.add('hidden');
                verifyOtpBtn.disabled = false;
            }
        });

        // Resend OTP button
        resendOtpBtn.addEventListener('click', async function() {
            authMessage.textContent = '';
            authSpinner.classList.remove('hidden');
            resendOtpBtn.disabled = true;

            const mobileNumber = displayMobileNumber.textContent;

            try {
                const response = await fetch('/api/resend-otp', { // Make sure this route exists
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ mobile_number: mobileNumber })
                });
                const result = await response.json();

                if (response.ok) {
                    authMessage.textContent = result.message || 'کد تایید مجدداً ارسال شد.';
                    authMessage.style.color = 'green';
                } else {
                    authMessage.textContent = result.message || 'خطا در ارسال مجدد کد تایید.';
                    authMessage.style.color = 'red';
                }
            } catch (error) {
                console.error('Error resending OTP:', error);
                authMessage.textContent = 'خطا در ارتباط با سرور.';
                authMessage.style.color = 'red';
            } finally {
                authSpinner.classList.add('hidden');
                resendOtpBtn.disabled = false;
            }
        });

        // Back to mobile number input
        backToMobileBtn.addEventListener('click', function() {
            mobileLoginStep.classList.remove('hidden');
            smsVerifyStep.classList.add('hidden');
            authMessage.textContent = '';
            otpCodeInput.value = ''; // پاک کردن کد تایید
        });

        // Initial check if modal should be shown (e.g., if there's an error from backend on page load)
        // This part needs careful consideration based on your specific backend error handling
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('login_error')) {
            showModal();
            authMessage.textContent = urlParams.get('login_error');
            authMessage.style.color = 'red';
        }

        // Check for specific scenario: if a user is redirected to a page that expects auth,
        // and they are not authenticated, you might want to show the modal automatically.
        // For example, if redirected from a protected route.
        // For now, it will only show if 'login_error' param is present.
    });
</script>
