// resources/js/ui/navbar_new.js
console.log('navbar_new.js loaded and starting...');

// Import necessary API functions
import { getJwtToken, logoutUser, fetchCartContents, removeCartItem, fetchUserData, clearJwtToken } from '../core/api.js';

// === Mini Cart Logic Functions ===
/**
 * محتویات مینی سبد خرید را بر اساس داده‌های سبد خرید دریافت شده رندر می‌کند.
 */
async function renderMiniCart() {
    const miniCartContent = document.getElementById('mini-cart-items-container');
    const miniCartEmptyMessage = document.getElementById('mini-cart-empty-message');
    const miniCartTotalPriceElement = document.getElementById('mini-cart-total-price');
    const miniCartTotalQuantityElement = document.getElementById('mini-cart-total-quantity');
    const miniCartCountElement = document.getElementById('mini-cart-count');
    const mobileCartCountElement = document.getElementById('mobile-cart-count');
    const miniCartSummary = document.getElementById('mini-cart-summary');
    const miniCartActions = document.getElementById('mini-cart-actions');

    if (!miniCartContent || !miniCartTotalPriceElement || !miniCartEmptyMessage || !miniCartSummary || !miniCartActions || !miniCartTotalQuantityElement) {
        console.error('Navbar_new.js: One or more essential mini cart DOM elements not found. Skipping mini cart rendering.');
        console.error('Missing elements:', {
            miniCartContent: miniCartContent ? 'Found' : 'Not Found',
            miniCartTotalPriceElement: miniCartTotalPriceElement ? 'Found' : 'Not Found',
            miniCartEmptyMessage: miniCartEmptyMessage ? 'Found' : 'Not Found',
            miniCartSummary: miniCartSummary ? 'Found' : 'Not Found',
            miniCartActions: miniCartActions ? 'Found' : 'Not Found',
            miniCartTotalQuantityElement: miniCartTotalQuantityElement ? 'Found' : 'Not Found'
        });
        return;
    }

    try {
        const currentJwtToken = localStorage.getItem('jwt_token');
        console.log('DEBUG: renderMiniCart - JWT Token in localStorage before fetchCartContents:', currentJwtToken ? 'Found' : 'Not Found', currentJwtToken);

        const response = await fetchCartContents();
        const data = response.data;

        const cartItems = data.items || [];
        const totalPrice = data.summary ? data.summary.totalPrice : 0;
        const totalItemsInCart = data.summary ? data.summary.totalQuantity : 0;

        miniCartContent.innerHTML = '';

        if (cartItems.length === 0) {
            miniCartEmptyMessage.classList.remove('hidden');
            miniCartContent.classList.add('hidden');
            miniCartSummary.classList.add('hidden');
            miniCartActions.classList.add('hidden');
            miniCartTotalQuantityElement.classList.add('hidden');
            miniCartTotalQuantityElement.textContent = '0';
        } else {
            miniCartEmptyMessage.classList.add('hidden');
            miniCartContent.classList.remove('hidden');
            miniCartSummary.classList.remove('hidden');
            miniCartActions.classList.remove('hidden');
            miniCartTotalQuantityElement.classList.remove('hidden');

            cartItems.forEach(item => {
                const cartItemDiv = document.createElement('div');
                cartItemDiv.classList.add('flex', 'items-center', 'p-3', 'border-b', 'border-gray-100', 'last:border-b-0');
                cartItemDiv.innerHTML = `
                    <img src="${item.product.image_url || 'https://placehold.co/50x50/E5E7EB/4B5563?text=Product'}" alt="${item.product.name}" class="w-12 h-12 object-cover rounded-md ml-3">
                    <div class="flex-1 text-right">
                        <p class="text-sm font-semibold text-gray-800">${item.product.name}</p>
                        <p class="text-xs text-gray-500">${Number(item.price).toLocaleString('fa-IR')} تومان</p>
                    </div>
                    <button class="remove-item-btn text-red-400 hover:text-red-600 transition-colors duration-200" data-id="${item.id}">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                `;
                miniCartContent.appendChild(cartItemDiv);
            });

            miniCartTotalPriceElement.textContent = `${Number(totalPrice).toLocaleString('fa-IR')} تومان`;
            miniCartTotalQuantityElement.textContent = totalItemsInCart;
        }

        if (miniCartCountElement) {
            miniCartCountElement.textContent = totalItemsInCart;
            if (totalItemsInCart > 0) {
                miniCartCountElement.classList.remove('hidden');
            } else {
                miniCartCountElement.classList.add('hidden');
            }
        } else {
            console.warn('Navbar_new.js: miniCartCountElement (desktop) not found. Cannot update item count badge.');
        }

        if (mobileCartCountElement) {
            mobileCartCountElement.textContent = totalItemsInCart;
            if (totalItemsInCart > 0) {
                mobileCartCountElement.classList.remove('hidden');
            } else {
                mobileCartCountElement.classList.add('hidden');
            }
        } else {
            console.warn('Navbar_new.js: mobileCartCountElement (mobile) not found. Cannot update item count badge.');
        }

    } catch (error) {
        console.error('Error rendering mini cart:', error);
    }
}

