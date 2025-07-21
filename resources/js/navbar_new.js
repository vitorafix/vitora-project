// resources/js/navbar_new.js

// Import necessary API functions
import { getJwtToken, logoutUser, fetchCartContents, removeCartItem } from './api.js'; // Added fetchCartContents, removeCartItem

// === Mini Cart Logic Functions ===
// این توابع در بالاترین سطح تعریف شده‌اند تا بتوانند اکسپورت شوند یا از توابع اکسپورت شده فراخوانی شوند.

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
        const response = await window.axios.get('/api/cart/contents');
        const data = response.data.data;

        const cartItems = data.items || [];
        const totalPrice = data.totalPrice || 0;
        const totalItemsInCart = data.totalQuantity || 0;

        if (miniCartContent) {
            miniCartContent.innerHTML = ''; // پاک کردن محتوای فعلی
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
                        <p class="text-xs text-gray-500">${item.quantity} x ${Number(item.price).toLocaleString('fa-IR')} تومان</p>
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

        // به‌روزرسانی نشان تعداد آیتم در مینی سبد خرید (دسکتاپ)
        if (miniCartCountElement) {
            miniCartCountElement.textContent = totalItemsInCart;
            if (totalItemsInCart > 0) {
                miniCartCountElement.classList.remove('hidden');
            } else {
                miniCartCountElement.classList.add('hidden');
            }
        }
        // به‌روزرسانی نشان تعداد آیتم در موبایل
        if (mobileCartCountElement) {
            mobileCartCountElement.textContent = totalItemsInCart;
            if (totalItemsInCart > 0) {
                mobileCartCountElement.style.display = 'block';
            } else {
                mobileCartCountElement.style.display = 'none';
            }
        }

    } catch (error) {
        console.error('Error rendering mini cart:', error);
        // window.showMessage('خطا در بارگذاری سبد خرید کوچک.', 'error'); // از نمایش پیام در هر خطای مینی سبد خرید خودداری کنید
    }
}

// === User Status Logic Function ===
/**
 * داده‌های کاربر را از API دریافت کرده و وضعیت UI (وضعیت کاربر در نوار ناوبری) را به‌روزرسانی می‌کند.
 */
async function updateNavbarUserStatus() {
    const jwtToken = getJwtToken();
    const userStatusGuest = document.getElementById('user-status-guest');
    const userStatusLoggedIn = document.getElementById('user-status-logged-in');
    const loginRegisterLink = document.getElementById('login-register-link');
    const logoutLink = document.getElementById('logout-link');
    const mobileUserStatusGuest = document.getElementById('mobile-user-status-guest');
    const mobileUserStatusLoggedIn = document.getElementById('mobile-user-status-logged-in');

    if (jwtToken) {
        try {
            const response = await window.axios.get('/api/user');
            const user = response.data;

            if (userStatusLoggedIn) { // بررسی کنید که userStatusLoggedIn تعریف شده باشد
                userStatusLoggedIn.textContent = `سلام، ${user.name || user.mobile_number}`;
                userStatusLoggedIn.classList.remove('hidden');
            }
            if (userStatusGuest) userStatusGuest.classList.add('hidden');
            if (loginRegisterLink) loginRegisterLink.classList.add('hidden');
            if (logoutLink) logoutLink.classList.remove('hidden');
            if (mobileUserStatusGuest) mobileUserStatusGuest.classList.add('hidden');
            if (mobileUserStatusLoggedIn) mobileUserStatusLoggedIn.classList.remove('hidden');

            console.log('Navbar user status updated: Logged in as', user.name || user.mobile_number);
        } catch (error) {
            console.error('Error fetching user data with JWT. Treating as guest:', error);
            clearJwtToken(); // پاک کردن توکن نامعتبر
            if (userStatusLoggedIn) userStatusLoggedIn.classList.add('hidden');
            if (userStatusGuest) userStatusGuest.classList.remove('hidden');
            if (loginRegisterLink) loginRegisterLink.classList.remove('hidden');
            if (logoutLink) logoutLink.classList.add('hidden');
            if (mobileUserStatusGuest) mobileUserStatusGuest.classList.remove('hidden');
            if (mobileUserStatusLoggedIn) mobileUserStatusLoggedIn.classList.add('hidden');
        }
    } else {
        if (userStatusLoggedIn) userStatusLoggedIn.classList.add('hidden');
        if (userStatusGuest) userStatusGuest.classList.remove('hidden');
        if (loginRegisterLink) loginRegisterLink.classList.remove('hidden');
        if (logoutLink) logoutLink.classList.add('hidden');
        if (mobileUserStatusGuest) mobileUserStatusGuest.classList.remove('hidden');
        if (mobileUserStatusLoggedIn) mobileUserStatusLoggedIn.classList.add('hidden');
        console.log('Navbar user status updated: Guest user.');
    }
}

