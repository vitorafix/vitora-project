// resources/js/app.tsx
import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import ReactDOM from 'react-dom'; // برای استفاده از createPortal
import MiniCart from './components/cart/MiniCart';
import { CartProvider, useCartContext } from './context/CartContext';

// تعریف کامپوننت "افزودن به سبد" که از Context استفاده می‌کند
interface AddToCartButtonProps {
    productId: string;
    productName: string;
    price: number;
}

export const AddToCartButton: React.FC<AddToCartButtonProps> = ({ productId, productName, price }) => {
    // اکنون از useCartContext استفاده می‌کنیم
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
                'در حال افزودن...' // Adding...
            ) : (
                <>
                    <i className="fas fa-cart-plus ml-2"></i>
                    افزودن به سبد // Add to Cart
                </>
            )}
        </button>
    );
};

// کامپوننت اصلی اپلیکیشن React
const App: React.FC = () => {
    const [isMiniCartOpen, setIsMiniCartOpen] = useState(false);
    // دسترسی به loadCart از Context برای اکسپوز کردن سراسری
    const { loadCart } = useCartContext();

    // اکسپوز کردن loadCart به صورت سراسری برای دیباگ یا استفاده توسط کدهای غیر React
    useEffect(() => {
        if (typeof window !== 'undefined') {
            (window as any).loadCart = loadCart;
            console.log('window.loadCart exposed for debugging.');
        }
    }, [loadCart]); // فقط زمانی که loadCart تغییر کند، اجرا شود

    const toggleMiniCart = () => {
        setIsMiniCartOpen(prev => !prev);
    };

    const closeMiniCart = () => {
        setIsMiniCartOpen(false);
    };

    // وضعیت برای نگهداری پورتال‌های AddToCartButton
    const [addToCartPortals, setAddToCartPortals] = useState<JSX.Element[]>([]);

    useEffect(() => {
        const productRoots = document.querySelectorAll('[id^="add-to-cart-root-"]');
        const portals: JSX.Element[] = [];

        productRoots.forEach(rootElement => {
            const productId = rootElement.id.replace('add-to-cart-root-', '');
            const productName = rootElement.dataset.productName;
            const price = parseFloat(rootElement.dataset.productPrice || '0');

            if (productId && productName && !isNaN(price) && price > 0) {
                portals.push(
                    // استفاده از ReactDOM.createPortal برای رندر کردن کامپوننت در یک DOM Node دیگر
                    ReactDOM.createPortal(
                        <React.StrictMode>
                            <AddToCartButton
                                productId={productId}
                                productName={productName}
                                price={price}
                            />
                        </React.StrictMode>,
                        rootElement
                    )
                );
                console.log(`AddToCartButton portal created for product ${productId}`);
            } else {
                let warningMessage = `Could not create AddToCartButton portal for root: ${rootElement.id}. Missing/invalid data: `;
                if (!productId) warningMessage += 'productId; ';
                if (!productName) warningMessage += 'productName; ';
                if (isNaN(price) || price <= 0) warningMessage += `price (${rootElement.dataset.productPrice}); `;
                console.warn(warningMessage);
            }
        });
        setAddToCartPortals(portals);
    }, []); // این useEffect فقط یک بار در mount اجرا می‌شود

    return (
        <>
            {/* بخش MiniCart، فرض بر این است که بخشی از layout اصلی است */}
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
                    سبد خرید <span className="mr-1 text-green-700 font-bold">(تست)</span> // Shopping Cart (Test)
                </button>
                <MiniCart isOpen={isMiniCartOpen} onClose={closeMiniCart} />
            </div>

            {/* رندر کردن تمام پورتال‌های AddToCartButton */}
            {addToCartPortals}
        </>
    );
};

// تابع برای مقداردهی اولیه اپلیکیشن اصلی React
const initializeMainReactApp = () => {
    // یک المنت ریشه واحد برای کل اپلیکیشن React
    const reactRootElement = document.getElementById('react-root');

    if (reactRootElement) {
        const root = createRoot(reactRootElement);
        root.render(
            <React.StrictMode>
                <CartProvider>
                    <App />
                </CartProvider>
            </React.StrictMode>
        );
        console.log('Main React application mounted successfully with CartProvider.');
    } else {
        console.warn('Could not find #react-root element to mount the main React application. Ensure it exists in app.blade.php.');
        // در سناریوی واقعی، اگر #react-root پیدا نشد، ممکن است نیاز به یک fallback داشته باشید
        // اما برای این تمرین، فرض می‌کنیم که المنت ریشه اصلی وجود خواهد داشت.
    }
};

// اطمینان حاصل کنید که اپلیکیشن اصلی React فقط پس از بارگذاری کامل DOM مقداردهی اولیه می‌شود
window.addEventListener('DOMContentLoaded', initializeMainReactApp);

// این export بیشتر برای استفاده داخلی Vite است اگر این فایل نقطه ورود باشد.
export default App;
