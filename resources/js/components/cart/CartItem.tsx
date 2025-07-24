// resources/js/components/cart/CartItem.tsx
import React from 'react';
// وارد کردن تایپ CartItem از فایل مشترک با نام مستعار CartItemType
import { CartItem as CartItemType } from '../../types/cart';

// تعریف تایپ برای پراپ‌های CartItemProps
// توجه: تعریف interface CartItem از اینجا حذف شده است
// زیرا اکنون از CartItemType که از 'types/cart' وارد شده، استفاده می‌کنیم.
interface CartItemProps {
    item: CartItemType; // استفاده از تایپ CartItemType وارد شده
    onRemove: (itemId: string) => void;
    onUpdateQuantity: (itemId: string, quantity: number) => void;
}

const CartItem: React.FC<CartItemProps> = ({ item, onRemove, onUpdateQuantity }) => {
    return (
        <div className="flex items-center p-4 border-b border-gray-100 last:border-b-0">
            <img
                src={item.image || `https://placehold.co/60x60/E2E8F0/64748B?text=No+Image`} // Placeholder if no image
                alt={item.name}
                className="w-16 h-16 object-cover rounded-md ml-3"
                onError={(e) => {
                    (e.target as HTMLImageElement).src = `https://placehold.co/60x60/E2E8F0/64748B?text=No+Image`; // Fallback on error
                }}
            />
            <div className="flex-1">
                <h4 className="text-sm font-medium text-gray-800">{item.name}</h4>
                <p className="text-xs text-gray-600 mt-1">{item.quantity} x {item.price.toLocaleString()} تومان</p>
                <div className="flex items-center mt-2">
                    <button
                        onClick={() => onUpdateQuantity(item.id, item.quantity - 1)}
                        disabled={item.quantity <= 1}
                        className="text-gray-500 hover:text-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i className="fas fa-minus-circle"></i>
                    </button>
                    <span className="mx-2 text-sm font-semibold">{item.quantity}</span>
                    <button
                        onClick={() => onUpdateQuantity(item.id, item.quantity + 1)}
                        className="text-gray-500 hover:text-green-700"
                    >
                        <i className="fas fa-plus-circle"></i>
                    </button>
                    <button
                        onClick={() => onRemove(item.id)}
                        className="text-red-500 hover:text-red-700 ml-auto"
                        aria-label="حذف آیتم"
                    >
                        <i className="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    );
};

export default CartItem;
