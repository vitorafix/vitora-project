// resources/js/app.tsx
import React from 'react';
import { createRoot } from 'react-dom/client';
import MiniCart from './components/cart/MiniCart'; // مسیر صحیح به MiniCart.tsx

// این فایل نقطه ورود اصلی برنامه React شما خواهد بود.
// می‌توانید در آینده کامپوننت‌های React بیشتری را اینجا رندر کنید.

const App: React.FC = () => {
    // وضعیت برای مدیریت باز یا بسته بودن MiniCart
    const [isMiniCartOpen, setIsMiniCartOpen] = React.useState(false);

    // تابعی برای تغییر وضعیت MiniCart
    const toggleMiniCart = () => {
        setIsMiniCartOpen(prev => !prev);
    };

    // تابعی برای بستن MiniCart
    const closeMiniCart = () => {
        setIsMiniCartOpen(false);
    };

    // در اینجا می‌توانید دکمه‌ای برای باز کردن MiniCart قرار دهید
    // در یک پروژه واقعی، این دکمه احتمالاً در کامپوننت ناوبری (navbar) شما خواهد بود.
    // فعلاً برای تست، یک دکمه ساده اینجا اضافه می‌کنیم.
    return (
        <div className="relative inline-block text-left">
            <button
                type="button"
                className="inline-flex justify-center items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                onClick={toggleMiniCart}
                aria-expanded={isMiniCartOpen}
                aria-haspopup="true"
            >
                <i className="fas fa-shopping-cart mr-2"></i>
                سبد خرید (تست)
            </button>

            {/* MiniCart React Component */}
            <MiniCart isOpen={isMiniCartOpen} onClose={closeMiniCart} />
        </div>
    );
};

// پیدا کردن المنت ریشه برای رندر کردن برنامه React
const miniCartRootElement = document.getElementById('mini-cart-root');

if (miniCartRootElement) {
    // استفاده از createRoot برای React 18
    const root = createRoot(miniCartRootElement);
    root.render(
        <React.StrictMode>
            <App />
        </React.StrictMode>
    );
    console.log('React MiniCart application mounted successfully.');
} else {
    console.warn('Could not find #mini-cart-root element to mount React application.');
}
