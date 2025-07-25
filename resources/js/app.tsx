// resources/js/app.tsx
import React from 'react';
import { createRoot } from 'react-dom/client';
import MiniCart from './components/cart/MiniCart';
import { CartProvider, useCartContext } from './context/CartContext';
import { addToCart } from './core/api';

// Define a component for the "Add to Cart" button that uses Context
interface AddToCartButtonProps {
    productId: string;
    productName: string;
    price: number;
    // You can add other props like image here
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

// Main component that includes MiniCart
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
                سبد خرید <span className="mr-1 text-green-700 font-bold">(تست)</span> // Shopping Cart (Test)
            </button>

            {/* MiniCart React Component */}
            <MiniCart isOpen={isMiniCartOpen} onClose={closeMiniCart} />
        </div>
    );
};

// Function to initialize MiniCart and AddToCartButtons
const initializeReactApps = () => {
    // Find the root element for rendering the React application (MiniCart)
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

    // For rendering Add to Cart buttons on product pages
    document.querySelectorAll('[id^="add-to-cart-root-"]').forEach(rootElement => {
        const productId = rootElement.id.replace('add-to-cart-root-', '');
        // Read product information directly from data attributes on the connection div
        const productName = rootElement.dataset.productName; // No default 'نامشخص' to clearly identify missing data
        const price = parseFloat(rootElement.dataset.productPrice || '0'); // Still parse as float, default to 0 if not a number

        // Check if all necessary product data is present and valid
        if (productId && productName && !isNaN(price) && price > 0) { // Added !isNaN(price) and price > 0 check
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
            // More specific warning message
            let warningMessage = `Could not mount AddToCartButton for root: ${rootElement.id}. Missing/invalid data: `;
            if (!productId) warningMessage += 'productId; ';
            if (!productName) warningMessage += 'productName; ';
            if (isNaN(price) || price <= 0) warningMessage += `price (${rootElement.dataset.productPrice}); `;
            console.warn(warningMessage);
        }
    });
};

// Ensure the React applications are initialized only after the DOM is fully loaded
window.addEventListener('DOMContentLoaded', initializeReactApps);