// === User Status Logic Function ===
export async function updateNavbarUserStatus() {
    const jwtToken = getJwtToken();
    const desktopUserStatusDisplay = document.getElementById('desktop-user-status-display');
    const userStatusGuestDiv = document.getElementById('user-status-guest');
    const userStatusLoggedInDiv = document.getElementById('user-status-logged-in');
    const loggedInUserFullNameDesktop = document.getElementById('logged-in-user-full-name');
    const loginRegisterLink = document.getElementById('login-register-link');

    const mobileUserStatusGuestDiv = document.getElementById('mobile-user-status-guest');
    const mobileUserStatusLoggedInDiv = document.getElementById('mobile-user-status-logged-in');
    const mobileLoggedInUserName = document.getElementById('mobile-logged-in-user-name');
    const mobileLoggedInUserMobile = document.getElementById('mobile-logged-in-user-mobile');

    console.log('DEBUG: updateNavbarUserStatus - Function called.');
    console.log('DEBUG: updateNavbarUserStatus - JWT Token in localStorage:', jwtToken ? 'Found' : 'Not Found', jwtToken);

    if (jwtToken) {
        console.log('DEBUG: updateNavbarUserStatus - JWT Token found. Attempting to fetch user data.');
        try {
            const user = await fetchUserData();
            const fullName = `${user.name || ''} ${user.lastname || ''}`.trim();
            console.log('DEBUG: updateNavbarUserStatus - User data fetched:', user);

            // Desktop Navbar Updates
            if (desktopUserStatusDisplay) {
                desktopUserStatusDisplay.textContent = `سلام، ${user.name || user.mobile_number}`;
                console.log('DEBUG: desktopUserStatusDisplay updated to:', desktopUserStatusDisplay.textContent);
            }
            if (userStatusGuestDiv) {
                userStatusGuestDiv.classList.add('hidden');
                console.log('DEBUG: userStatusGuestDiv hidden. ClassList:', userStatusGuestDiv.classList.toString());
            }
            if (userStatusLoggedInDiv) {
                userStatusLoggedInDiv.classList.remove('hidden');
                console.log('DEBUG: userStatusLoggedInDiv shown. ClassList:', userStatusLoggedInDiv.classList.toString());
                if (loggedInUserFullNameDesktop) {
                    loggedInUserFullNameDesktop.textContent = fullName;
                    console.log('DEBUG: loggedInUserFullNameDesktop updated to:', loggedInUserFullNameDesktop.textContent);
                }
            }
            if (loginRegisterLink) {
                loginRegisterLink.classList.add('hidden');
                console.log('DEBUG: loginRegisterLink hidden. ClassList:', loginRegisterLink.classList.toString());
            }

            // Mobile Navbar Updates
            if (mobileUserStatusGuestDiv) {
                mobileUserStatusGuestDiv.classList.add('hidden');
                console.log('DEBUG: mobileUserStatusGuestDiv hidden. ClassList:', mobileUserStatusGuestDiv.classList.toString());
            }
            if (mobileUserStatusLoggedInDiv) {
                mobileUserStatusLoggedInDiv.classList.remove('hidden');
                console.log('DEBUG: mobileUserStatusLoggedInDiv shown. ClassList:', mobileUserStatusLoggedInDiv.classList.toString());
                if (mobileLoggedInUserName) {
                    mobileLoggedInUserName.textContent = fullName;
                    console.log('DEBUG: mobileLoggedInUserName updated to:', mobileLoggedInUserName.textContent);
                }
                if (mobileLoggedInUserMobile) {
                    mobileLoggedInUserMobile.textContent = user.mobile_number;
                    console.log('DEBUG: mobileLoggedInUserMobile updated to:', mobileLoggedInUserMobile.textContent);
                }
            }

            document.querySelectorAll('.nav-link-dropdown-compact.text-red-500').forEach(button => {
                button.classList.remove('hidden');
                console.log('DEBUG: Logout button shown:', button);
            });

            console.log('Navbar user status updated: Logged in as', fullName || user.mobile_number);

        } catch (error) {
            console.error('Error fetching user data with JWT. Treating as guest:', error);
            if (error.response && error.response.status === 401) {
                clearJwtToken();
                console.log('JWT token cleared due to 401 Unauthorized.');
            }
            // Revert to guest state
            if (desktopUserStatusDisplay) {
                desktopUserStatusDisplay.textContent = 'کاربر مهمان';
                console.log('DEBUG: desktopUserStatusDisplay updated to:', desktopUserStatusDisplay.textContent);
            }
            if (userStatusLoggedInDiv) {
                userStatusLoggedInDiv.classList.add('hidden');
                console.log('DEBUG: userStatusLoggedInDiv hidden. ClassList:', userStatusLoggedInDiv.classList.toString());
            }
            if (userStatusGuestDiv) {
                userStatusGuestDiv.classList.remove('hidden');
                console.log('DEBUG: userStatusGuestDiv shown. ClassList:', userStatusGuestDiv.classList.toString());
            }
            if (loginRegisterLink) {
                loginRegisterLink.classList.remove('hidden');
                console.log('DEBUG: loginRegisterLink shown. ClassList:', loginRegisterLink.classList.toString());
            }

            if (mobileUserStatusLoggedInDiv) {
                mobileUserStatusLoggedInDiv.classList.add('hidden');
                console.log('DEBUG: mobileUserStatusLoggedInDiv hidden. ClassList:', mobileUserStatusLoggedInDiv.classList.toString());
            }
            if (mobileUserStatusGuestDiv) {
                mobileUserStatusGuestDiv.classList.remove('hidden');
                console.log('DEBUG: mobileUserStatusGuestDiv shown. ClassList:', mobileUserStatusGuestDiv.classList.toString());
            }

            document.querySelectorAll('.nav-link-dropdown-compact.text-red-500').forEach(button => {
                button.classList.add('hidden');
                console.log('DEBUG: Logout button hidden:', button);
            });

            console.log('Navbar user status updated: Guest user (due to error or no token).');
        }
    } else {
        // No JWT token, ensure guest state is displayed
        console.log('DEBUG: updateNavbarUserStatus - No JWT Token found. Displaying guest state.');
        if (desktopUserStatusDisplay) {
            desktopUserStatusDisplay.textContent = 'ورود / ثبت‌نام'; // Changed to match your navigation.blade.php for guest state
            console.log('DEBUG: desktopUserStatusDisplay updated to:', desktopUserStatusDisplay.textContent);
        }
        if (userStatusLoggedInDiv) {
            userStatusLoggedInDiv.classList.add('hidden');
            console.log('DEBUG: userStatusLoggedInDiv hidden. ClassList:', userStatusLoggedInDiv.classList.toString());
        }
        if (userStatusGuestDiv) {
            userStatusGuestDiv.classList.remove('hidden');
            console.log('DEBUG: userStatusGuestDiv shown. ClassList:', userStatusGuestDiv.classList.toString());
        }
        if (loginRegisterLink) {
            loginRegisterLink.classList.remove('hidden');
            console.log('DEBUG: loginRegisterLink shown. ClassList:', loginRegisterLink.classList.toString());
        }

        if (mobileUserStatusLoggedInDiv) {
            mobileUserStatusLoggedInDiv.classList.add('hidden');
            console.log('DEBUG: mobileUserStatusLoggedInDiv hidden. ClassList:', mobileUserStatusLoggedInDiv.classList.toString());
        }
        if (mobileUserStatusGuestDiv) {
            mobileUserStatusGuestDiv.classList.remove('hidden');
            console.log('DEBUG: mobileUserStatusGuestDiv shown. ClassList:', mobileUserStatusGuestDiv.classList.toString());
        }

        document.querySelectorAll('.nav-link-dropdown-compact.text-red-500').forEach(button => {
            button.classList.add('hidden');
            console.log('DEBUG: Logout button hidden:', button);
        });

        console.log('Navbar user status updated: Guest user (no token).');
    }
}

