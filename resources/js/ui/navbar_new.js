// resources/js/ui/navbar_new.js
console.log('navbar_new.js loaded and starting...');

// Import necessary API functions
// تغییر: مسیر import برای api.js به فولدر core اصلاح شده است.
import { getJwtToken, logoutUser, fetchCartContents, removeCartItem, fetchUserData, clearJwtToken } from '../core/api.js';

// === Mini Cart Logic Functions ===
/**
 * محتویات مینی سبد خرید را بر اساس داده‌های سبد خرید دریافت شده رندر می‌کند.
 */
async function renderMiniCart() {
    // CHANGED: Changed 'mini-cart-content' to 'mini-cart-items-container' for consistency with cart.js/events.js
    const miniCartContent = document.getElementById('mini-cart-items-container');
    const miniCartEmptyMessage = document.getElementById('mini-cart-empty-message');
    const miniCartTotalPriceElement = document.getElementById('mini-cart-total-price');
    // Ensure miniCartTotalQuantityElement is correctly referenced
    const miniCartTotalQuantityElement = document.getElementById('mini-cart-total-quantity'); // Added for consistency
    const miniCartCountElement = document.getElementById('mini-cart-count'); // نشان برای تعداد آیتم در دسکتاپ
    const mobileCartCountElement = document.getElementById('mobile-cart-count'); // نشان برای تعداد آیتم در موبایل
    const miniCartSummary = document.getElementById('mini-cart-summary');
    const miniCartActions = document.getElementById('mini-cart-actions');

    // NEW: بررسی وجود عناصر DOM حیاتی برای رندرینگ Mini Cart
    if (!miniCartContent || !miniCartTotalPriceElement || !miniCartEmptyMessage || !miniCartSummary || !miniCartActions || !miniCartTotalQuantityElement) { // Added miniCartTotalQuantityElement to check
        console.error('Navbar_new.js: One or more essential mini cart DOM elements not found. Skipping mini cart rendering.');
        console.error('Missing elements:', {
            miniCartContent: miniCartContent ? 'Found' : 'Not Found',
            miniCartTotalPriceElement: miniCartTotalPriceElement ? 'Found' : 'Not Found',
            miniCartEmptyMessage: miniCartEmptyMessage ? 'Found' : 'Not Found',
            miniCartSummary: miniCartSummary ? 'Found' : 'Not Found',
            miniCartActions: miniCartActions ? 'Found' : 'Not Found',
            miniCartTotalQuantityElement: miniCartTotalQuantityElement ? 'Found' : 'Not Found' // Added check
        });
        // این پیام به شما کمک می‌کند تا در صورت عدم وجود عناصر، مشکل را در HTML پیدا کنید.
        return; // از ادامه رندرینگ در صورت عدم وجود عناصر جلوگیری می‌کند
    }

    try {
        const currentJwtToken = localStorage.getItem('jwt_token');
        console.log('DEBUG: renderMiniCart - JWT Token in localStorage before fetchCartContents:', currentJwtToken ? 'Found' : 'Not Found', currentJwtToken);

        const response = await fetchCartContents();
        const data = response.data;

        const cartItems = data.items || [];
        const totalPrice = data.summary ? data.summary.totalPrice : 0;
        const totalItemsInCart = data.summary ? data.summary.totalQuantity : 0;

        miniCartContent.innerHTML = ''; // پاک کردن محتوای قبلی

        if (cartItems.length === 0) {
            miniCartEmptyMessage.classList.remove('hidden');
            miniCartContent.classList.add('hidden');
            miniCartSummary.classList.add('hidden');
            miniCartActions.classList.add('hidden');
            miniCartTotalQuantityElement.classList.add('hidden'); // Hide quantity if empty
            miniCartTotalQuantityElement.textContent = '0'; // Set to 0 if empty
        } else {
            miniCartEmptyMessage.classList.add('hidden');
            miniCartContent.classList.remove('hidden');
            miniCartSummary.classList.remove('hidden');
            miniCartActions.classList.remove('hidden');
            miniCartTotalQuantityElement.classList.remove('hidden'); // Show quantity if not empty

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
            miniCartTotalQuantityElement.textContent = totalItemsInCart; // Update total quantity
        }

        // NEW: اطمینان از وجود miniCartCountElement و mobileCartCountElement قبل از دسترسی
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
    const desktopUserStatusDisplay = document.getElementById('desktop-user-status-display'); // Element for desktop user status text
    const userStatusGuestDiv = document.getElementById('user-status-guest'); // Desktop guest div
    const userStatusLoggedInDiv = document.getElementById('user-status-logged-in'); // Desktop logged-in div
    const loggedInUserFullNameDesktop = document.getElementById('logged-in-user-full-name'); // Desktop full name display
    const loginRegisterLink = document.getElementById('login-register-link'); // Desktop login/register link

    const mobileUserStatusGuestDiv = document.getElementById('mobile-user-status-guest'); // Mobile guest div
    const mobileUserStatusLoggedInDiv = document.getElementById('mobile-user-status-logged-in'); // Mobile logged-in div
    const mobileLoggedInUserName = document.getElementById('mobile-logged-in-user-name'); // Mobile name display
    const mobileLoggedInUserMobile = document.getElementById('mobile-logged-in-user-mobile'); // Mobile mobile display

    console.log('DEBUG: updateNavbarUserStatus - JWT Token in localStorage before fetchUserData:', jwtToken ? 'Found' : 'Not Found', jwtToken);

    if (jwtToken) {
        try {
            const user = await fetchUserData();
            const fullName = `${user.name || ''} ${user.lastname || ''}`.trim();

            // Desktop Navbar Updates
            if (desktopUserStatusDisplay) { // Update the main display text
                desktopUserStatusDisplay.textContent = `سلام، ${user.name || user.mobile_number}`;
            }
            if (userStatusGuestDiv) userStatusGuestDiv.classList.add('hidden');
            if (userStatusLoggedInDiv) {
                userStatusLoggedInDiv.classList.remove('hidden');
                if (loggedInUserFullNameDesktop) loggedInUserFullNameDesktop.textContent = fullName; // Set full name
            }
            if (loginRegisterLink) loginRegisterLink.classList.add('hidden');

            // Mobile Navbar Updates
            if (mobileUserStatusGuestDiv) mobileUserStatusGuestDiv.classList.add('hidden');
            if (mobileUserStatusLoggedInDiv) {
                mobileUserStatusLoggedInDiv.classList.remove('hidden');
                if (mobileLoggedInUserName) mobileLoggedInUserName.textContent = fullName; // Set full name
                if (mobileLoggedInUserMobile) mobileLoggedInUserMobile.textContent = user.mobile_number;
            }

            // Show all logout buttons if user is logged in
            document.querySelectorAll('.nav-link-dropdown-compact.text-red-500').forEach(button => {
                button.classList.remove('hidden');
            });


            console.log('Navbar user status updated: Logged in as', fullName || user.mobile_number);
            // NEW DEBUG LOGS: Check computed style after updates
            if (userStatusGuestDiv) console.log('DEBUG: userStatusGuestDiv computed style display after update:', window.getComputedStyle(userStatusGuestDiv).display);
            if (userStatusLoggedInDiv) console.log('DEBUG: userStatusLoggedInDiv computed style display after update:', window.getComputedStyle(userStatusLoggedInDiv).display);
            if (mobileUserStatusGuestDiv) console.log('DEBUG: mobileUserStatusGuestDiv computed style display after update:', window.getComputedStyle(mobileUserStatusGuestDiv).display);
            if (mobileUserStatusLoggedInDiv) console.log('DEBUG: mobileUserStatusLoggedInDiv computed style display after update:', window.getComputedStyle(mobileUserStatusLoggedInDiv).display);

        } catch (error) {
            console.error('Error fetching user data with JWT. Treating as guest:', error);
            if (error.response && error.response.status === 401) {
                clearJwtToken();
                console.log('JWT token cleared due to 401 Unauthorized.');
            }
            // Revert to guest state
            if (desktopUserStatusDisplay) desktopUserStatusDisplay.textContent = 'کاربر مهمان'; // Default text for main status
            if (userStatusLoggedInDiv) userStatusLoggedInDiv.classList.add('hidden');
            if (userStatusGuestDiv) userStatusGuestDiv.classList.remove('hidden');
            if (loginRegisterLink) loginRegisterLink.classList.remove('hidden');

            if (mobileUserStatusLoggedInDiv) mobileUserStatusLoggedInDiv.classList.add('hidden');
            if (mobileUserStatusGuestDiv) mobileUserStatusGuestDiv.classList.remove('hidden');

            // Hide all logout buttons if user is guest
            document.querySelectorAll('.nav-link-dropdown-compact.text-red-500').forEach(button => {
                button.classList.add('hidden');
            });

            console.log('Navbar user status updated: Guest user.');
        }
    } else {
        // No JWT token, ensure guest state is displayed
        if (desktopUserStatusDisplay) desktopUserStatusDisplay.textContent = 'کاربر مهمان'; // Default text for main status
        if (userStatusLoggedInDiv) userStatusLoggedInDiv.classList.add('hidden');
        if (userStatusGuestDiv) userStatusGuestDiv.classList.remove('hidden');
        if (loginRegisterLink) loginRegisterLink.classList.remove('hidden');

        if (mobileUserStatusLoggedInDiv) mobileUserStatusLoggedInDiv.classList.add('hidden');
        if (mobileUserStatusGuestDiv) mobileUserStatusGuestDiv.classList.remove('hidden');

        // Hide all logout buttons if no JWT token
        document.querySelectorAll('.nav-link-dropdown-compact.text-red-500').forEach(button => {
            button.classList.add('hidden');
        });

        console.log('Navbar user status updated: Guest user.');
    }
}

// === Main Initialization Function for Navbar and Mini-Cart ===
export function initializeNavbarAndCart() {
    renderMiniCart();
    updateNavbarUserStatus();

    const miniCartContent = document.getElementById('mini-cart-items-container');
    // Select logout buttons using the class found by the debug script
    const logoutButtons = document.querySelectorAll('.nav-link-dropdown-compact.text-red-500'); // Targeted selector

    const addCartButtons = document.querySelectorAll('.add-to-cart-btn');

    if (miniCartContent) {
        miniCartContent.addEventListener('click', async function(event) {
            const removeButton = event.target.closest('.remove-item-btn');
            if (removeButton) {
                const itemId = removeButton.dataset.id;
                // window.showConfirmationModal is assumed to be global from app.js
                window.showConfirmationModal(
                    'حذف محصول',
                    'آیا مطمئن هستید که می‌خواهید این محصول را حذف کنید؟',
                    async () => {
                        try {
                            const response = await removeCartItem(itemId);
                            if (response.success) {
                                // window.showMessage is assumed to be global from app.js
                                window.showMessage(response.message || 'آیتم از سبد خرید حذف شد.', 'success');
                                await renderMiniCart();
                                // window.loadCart is assumed to be global from app.js or cart.js
                                if (window.location.pathname === '/cart' && typeof window.cartManager !== 'undefined' && typeof window.cartManager.loadAndRenderCart === 'function') {
                                    window.cartManager.loadAndRenderCart(); // Call the method on cartManager instance
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

    // Add Event Listener for all identified logout buttons
    if (logoutButtons.length > 0) {
        logoutButtons.forEach(button => {
            button.addEventListener('click', async function(event) {
                event.preventDefault();
                try {
                    console.log('Attempting logout via attached listener in navbar_new.js...'); // Add debug log
                    await logoutUser(); // logoutUser handles token clearing and redirection
                    console.log('Logout initiated. Redirection expected from api.js.'); // Add debug log
                    // No need to call updateNavbarUserStatus here as logoutUser redirects
                } catch (error) {
                    console.error('Error during logout in navbar_new.js:', error);
                    window.showMessage('خطا در خروج از حساب کاربری.', 'error');
                }
            });
            console.log('Attached logout listener to:', button);
        });
    } else {
        console.warn('No logout buttons found with selector ".nav-link-dropdown-compact.text-red-500" during navbar initialization.');
    }


    addCartButtons.forEach(button => {
        button.addEventListener('click', async function() {
            renderMiniCart();
        });
    });

    // Removed the problematic window.renderMainCart override.
    // The main cart logic is now managed by CartManager in cart.js.
    // If you need to trigger a main cart refresh from here,
    // ensure window.cartManager is available and call its loadAndRenderCart method.
    // Example:
    // if (window.cartManager && typeof window.cartManager.loadAndRenderCart === 'function') {
    //     window.cartManager.loadAndRenderCart();
    // }
}
