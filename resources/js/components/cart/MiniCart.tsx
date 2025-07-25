// resources/js/components/cart/MiniCart.tsx
import React from 'react';
import CartItem from './CartItem';
import CartFooter from './CartFooter';
import { useCartContext } from '../../context/CartContext'; // Using useCartContext

interface MiniCartProps {
    isOpen: boolean;
    onClose: () => void;
}

const MiniCart: React.FC<MiniCartProps> = ({ isOpen, onClose }) => {
    // Now we are using useCartContext
    const { cartItems, cartTotal, removeFromCart, updateQuantity, cartLoading } = useCartContext();

    if (!isOpen) return null;

    return (
        // Positioning adjusted for portal usage: `top-full` places it directly below the parent,
        // `right-0` aligns its right edge with the parent's right edge (suitable for RTL dropdowns).
        // `z-[9999]` ensures it's always on top.
        <div className="absolute top-full right-0 mt-1 w-72 md:w-80 bg-white rounded-lg shadow-xl border border-gray-200 z-[9999] overflow-hidden">
            <div className="p-4 border-b border-gray-200 flex justify-between items-center">
                <h3 className="text-lg font-semibold text-gray-800">سبد خرید</h3> {/* Shopping Cart */}
                <button
                    onClick={onClose}
                    className="text-gray-500 hover:text-gray-700 transition-colors"
                    aria-label="بستن سبد خرید" // Close shopping cart
                >
                    <i className="fas fa-times"></i>
                </button>
            </div>
            {cartLoading ? (
                <p className="text-center text-gray-500 py-8">در حال بارگذاری سبد خرید...</p> {/* Loading shopping cart... */}
            ) : (
                <div className="max-h-80 overflow-y-auto custom-scrollbar">
                    {cartItems.length === 0 ? (
                        <p className="text-center text-gray-500 py-8">سبد خرید شما خالی است.</p> {/* Your shopping cart is empty. */}
                    ) : (
                        cartItems.map(item => (
                            <CartItem
                                key={item.id}
                                item={item}
                                onRemove={removeFromCart}
                                onUpdateQuantity={updateQuantity}
                            />
                        ))
                    )}
                </div>
            )}
            {cartItems.length > 0 && (
                <CartFooter total={cartTotal} loading={cartLoading} />
            )}
        </div>
    );
};

export default MiniCart;
