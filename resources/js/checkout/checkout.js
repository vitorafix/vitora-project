// resources/js/checkout/checkout.js
// This file handles all client-side logic for the checkout process,
// including form validation, address selection, progress bar updates,
// API calls with retry mechanisms, and local storage for draft orders.

// Import necessary global functions (assuming they are defined in app.js or core/utils.js)
// If debounce is moved to core/utils.js, import it like this:
// import { debounce } from '../core/utils.js';

// Define validation rules using regular expressions
const validationRules = {
    phone_number: /^09[0-9]{9}$/, // Validates Iranian mobile numbers starting with 09 and 9 digits after
    postal_code: /^[0-9]{10}$/, // Validates 10-digit postal codes
    first_name: /^[\u0600-\u06FF\s]{2,50}$/, // Validates Persian characters, 2-50 length for names
    last_name: /^[\u0600-\u06FF\s]{2,50}$/, // Validates Persian characters, 2-50 length for names
    address: /^.{10,200}$/ // Minimum 10 characters, max 200 for address
};

// Define custom error messages for each field
const errorMessages = {
    first_name: {
        required: 'لطفاً نام کوچک خود را وارد کنید.', // Please enter your first name.
        pattern: 'نام کوچک باید شامل حروف فارسی باشد و حداقل ۲ کاراکتر باشد.' // First name must contain Persian letters and be at least 2 characters long.
    },
    last_name: {
        required: 'لطفاً نام خانوادگی خود را وارد کنید.', // Please enter your last name.
        pattern: 'نام خانوادگی باید شامل حروف فارسی باشد و حداقل ۲ کاراکتر باشد.' // Last name must contain Persian letters and be at least 2 characters long.
    },
    phone_number: {
        required: 'لطفاً شماره تلفن خود را وارد کنید.', // Please enter your phone number.
        pattern: 'فرمت شماره تلفن صحیح نیست. مثال: 09123456789' // Phone number format is incorrect. Example: 09123456789
    },
    address: {
        required: 'لطفاً آدرس کامل خود را وارد کنید.', // Please enter your full address.
        pattern: 'آدرس باید حداقل ۱۰ کاراکتر باشد.' // Address must be at least 10 characters long.
    },
    province: {
        required: 'لطفاً استان خود را وارد کنید.' // Please enter your province.
    },
    city: {
        required: 'لطفاً شهر خود را وارد کنید.' // Please enter your city.
    },
    postal_code: {
        required: 'لطفاً کد پستی خود را وارد کنید.', // Please enter your postal code.
        pattern: 'کد پستی باید ۱۰ رقمی باشد.' // Postal code must be 10 digits.
    },
    shipping_method: {
        required: 'لطفاً روش ارسال را انتخاب کنید.' // Please select a shipping method.
    },
    payment_method: {
        required: 'لطفاً روش پرداخت را انتخاب کنید.' // Please select a payment method.
    },
    terms_agree: {
        required: 'برای ثبت سفارش، باید قوانین و مقررات را بپذیرید.' // To place an order, you must accept the terms and conditions.
    }
};

/**
 * Helper function for debouncing.
 * Prevents a function from being called too frequently.
 * @param {Function} func - The function to debounce.
 * @param {number} wait - The number of milliseconds to wait.
 * @returns {Function} The debounced function.
 */
// NOTE: This debounce function should ideally be moved to resources/js/core/utils.js
// For now, it remains here. If you move it, remove this definition and import it.
const debounce = (func, wait) => {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

/**
 * Displays a field-specific error message.
 * Also updates the live region for accessibility.
 * @param {HTMLElement} inputElement - The input element.
 * @param {string} message - The error message.
 */
function showFieldError(inputElement, message) {
    const errorDivId = inputElement.id + '-error';
    const errorDiv = document.getElementById(errorDivId);
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.classList.remove('hidden');
    }
    inputElement.classList.add('border-red-500');
    inputElement.classList.remove('focus:ring-green-700', 'focus:border-green-700');

    // Update live region for accessibility
    const formErrorsLiveRegion = document.getElementById('form-errors-live-region');
    if (formErrorsLiveRegion) {
        formErrorsLiveRegion.textContent = message;
    }
}

