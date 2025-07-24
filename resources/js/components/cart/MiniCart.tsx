// resources/js/components/cart/MiniCart.tsx
import React from 'react'; // این خط باید دقیقا اولین خط کد باشد
import CartItem from './CartItem';
import CartFooter from './CartFooter';
import { useCart } from '../../hooks/useCart';

interface MiniCartProps {
    isOpen: boolean;
    onClose: () => void;
}

const MiniCart: React.FC<MiniCartProps> = ({ isOpen, onClose }) => {
    const { cartItems, cartTotal, removeFromCart, updateQuantity, cartLoading } = useCart();

    if (!isOpen) return null;

    return (
        <div className="mini-cart-dropdown-content active absolute left-0 md:left-auto md:right-0 mt-2 w-72 md:w-80 bg-white rounded-lg shadow-xl z-50 overflow-hidden">
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
                <div className="max-h-80 overflow-y-auto custom-scrollbar">
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
            {cartItems.length > 0 && (
                <CartFooter total={cartTotal} loading={cartLoading} />
            )}
        </div>
    );
};

export default MiniCart;
