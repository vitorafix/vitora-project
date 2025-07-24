// resources/js/app.tsx
import React from 'react';
import { createRoot } from 'react-dom/client';
import MiniCart from './components/cart/MiniCart';
import { CartProvider, useCartContext } from './context/CartContext';
import { addToCart } from './core/api';

// تعریف یک کامپوننت برای دکمه "افزودن به سبد خرید" که از Context استفاده می‌کند
interface AddToCartButtonProps {
    productId: string;
    productName: string;
    price: number;
    // می‌توانید پراپ‌های دیگری مانند image را نیز اضافه کنید
}

export const AddToCartButton: React.FC<AddToCartButtonProps> = ({ productId, productName, price }) => {
    const { addItem, cartLoading } = useCartContext();

    const handleAddToCart = async () => {
        try {
            await addItem(productId, 1);
        } catch (error) {
            console.error("Failed to add item via button:", error);
        }
    };

    return (
        <button
            onClick={handleAddToCart}
            disabled={cartLoading}
            className={`btn-primary ${cartLoading ? 'btn-disabled' : ''} flex items-center`}
        >
            {cartLoading ? (
                'در حال افزودن...'
            ) : (
                <>
                    <i className="fas fa-cart-plus ml-2"></i>
                    افزودن به سبد
                </>
            )}
        </button>
    );
};

// کامپوننت اصلی که MiniCart را شامل می‌شود
const MiniCartApp: React.FC = () => {
    const [isMiniCartOpen, setIsMiniCartOpen] = React.useState(false);

    const toggleMiniCart = () => {
        setIsMiniCartOpen(prev => !prev);
    };

    const closeMiniCart = () => {
        setIsMiniCartOpen(false);
    };

    return (
        <div className="mini-cart-dropdown relative" onMouseLeave={closeMiniCart}>
            <button
                type="button"
                className="inline-flex justify-center items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                onClick={toggleMiniCart}
                onMouseEnter={() => setIsMiniCartOpen(true)}
                aria-expanded={isMiniCartOpen}
                aria-haspopup="true"
            >
                <i className="fas fa-shopping-cart ml-2"></i>
                سبد خرید <span className="mr-1 text-green-700 font-bold">(تست)</span>
            </button>

            {/* MiniCart React Component */}
            <MiniCart isOpen={isMiniCartOpen} onClose={closeMiniCart} />
        </div>
    );
};

// پیدا کردن المنت ریشه برای رندر کردن برنامه React
const miniCartRootElement = document.getElementById('mini-cart-root');

if (miniCartRootElement) {
    const root = createRoot(miniCartRootElement);
    root.render(
        <React.StrictMode>
            <CartProvider>
                <MiniCartApp />
            </CartProvider>
        </React.StrictMode>
    );
    console.log('React MiniCart application mounted successfully.');
} else {
    console.warn('Could not find #mini-cart-root element to mount React application.');
}

// برای رندر کردن دکمه‌های افزودن به سبد خرید در صفحات محصول
document.querySelectorAll('[id^="add-to-cart-root-"]').forEach(rootElement => {
    const productId = rootElement.id.replace('add-to-cart-root-', '');
    // اطلاعات محصول را مستقیماً از data attributes روی div نقطه اتصال می‌خوانیم
    const productName = rootElement.dataset.productName || 'نامشخص';
    const price = parseFloat(rootElement.dataset.productPrice || '0');

    if (productId && productName && price) {
        const root = createRoot(rootElement);
        root.render(
            <React.StrictMode>
                <CartProvider>
                    <AddToCartButton
                        productId={productId}
                        productName={productName}
                        price={price}
                    />
                </CartProvider>
            </React.StrictMode>
        );
        console.log(`AddToCartButton mounted for product ${productId}`);
    } else {
        console.warn(`Could not find product data for mounting AddToCartButton for root: ${rootElement.id}`);
    }
});