// === Main Initialization Function for Navbar and Mini-Cart ===
export function initializeNavbarAndCart() {
    console.log('DEBUG: initializeNavbarAndCart function called.');
    renderMiniCart();
    updateNavbarUserStatus();

    const miniCartContent = document.getElementById('mini-cart-items-container');
    const logoutButtons = document.querySelectorAll('.nav-link-dropdown-compact.text-red-500');
    const addCartButtons = document.querySelectorAll('.add-to-cart-btn');

    if (miniCartContent) {
        miniCartContent.addEventListener('click', async function(event) {
            const removeButton = event.target.closest('.remove-item-btn');
            if (removeButton) {
                const itemId = removeButton.dataset.id;
                window.showConfirmationModal(
                    'حذف محصول',
                    'آیا مطمئن هستید که می‌خواهید این محصول را حذف کنید؟',
                    async () => {
                        try {
                            const response = await removeCartItem(itemId);
                            if (response.success) {
                                window.showMessage(response.message || 'آیتم از سبد خرید حذف شد.', 'success');
                                await renderMiniCart();
                                if (window.location.pathname === '/cart' && typeof window.cartManager !== 'undefined' && typeof window.cartManager.loadAndRenderCart === 'function') {
                                    window.cartManager.loadAndRenderCart();
                                }
                            } else {
                                window.showMessage(response.message || 'خطا در حذف محصول.', 'error');
                            }
                        } catch (error) {
                            console.error('Error removing cart item from mini cart:', error);
                            window.showMessage('خطا در ارتباط با سرور.', 'error');
                        }
                    }
                );
            }
        });
    }

    if (logoutButtons.length > 0) {
        logoutButtons.forEach(button => {
            button.addEventListener('click', async function(event) {
                event.preventDefault();
                console.log('DEBUG: Logout button clicked. Initiating logout process.');
                try {
                    await logoutUser();
                    console.log('DEBUG: Logout initiated. Redirection expected from api.js.');
                } catch (error) {
                    console.error('Error during logout in navbar_new.js:', error);
                    window.showMessage('خطا در خروج از حساب کاربری.', 'error');
                }
            });
            console.log('DEBUG: Attached logout listener to:', button);
        });
    } else {
        console.warn('No logout buttons found with selector ".nav-link-dropdown-compact.text-red-500" during navbar initialization.');
    }

    addCartButtons.forEach(button => {
        button.addEventListener('click', async function() {
            renderMiniCart();
        });
    });
}
