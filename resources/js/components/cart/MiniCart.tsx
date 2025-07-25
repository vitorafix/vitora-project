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

    // اگر سبد خرید باز نباشد، چیزی نمایش داده نمی‌شود
    if (!isOpen) return null;

    return (
        <div className="absolute top-full right-0 mt-1 w-72 md:w-80 bg-white rounded-lg shadow-xl border border-gray-200 z-[9999] overflow-hidden">
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
                // *** شروع تغییرات اصلی ***
                // در این قسمت، حداکثر ارتفاع با max-h-60 (معادل 240px) تنظیم شده تا فضای کافی برای حدود ۳ آیتم فراهم شود.
                // overflow-y-auto باعث می‌شود در صورت بیشتر بودن محتوا، اسکرول عمودی ظاهر شود.
                // کلاس‌های scrollbar-* برای استایل‌دهی به نوار اسکرول با استفاده از Tailwind CSS هستند.
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
                // *** پایان تغییرات اصلی ***
            )}
            {/* فوتر سبد خرید فقط زمانی نمایش داده می‌شود که آیتمی در سبد وجود داشته باشد */}
            {cartItems.length > 0 && (
                <CartFooter total={cartTotal} loading={cartLoading} />
            )}
        </div>
    );
};

export default MiniCart;
