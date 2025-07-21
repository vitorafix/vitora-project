// resources/js/auth.js
console.log('auth.js loaded and starting...');

// ایمپورت کردن توابع مورد نیاز از api.js
import { sendOtp, verifyOtpAndLogin, logoutUser } from './api.js'; // verifyOtpAndLogin و logoutUser اضافه شدند

document.addEventListener('DOMContentLoaded', function() {

    // Function to convert Persian/Arabic digits to English and remove non-digits
    // این تابع برای هر سه فرم ثبت‌نام، ورود و تأیید OTP استفاده می‌شود.
    const convertAndFilterDigits = (value) => {
        const persianToEnglishMap = {
            '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
            '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
            '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
            '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9'
        };
        let convertedValue = '';
        for (let i = 0; i < value.length; i++) {
            const char = value[i];
            convertedValue += persianToEnglishMap[char] || char;
        }
        // Remove any non-digit characters after conversion
        return convertedValue.replace(/\D/g, '');
    };

    // Class to manage countdown timers (از verify-otp.blade.php منتقل شد)
    class CountdownTimer {
        constructor(element, initialSeconds, onCompleteCallback) {
            this.element = element;
            this.seconds = initialSeconds;
            this.onCompleteCallback = onCompleteCallback;
            this.interval = null;
        }

        start() {
            this.stop(); // Ensure any existing timer is stopped
            this.updateDisplay(); // Update immediately
            this.interval = setInterval(() => {
                this.seconds--;
                this.updateDisplay();
                if (this.seconds <= 0) {
                    this.stop();
                    this.onCompleteCallback?.(); // Call callback if provided
                }
            }, 1000);
        }

        stop() {
            clearInterval(this.interval);
            this.interval = null;
        }

        reset(newSeconds) {
            this.stop();
            this.seconds = newSeconds;
            this.updateDisplay();
        }

        updateDisplay() {
            const minutes = Math.floor(this.seconds / 60);
            const seconds = this.seconds % 60;
            this.element.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
    }

    // Function to clear OTP input fields and focus on the first one (از verify-otp.blade.php منتقل شد)
    const clearOtpFields = () => {
        const otpDigitInputs = document.querySelectorAll('.otp-digit-input');
        otpDigitInputs.forEach(input => {
            input.value = '';
        });
        if (otpDigitInputs.length > 0) {
            otpDigitInputs[0].focus(); // Focus on the first field for convenience
        }
    };

    // Function to get the combined OTP string from individual inputs (از verify-otp.blade.php منتقل شد)
    const getCombinedOtp = () => {
        const otpDigitInputs = document.querySelectorAll('.otp-digit-input');
        let otp = '';
        otpDigitInputs.forEach(input => {
            otp += input.value;
        });
        return otp;
    };


    // --- منطق برای صفحه ثبت‌نام (register.blade.php) ---
    const registerNameInput = document.getElementById('name');
    const registerLastnameInput = document.getElementById('lastname');
    const registerMobileNumberInput = document.getElementById('mobile_number'); // این ID در هر دو صفحه مشترک است
    const registerButton = document.getElementById('register-button');

    if (registerNameInput && registerMobileNumberInput && registerButton) {
        console.log('Register elements found. Initializing register logic.');

        const registerButtonOriginalText = registerButton.innerHTML; // Store original text

        // Apply digit conversion and filtering to mobile number input for register form
        registerMobileNumberInput.addEventListener('input', function(event) {
            event.target.value = convertAndFilterDigits(event.target.value);
        });

        // Apply digit conversion and filtering to name and lastname inputs (if needed)
        if (registerNameInput) {
            registerNameInput.addEventListener('input', function(event) {
                event.target.value = event.target.value.replace(/[^a-zA-Z\u0600-\u06FF\s]/g, '');
            });
        }
        if (registerLastnameInput) {
            registerLastnameInput.addEventListener('input', function(event) {
                event.target.value = event.target.value.replace(/[^a-zA-Z\u0600-\u06FF\s]/g, '');
            });
        }

        registerButton.addEventListener('click', async function() {
            const name = registerNameInput.value;
            const lastname = registerLastnameInput ? registerLastnameInput.value : '';
            const mobileNumber = registerMobileNumberInput.value;

            // Basic client-side validation
            if (!name || name.trim() === '') {
                window.showMessage('لطفاً نام خود را وارد کنید.', 'error');
                return;
            }
            if (!mobileNumber || !/^09\d{9}$/.test(mobileNumber)) {
                window.showMessage('لطفاً یک شماره موبایل معتبر وارد کنید (مثال: 09123456789).', 'error');
                return;
            }

            // Show loading state
            registerButton.disabled = true;
            registerButton.classList.add('opacity-50', 'cursor-not-allowed');
            registerButton.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال ثبت‌نام...';

            try {
                const response = await sendOtp(mobileNumber, {
                    name: name,
                    lastname: lastname,
                });

                window.showMessage(response.message || 'ثبت‌نام با موفقیت انجام شد. کد تأیید ارسال شد.', 'success');

                window.location.href = `${window.location.origin}/auth/verify-otp-form?mobile_number=${mobileNumber}`;

            } catch (error) {
                const errorMessage = error.response?.data?.message || 'خطا در ثبت‌نام. لطفاً دوباره تلاش کنید.';
                window.showMessage(errorMessage, 'error');
                console.error('Error during registration:', error);
            } finally {
                // Hide loading state
                registerButton.disabled = false;
                registerButton.classList.remove('opacity-50', 'cursor-not-allowed');
                registerButton.innerHTML = registerButtonOriginalText;
            }
        });
    }

    // --- منطق برای صفحه ورود (login.blade.php) ---
    const loginMobileNumberInput = document.getElementById('mobile_number'); // این ID در هر دو صفحه مشترک است
    const sendOtpButton = document.getElementById('send-otp-button');

    if (loginMobileNumberInput && sendOtpButton) {
        console.log('Login elements found. Initializing login logic.');

        const sendOtpButtonOriginalText = sendOtpButton.innerHTML; // Store original text

        // Apply digit conversion and filtering to mobile number input for login form
        loginMobileNumberInput.addEventListener('input', function(event) {
            event.target.value = convertAndFilterDigits(event.target.value);
        });

        sendOtpButton.addEventListener('click', async function() {
            const mobileNumber = loginMobileNumberInput.value;

            // Basic client-side validation for mobile number format
            if (!mobileNumber || !/^09\d{9}$/.test(mobileNumber)) {
                window.showMessage('لطفاً یک شماره موبایل معتبر وارد کنید (مثال: 09123456789).', 'error');
                return;
            }

            // Show loading state
            sendOtpButton.disabled = true;
            sendOtpButton.classList.add('opacity-50', 'cursor-not-allowed');
            sendOtpButton.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال ارسال...';

            try {
                const response = await sendOtp(mobileNumber); // Use the imported sendOtp function

                window.showMessage(response.message || 'کد تأیید با موفقیت ارسال شد.', 'success');

                // Redirect to OTP verification page, passing the mobile number
                if (response.show_register_link) { // Check for the flag from backend
                    window.location.href = `${window.location.origin}/auth/register-form?mobile_number=${mobileNumber}`;
                } else {
                    window.location.href = `${window.location.origin}/auth/verify-otp-form?mobile_number=${mobileNumber}`;
                }

            } catch (error) {
                const errorMessage = error.response?.data?.message || 'خطا در ارسال کد تأیید. لطفاً دوباره تلاش کنید.';
                window.showMessage(errorMessage, 'error');
                console.error('Error sending OTP:', error);
            } finally {
                // Hide loading state
                sendOtpButton.disabled = false;
                sendOtpButton.classList.remove('opacity-50', 'cursor-not-allowed');
                sendOtpButton.innerHTML = sendOtpButtonOriginalText;
            }
        });
    }

    // --- منطق برای صفحه تأیید OTP (verify-otp.blade.php) ---
    const countdownTimerElement = document.getElementById('countdown-timer');
    const resendButton = document.getElementById('resend-otp-button');
    const resendTimerElement = resendButton ? resendButton.querySelector('#resend-timer') : null; // Select the span inside the button
    const otpDigitInputs = document.querySelectorAll('.otp-digit-input'); // Get all OTP digit inputs
    const hiddenMobileNumberInput = document.getElementById('hidden-mobile-number');
    const currentMobileNumberSpan = document.getElementById('current-mobile-number');

    const changeMobileButton = document.getElementById('change-mobile-button');
    const changeMobileModal = document.getElementById('change-mobile-modal');
    const closeModalButton = document.getElementById('close-modal-button');
    const newMobileInput = document.getElementById('new_mobile_number');
    const sendNewOtpButton = document.getElementById('send-new-otp-button');
    const modalErrorMessage = document.getElementById('modal-error-message');

    let mainCountdownTimer;
    let resendCooldownTimer;

    // Only initialize OTP verification logic if elements are present
    if (countdownTimerElement && resendButton && otpDigitInputs.length > 0 && hiddenMobileNumberInput) {
        console.log('OTP verification elements found. Initializing OTP logic.');

        const resendButtonOriginalText = resendButton.innerHTML; // Store original text
        const sendNewOtpButtonOriginalText = sendNewOtpButton ? sendNewOtpButton.innerHTML : ''; // Store original text

        // Apply auto-focus/backspace/paste logic for OTP inputs
        otpDigitInputs.forEach((input, index) => {
            input.addEventListener('input', function(event) {
                event.target.value = convertAndFilterDigits(event.target.value);

                if (event.target.value.length === 1 && index < otpDigitInputs.length - 1) {
                    otpDigitInputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', function(event) {
                if (event.key === 'Backspace' && event.target.value === '' && index > 0) {
                    otpDigitInputs[index - 1].focus();
                }
            });

            input.addEventListener('paste', function(event) {
                event.preventDefault();
                const pasteData = event.clipboardData.getData('text');
                const cleanedData = convertAndFilterDigits(pasteData);

                for (let i = 0; i < otpDigitInputs.length; i++) {
                    if (i < cleanedData.length) {
                        otpDigitInputs[i].value = cleanedData[i];
                    } else {
                        otpDigitInputs[i].value = '';
                    }
                }

                const lastFilledIndex = Math.min(cleanedData.length - 1, otpDigitInputs.length - 1);
                if (lastFilledIndex >= 0) {
                    otpDigitInputs[lastFilledIndex].focus();
                }
            });
        });

        // Apply digit conversion and filtering to new mobile number input in modal
        if (newMobileInput) {
            newMobileInput.addEventListener('input', function(event) {
                event.target.value = convertAndFilterDigits(event.target.value);
            });
        }

        // Initialize main countdown timer
        mainCountdownTimer = new CountdownTimer(countdownTimerElement, 120, () => {
            resendButton.disabled = false;
            resendButton.classList.remove('opacity-50', 'cursor-not-allowed');
            resendButton.removeAttribute('aria-disabled');
            if (resendTimerElement) resendTimerElement.textContent = ''; // Clear timer text
            resendButton.innerHTML = resendButtonOriginalText; // Restore original text
        });

        // Initialize resend cooldown timer (initially not running)
        resendCooldownTimer = new CountdownTimer(resendTimerElement, 120, () => {
            resendButton.disabled = false;
            resendButton.classList.remove('opacity-50', 'cursor-not-allowed');
            resendButton.removeAttribute('aria-disabled');
            if (resendTimerElement) resendTimerElement.textContent = ''; // Clear timer text
            resendButton.innerHTML = resendButtonOriginalText; // Restore original text
        });

        // Update startCountdown function to use the new class
        function startCountdown() {
            mainCountdownTimer.reset(120);
            mainCountdownTimer.start();
            resendButton.disabled = true;
            resendButton.classList.add('opacity-50', 'cursor-not-allowed');
            resendButton.setAttribute('aria-disabled', 'true');
            if (resendTimerElement) resendTimerElement.textContent = '02:00'; // Reset resend timer display
        }

        // Update startResendCooldown function to use the new class
        function startResendCooldown() {
            resendCooldownTimer.reset(120);
            resendCooldownTimer.start();
            resendButton.disabled = true;
            resendButton.classList.add('opacity-50', 'cursor-not-allowed');
            resendButton.setAttribute('aria-disabled', 'true');
        }

        // --- Event Listeners for OTP verification page ---
        const verifyOtpAjaxButton = document.getElementById('verify-otp-ajax-button');
        if (verifyOtpAjaxButton) {
            verifyOtpAjaxButton.addEventListener('click', async function() {
                const mobileNumber = hiddenMobileNumberInput.value;
                const otp = getCombinedOtp();

                // Show loading state
                verifyOtpAjaxButton.disabled = true;
                verifyOtpAjaxButton.classList.add('opacity-50', 'cursor-not-allowed');
                verifyOtpAjaxButton.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال بررسی...';

                try {
                    const data = await verifyOtpAndLogin(mobileNumber, otp);
                    window.showMessage(data.message || 'ورود با موفقیت انجام شد.', 'success');
                    console.log('User data after login:', data.user);
                    console.log('JWT Token:', data.token);

                    // Redirect to dashboard or home page after successful login
                    window.location.href = '/'; // Or your dashboard route
                } catch (error) {
                    const errorMessage = error.response?.data?.message || 'خطا در ورود. لطفاً دوباره تلاش کنید.';
                    window.showMessage(errorMessage, 'error');
                    console.error('Error during OTP verification and login:', error);
                    clearOtpFields(); // Clear OTP fields on error
                } finally {
                    // Hide loading state
                    verifyOtpAjaxButton.disabled = false;
                    verifyOtpAjaxButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    verifyOtpAjaxButton.innerHTML = 'ثبت و ورود <i class="fas fa-sign-in-alt mr-2"></i>';
                }
            });
        }

        if (resendButton) {
            resendButton.addEventListener('click', async function() {
                const mobileNumber = this.dataset.mobileNumber;
                if (!mobileNumber) {
                    window.showMessage('شماره موبایل یافت نشد.', 'error');
                    return;
                }

                // Show loading state
                resendButton.disabled = true;
                resendButton.classList.add('opacity-50', 'cursor-not-allowed');
                resendButton.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال ارسال...';

                try {
                    const response = await sendOtp(mobileNumber);

                    window.showMessage(response.message || 'کد تأیید مجدداً ارسال شد.', 'success');
                    clearOtpFields();
                    startCountdown();
                    startResendCooldown();
                } catch (error) {
                    const errorMessage = error.response?.data?.message || 'خطا در ارسال مجدد کد.';
                    window.showMessage(errorMessage, 'error');
                    console.error('Error resending OTP:', error);
                    clearOtpFields();
                } finally {
                    resendButton.disabled = false;
                    resendButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    resendButton.innerHTML = resendButtonOriginalText;
                }
            });
        }

        // --- Change Mobile Modal Logic ---
        if (changeMobileButton && changeMobileModal && closeModalButton && newMobileInput && sendNewOtpButton) {
            changeMobileButton.addEventListener('click', function() {
                changeMobileModal.classList.add('active');
                modalErrorMessage.classList.add('hidden');
                modalErrorMessage.classList.remove('animate-pulse');
                newMobileInput.value = '';
                clearOtpFields();
            });

            closeModalButton.addEventListener('click', function() {
                changeMobileModal.classList.remove('active');
            });

            sendNewOtpButton.addEventListener('click', async function() {
                const newMobileNumber = newMobileInput.value;

                // Basic client-side validation for new mobile number format
                if (!newMobileNumber || !/^09\d{9}$/.test(newMobileNumber)) {
                    modalErrorMessage.textContent = 'لطفاً یک شماره موبایل معتبر وارد کنید (مثال: 09123456789).';
                    modalErrorMessage.classList.remove('hidden');
                    modalErrorMessage.classList.add('animate-pulse');
                    setTimeout(() => modalErrorMessage.classList.remove('animate-pulse'), 2000);
                    return;
                }

                // Show loading state
                sendNewOtpButton.disabled = true;
                sendNewOtpButton.classList.add('opacity-50', 'cursor-not-allowed');
                sendNewOtpButton.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال ارسال...';

                try {
                    const response = await sendOtp(newMobileNumber);

                    window.showMessage(response.message || 'شماره موبایل با موفقیت تغییر یافت. کد جدید ارسال شد.', 'success');
                    hiddenMobileNumberInput.value = newMobileNumber;
                    if (currentMobileNumberSpan) currentMobileNumberSpan.textContent = newMobileNumber;
                    changeMobileModal.classList.remove('active');
                    clearOtpFields();
                    startCountdown();
                    startResendCooldown();
                } catch (error) {
                    const errorMessage = error.response?.data?.message || 'خطا در تغییر شماره موبایل.';
                    modalErrorMessage.textContent = errorMessage;
                    modalErrorMessage.classList.remove('hidden');
                    modalErrorMessage.classList.add('animate-pulse');
                    setTimeout(() => modalErrorMessage.classList.remove('animate-pulse'), 2000);
                    console.error('Error changing mobile number:', error);
                    clearOtpFields();
                } finally {
                    sendNewOtpButton.disabled = false;
                    sendNewOtpButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    sendNewOtpButton.innerHTML = sendNewOtpButtonOriginalText;
                }
            });
        }

        // Initial start of the countdown when the page loads
        startCountdown();

        // Clear OTP fields if there are validation errors on page load
        const errorContainer = document.querySelector('.error-container');
        if (errorContainer && !errorContainer.classList.contains('hidden')) {
            clearOtpFields();
        }
    }
});