// === Main Initialization Function for Navbar and Mini-Cart ===
// این تابع اکسپورت خواهد شد و از app.js فراخوانی می‌شود.
export function initializeNavbarAndCart() {
    // فراخوانی اولیه رندر مینی سبد خرید و وضعیت کاربر در هنگام بارگذاری صفحه
    renderMiniCart();
    updateNavbarUserStatus();

    // Event listeners که به عناصر DOM وابسته هستند، پس از آماده شدن DOM اضافه می‌شوند.
    document.addEventListener('DOMContentLoaded', function() {
        const miniCartContent = document.getElementById('mini-cart-content');
        const logoutLink = document.getElementById('logout-link');
        const addCartButtons = document.querySelectorAll('.add-to-cart-btn'); // دکمه‌های افزودن به سبد خرید

        // اضافه کردن Event Listener برای حذف آیتم‌ها از مینی سبد خرید
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
                                const response = await window.axios.post(`/api/cart/remove-item/${itemId}`);
                                if (response.status === 200) {
                                    window.showMessage(response.data.message || 'آیتم از سبد خرید حذف شد.', 'success');
                                    await renderMiniCart(); // رندر مجدد مینی سبد خرید
                                    // اگر صفحه اصلی سبد خرید باز است، آن را نیز رفرش کنید
                                    if (window.location.pathname === '/cart' && typeof window.loadCart === 'function') {
                                        window.loadCart(); // فراخوانی تابع بارگذاری سبد خرید اصلی
                                    }
                                } else {
                                    window.showMessage(response.data.message || 'خطا در حذف محصول.', 'error');
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

        // اضافه کردن Event Listener برای لینک خروج از حساب
        if (logoutLink) {
            logoutLink.addEventListener('click', async function(event) {
                event.preventDefault();
                try {
                    await logoutUser(); // فراخوانی تابع خروج از حساب از api.js
                    window.showMessage('با موفقیت از حساب کاربری خود خارج شدید.', 'success');
                    // ریدایرکت به صفحه اصلی یا صفحه ورود پس از خروج
                    window.location.href = '/'; // ریدایرکت به صفحه اصلی
                } catch (error) {
                    console.error('Error during logout:', error);
                    window.showMessage('خطا در خروج از حساب کاربری.', 'error');
                }
            });
        }

        // === یکپارچه‌سازی دکمه افزودن به سبد خرید ===
        // این بخش تضمین می‌کند که وقتی از صفحه اصلی/محصولات به سبد خرید اضافه می‌کنید،
        // مینی سبد خرید در نوار ناوبری نیز به‌روزرسانی شود.
        addCartButtons.forEach(button => {
            button.addEventListener('click', async function() {
                // فرض بر این است که addProductToCart در جای دیگری فراخوانی می‌شود و در صورت موفقیت، renderMiniCart فراخوانی می‌شود.
                // اگر این دکمه مستقیماً به سبد خرید اضافه می‌کند، می‌توانید addProductToCart را اینجا فراخوانی کنید.
                // در حال حاضر، فقط پس از هر عملیات افزودن به سبد خرید، مینی سبد خرید را رندر مجدد می‌کنیم.
                renderMiniCart();
            });
        });

        // === اطمینان از به‌روزرسانی مینی سبد خرید توسط سبد خرید اصلی ===
        // این یک راه حل موقت برای اطمینان از به‌روزرسانی مینی سبد خرید در نوار ناوبری است،
        // زمانی که سبد خرید اصلی (cart.blade.php) تغییر می‌کند.
        // فرض بر این است که renderMainCart سراسری است یا قابل دسترسی است.
        // یک راه حل قوی‌تر شامل یک رویداد باس مشترک یا مدیریت وضعیت است.
        if (window.renderMainCart) {
            const originalRenderMainCart = window.renderMainCart;
            window.renderMainCart = async function() {
                await originalRenderMainCart();
                renderMiniCart(); // همچنین مینی سبد خرید را پس از رندر مجدد سبد خرید اصلی به‌روزرسانی کنید
            };
        }
    });
}