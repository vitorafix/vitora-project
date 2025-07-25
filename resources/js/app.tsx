// resources/js/app.tsx
import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import ReactDOM from 'react-dom'; // For using createPortal
import MiniCart from './components/cart/MiniCart';
import { CartProvider, useCartContext } from './context/CartContext';

// Define the "Add to Cart" component that uses Context
interface AddToCartButtonProps {
    productId: string;
    productName: string;
    price: number;
}

export const AddToCartButton: React.FC<AddToCartButtonProps> = ({ productId, productName, price }) => {
    // Now we are using useCartContext
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

// Main React application component
const App: React.FC = () => {
    // بازگرداندن به حالت اولیه: isMiniCartOpen را به false تنظیم کنید
    const [isMiniCartOpen, setIsMiniCartOpen] = useState(false); // <--- این خط را به false تغییر دادیم
    // Access loadCart from Context for global exposure
    const { loadCart } = useCartContext();

    // Expose loadCart globally for debugging or use by non-React code
    useEffect(() => {
        if (typeof window !== 'undefined') {
            (window as any).loadCart = loadCart;
            console.log('window.loadCart exposed for debugging.');
        }
    }, [loadCart]); // Run only when loadCart changes

    // توابع toggleMiniCart و closeMiniCart اکنون به درستی کار خواهند کرد
    const toggleMiniCart = () => {
        setIsMiniCartOpen(prev => !prev);
    };

    const closeMiniCart = () => {
        setIsMiniCartOpen(false);
    };

    // State to hold AddToCartButton portals
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
                    // Use ReactDOM.createPortal to render the component in another DOM Node
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
    }, []); // This useEffect runs only once on mount

    // Effect to handle MiniCart button events
    useEffect(() => {
        const miniCartToggleBtn = document.getElementById('mini-cart-toggle');
        const miniCartRoot = document.getElementById('mini-cart-root');

        if (miniCartToggleBtn) {
            miniCartToggleBtn.addEventListener('click', toggleMiniCart);
            miniCartToggleBtn.addEventListener('mouseenter', () => setIsMiniCartOpen(true));
        }

        if (miniCartRoot) {
            miniCartRoot.addEventListener('mouseleave', closeMiniCart);
        }

        return () => {
            // Cleanup event listeners when component unmounts
            if (miniCartToggleBtn) {
                miniCartToggleBtn.removeEventListener('click', toggleMiniCart);
                miniCartToggleBtn.removeEventListener('mouseenter', () => setIsMiniCartOpen(true));
            }
            if (miniCartRoot) {
                miniCartRoot.removeEventListener('mouseleave', closeMiniCart);
            }
        };
    }, [toggleMiniCart, closeMiniCart]); // Dependencies ensure effect re-runs if these functions change

    return (
        <>
            {/* MiniCart will now be rendered via a Portal into #mini-cart-root in the navbar. */}
            {typeof document !== 'undefined' && document.getElementById('mini-cart-root') &&
                ReactDOM.createPortal(
                    <MiniCart isOpen={isMiniCartOpen} onClose={closeMiniCart} />,
                    document.getElementById('mini-cart-root') as HTMLElement
                )
            }

            {/* Render all AddToCartButton portals */}
            {addToCartPortals}
        </>
    );
};

// Function to initialize the main React application
const initializeMainReactApp = () => {
    // A single root element for the entire React application
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
    }
};

// Ensure the main React application is initialized only after the DOM is fully loaded
window.addEventListener('DOMContentLoaded', initializeMainReactApp);

export default App;
