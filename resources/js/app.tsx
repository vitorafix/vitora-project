// resources/js/app.tsx
import React, { useEffect, useState, useRef, useCallback } from 'react';
import { createRoot } from 'react-dom/client';
import ReactDOM from 'react-dom'; // For using createPortal
import MiniCart from './components/cart/MiniCart';
import { CartProvider, useCartContext } from './context/CartContext';

// NEW: Import the analytics module
import './core/analytics.js'; // Import analytics.js from the core folder

// Define the "Add to Cart" component that uses Context
interface AddToCartButtonProps {
    productId: string;
    productName: string;
    price: number;
}

export const AddToCartButton: React.FC<AddToCartButtonProps> = ({ productId, productName, price }) => {
    const { addItem } = useCartContext();
    // اضافه کردن وضعیت loading محلی برای هر دکمه
    const [isLoading, setIsLoading] = useState(false); 

    const handleAddToCart = async () => {
        setIsLoading(true); // شروع بارگذاری برای این دکمه خاص
        try {
            await addItem(productId, 1);
        } catch (error) {
            console.error("Failed to add item via button:", error);
        } finally {
            setIsLoading(false); // پایان بارگذاری برای این دکمه خاص
        }
    };

    return (
        <button
            onClick={handleAddToCart}
            disabled={isLoading} // استفاده از isLoading محلی
            className={`btn-primary ${isLoading ? 'btn-disabled' : ''} flex items-center`}
        >
            {isLoading ? (
                'در حال افزودن...' // Adding...
            ) : (
                <>
                    <i className="fas fa-cart-plus ml-2"></i>
                    افزودن به سبد
                </>
            )}
        </button>
    );
};

// Main React application component
const App: React.FC = () => {
    const [isMiniCartOpen, setIsMiniCartOpen] = useState(false);
    const { loadCart } = useCartContext();

    // Ref for the mini-cart toggle button and the mini-cart dropdown container
    const miniCartToggleRef = useRef<HTMLElement | null>(null);
    const miniCartDropdownRef = useRef<HTMLElement | null>(null); // This will refer to #mini-cart-root

    // Expose loadCart globally for debugging or use by non-React code
    useEffect(() => {
        if (typeof window !== 'undefined') {
            (window as any).loadCart = loadCart;
            console.log('window.loadCart exposed for debugging.');
        }
    }, [loadCart]);

    const toggleMiniCart = useCallback(() => {
        setIsMiniCartOpen(prev => !prev);
    }, []);

    const closeMiniCart = useCallback(() => {
        setIsMiniCartOpen(false);
    }, []);

    // Handle clicks outside the mini-cart area
    const handleClickOutside = useCallback((event: MouseEvent) => {
        // اگر کلیک روی دکمه باز و بسته کردن سبد خرید نبود و روی خود مینی سبد هم نبود، آن را ببند
        if (
            miniCartToggleRef.current && !miniCartToggleRef.current.contains(event.target as Node) &&
            miniCartDropdownRef.current && !miniCartDropdownRef.current.contains(event.target as Node)
        ) {
            closeMiniCart();
        }
    }, [closeMiniCart]);

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
    }, []);

    // Effect to handle MiniCart button and dropdown events
    useEffect(() => {
        const miniCartToggleBtn = document.getElementById('mini-cart-toggle');
        const miniCartRoot = document.getElementById('mini-cart-root');

        // Assign refs
        miniCartToggleRef.current = miniCartToggleBtn;
        miniCartDropdownRef.current = miniCartRoot;

        // متغیر برای نگهداری تایمر mouseleave
        let mouseLeaveTimeout: NodeJS.Timeout | null = null;

        const handleMouseEnterToggle = () => {
            // اگر تایمر فعال بود، آن را پاک کن تا از بسته شدن ناخواسته جلوگیری شود
            if (mouseLeaveTimeout) {
                clearTimeout(mouseLeaveTimeout);
                mouseLeaveTimeout = null;
            }
            setIsMiniCartOpen(true);
        };

        // حتما event را دریافت کن
        const handleMouseLeaveToggleOrDropdown = (event: MouseEvent) => {
            // یک تاخیر کوتاه برای بسته شدن ایجاد می‌کنیم تا فرصت حرکت ماوس را بدهیم
            mouseLeaveTimeout = setTimeout(() => {
                // فقط اگر ماوس واقعا از هر دو عنصر خارج شده باشد، ببند
                if (
                    miniCartToggleRef.current && !miniCartToggleRef.current.contains(document.elementFromPoint(event.clientX, event.clientY)!) &&
                    miniCartDropdownRef.current && !miniCartDropdownRef.current.contains(document.elementFromPoint(event.clientX, event.clientY)!)
                ) {
                    closeMiniCart();
                }
            }, 100); // 100 میلی‌ثانیه تاخیر
        };

        if (miniCartToggleBtn) {
            miniCartToggleBtn.addEventListener('click', toggleMiniCart);
            miniCartToggleBtn.addEventListener('mouseenter', handleMouseEnterToggle);
            miniCartToggleBtn.addEventListener('mouseleave', handleMouseLeaveToggleOrDropdown); // اضافه کردن mouseleave به دکمه
        }

        if (miniCartRoot) {
            miniCartRoot.addEventListener('mouseenter', handleMouseEnterToggle); // وقتی ماوس روی مینی‌سبد می‌رود، آن را باز نگه دار
            miniCartRoot.addEventListener('mouseleave', handleMouseLeaveToggleOrDropdown); // وقتی ماوس از مینی‌سبد خارج می‌شود، با تاخیر ببند
        }

        // Add click outside listener to the document
        document.addEventListener('mousedown', handleClickOutside);

        return () => {
            // Cleanup event listeners when component unmounts
            if (miniCartToggleBtn) {
                miniCartToggleBtn.removeEventListener('click', toggleMiniCart);
                miniCartToggleBtn.removeEventListener('mouseenter', handleMouseEnterToggle);
                miniCartToggleBtn.removeEventListener('mouseleave', handleMouseLeaveToggleOrDropdown);
            }
            if (miniCartRoot) {
                miniCartRoot.removeEventListener('mouseenter', handleMouseEnterToggle);
                miniCartRoot.removeEventListener('mouseleave', handleMouseLeaveToggleOrDropdown);
            }
            document.removeEventListener('mousedown', handleClickOutside);
            if (mouseLeaveTimeout) { // پاک کردن تایمر در هنگام unmount
                clearTimeout(mouseLeaveTimeout);
            }
        };
    }, [toggleMiniCart, closeMiniCart, handleClickOutside]); // Dependencies

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