/**
 * Clears a field-specific error message.
 * @param {HTMLElement} inputElement - The input element.
 */
function clearFieldError(inputElement) {
    const errorDivId = inputElement.id + '-error';
    const errorDiv = document.getElementById(errorDivId);
    if (errorDiv) {
        errorDiv.textContent = '';
        errorDiv.classList.add('hidden');
    }
    inputElement.classList.remove('border-red-500');
    inputElement.classList.add('focus:ring-green-700', 'focus:border-green-700');

    // Clear live region if this was the last error (simple approach)
    const formErrorsLiveRegion = document.getElementById('form-errors-live-region');
    if (formErrorsLiveRegion && formErrorsLiveRegion.textContent === inputElement.getAttribute('aria-describedby')) {
        formErrorsLiveRegion.textContent = '';
    }
}

/**
 * Validates a single field against a given rule.
 * @param {string} fieldId - The ID of the field.
 * @param {string} value - The value of the field.
 * @param {RegExp|null} rule - The regex rule to apply, or null if not applicable.
 * @param {HTMLElement} inputElement - The DOM element of the field.
 * @returns {boolean} True if validation passes, false otherwise.
 */
function validateField(fieldId, value, rule, inputElement) {
    if (rule && !rule.test(value)) {
        showFieldError(inputElement, errorMessages[fieldId].pattern);
        return false;
    }
    clearFieldError(inputElement);
    return true;
}

/**
 * Updates the progress bar.
 * @param {number} step - Current step.
 * @param {number} totalSteps - Total number of steps.
 */
function showProgress(step, totalSteps) {
    const progressBar = document.getElementById('progress-bar');
    if (progressBar) {
        const percentage = (step / totalSteps) * 100;
        progressBar.style.width = `${percentage}%`;
    }
}

/**
 * Helper function to validate all form fields on submit.
 * @param {Object} data - Object containing form data.
 * @returns {boolean} True if all validations pass, false otherwise.
 */
function validateFormFields(data) {
    // Clear previous errors
    document.querySelectorAll('.text-red-500.text-sm.mt-1').forEach(el => {
        el.textContent = '';
        el.classList.add('hidden');
    });
    document.querySelectorAll('.form-input, .form-radio, .form-checkbox').forEach(el => {
        el.classList.remove('border-red-500');
        el.classList.add('focus:ring-green-700', 'focus:border-green-700');
    });

    let isValid = true;
    let firstInvalidElement = null;

    // Check if a specific address is selected or if new address fields are filled
    const selectedAddressId = document.querySelector('input[name="selected_address_id"]:checked')?.value;

    const requiredAddressFields = [
        { id: 'first_name', rule: validationRules.first_name },
        { id: 'last_name', rule: validationRules.last_name },
        { id: 'phone_number', rule: validationRules.phone_number },
        { id: 'address', rule: validationRules.address },
        { id: 'province', rule: null },
        { id: 'city', rule: null },
        { id: 'postal_code', rule: validationRules.postal_code }
    ];

    // Only validate address fields if "new address" is selected or no addresses exist
    if (selectedAddressId === 'new' || document.querySelectorAll('input[name="selected_address_id"]').length === 0) {
        for (const field of requiredAddressFields) {
            const inputElement = document.getElementById(field.id);
            if (!inputElement || !inputElement.value.trim()) {
                showFieldError(inputElement, errorMessages[field.id].required);
                isValid = false;
                if (!firstInvalidElement) firstInvalidElement = inputElement;
            } else if (field.rule && !validateField(field.id, inputElement.value.trim(), field.rule, inputElement)) {
                isValid = false;
                if (!firstInvalidElement) firstInvalidElement = inputElement;
            }
        }
    } else {
        if (!selectedAddressId) {
            window.showMessage('لطفاً یک آدرس را انتخاب کنید یا آدرس جدیدی وارد کنید.', 'error');
            isValid = false;
            const addressRadios = document.querySelectorAll('input[name="selected_address_id"]');
            if (addressRadios.length > 0) addressRadios[0].focus();
        }
    }

    // Specific validation for radio buttons (shipping and payment)
    const shippingMethod = document.querySelector('input[name="shipping_method"]:checked');
    if (!shippingMethod) {
        const shippingRadioGroup = document.getElementById('shipping_post');
        if (shippingRadioGroup) {
            showFieldError(shippingRadioGroup, errorMessages.shipping_method.required);
            isValid = false;
            if (!firstInvalidElement) firstInvalidElement = shippingRadioGroup;
        } else {
            window.showMessage(errorMessages.shipping_method.required, 'error');
            isValid = false;
        }
    }

    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    if (!paymentMethod) {
        const paymentRadioGroup = document.getElementById('payment_online');
        if (paymentRadioGroup) {
            showFieldError(paymentRadioGroup, errorMessages.payment_method.required);
            isValid = false;
            if (!firstInvalidElement) firstInvalidElement = paymentRadioGroup;
        } else {
            window.showMessage(errorMessages.payment_method.required, 'error');
            isValid = false;
        }
    }

    // Validate terms and conditions checkbox
    const termsAgreeCheckbox = document.getElementById('terms_agree');
    if (termsAgreeCheckbox && !termsAgreeCheckbox.checked) {
        showFieldError(termsAgreeCheckbox, errorMessages.terms_agree.required);
        isValid = false;
        if (!firstInvalidElement) firstInvalidElement = termsAgreeCheckbox;
    } else if (!termsAgreeCheckbox) {
        console.error("Terms and conditions checkbox (terms_agree) not found.");
        window.showMessage(errorMessages.terms_agree.required, 'error');
        isValid = false;
    }

    if (!isValid && firstInvalidElement) {
        firstInvalidElement.focus();
    }

    const formErrorsLiveRegion = document.getElementById('form-errors-live-region');
    if (formErrorsLiveRegion) {
        if (isValid) {
            formErrorsLiveRegion.textContent = '';
        } else {
            formErrorsLiveRegion.textContent = 'لطفاً تمام فیلدهای الزامی را به درستی پر کنید.';
        }
    }

    return isValid;
}

