// Get references to DOM elements for mini cart functionality
const cartIconContainer = document.getElementById('cart-icon-container');
const cartItemCountSpan = document.getElementById('cart-item-count'); // This is the count in the header for both
const miniCartDropdown = document.getElementById('mini-cart-dropdown');
const miniCartContent = document.getElementById('mini-cart-content');
const miniCartEmptyMessage = document.getElementById('mini-cart-empty-message');
const miniCartTotalPriceSpan = document.getElementById('mini-cart-total-price');
const miniCartSummary = document.getElementById('mini-cart-summary');
const miniCartActions = document.getElementById('mini-cart-actions');

// Get references for main cart functionality (only if on cart page)
const cartItemsContainer = document.getElementById('cart-items-container');
const cartContent = document.getElementById('cart-content'); // Main div for cart table/summary
const cartTotalRow = document.getElementById('cart-total-row'); // Reference to the new tfoot total row
const cartTotalPriceFooterSpan = document.getElementById('cart-total-price-footer'); // Reference to the span within tfoot


// Get references for search functionality
const searchAreaWrapper = document.getElementById('search-area-wrapper');
const searchInput = document.getElementById('search-input');
const searchResultsContainer = document.getElementById('search-results-container');
const searchResultsDiv = document.getElementById('search-results');
const searchResultsEmptyDiv = document.getElementById('search-results-empty');
const searchLoadingIndicator = document.getElementById('search-loading-indicator');

// Get references for Auth Modal
const authModalOverlay = document.getElementById('auth-modal-overlay');
const authModalCloseBtn = document.getElementById('auth-modal-close-btn');
const mobileLoginStep = document.getElementById('mobile-login-step');
const smsVerifyStep = document.getElementById('sms-verify-step');
const completeProfileStep = document.getElementById('complete-profile-step');

// Mobile login step elements
const mobileNumberInput = document.getElementById('mobile-number');
const getOtpBtn = document.getElementById('get-otp-btn');
// authSwitchLink را مستقیماً بررسی می‌کنیم چون از querySelector استفاده می‌کند و اگر یافت نشود null برمی‌گرداند.
// const authSwitchLink = document.querySelector('#mobile-login-step .auth-switch-link');


// SMS verify step elements
const otpCodeInput = document.getElementById('otp-code');
const verifyOtpBtn = document.getElementById('verify-otp-btn');
const resendOtpBtn = document.getElementById('resend-otp-btn');
const displayMobileNumberSpan = document.getElementById('display-mobile-number');

// Complete profile step elements
const completeProfileForm = document.getElementById('complete-profile-form');
const firstNameInput = document.getElementById('first-name');
const lastNameInput = document.getElementById('last-name');
const emailInput = document.getElementById('email');
const streetAddressInput = document.getElementById('street-address');
const provinceSelect = document.getElementById('province');
const citySelect = document.getElementById('city');
const postalCodeInput = document.getElementById('postal-code');
const completeProfileSubmitBtn = document.getElementById('complete-profile-submit-btn');

// --- Utility Functions ---

// Function to show custom message boxes (replaces alert/confirm)
function showMessage(message, type = 'info', duration = 3000) {
    const messageBox = document.createElement('div');
    // FIXED: Corrected the incomplete className string
    messageBox.className = `fixed bottom-5 left-1/2 transform -translate-x-1/2 px-6 py-3 rounded-lg shadow-lg text-white z-50 transition-all duration-300 ease-out message-box ${type === 'success' ? 'bg-green-600' : (type === 'error' ? 'bg-red-600' : 'bg-gray-800')}`;
    messageBox.textContent = message;
    document.body.appendChild(messageBox);

    setTimeout(() => {
        messageBox.classList.add('opacity-0', 'translate-y-full');
        messageBox.addEventListener('transitionend', () => messageBox.remove());
    }, duration);
}

// Function to format numbers as Iranian currency
function formatCurrency(number) {
    return new Intl.NumberFormat('fa-IR').format(number);
}

// Function to get CSRF token
function getCsrfToken() {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    return csrfMeta ? csrfMeta.getAttribute('content') : '';
}


// --- Cart Functionality (Integrated with Backend) ---

