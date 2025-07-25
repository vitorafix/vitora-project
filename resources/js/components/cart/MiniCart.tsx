// resources/js/components/cart/MiniCart.tsx
import React from 'react';
import CartItem from './CartItem'; // مطمئن شوید که به CartItem واقعی اشاره دارد
import CartFooter from './CartFooter';
import { useCartContext } from '../../context/CartContext';

interface MiniCartProps {
    isOpen: boolean;
    onClose: () => void;
}

const MiniCart: React.FC<MiniCartProps> = ({ isOpen, onClose }) => {
    const { cartItems, cartTotal, removeFromCart, updateQuantity, cartLoading } = useCartContext();

    // اضافه کردن این console.log ها برای بررسی وضعیت cartItems در MiniCart
    console.log("MiniCart - cartItems (from context):", cartItems);
    console.log("MiniCart - cartItems.length (from context):", cartItems.length);
    console.log("MiniCart - cartLoading (from context):", cartLoading);


    if (!isOpen) return null;

    return (
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
                <>
                    <p className="text-center text-gray-500 py-8">در حال بارگذاری سبد خرید...</p> {/* Loading shopping cart... */}
                </>
            ) : (
                <div className="max-h-80 overflow-y-auto custom-scrollbar">
                    {cartItems.length === 0 ? (
                        <>
                            <p className="text-center text-gray-500 py-8">سبد خرید شما خالی است.</p> {/* Your shopping cart is empty. */}
                        </>
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