/**
 * Handles server-side validation errors (status 422).
 * @param {Object} errors - The error object from the server response.
 */
function handleServerValidationError(errors) {
    let errorMessage = 'لطفاً اطلاعات ورودی را بررسی کنید: <br>';
    for (const field in errors) {
        errorMessage += `- ${errors[field].join(', ')}<br>`;
        const inputElement = document.getElementById(field);
        if (inputElement) {
            showFieldError(inputElement, errors[field].join(', '));
        }
    }
    window.showMessage(errorMessage, 'error', 5000);
    console.error('Order placement error (server validation failed):', { errors: errors });
}

/**
 * Handles general client-side errors (4xx other than 422).
 * @param {Object} result - The response body from the server.
 * @param {number} status - The HTTP status code.
 */
function handleClientError(result, status) {
    window.showMessage(result.message || 'خطا در ثبت سفارش. لطفاً دوباره تلاش کنید.', 'error');
    console.error('Order placement error (client error):', { status: status, responseBody: result });
}

/**
 * Handles network errors or retryable server errors (5xx).
 * @param {Object} result - The response body from the server.
 * @param {number} status - The HTTP status code.
 * @param {number} attempt - Current retry attempt number.
 * @param {number} maxRetries - Maximum retry attempts.
 */
function handleRetryableError(result, status, attempt, maxRetries) {
    console.warn(`Attempt ${attempt} failed. Retrying...`, { status: status, responseBody: result });
    if (attempt < maxRetries) {
        window.showMessage('خطا در ثبت سفارش. تلاش مجدد...', 'info', 2000);
    } else {
        window.showMessage(result.message || 'خطا در ثبت سفارش. لطفاً دوباره تلاش کنید.', 'error');
        console.error('Order placement error (all retries failed):', { status: status, responseBody: result });
    }
}

/**
 * Attempts to place an order with a retry mechanism and timeout.
 * @param {Object} data - The order data.
 * @param {number} maxRetries - Maximum number of retries.
 * @param {number} delay - Initial delay between retries in milliseconds.
 * @returns {Promise<Object>} The response JSON if successful.
 * @throws {Error} If all retries fail or validation fails.
 */
