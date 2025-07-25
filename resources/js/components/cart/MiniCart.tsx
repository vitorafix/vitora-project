// resources/js/components/cart/MiniCart.tsx
import React from 'react';
import CartItem from './CartItem';
import CartFooter from './CartFooter';
import { useCartContext } from '../../context/CartContext';

interface MiniCartProps {
    isOpen: boolean;
    onClose: () => void;
}

const MiniCart: React.FC<MiniCartProps> = ({ isOpen, onClose }) => {
    const { cartItems, cartTotal, removeFromCart, updateQuantity, cartLoading } = useCartContext();

    // If the cart is not open, nothing is displayed
    if (!isOpen) return null;

    return (
        <div
            className="absolute top-full right-1/2 mt-1 w-72 md:w-80 bg-white rounded-lg shadow-xl border border-gray-200 z-[9999] overflow-hidden"
            style={{ transform: 'translate(82.4%, -1.2vh)' }} // Style to move the box up by 2% (from 0.8vh to -1.2vh) and position it slightly more to the left
        >
            <div className="p-4 border-b border-gray-200 flex justify-between items-center">
                <h3 className="text-lg font-semibold text-gray-800">سبد خرید</h3>
                <button
                    onClick={onClose}
                    className="text-gray-500 hover:text-gray-700 transition-colors"
                    aria-label="بستن سبد خرید"
                >
                    <i className="fas fa-times"></i>
                </button>
            </div>
            {cartLoading ? (
                <p className="text-center text-gray-500 py-8">در حال بارگذاری سبد خرید...</p>
            ) : (
                <div className="max-h-60 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100">
                    {cartItems.length === 0 ? (
                        <p className="text-center text-gray-500 py-8">سبد خرید شما خالی است.</p>
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
            {/* Cart footer is only displayed when there is an item in the cart */}
            {cartItems.length > 0 && (
                <CartFooter total={cartTotal} loading={cartLoading} />
            )}
        </div>
    );
};

export default MiniCart;
