// resources/js/navbar_new.js
console.log('navbar_new.js loaded and starting...');

// Import necessary API functions
import { getJwtToken, logoutUser, fetchCartContents, removeCartItem, fetchUserData, clearJwtToken } from './api.js'; 

// === Mini Cart Logic Functions ===
/**
 * محتویات مینی سبد خرید را بر اساس داده‌های سبد خرید دریافت شده رندر می‌کند.
 */
async function renderMiniCart() {
    const miniCartContent = document.getElementById('mini-cart-content');
    const miniCartEmptyMessage = document.getElementById('mini-cart-empty-message');
    const miniCartTotalPriceElement = document.getElementById('mini-cart-total-price');
    const miniCartCountElement = document.getElementById('mini-cart-count'); // نشان برای تعداد آیتم در دسکتاپ
    const mobileCartCountElement = document.getElementById('mobile-cart-count'); // نشان برای تعداد آیتم در موبایل
    const miniCartSummary = document.getElementById('mini-cart-summary');
    const miniCartActions = document.getElementById('mini-cart-actions');

    try {
        const currentJwtToken = localStorage.getItem('jwt_token');
        console.log('DEBUG: renderMiniCart - JWT Token in localStorage before fetchCartContents:', currentJwtToken ? 'Found' : 'Not Found', currentJwtToken);

        const response = await fetchCartContents(); 
        const data = response.data; 

        const cartItems = data.items || [];
        const totalPrice = data.summary ? data.summary.totalPrice : 0; 
        const totalItemsInCart = data.summary ? data.summary.totalQuantity : 0; 

        if (miniCartContent) {
            miniCartContent.innerHTML = ''; 
        }

        if (cartItems.length === 0) {
            if (miniCartEmptyMessage) miniCartEmptyMessage.classList.remove('hidden');
            if (miniCartContent) miniCartContent.classList.add('hidden');
            if (miniCartSummary) miniCartSummary.classList.add('hidden');
            if (miniCartActions) miniCartActions.classList.add('hidden');
        } else {
            if (miniCartEmptyMessage) miniCartEmptyMessage.classList.add('hidden');
            if (miniCartContent) miniCartContent.classList.remove('hidden');
            if (miniCartSummary) miniCartSummary.classList.remove('hidden');
            if (miniCartActions) miniCartActions.classList.remove('hidden');

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
                if (miniCartContent) {
                    miniCartContent.appendChild(cartItemDiv);
                }
            });

            if (miniCartTotalPriceElement) {
                miniCartTotalPriceElement.textContent = `${Number(totalPrice).toLocaleString('fa-IR')} تومان`;
            }
        }

        if (miniCartCountElement) {
            miniCartCountElement.textContent = totalItemsInCart;
            if (totalItemsInCart > 0) {
                miniCartCountElement.classList.remove('hidden');
            } else {
                miniCartCountElement.classList.add('hidden');
            }
        }
        if (mobileCartCountElement) {
            mobileCartCountElement.textContent = totalItemsInCart;
            if (totalItemsInCart > 0) {
                mobileCartCountElement.classList.remove('hidden'); // Use remove/add hidden for consistency
            } else {
                mobileCartCountElement.classList.add('hidden');
            }
        }

    } catch (error) {
        console.error('Error rendering mini cart:', error);
    }
}