async function placeOrderWithRetry(data, maxRetries = 3, delay = 1000) {
    for (let i = 0; i < maxRetries; i++) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

        try {
            showProgress(i + 1, maxRetries + 1); // +1 for the final success step
            const response = await fetch('/order/place', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify(data),
                signal: controller.signal // Add timeout signal
            });

            clearTimeout(timeoutId); // Clear timeout if request completes in time

            const result = await response.json();

            if (response.ok) {
                showProgress(maxRetries + 1, maxRetries + 1); // 100% progress on success
                return result;
            }

            // Handle specific non-retryable errors (e.g., validation errors)
            if (response.status === 422) {
                handleServerValidationError(result.errors);
                throw new Error('Validation failed on server'); // Throw to exit retry loop
            } else if (response.status >= 400 && response.status < 500) {
                handleClientError(result, response.status);
                throw new Error('Client error, not retrying');
            } else {
                handleRetryableError(result, response.status, i + 1, maxRetries);
                if (i < maxRetries - 1) {
                    await new Promise(resolve => setTimeout(resolve, delay * (i + 1))); // Exponential backoff
                } else {
                    throw new Error('All retries failed');
                }
            }
        } catch (error) {
            clearTimeout(timeoutId); // Ensure timeout is cleared even on network errors
            if (error.name === 'AbortError') {
                window.showMessage('درخواست بیش از حد طولانی شد. لطفاً دوباره تلاش کنید.', 'error'); // Request took too long. Please try again.
                console.error('Request timed out:', error);
                throw error; // Re-throw to exit retry loop
            }
            // Network errors or unexpected issues
            console.error(`Attempt ${i + 1} caught an error:`, { message: error.message, stack: error.stack });
            if (i < maxRetries - 1) {
                window.showMessage('خطا در ارتباط با سرور. تلاش مجدد...', 'info', 2000); // Error connecting to server. Retrying...
                await new Promise(resolve => setTimeout(resolve, delay * (i + 1)));
            } else {
                window.showMessage('خطا در ارتباط با سرور. لطفاً اتصال اینترنت خود را بررسی کنید.', 'error'); // Error connecting to server. Please check your internet connection.
                throw error; // Re-throw the error after last retry fails
            }
        }
    }
    throw new Error('Unexpected error in placeOrderWithRetry'); // Should not be reached
}

// Get CSRF Token from meta tag
function getCsrfToken() {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    return csrfMeta ? csrfMeta.getAttribute('content') : '';
}

/**
 * Saves form data to local storage as a draft.
 */
function saveDraft() {
    const placeOrderForm = document.getElementById('place-order-form');
    if (!placeOrderForm) return; // Ensure form exists before trying to save

    const formData = new FormData(placeOrderForm);
    const data = Object.fromEntries(formData.entries());
    try {
        localStorage.setItem('orderDraft', JSON.stringify(data));
        // console.log('Draft saved.'); // For debugging
    } catch (e) {
        console.error('Error saving draft to local storage:', e);
    }
}

/**
 * Loads form data from local storage draft.
 */
function loadDraft() {
    const placeOrderForm = document.getElementById('place-order-form');
    if (!placeOrderForm) return; // Ensure form exists before trying to load

    try {
        const draft = localStorage.getItem('orderDraft');
        if (draft) {
            const data = JSON.parse(draft);
            Object.entries(data).forEach(([key, value]) => {
                const field = placeOrderForm.querySelector(`[name="${key}"]`);
                if (field) {
                    if (field.type === 'radio' || field.type === 'checkbox') {
                        if (field.value === value) {
                            field.checked = true;
                        }
                    } else {
                        field.value = value;
                    }
                }
            });
            // console.log('Draft loaded.'); // For debugging
        }
    } catch (e) {
        console.error('Error loading draft from local storage:', e);
        localStorage.removeItem('orderDraft'); // Clear corrupted draft
    }
}

// --- Address Selection Logic ---

// Define addressFields object with null initial values
const addressFields = {
    first_name: null,
    last_name: null,
    phone_number: null,
    address: null,
    province: null,
    city: null,
    postal_code: null
};

