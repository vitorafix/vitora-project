// resources/js/components/cart/CartItem.tsx
import React from 'react';
import { CartItem as CartItemType } from '../../types/cart'; // وارد کردن تایپ CartItem
import { useCartContext } from '../../context/CartContext'; // برای دسترسی به توابع

interface CartItemProps {
    item: CartItemType;
    onRemove: (itemId: string) => void;
    onUpdateQuantity: (itemId: string, quantity: number) => void;
}

const CartItem: React.FC<CartItemProps> = ({ item, onRemove, onUpdateQuantity }) => {
    // برای دیباگ کردن ساختار پراپ item
    console.log("CartItem received item (full content):", JSON.stringify(item, null, 2));

    const { cartLoading } = useCartContext();

    // استفاده از item.product?.name برای نام محصول
    const productName = item.product?.name || 'نامشخص';
    // استفاده از item.product?.image برای تصویر محصول
    const productImage = item.product?.image;
    // استفاده مستقیم از formattedUnitPrice که توسط بک‌اند فرمت شده است
    const formattedPrice = item.formattedUnitPrice || 'قیمت نامعتبر';

    // اضافه کردن لاگ‌های جدید برای بررسی مقادیر نهایی
    console.log(`CartItem - Product Name: "${productName}"`);
    console.log(`CartItem - Formatted Price: "${formattedPrice}"`);
    console.log(`CartItem - Product Image: "${productImage}"`);


    const handleQuantityChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        const newQuantity = parseInt(e.target.value, 10);
        if (!isNaN(newQuantity) && newQuantity > 0) {
            onUpdateQuantity(item.id, newQuantity);
        }
    };

    return (
        <div className="flex items-center p-3 border-b border-gray-100 last:border-b-0">
            {/* تصویر محصول */}
            <div className="flex-shrink-0 w-16 h-16 rounded-md overflow-hidden mr-3">
                <img
                    src={productImage || `https://placehold.co/64x64/E2E8F0/64748B?text=${encodeURIComponent(productName)}`}
                    alt={productName}
                    className="w-full h-full object-cover"
                    onError={(e) => {
                        e.currentTarget.src = `https://placehold.co/64x64/E2E8F0/64748B?text=${encodeURIComponent(productName)}`;
                    }}
                />
            </div>

            {/* جزئیات محصول */}
            <div className="flex-grow">
                <h4 className="text-sm font-medium text-gray-900 line-clamp-2">{productName}</h4>
                {/* استفاده مستقیم از formattedPrice */}
                <p className="text-xs text-gray-500 mt-1">
                    {formattedPrice}
                </p>
            </div>

            {/* کنترل تعداد و حذف */}
            <div className="flex items-center ml-3">
                <select
                    value={item.quantity}
                    onChange={handleQuantityChange}
                    disabled={cartLoading}
                    className="form-select text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 w-16 text-center"
                    aria-label={`تعداد ${productName}`}
                >
                    {[...Array(10).keys()].map(i => ( // فرض می‌کنیم حداکثر 10 عدد از هر کالا
                        <option key={i + 1} value={i + 1}>{i + 1}</option>
                    ))}
                </select>
                <button
                    onClick={() => onRemove(item.id)}
                    disabled={cartLoading}
                    className="ml-2 text-red-500 hover:text-red-700 transition-colors p-1 rounded-full hover:bg-red-50"
                    aria-label={`حذف ${productName}`}
                >
                    <i className="fas fa-trash-alt text-sm"></i>
                </button>
            </div>
        </div>
    );
};

export default CartItem;