// === User Status Logic Function ===
export async function updateNavbarUserStatus() {
    const jwtToken = getJwtToken();
    const userStatusText = document.getElementById('user-status-text'); // New element for "سلام، [نام]"
    const userStatusGuestDiv = document.getElementById('user-status-guest'); // Desktop guest div
    const userStatusLoggedInDiv = document.getElementById('user-status-logged-in'); // Desktop logged-in div
    const loggedInUserNameDesktop = document.getElementById('logged-in-user-name'); // Desktop name display
    const loginRegisterLink = document.getElementById('login-register-link'); // Desktop login/register link
    const logoutLinkDesktop = document.getElementById('logout-link'); // Desktop logout button

    const mobileUserStatusGuestDiv = document.getElementById('mobile-user-status-guest'); // Mobile guest div
    const mobileUserStatusLoggedInDiv = document.getElementById('mobile-user-status-logged-in'); // Mobile logged-in div
    const mobileLoggedInUserName = document.getElementById('mobile-logged-in-user-name'); // Mobile name display
    const mobileLoggedInUserMobile = document.getElementById('mobile-logged-in-user-mobile'); // Mobile mobile display
    const logoutLinkMobile = document.getElementById('logout-link-mobile'); // Mobile logout button

    console.log('DEBUG: updateNavbarUserStatus - JWT Token in localStorage before fetchUserData:', jwtToken ? 'Found' : 'Not Found', jwtToken);

    if (jwtToken) {
        try {
            const user = await fetchUserData(); 
            
            // Desktop Navbar Updates
            if (userStatusText) {
                userStatusText.textContent = `سلام، ${user.name || user.mobile_number}`;
            }
            if (userStatusGuestDiv) userStatusGuestDiv.classList.add('hidden');
            if (userStatusLoggedInDiv) {
                userStatusLoggedInDiv.classList.remove('hidden');
                if (loggedInUserNameDesktop) loggedInUserNameDesktop.textContent = user.name || user.mobile_number;
            }
            if (loginRegisterLink) loginRegisterLink.classList.add('hidden');
            if (logoutLinkDesktop) logoutLinkDesktop.classList.remove('hidden');

            // Mobile Navbar Updates
            if (mobileUserStatusGuestDiv) mobileUserStatusGuestDiv.classList.add('hidden');
            if (mobileUserStatusLoggedInDiv) {
                mobileUserStatusLoggedInDiv.classList.remove('hidden');
                if (mobileLoggedInUserName) mobileLoggedInUserName.textContent = user.name || user.mobile_number;
                if (mobileLoggedInUserMobile) mobileLoggedInUserMobile.textContent = user.mobile_number;
            }
            if (logoutLinkMobile) logoutLinkMobile.classList.remove('hidden');

            console.log('Navbar user status updated: Logged in as', user.name || user.mobile_number);
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
            if (userStatusText) userStatusText.textContent = 'کاربر مهمان'; // Default text for main status
            if (userStatusLoggedInDiv) userStatusLoggedInDiv.classList.add('hidden');
            if (userStatusGuestDiv) userStatusGuestDiv.classList.remove('hidden');
            if (loginRegisterLink) loginRegisterLink.classList.remove('hidden');
            if (logoutLinkDesktop) logoutLinkDesktop.classList.add('hidden');

            if (mobileUserStatusLoggedInDiv) mobileUserStatusLoggedInDiv.classList.add('hidden');
            if (mobileUserStatusGuestDiv) mobileUserStatusGuestDiv.classList.remove('hidden');
            if (logoutLinkMobile) logoutLinkMobile.classList.add('hidden');

            console.log('Navbar user status updated: Guest user.');
        }
    } else {
        // No JWT token, ensure guest state is displayed
        if (userStatusText) userStatusText.textContent = 'کاربر مهمان'; // Default text for main status
        if (userStatusLoggedInDiv) userStatusLoggedInDiv.classList.add('hidden');
        if (userStatusGuestDiv) userStatusGuestDiv.classList.remove('hidden');
        if (loginRegisterLink) loginRegisterLink.classList.remove('hidden');
        if (logoutLinkDesktop) logoutLinkDesktop.classList.add('hidden');

        if (mobileUserStatusLoggedInDiv) mobileUserStatusLoggedInDiv.classList.add('hidden');
        if (mobileUserStatusGuestDiv) mobileUserStatusGuestDiv.classList.remove('hidden');
        if (logoutLinkMobile) logoutLinkMobile.classList.add('hidden');

        console.log('Navbar user status updated: Guest user.');
    }
}

// === Main Initialization Function for Navbar and Mini-Cart ===
export function initializeNavbarAndCart() {
    renderMiniCart(); 
    updateNavbarUserStatus(); 

    document.addEventListener('DOMContentLoaded', function() {
        const miniCartContent = document.getElementById('mini-cart-content');
        const logoutLinkDesktop = document.getElementById('logout-link'); // Desktop logout
        const logoutLinkMobile = document.getElementById('logout-link-mobile'); // Mobile logout
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
                                    if (window.location.pathname === '/cart' && typeof window.loadCart === 'function') {
                                        window.loadCart(); 
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

        // Add Event Listener for Desktop Logout Link
        if (logoutLinkDesktop) {
            logoutLinkDesktop.addEventListener('click', async function(event) {
                event.preventDefault();
                try {
                    await logoutUser(); 
                    window.showMessage('با موفقیت از حساب کاربری خود خارج شدید.', 'success');
                    window.location.href = '/'; 
                } catch (error) {
                    console.error('Error during desktop logout:', error);
                    window.showMessage('خطا در خروج از حساب کاربری.', 'error');
                }
            });
        }

        // Add Event Listener for Mobile Logout Link
        if (logoutLinkMobile) {
            logoutLinkMobile.addEventListener('click', async function(event) {
                event.preventDefault();
                try {
                    await logoutUser(); 
                    window.showMessage('با موفقیت از حساب کاربری خود خارج شدید.', 'success');
                    window.location.href = '/'; 
                } catch (error) {
                    console.error('Error during mobile logout:', error);
                    window.showMessage('خطا در خروج از حساب کاربری.', 'error');
                }
            });
        }

        addCartButtons.forEach(button => {
            button.addEventListener('click', async function() {
                renderMiniCart();
            });
        });

        if (window.renderMainCart) {
            const originalRenderMainCart = window.renderMainCart;
            window.renderMainCart = async function() {
                await originalRenderMainCart();
                renderMiniCart(); 
            };
        }
    });
}