// Populate addressFields with actual DOM elements after DOMContentLoaded
function initializeAddressFields() {
    addressFields.first_name = document.getElementById('first_name');
    addressFields.last_name = document.getElementById('last_name');
    addressFields.phone_number = document.getElementById('phone_number');
    addressFields.address = document.getElementById('address');
    addressFields.province = document.getElementById('province');
    addressFields.city = document.getElementById('city');
    addressFields.postal_code = document.getElementById('postal_code');
}


/**
 * Populates address fields with data from a given address object.
 * @param {Object} address - The address object containing user details.
 */
function populateAddressFields(address) {
    // Check if the element exists before trying to set its value
    if (addressFields.first_name) addressFields.first_name.value = address.first_name || '';
    if (addressFields.last_name) addressFields.last_name.value = address.last_name || '';
    if (addressFields.phone_number) addressFields.phone_number.value = address.phone_number || '';
    if (addressFields.address) addressFields.address.value = address.address || '';
    if (addressFields.province) addressFields.province.value = address.province || '';
    if (addressFields.city) addressFields.city.value = address.city || '';
    if (addressFields.postal_code) addressFields.postal_code.value = address.postal_code || '';
    // Clear any validation errors when populating fields
    Object.keys(addressFields).forEach(fieldId => {
        const inputElement = addressFields[fieldId];
        if (inputElement) clearFieldError(inputElement); // Only clear if element exists
    });
}

/**
 * Clears all address input fields.
 */
function clearAddressFields() {
    if (addressFields.first_name) addressFields.first_name.value = '';
    if (addressFields.last_name) addressFields.last_name.value = '';
    if (addressFields.phone_number) addressFields.phone_number.value = '';
    if (addressFields.address) addressFields.address.value = '';
    if (addressFields.province) addressFields.province.value = '';
    if (addressFields.city) addressFields.city.value = '';
    if (addressFields.postal_code) addressFields.postal_code.value = '';
    // Clear any validation errors
    Object.keys(addressFields).forEach(fieldId => {
        const inputElement = addressFields[fieldId];
        if (inputElement) clearFieldError(inputElement); // Only clear if element exists
    });
}

/**
 * Function to format numbers with commas
 * @param {number} num - The number to format.
 * @returns {string} The formatted number string.
 */
function formatNumber(num) {
    return new Intl.NumberFormat('fa-IR').format(num);
}

/**
 * Updates the total cart price displayed in the summary.
 */
function updateCartTotal() {
    const cartItemsSummary = document.getElementById('cart-items-summary');
    const cartTotalPriceElement = document.getElementById('cart-total-price');

    if (!cartItemsSummary || !cartTotalPriceElement) {
        console.warn('Cart summary elements not found for updateCartTotal.');
        return;
    }

    let total = 0;
    document.querySelectorAll('#cart-items-summary > div').forEach(itemElement => {
        const subtotal = parseFloat(itemElement.querySelector('.item-subtotal').dataset.subtotal);
        total += subtotal;
    });
    cartTotalPriceElement.textContent = `${formatNumber(total)} تومان`;
    cartTotalPriceElement.dataset.totalPrice = total; // Update data attribute
}

/**
 * Sets the loading state for the form and button.
 * @param {boolean} isLoading - True to set loading state, false to reset.
 */
function setLoadingState(isLoading) {
    const placeOrderForm = document.getElementById('place-order-form');
    const placeOrderBtn = document.getElementById('place-order-btn');

    if (placeOrderBtn) {
        if (isLoading) {
            placeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال ثبت سفارش...'; // Placing order...
            placeOrderBtn.disabled = true;
            placeOrderBtn.classList.add('opacity-70', 'cursor-not-allowed');
        } else {
            placeOrderBtn.innerHTML = 'ثبت سفارش نهایی'; // Finalize Order
            placeOrderBtn.disabled = false;
            placeOrderBtn.classList.remove('opacity-70', 'cursor-not-allowed');
        }
    }
    if (placeOrderForm) {
        // Disable all form inputs to prevent interaction during submission
        const formElements = placeOrderForm.querySelectorAll('input, select, textarea, button:not(#place-order-btn)');
        formElements.forEach(el => {
            if (el !== placeOrderBtn) { // Don't disable the main button twice
                el.disabled = isLoading;
            }
        });
    }
}

