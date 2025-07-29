// resources/js/auth/auth.js
console.log('auth.js loaded and starting...');

import { sendOtp, verifyOtpAndLogin, logoutUser, storeJwtToken, clearJwtToken, getJwtToken, registerUserAndSendOtp, requestOtpForRegister } from "../core/api.js";
import { updateNavbarUserStatus } from "../ui/navbar_new.js";

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
    return convertedValue.replace(/\D/g, '');
};

class CountdownTimer {
    constructor(element, initialSeconds, onCompleteCallback) {
        this.element = element;
        this.seconds = initialSeconds;
        this.onCompleteCallback = onCompleteCallback;
        this.interval = null;
    }

    start() {
        this.stop();
        this.updateDisplay();
        this.interval = setInterval(() => {
            this.seconds--;
            this.updateDisplay();
            if (this.seconds <= 0) {
                this.stop();
                this.onCompleteCallback?.();
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

const clearOtpFields = () => {
    const otpDigitInputs = document.querySelectorAll('.otp-digit-input');
    otpDigitInputs.forEach(input => {
        input.value = '';
    });
    if (otpDigitInputs.length > 0) {
        otpDigitInputs[0].focus();
    }
};

const getCombinedOtp = () => {
    const otpDigitInputs = document.querySelectorAll('.otp-digit-input');
    let otp = '';
    otpDigitInputs.forEach(input => {
        otp += input.value;
    });
    return otp;
};

export function initAuth() {
    console.log('Auth module initializing...');

    const registerNameInput = document.getElementById('name');
    const registerLastnameInput = document.getElementById('lastname');
    const registerMobileNumberInput = document.getElementById('mobile_number');
    const registerButton = document.getElementById('register-button');

    if (registerNameInput && registerMobileNumberInput && registerButton) {
        console.log('Auth.js: Register elements found. Initializing register logic.');

        const registerButtonOriginalText = registerButton.innerHTML;

        registerMobileNumberInput.addEventListener('input', function(event) {
            event.target.value = convertAndFilterDigits(event.target.value);
        });

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

            if (!name || name.trim() === '') {
                window.showMessage('لطفاً نام خود را وارد کنید.', 'error');
                return;
            }
            if (!mobileNumber || !/^09\d{9}$/.test(mobileNumber)) {
                window.showMessage('لطفاً یک شماره موبایل معتبر وارد کنید (مثال: 09123456789).', 'error');
                return;
            }

            registerButton.disabled = true;
            registerButton.classList.add('opacity-50', 'cursor-not-allowed');
            registerButton.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال ثبت‌نام...';

            console.log('Auth.js: Register button clicked!');

            try {
                const response = await registerUserAndSendOtp(mobileNumber, name, lastname);

                window.showMessage(response.message || 'ثبت‌نام با موفقیت انجام شد. کد تأیید ارسال شد.', 'success');

                window.location.href = `${window.location.origin}/auth/verify-otp-form?mobile_number=${mobileNumber}`;

            } catch (error) {
                const errorMessage = error.response?.data?.message || 'خطا در ثبت‌نام. لطفاً دوباره تلاش کنید.';
                window.showMessage(errorMessage, 'error');
                console.error('Auth.js: Error during registration:', error);
            } finally {
                registerButton.disabled = false;
                registerButton.classList.remove('opacity-50', 'cursor-not-allowed');
                registerButton.innerHTML = registerButtonOriginalText;
            }
        });
    } else {
        console.log('Auth.js: Register elements NOT found. Skipping register logic.');
        console.log('Auth.js: registerNameInput:', registerNameInput);
        console.log('Auth.js: registerMobileNumberInput:', registerMobileNumberInput);
        console.log('Auth.js: registerButton:', registerButton);
    }

    const loginMobileNumberInput = document.getElementById('mobile_number');
    const sendOtpButton = document.getElementById('send-otp-button');

    console.log('Auth.js: Checking login elements: loginMobileNumberInput:', loginMobileNumberInput, 'sendOtpButton:', sendOtpButton);


    if (loginMobileNumberInput && sendOtpButton) {
        console.log('Auth.js: Login elements found. Initializing login logic.');

        const sendOtpButtonOriginalText = sendOtpButton.innerHTML;

        loginMobileNumberInput.addEventListener('input', function(event) {
            event.target.value = convertAndFilterDigits(event.target.value);
        });

        sendOtpButton.addEventListener('click', async function() {
            const mobileNumber = loginMobileNumberInput.value;

            if (!mobileNumber || !/^09\d{9}$/.test(mobileNumber)) {
                window.showMessage('لطفاً یک شماره موبایل معتبر وارد کنید (مثال: 09123456789).', 'error');
                return;
            }

            sendOtpButton.disabled = true;
            sendOtpButton.classList.add('opacity-50', 'cursor-not-allowed');
            sendOtpButton.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال ارسال...';

            try {
                const data = await sendOtp(mobileNumber);

                if (data.status === 'not_registered') {
                    console.warn('Auth.js: User not registered. Redirecting to registration page.');
                    window.showMessage(data.message || 'این شماره در سیستم ثبت نشده است. لطفاً ابتدا ثبت‌نام کنید.', 'warning');
                    window.location.href = `${window.location.origin}/auth/register?mobile_number=${mobileNumber}`;
                } else if (data.status === 'otp_sent') {
                    window.showMessage(data.message || 'کد تأیید با موفقیت ارسال شد.', 'success');
                    console.log('Auth.js: User exists, redirecting to OTP verification form.');
                    window.location.href = `${window.location.origin}/auth/verify-otp-form?mobile_number=${mobileNumber}`;
                } else {
                    const errorMessage = data.message || `پاسخ غیرمنتظره از سرور: ${data.status}`;
                    window.showMessage(errorMessage, 'error');
                    console.error('Auth.js: Unexpected server response status:', data);
                }

            } catch (error) {
                const errorMessage = error.response?.data?.message || 'خطا در برقراری ارتباط با سرور. لطفاً دوباره تلاش کنید.';
                window.showMessage(errorMessage, 'error');
                console.error('Auth.js: Error sending OTP (network/server error):', error);
            } finally {
                sendOtpButton.disabled = false;
                sendOtpButton.classList.remove('opacity-50', 'cursor-not-allowed');
                sendOtpButton.innerHTML = sendOtpButtonOriginalText;
            }
        });
    } else {
        console.log('Auth.js: Login elements NOT found. Skipping login logic.');
        console.log('Auth.js: loginMobileNumberInput:', loginMobileNumberInput);
        console.log('Auth.js: sendOtpButton:', sendOtpButton);
    }

    const countdownTimerElement = document.getElementById('countdown-timer');
    const resendButton = document.getElementById('resend-otp-button');
    const resendTimerElement = resendButton ? resendButton.querySelector('#resend-timer') : null;
    const otpDigitInputs = document.querySelectorAll('.otp-digit-input');
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

    if (countdownTimerElement && resendButton && otpDigitInputs.length > 0 && hiddenMobileNumberInput) {
        console.log('Auth.js: OTP verification elements found. Initializing OTP logic.');

        const resendButtonOriginalText = resendButton.innerHTML;
        const sendNewOtpButtonOriginalText = sendNewOtpButton ? sendNewOtpButton.innerHTML : '';

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

        if (newMobileInput) {
            newMobileInput.addEventListener('input', function(event) {
                event.target.value = convertAndFilterDigits(event.target.value);
            });
        }

        mainCountdownTimer = new CountdownTimer(countdownTimerElement, 120, () => {
            resendButton.disabled = false;
            resendButton.classList.remove('opacity-50', 'cursor-not-allowed');
            resendButton.removeAttribute('aria-disabled');
            if (resendTimerElement)
                resendTimerElement.textContent = '';
            resendButton.innerHTML = resendButtonOriginalText;
        });

        resendCooldownTimer = new CountdownTimer(resendTimerElement, 120, () => {
            resendButton.disabled = false;
            resendButton.classList.remove('opacity-50', 'cursor-not-allowed');
            resendButton.removeAttribute('aria-disabled');
            if (resendTimerElement)
                resendTimerElement.textContent = '';
            resendButton.innerHTML = resendButtonOriginalText;
        });

        function startCountdown() {
            mainCountdownTimer.reset(120);
            mainCountdownTimer.start();
            resendButton.disabled = true;
            resendButton.classList.add('opacity-50', 'cursor-not-allowed');
            resendButton.setAttribute('aria-disabled', 'true');
            if (resendTimerElement)
                resendTimerElement.textContent = '02:00';
        }

        function startResendCooldown() {
            resendCooldownTimer.reset(120);
            resendCooldownTimer.start();
            resendButton.disabled = true;
            resendButton.classList.add('opacity-50', 'cursor-not-allowed');
            resendButton.setAttribute('aria-disabled', 'true');
        }

        const verifyOtpAjaxButton = document.getElementById('verify-otp-ajax-button');
        if (verifyOtpAjaxButton) {
            console.log('Auth.js: "ثبت و ورود" button (verify-otp-ajax-button) found!');
            verifyOtpAjaxButton.addEventListener('click', async function() {
                console.log('Auth.js: "ثبت و ورود" button clicked!');
                const mobileNumber = hiddenMobileNumberInput.value;
                const otp = getCombinedOtp();
                console.log('Auth.js: Mobile Number for verification:', mobileNumber);
                console.log('Auth.js: Combined OTP:', otp);

                verifyOtpAjaxButton.disabled = true;
                verifyOtpAjaxButton.classList.add('opacity-50', 'cursor-not-allowed');
                verifyOtpAjaxButton.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال بررسی...';

                try {
                    const data = await verifyOtpAndLogin(mobileNumber, otp);
                    window.showMessage(data.message || 'ورود با موفقیت انجام شد.', 'success');
                    console.log('Auth.js: User data after login:', data.user);
                    console.log('Auth.js: JWT Token:', data.access_token);

                    if (data.access_token) {
                        localStorage.setItem('jwt_token', data.access_token);

                        try {
                            const jwtLoginResponse = await window.axios.post('/api/auth/jwt-login', {
                                token: data.access_token
                            });
                            console.log('Auth.js: Web session login successful:', jwtLoginResponse.data);
                        } catch (jwtLoginError) {
                            console.error('Auth.js: Error during web session login (jwt-login API call):', jwtLoginError);
                            window.showMessage('خطا در ورود به سشن وب. لطفاً دوباره تلاش کنید.', 'error');
                        }
                    }
                    updateNavbarUserStatus();

                    window.location.href = '/';
                } catch (error) {
                    const errorMessage = error.response?.data?.message || 'خطا در ورود. لطفاً دوباره تلاش کنید.';
                    window.showMessage(errorMessage, 'error');
                    console.error('Auth.js: Error during OTP verification and login:', error);
                    clearOtpFields();
                } finally {
                    verifyOtpAjaxButton.disabled = false;
                    verifyOtpAjaxButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    verifyOtpAjaxButton.innerHTML = 'ثبت و ورود <i class="fas fa-sign-in-alt mr-2"></i>';
                }
            });
        } else {
            console.log('Auth.js: "ثبت و ورود" button (verify-otp-ajax-button) NOT found!');
        }

        if (resendButton) {
            resendButton.addEventListener('click', async function() {
                const mobileNumber = this.dataset.mobileNumber;
                if (!mobileNumber) {
                    window.showMessage('شماره موبایل یافت نشد.', 'error');
                    return;
                }

                resendButton.disabled = true;
                resendButton.classList.add('opacity-50', 'cursor-not-allowed');
                resendButton.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال ارسال...';

                try {
                    const response = await requestOtpForRegister(mobileNumber);

                    window.showMessage(response.message || 'کد تأیید مجدداً ارسال شد.', 'success');
                    clearOtpFields();
                    startCountdown();
                    startResendCooldown();
                } catch (error) {
                    const errorMessage = error.response?.data?.message || 'خطا در ارسال مجدد کد.';
                    window.showMessage(errorMessage, 'error');
                    console.error('Auth.js: Error resending OTP:', error);
                    clearOtpFields();
                } finally {
                    resendButton.disabled = false;
                    resendButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    resendButton.innerHTML = resendButtonOriginalText;
                }
            });
        }

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

                if (!newMobileNumber || !/^09\d{9}$/.test(newMobileNumber)) {
                    modalErrorMessage.textContent = 'لطفاً یک شماره موبایل معتبر وارد کنید (مثال: 09123456789).';
                    modalErrorMessage.classList.remove('hidden');
                    modalErrorMessage.classList.add('animate-pulse');
                    setTimeout(() => modalErrorMessage.classList.remove('animate-pulse'), 2000);
                    return;
                }

                sendNewOtpButton.disabled = true;
                sendNewOtpButton.classList.add('opacity-50', 'cursor-not-allowed');
                sendNewOtpButton.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال ارسال...';

                try {
                    const response = await requestOtpForRegister(newMobileNumber);

                    window.showMessage(response.message || 'شماره موبایل با موفقیت تغییر یافت. کد جدید ارسال شد.', 'success');
                    hiddenMobileNumberInput.value = newMobileNumber;
                    if (currentMobileNumberSpan)
                        currentMobileNumberSpan.textContent = newMobileNumber;
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
                    console.error('Auth.js: Error changing mobile number:', error);
                    clearOtpFields();
                } finally {
                    sendNewOtpButton.disabled = false;
                    sendNewOtpButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    sendNewOtpButton.innerHTML = sendNewOtpButtonOriginalText;
                }
            });
        }

        startCountdown();

        const errorContainer = document.querySelector('.error-container');
        if (errorContainer && !errorContainer.classList.contains('hidden')) {
            clearOtpFields();
        }
    } else {
        console.log('Auth.js: OTP verification elements NOT found. Skipping OTP logic.');
        console.log('Auth.js: countdownTimerElement:', countdownTimerElement);
        console.log('Auth.js: resendButton:', resendButton);
        console.log('Auth.js: otpDigitInputs.length:', otpDigitInputs.length);
        console.log('Auth.js: hiddenMobileNumberInput:', hiddenMobileNumberInput);
    }
}
