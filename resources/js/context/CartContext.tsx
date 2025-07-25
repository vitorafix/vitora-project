// resources/js/context/CartContext.tsx
import React, { createContext, useContext } from 'react';
import { useCart } from '../hooks/useCart'; // مسیر صحیح به هوک useCart شما
import { CartItem } from '../types/cart'; // وارد کردن تایپ CartItem از فایل مشترک

// تعریف تایپ برای وضعیت و توابع سبد خرید که از طریق Context ارائه می‌شوند
// ReturnType<typeof useCart> به طور خودکار تایپ‌های بازگشتی هوک useCart را استخراج می‌کند
interface CartContextType {
    cartItems: CartItem[];
    cartTotal: number;
    cartSubtotal: number;
    cartDiscount: number;
    cartShipping: number;
    cartTax: number;
    cartLoading: boolean;
    cartError: string | null;
    addItem: (productId: string, quantity: number) => Promise<void>;
    updateQuantity: (itemId: string, quantity: number) => Promise<void>;
    removeFromCart: (itemId: string) => Promise<void>;
    clearAllItems: () => Promise<void>; // نام تابع را به clearAllItems تغییر دادیم
    loadCart: () => Promise<void>;
    // توابع مربوط به کوپن را در صورت نیاز اینجا اضافه کنید
    // applyCouponCode: (code: string) => Promise<void>;
    // removeAppliedCoupon: () => Promise<void>;
}

// ایجاد Context
const CartContext = createContext<CartContextType | undefined>(undefined);

// هوک سفارشی برای استفاده از CartContext
export const useCartContext = () => {
    const context = useContext(CartContext);
    if (!context) {
        throw new Error('useCartContext must be used within a CartProvider');
    }
    return context;
};

// کامپوننت CartProvider
export const CartProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    // هوک useCart فقط یک بار در اینجا فراخوانی می‌شود
    const cart = useCart();

    return (
        <CartContext.Provider value={cart}>
            {children}
        </CartContext.Provider>
    );
};