/**
 * Initializes all client-side logic for the checkout page.
 * This function will be called by app.js when the checkout module is dynamically loaded.
 */
export function initCheckout() {
    console.log('Checkout module initializing...');

    // Cache DOM elements for better performance
    const placeOrderForm = document.getElementById('place-order-form');
    const placeOrderBtn = document.getElementById('place-order-btn');
    const progressBar = document.getElementById('progress-bar');
    const formErrorsLiveRegion = document.getElementById('form-errors-live-region'); // Live region for accessibility

    // Elements for quantity control in cart summary
    const cartItemsSummary = document.getElementById('cart-items-summary');
    const cartTotalPriceElement = document.getElementById('cart-total-price');

    // Retrieve data passed from Blade template via data attributes
    const addressesData = placeOrderForm ? JSON.parse(placeOrderForm.dataset.addresses || '[]') : [];
    const defaultAddressData = placeOrderForm ? JSON.parse(placeOrderForm.dataset.defaultAddress || 'null') : null;

    // Setup real-time validation for relevant input fields using event delegation
    if (placeOrderForm) {
        placeOrderForm.addEventListener('input', debounce((e) => {
            const target = e.target;
            const fieldId = target.id;
            const value = target.value.trim();

            if (validationRules[fieldId]) {
                validateField(fieldId, value, validationRules[fieldId], target);
            }
        }, 500)); // 500ms debounce
    }

    // --- Address Selection Logic ---
    const addressRadiosContainer = document.getElementById('address-selection-radios');
    if (addressRadiosContainer) {
        addressRadiosContainer.addEventListener('change', function(event) {
            if (event.target.name === 'selected_address_id') {
                const selectedValue = event.target.value;
                if (selectedValue === 'new') {
                    clearAddressFields();
                } else {
                    const selectedAddress = addressesData.find(addr => addr.id == selectedValue);
                    if (selectedAddress) {
                        populateAddressFields(selectedAddress);
                    }
                }
            }
        });
    }

    // Initialize address fields and then populate if default address exists
    initializeAddressFields(); // Call this after DOMContentLoaded
    if (defaultAddressData) {
        populateAddressFields(defaultAddressData);
        // Ensure the correct radio button is checked if a default address is loaded
        const defaultRadio = document.getElementById(`address_${defaultAddressData.id}`);
        if (defaultRadio) {
            defaultRadio.checked = true;
        }
    } else {
        // If no default address or no addresses at all, ensure "new address" is selected and fields are clear
        const newAddressRadio = document.getElementById('address_new');
        if (newAddressRadio) {
            newAddressRadio.checked = true;
        }
        clearAddressFields();
    }
    // --- End Address Selection Logic ---

    // Initial load of draft on module initialization
    loadDraft();

    // Auto-save every 30 seconds
    setInterval(saveDraft, 30000);

    // 1. Add confirmation before leaving the page if there's unsaved data
    window.addEventListener('beforeunload', function(e) {
        const hasUnsavedData = localStorage.getItem('orderDraft');
        if (hasUnsavedData) {
            e.preventDefault();
            e.returnValue = 'تغییرات شما ذخیره نشده است. مطمئن هستید که می‌خواهید صفحه را ترک کنید؟';
        }
    });

    // 3. Add form timeout
    const FORM_TIMEOUT = 30 * 60 * 1000; // 30 minutes in milliseconds
    let formTimeoutId = null;

    /**
     * Starts or resets the form timeout.
     * If the timeout expires, the form will be disabled.
     */
    function startFormTimeout() {
        if (formTimeoutId) clearTimeout(formTimeoutId); // Clear any existing timeout
        formTimeoutId = setTimeout(() => {
            window.showMessage('جلسه شما منقضی شده است. لطفاً صفحه را بازخوانی کنید تا اطلاعات به روز شوند.', 'warning', 7000);
            if (placeOrderForm) {
                placeOrderForm.classList.add('pointer-events-none', 'opacity-70');
            }
            if (placeOrderBtn) {
                placeOrderBtn.disabled = true;
            }
        }, FORM_TIMEOUT);
    }

    // Start timeout on initial load and reset on user interaction
    startFormTimeout();
    if (placeOrderForm) {
        placeOrderForm.addEventListener('input', startFormTimeout);
        placeOrderForm.addEventListener('change', startFormTimeout);

        placeOrderForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            if (!navigator.onLine) {
                window.showMessage('اتصال اینترنت برقرار نیست. لطفاً اتصال خود را بررسی کنید و دوباره تلاش کنید.', 'error');
                return;
            }

            const formData = new FormData(placeOrderForm);
            const data = Object.fromEntries(formData.entries());

            if (!validateFormFields(data)) {
                showProgress(0, 1);
                return;
            }

            setLoadingState(true);
            showProgress(0, 4);

            try {
                const result = await placeOrderWithRetry(data);

                window.showMessage(result.message, 'success');
                localStorage.removeItem('orderDraft');
                if (result.orderId) {
                    if (formTimeoutId) clearTimeout(formTimeoutId);
                    setTimeout(() => {
                        window.location.href = `/order/confirmation/${result.orderId}`;
                    }, 1500);
                } else {
                    console.warn('Order ID not returned from server. Cart might need manual clearing.');
                }
            } catch (error) {
                console.error('Final order placement attempt failed:', error);
            } finally {
                setLoadingState(false);
                showProgress(0, 1);
            }
        });
    }

    // Initial call to update cart total on load if cart summary exists
    if (cartItemsSummary && cartTotalPriceElement) {
        updateCartTotal();
    }

    // Event listener for quantity buttons in cart summary on checkout page
    if (cartItemsSummary) {
        cartItemsSummary.addEventListener('click', async function(event) {
            const target = event.target;
            if (target.classList.contains('quantity-btn')) {
                const itemElement = target.closest('[data-item-id]');
                if (!itemElement) return;

                const itemId = itemElement.dataset.itemId;
                const itemPrice = parseFloat(itemElement.dataset.itemPrice);
                const quantitySpan = itemElement.querySelector('.item-quantity');
                let currentQuantity = parseInt(quantitySpan.dataset.quantity);
                const itemSubtotalElement = itemElement.querySelector('.item-subtotal');

                let oldQuantity = currentQuantity;

                if (target.classList.contains('plus-btn')) {
                    currentQuantity++;
                } else if (target.classList.contains('minus-btn')) {
                    if (currentQuantity > 1) {
                        currentQuantity--;
                    } else {
                        return;
                    }
                }

                quantitySpan.textContent = formatNumber(currentQuantity);
                quantitySpan.dataset.quantity = currentQuantity;

                const newSubtotal = itemPrice * currentQuantity;
                itemSubtotalElement.textContent = `${formatNumber(newSubtotal)} تومان`;
                itemSubtotalElement.dataset.subtotal = newSubtotal;

                updateCartTotal();

                try {
                    const response = await fetch(`/cart/update/${itemId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken()
                        },
                        body: JSON.stringify({
                            quantity: currentQuantity
                        })
                    });
                    const result = await response.json();

                    if (response.ok) {
                        window.showMessage(result.message || 'تعداد محصول به‌روزرسانی شد.', 'success');
                    } else {
                        window.showMessage(result.message || 'خطا در به‌روزرسانی تعداد محصول.', 'error');
                        quantitySpan.textContent = formatNumber(oldQuantity);
                        quantitySpan.dataset.quantity = oldQuantity;
                        itemSubtotalElement.textContent = `${formatNumber(itemPrice * oldQuantity)} تومان`;
                        itemSubtotalElement.dataset.subtotal = itemPrice * oldQuantity;
                        updateCartTotal();
                    }
                } catch (error) {
                    console.error('Error updating cart item quantity:', error);
                    window.showMessage('خطا در ارتباط با سرور. لطفاً اتصال اینترنت خود را بررسی کنید.', 'error');
                    quantitySpan.textContent = formatNumber(oldQuantity);
                    quantitySpan.dataset.quantity = oldQuantity;
                    itemSubtotalElement.textContent = `${formatNumber(itemPrice * oldQuantity)} تومان`;
                    itemSubtotalElement.dataset.subtotal = itemPrice * oldQuantity;
                    updateCartTotal();
                }
            }
        });
    }
    console.log('Checkout module initialized successfully.');
}
