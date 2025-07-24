// resources/js/ui/navbar_new.js
console.log('navbar_new.js loaded and starting...');

// Import necessary API functions
import { getJwtToken, logoutUser, fetchCartContents, removeCartItem, fetchUserData, clearJwtToken } from '../core/api.js';

// === Mini Cart Logic Functions (REMOVED - NOW HANDLED BY REACT) ===
// تابع renderMiniCart و منطق مربوط به آن به طور کامل حذف شده است،
// زیرا رندر MiniCart و مدیریت آیتم‌ها اکنون توسط کامپوننت‌های React انجام می‌شود.

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
    // renderMiniCart(); // این خط را غیرفعال کردیم، چون MiniCart اکنون توسط React مدیریت می‌شود
    updateNavbarUserStatus();

    // const miniCartContent = document.getElementById('mini-cart-items-container'); // این المنت دیگر توسط React مدیریت می‌شود
    const logoutButtons = document.querySelectorAll('.nav-link-dropdown-compact.text-red-500');
    // const addCartButtons = document.querySelectorAll('.add-to-cart-btn'); // دکمه‌های افزودن به سبد خرید اکنون توسط React مدیریت می‌شوند

    // بلوک کد مربوط به حذف آیتم از MiniCart قدیمی حذف شده است.
    // بلوک کد مربوط به دکمه‌های افزودن به سبد خرید قدیمی حذف شده است.

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
}
