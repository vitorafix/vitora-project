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
    const [isMiniCartOpen, setIsMiniCartOpen] = useState(false);
    // Access loadCart from Context for global exposure
    const { loadCart } = useCartContext();

    // Expose loadCart globally for debugging or use by non-React code
    useEffect(() => {
        if (typeof window !== 'undefined') {
            (window as any).loadCart = loadCart;
            console.log('window.loadCart exposed for debugging.');
        }
    }, [loadCart]); // Run only when loadCart changes

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

    return (
        <>
            {/* MiniCart section, assuming it's part of the main layout */}
            {/* Added 'inline-block' to ensure the div wraps its content, helping with MiniCart positioning */}
            <div className="mini-cart-dropdown relative inline-block" onMouseLeave={closeMiniCart}>
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
        // In a real scenario, if #react-root is not found, you might need a fallback
        // But for this exercise, we assume the main root element will exist.
    }
};

// Ensure the main React application is initialized only after the DOM is fully loaded
window.addEventListener('DOMContentLoaded', initializeMainReactApp);

// This export is mostly for internal Vite use if this file is the entry point.
export default App;