// Function to fetch and render cart contents
async function fetchAndRenderCart() {
    try {
        const response = await fetch('/cart/contents');
        const data = await response.json();

        // Update mini cart icon count
        if (cartItemCountSpan) {
            cartItemCountSpan.textContent = formatCurrency(data.totalItemsInCart);
        }

        // Update mini cart dropdown content
        if (miniCartContent) {
            miniCartContent.innerHTML = ''; // Clear previous content
            if (data.cartItems.length === 0) {
                if (miniCartEmptyMessage) miniCartEmptyMessage.classList.remove('hidden');
                if (miniCartSummary) miniCartSummary.classList.add('hidden');
                if (miniCartActions) miniCartActions.classList.add('hidden');
            } else {
                if (miniCartEmptyMessage) miniCartEmptyMessage.classList.add('hidden');
                if (miniCartSummary) miniCartSummary.classList.remove('hidden');
                if (miniCartActions) miniCartActions.classList.remove('hidden');

                data.cartItems.forEach(item => {
                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'flex items-center py-2 border-b border-gray-200 last:border-b-0';
                    itemDiv.innerHTML = `
                        <img src="${item.product.image || 'https://placehold.co/50x50/E5E7EB/4B5563?text=Product'}" alt="${item.product.title}" class="w-12 h-12 object-cover rounded-md ml-3">
                        <div class="flex-grow">
                            <h4 class="text-sm font-semibold text-gray-800"><a href="/products/${item.product.id}" class="hover:text-green-700 transition-colors duration-200">${item.product.title}</a></h4>
                            <p class="text-xs text-gray-600">${formatCurrency(item.quantity)} x ${formatCurrency(item.price)} تومان</p>
                        </div>
                        <button class="remove-from-cart-btn text-red-500 hover:text-red-700 transition-colors duration-200" data-cart-item-id="${item.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                    miniCartContent.appendChild(itemDiv);
                });
                if (miniCartTotalPriceSpan) miniCartTotalPriceSpan.textContent = formatCurrency(data.totalPrice);
            }
        }

        // Update main cart page if on cart page
        if (cartItemsContainer) { // Check if we are on the main cart page
            renderMainCart(data.cartItems, data.totalPrice);
        }

    } catch (error) {
        console.error('Error fetching cart contents:', error);
        showMessage('خطا در بارگذاری سبد خرید.', 'error');
    }
}

// Function to render main cart table (on cart.blade.php)
function renderMainCart(items, totalPrice) {
    if (!cartItemsContainer || !cartContent || !cartTotalRow || !cartTotalPriceFooterSpan) return; // Only run if on cart page and elements exist

    cartItemsContainer.innerHTML = ''; // Clear existing items

    if (items.length === 0) {
        cartContent.innerHTML = `
            <div class="text-center py-10">
                <i class="fas fa-shopping-cart text-gray-400 text-6xl mb-4"></i>
                <p class="text-gray-600 text-xl font-semibold mb-2">سبد خرید شما خالی است.</p>
                <p class="text-gray-500">برای شروع خرید، محصولات مورد علاقه خود را اضافه کنید.</p>
                <a href="/products" class="btn-primary mt-6 inline-flex items-center">
                    شروع خرید
                    <i class="fas fa-arrow-left mr-2"></i>
                </a>
            </div>
        `;
        cartTotalRow.classList.add('hidden');
        return;
    }

    cartTotalRow.classList.remove('hidden');
    cartTotalPriceFooterSpan.textContent = formatCurrency(totalPrice);


    items.forEach(item => {
        const row = document.createElement('tr');
        row.className = 'border-b last:border-b-0 hover:bg-gray-50 transition-colors duration-150';
        row.innerHTML = `
            <td class="py-4 pr-2 flex items-center">
                <button class="remove-from-cart-btn text-red-500 hover:text-red-700 transition-colors duration-200 text-lg ml-4" data-cart-item-id="${item.id}">
                    <i class="fas fa-times-circle"></i>
                </button>
                <img src="${item.product.image || 'https://placehold.co/80x80/E5E7EB/4B5563?text=Product'}" alt="${item.product.title}" class="w-20 h-20 object-cover rounded-lg ml-4 border border-gray-200">
                <a href="/products/${item.product.id}" class="text-gray-800 font-semibold hover:text-green-700 transition-colors duration-200">${item.product.title}</a>
            </td>
            <td class="py-4 text-center text-gray-700">${formatCurrency(item.price)} تومان</td>
            <td class="py-4 text-center">
                <div class="flex items-center justify-center">
                    <button class="update-quantity-btn text-gray-600 hover:text-green-700 p-2 rounded-full" data-cart-item-id="${item.id}" data-action="decrease">
                        <i class="fas fa-minus"></i>
                    </button>
                    <input type="number" value="${item.quantity}" min="1" max="${item.product.stock}" data-cart-item-id="${item.id}" class="cart-quantity-input w-16 mx-2 p-2 border border-gray-300 rounded-md text-center focus:outline-none focus:ring-2 focus:ring-green-700">
                    <button class="update-quantity-btn text-gray-600 hover:text-green-700 p-2 rounded-full" data-cart-item-id="${item.id}" data-action="increase">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </td>
            <td class="py-4 pl-2 text-left text-green-700 font-bold">${formatCurrency(item.price * item.quantity)} تومان</td>
        `;
        cartItemsContainer.appendChild(row);
    });
}


// Event listener for adding to cart (using event delegation for reliability)
document.addEventListener('click', async (event) => {
    const button = event.target.closest('.add-to-cart-btn');
    if (button) {
        // Ensure event.currentTarget is not null here, although 'button' should now be the valid element
        // This is a safety check as per previous error reports, though with .closest(), 'button' should be reliable.
        if (!button) {
            console.error('Add to Cart Error: button element is null after .closest().');
            return;
        }

        const productId = button.dataset.productId;
        const quantityInput = button.closest('.flex') ? button.closest('.flex').querySelector('input[type="number"]') : null;
        const quantity = quantityInput ? parseInt(quantityInput.value) : 1;

        button.disabled = true; // Disable button to prevent multiple clicks
        const originalBtnText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال افزودن...';


        try {
            const response = await fetch('/cart/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({ product_id: productId, quantity: quantity })
            });

            const data = await response.json();

            if (response.ok) {
                showMessage(data.message, 'success');
                fetchAndRenderCart(); // Re-fetch and update cart display
            } else {
                showMessage(data.message || 'خطا در افزودن محصول به سبد خرید.', 'error');
            }
        } catch (error) {
            console.error('Add to cart error:', error);
            showMessage('خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.', 'error');
        } finally {
            if (button) { // Ensure button is still valid before resetting its state
                button.innerHTML = originalBtnText; // This line resets the button text
                button.disabled = false;
            }
        }
    }
});


// Event listener for removing from cart (delegation for dynamically added elements)
document.addEventListener('click', async (event) => {
    if (event.target.closest('.remove-from-cart-btn')) {
        const button = event.target.closest('.remove-from-cart-btn');
        const cartItemId = button.dataset.cartItemId;

        // Custom confirmation dialog
        const confirmRemove = await new Promise(resolve => {
            const confirmationModal = document.createElement('div');
            confirmationModal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            confirmationModal.innerHTML = `
                <div class="bg-white p-8 rounded-lg shadow-xl text-center">
                    <p class="text-lg font-semibold text-gray-800 mb-6">آیا مطمئن هستید که می‌خواهید این محصول را از سبد خرید حذف کنید؟</p>
                    <div class="flex justify-center space-x-4 space-x-reverse">
                        <button id="confirm-yes" class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors duration-200">بله، حذف کن</button>
                        <button id="confirm-no" class="px-6 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors duration-200">خیر، لغو</button>
                    </div>
                </div>
            `;
            document.body.appendChild(confirmationModal);

            document.getElementById('confirm-yes').addEventListener('click', () => {
                confirmationModal.remove();
                resolve(true);
            });
            document.getElementById('confirm-no').addEventListener('click', () => {
                confirmationModal.remove();
                resolve(false);
            });
        });

        // Fixed: Ensure confirmRemove is checked before proceeding
        if (!confirmRemove) {
            return;
        }


        try {
            const response = await fetch(`/cart/remove/${cartItemId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            });

            const data = await response.json();

            if (response.ok) {
                showMessage(data.message, 'success');
                fetchAndRenderCart(); // Re-fetch and update cart display
            } else {
                showMessage(data.message || 'خطا در حذف محصول از سبد خرید.', 'error');
            }
        } catch (error) {
            console.error('Remove from cart error:', error);
            showMessage('خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.', 'error');
        }
    }
});

// Event listener for updating quantity (delegation)
document.addEventListener('input', async (event) => {
    if (event.target.classList.contains('cart-quantity-input')) {
        const input = event.target;
        const cartItemId = input.dataset.cartItemId;
        const newQuantity = parseInt(input.value);

        if (isNaN(newQuantity) || newQuantity < 0) {
            input.value = input.min; // Reset to min value if invalid
            showMessage('تعداد وارد شده نامعتبر است.', 'error');
            return;
        }

        // Delay updating to prevent multiple rapid requests
        if (input.timeout) clearTimeout(input.timeout);
        input.timeout = setTimeout(async () => {
            try {
                const response = await fetch(`/cart/update/${cartItemId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify({ quantity: newQuantity })
                });

                const data = await response.json();

                if (response.ok) {
                    showMessage(data.message, 'success');
                    fetchAndRenderCart(); // Re-fetch and update cart display
                } else {
                    // Revert quantity if update failed (e.g., stock issue)
                    input.value = data.cartItem ? data.cartItem.quantity : (input.dataset.oldQuantity || 1);
                    showMessage(data.message || 'خطا در به‌روزرسانی تعداد محصول.', 'error');
                }
            } catch (error) {
                console.error('Update quantity error:', error);
                input.value = input.dataset.oldQuantity || 1; // Revert on network error
                showMessage('خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.', 'error');
            }
        }, 500); // Wait 500ms after last input
    }
});

document.addEventListener('click', async (event) => {
    if (event.target.closest('.update-quantity-btn')) {
        const button = event.target.closest('.update-quantity-btn');
        const cartItemId = button.dataset.cartItemId;
        const action = button.dataset.action;
        const quantityInput = document.querySelector(`.cart-quantity-input[data-cart-item-id="${cartItemId}"]`);
        if (!quantityInput) return; // Add null check for quantityInput

        let newQuantity = parseInt(quantityInput.value);
        if (action === 'increase') {
            newQuantity = newQuantity + 1;
        } else if (action === 'decrease') {
            newQuantity = newQuantity - 1;
        }

        // Manually update input value and trigger input event for consistency
        quantityInput.value = newQuantity;
        quantityInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
});


// Event listener for clearing the entire cart (on main cart page)
if (document.getElementById('clear-cart-btn')) {
    document.getElementById('clear-cart-btn').addEventListener('click', async () => {
        // Custom confirmation dialog
        const confirmClear = await new Promise(resolve => {
            const confirmationModal = document.createElement('div');
            confirmationModal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            confirmationModal.innerHTML = `
                <div class="bg-white p-8 rounded-lg shadow-xl text-center">
                    <p class="text-lg font-semibold text-gray-800 mb-6">آیا مطمئن هستید که می‌خواهید کل سبد خرید را خالی کنید؟</p>
                    <div class="flex justify-center space-x-4 space-x-reverse">
                        <button id="confirm-yes" class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors duration-200">بله، خالی کن</button>
                        <button id="confirm-no" class="px-6 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors duration-200">خیر، لغو</button>
                    </div>
                </div>
            `;
            document.body.appendChild(confirmationModal);

            document.getElementById('confirm-yes').addEventListener('click', () => {
                confirmationModal.remove();
                resolve(true);
            });
            document.getElementById('confirm-no').addEventListener('click', () => {
                confirmationModal.remove();
                resolve(false);
            });
        });

        if (!confirmClear) {
            return;
        }

        try {
            const response = await fetch('/cart/clear', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            });

            const data = await response.json();

            if (response.ok) {
                showMessage(data.message, 'success');
                fetchAndRenderCart(); // Re-fetch and update cart display
            } else {
                showMessage(data.message || 'خطا در خالی کردن سبد خرید.', 'error');
            }
        } catch (error) {
            console.error('Clear cart error:', error);
            showMessage('خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.', 'error');
        }
    });
}


// Initial fetch of cart contents on page load
document.addEventListener('DOMContentLoaded', fetchAndRenderCart);


// --- Search Functionality ---

let searchTimeout;
if (searchInput) {
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch(searchInput.value);
        }, 300); // Debounce search input
    });
}


async function performSearch(query) {
    if (query.length < 2) {
        if (searchResultsContainer) searchResultsContainer.classList.add('hidden');
        return;
    }

    if (searchLoadingIndicator) searchLoadingIndicator.classList.remove('hidden');
    if (searchResultsDiv) searchResultsDiv.innerHTML = ''; // Clear previous results
    if (searchResultsEmptyDiv) searchResultsEmptyDiv.classList.add('hidden');
    if (searchResultsContainer) searchResultsContainer.classList.remove('hidden');


    try {
        const response = await fetch(`/search?q=${encodeURIComponent(query)}`);
        const results = await response.json();

        if (results.length === 0) {
            if (searchResultsEmptyDiv) searchResultsEmptyDiv.classList.remove('hidden');
        } else {
            results.forEach(product => {
                const resultItem = document.createElement('a');
                resultItem.href = `/products/${product.id}`;
                resultItem.className = 'flex items-center p-2 hover:bg-gray-100 border-b border-gray-200 last:border-b-0';
                resultItem.innerHTML = `
                    <img src="${product.image || 'https://placehold.co/40x40/E5E7EB/4B5563?text=Product'}" alt="${product.title}" class="w-10 h-10 object-cover rounded ml-2">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">${product.title}</p>
                        <p class="text-xs text-green-700">${formatCurrency(product.price)} تومان</p>
                    </div>
                `;
                if (searchResultsDiv) searchResultsDiv.appendChild(resultItem);
            });
        }
    } catch (error) {
        console.error('Search error:', error);
        if (searchResultsEmptyDiv) {
            searchResultsEmptyDiv.textContent = 'خطا در جستجو.';
            searchResultsEmptyDiv.classList.remove('hidden');
        }
    } finally {
        if (searchLoadingIndicator) searchLoadingIndicator.classList.add('hidden');
    }
}

// Close search results when clicking outside
document.addEventListener('click', (event) => {
    // Add null checks for searchResultsContainer and searchInput
    if (searchResultsContainer && searchInput && !searchResultsContainer.contains(event.target) && !searchInput.contains(event.target)) {
        searchResultsContainer.classList.add('hidden');
    }
});


// --- Auth Modal Functionality ---

// Open Auth Modal (assuming you have a button for this, e.g., in nav.blade.php)
if (document.getElementById('open-auth-modal-btn')) { // Example ID for a button to open modal
    document.getElementById('open-auth-modal-btn').addEventListener('click', () => {
        if (authModalOverlay) authModalOverlay.classList.remove('hidden');
        if (mobileLoginStep) mobileLoginStep.classList.remove('hidden');
        if (smsVerifyStep) smsVerifyStep.classList.add('hidden');
        if (completeProfileStep) completeProfileStep.classList.add('hidden');
    });
}

// Close Auth Modal
if (authModalCloseBtn) {
    authModalCloseBtn.addEventListener('click', () => {
        if (authModalOverlay) authModalOverlay.classList.add('hidden');
    });
}

// Close modal on outside click
if (authModalOverlay) {
    authModalOverlay.addEventListener('click', (event) => {
        if (event.target === authModalOverlay) {
            authModalOverlay.classList.add('hidden');
        }
    });
}

// Handle Get OTP button click
if (getOtpBtn) {
    getOtpBtn.addEventListener('click', async (event) => {
        event.preventDefault();
        // اطمینان از وجود mobileNumberInput
        const mobileNumber = mobileNumberInput ? mobileNumberInput.value : '';
        const originalBtnText = getOtpBtn.innerHTML;

        if (!mobileNumber) {
            showMessage('لطفاً شماره موبایل را وارد کنید.', 'error');
            return;
        }

        getOtpBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال ارسال...';
        getOtpBtn.disabled = true;
        getOtpBtn.classList.add('opacity-70', 'cursor-not-allowed');

        try {
            // Simulate API call for OTP (replace with actual backend call)
            // In a real scenario, this would call a Laravel endpoint to send OTP
            // await new Promise(resolve => setTimeout(resolve, 2000)); // Simulate network delay

            // For now, let's just proceed to the next step
            if (mobileLoginStep) mobileLoginStep.classList.add('hidden');
            if (smsVerifyStep) smsVerifyStep.classList.remove('hidden');
            if (displayMobileNumberSpan) displayMobileNumberSpan.textContent = mobileNumber; // Display mobile number for verification
            showMessage('کد تایید به شماره شما ارسال شد.', 'success');
        } catch (error) {
            console.error('Get OTP error:', error);
            showMessage('خطا در ارسال کد تایید. لطفاً دوباره تلاش کنید.', 'error');
        } finally {
            if (getOtpBtn) { // بررسی مجدد
                getOtpBtn.innerHTML = originalBtnText;
                getOtpBtn.disabled = false;
                getOtpBtn.classList.remove('opacity-70', 'cursor-not-allowed');
            }
        }
    });
}

// Handle Verify OTP button click
if (verifyOtpBtn) {
    verifyOtpBtn.addEventListener('click', async (event) => {
        event.preventDefault();
        // اطمینان از وجود mobileNumberInput و otpCodeInput
        const mobileNumber = mobileNumberInput ? mobileNumberInput.value : '';
        const otpCode = otpCodeInput ? otpCodeInput.value : '';
        const originalBtnText = verifyOtpBtn.innerHTML;

        if (!otpCode) {
            showMessage('لطفاً کد تایید را وارد کنید.', 'error');
            return;
        }

        verifyOtpBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال تایید...';
        verifyOtpBtn.disabled = true;
        verifyOtpBtn.classList.add('opacity-70', 'cursor-not-allowed');

        try {
            // Simulate API call for OTP verification (replace with actual backend call)
            // In a real scenario, this would call a Laravel endpoint to verify OTP and log in/register
            // const response = await fetch('/api/verify-otp', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
            //     body: JSON.stringify({ mobile_number: mobileNumber, otp_code: otpCode })
            // });
            // const data = await response.json();

            // if (!response.ok) {
            //     throw new Error(data.message || 'Verification failed');
            // }

            // Assume verification is successful for now
            showMessage('تایید با موفقیت انجام شد. در حال ورود...', 'success');

            // Simulate user login/registration and then redirect or show complete profile
            // For now, let's just proceed to complete profile step
            if (smsVerifyStep) smsVerifyStep.classList.add('hidden');
            if (completeProfileStep) completeProfileStep.classList.remove('hidden');

            // Example: If user is new, show complete profile. If existing, redirect to home.
            // You would get this info from the backend response.
            // let loggedInUserFullData = {
            //     username: mobileNumber, // Or actual username from backend
            //     isProfileComplete: false // Get from backend
            // };
            // localStorage.setItem('loggedInUserFullData', JSON.stringify(loggedInUserFullData));


        } catch (error) {
            console.error('Verify OTP error:', error);
            showMessage('کد تایید نامعتبر است یا منقضی شده. لطفاً دوباره تلاش کنید.', 'error'); // Or specific error from backend
        } finally {
            if (verifyOtpBtn) { // بررسی مجدد
                verifyOtpBtn.innerHTML = originalBtnText;
                verifyOtpBtn.disabled = false;
                verifyOtpBtn.classList.remove('opacity-70', 'cursor-not-allowed');
            }
        }
    });
}


// Handle Resend OTP button click
if (resendOtpBtn) {
    resendOtpBtn.addEventListener('click', async () => {
        // اطمینان از وجود mobileNumberInput
        const mobileNumber = mobileNumberInput ? mobileNumberInput.value : '';
        const originalBtnText = resendOtpBtn.innerHTML;

        if (!mobileNumber) {
            showMessage('شماره موبایل برای ارسال مجدد یافت نشد.', 'error');
            return;
        }

        resendOtpBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال ارسال مجدد...';
        resendOtpBtn.disabled = true;
        resendOtpBtn.classList.add('opacity-70', 'cursor-not-allowed');

        try {
            // Simulate API call for resending OTP (replace with actual backend call)
            // In a real scenario, this would call a Laravel endpoint to resend OTP
            // await new Promise(resolve => setTimeout(resolve, 1500)); // Simulate network delay
            showMessage('کد تایید مجدداً ارسال شد.', 'success');
        } catch (error) {
            console.error('Resend OTP error:', error);
            showMessage('خطا در ارسال مجدد کد. لطفاً دوباره تلاش کنید.', 'error');
        } finally {
            if (resendOtpBtn) { // بررسی مجدد
                resendOtpBtn.innerHTML = originalBtnText;
                resendOtpBtn.disabled = false;
                resendOtpBtn.classList.remove('opacity-70', 'cursor-not-allowed');
            }
        }
    });
}


// --- Complete Profile Functionality ---
if (completeProfileForm) {
    completeProfileForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const originalBtnText = completeProfileSubmitBtn.innerHTML;
        // اطمینان از وجود completeProfileSubmitBtn
        if (completeProfileSubmitBtn) {
            completeProfileSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال ثبت...';
            completeProfileSubmitBtn.disabled = true;
            completeProfileSubmitBtn.classList.add('opacity-70', 'cursor-not-allowed');
        }


        const userData = {
            // اطمینان از وجود عناصر input
            first_name: firstNameInput ? firstNameInput.value : '',
            lastName: lastNameInput ? lastNameInput.value : '', // Fixed typo: lastNameInputInput -> lastNameInput
            email: emailInput ? emailInput.value : '',
            address: {
                street: streetAddressInput ? streetAddressInput.value : '',
                province: provinceSelect ? provinceSelect.value : '',
                city: citySelect ? citySelect.value : '',
                postal_code: postalCodeInput ? postalCodeInput.value : ''
            }
        };

        try {
            // In a real scenario, this would be an API call to update user profile
            // const response = await fetch('/api/complete-profile', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
            //     body: JSON.stringify(userData)
            // });
            // const data = await response.json();

            // if (!response.ok) {
            //     throw new Error(data.message || 'Profile update failed');
            // }

            // Simulate success and update local storage/session (replace with actual backend integration)
            let loggedInUserFullData = JSON.parse(localStorage.getItem('loggedInUserFullData') || '{}');
            if (loggedInUserFullData.username) { // Ensure a user is "logged in"
                loggedInUserFullData.firstName = firstNameInput ? firstNameInput.value : '';
                loggedInUserFullData.lastName = lastNameInput ? lastNameInput.value : '';
                loggedInUserFullData.email = emailInput ? emailInput.value : '';
                loggedInUserFullData.address = {
                    street: streetAddressInput ? streetAddressInput.value : '',
                    province: provinceSelect ? provinceSelect.value : '',
                    city: citySelect ? citySelect.value : '',
                    postalCode: postalCodeInput ? postalCodeInput.value : ''
                };
                loggedInUserFullData.isProfileComplete = true; // Mark as complete
            }

            // Update the user in the main 'registeredUsers' object in localStorage
            // users[loggedInUserFullData.username] = loggedInUserFullData;
            // localStorage.setItem('registeredUsers', JSON.stringify(users));
            // localStorage.setItem('loggedInUserFullData', JSON.stringify(loggedInUserFullData)); // Update full data in session storage

            showMessage('اطلاعات با موفقیت ثبت/بروزرسانی شد.', 'success');

            // Simulate redirection to home page after a short delay
            setTimeout(() => {
                window.location.href = '/'; // Redirect to home page
            }, 2000);

            // No form reset here as it's a profile update, data should remain

        } catch (error) {
            console.error('Profile completion submission error:', error);
            showMessage('خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.', 'error');
        } finally {
            // Reset button state regardless of success or failure
            if (completeProfileSubmitBtn) { // بررسی مجدد
                completeProfileSubmitBtn.innerHTML = originalBtnText;
                completeProfileSubmitBtn.disabled = false;
                completeProfileSubmitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
            }
        }
    });
}
