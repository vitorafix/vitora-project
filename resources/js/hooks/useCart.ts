// resources/js/hooks/useCart.ts
import { useState, useEffect, useCallback } from 'react';
import {
    fetchCartContents,
    addToCart,
    updateCartItemQuantity,
    removeCartItem,
    clearCart,
    applyCoupon, // اگر نیاز بود
    removeCoupon // اگر نیاز بود
} from '../core/api'; // مسیر صحیح به فایل api.js شما

// تعریف تایپ برای آیتم سبد خرید
interface CartItem {
    id: string;
    name: string;
    price: number;
    quantity: number;
    image?: string;
}

// تعریف تایپ برای وضعیت سبد خرید
interface CartState {
    items: CartItem[];
    total: number;
    subtotal: number;
    discount: number;
    shipping: number;
    tax: number;
    loading: boolean;
    error: string | null;
}

// هوک سفارشی useCart
export const useCart = () => {
    const [cart, setCart] = useState<CartState>({
        items: [],
        total: 0,
        subtotal: 0,
        discount: 0,
        shipping: 0,
        tax: 0,
        loading: true,
        error: null,
    });

    // تابع برای بارگذاری محتویات سبد خرید از API
    const loadCart = useCallback(async () => {
        setCart(prev => ({ ...prev, loading: true }));
        try {
            const response = await fetchCartContents();
            if (response.success && response.data) { // اطمینان از وجود response.data
                const { items, total, subtotal, discount, shipping, tax } = response.data;
                setCart({
                    items: items || [], // اطمینان از اینکه items یک آرایه است
                    total: total || 0,
                    subtotal: subtotal || 0,
                    discount: discount || 0,
                    shipping: shipping || 0,
                    tax: tax || 0,
                    loading: false,
                    error: null,
                });
            } else {
                // اگر response.success false بود یا response.data وجود نداشت
                console.error("Failed to fetch cart contents:", response.message || "No data received.");
                setCart(prev => ({ ...prev, loading: false, error: response.message || "Failed to fetch cart contents." }));
            }
        } catch (err: any) {
            console.error("Error loading cart:", err);
            setCart(prev => ({ ...prev, loading: false, error: err.message || "Error loading cart." }));
        }
    }, []);

    // Effect برای بارگذاری اولیه سبد خرید
    useEffect(() => {
        loadCart();
    }, [loadCart]);

    // تابع برای افزودن آیتم به سبد خرید
    const addItem = useCallback(async (productId: string, quantity: number) => {
        setCart(prev => ({ ...prev, loading: true }));
        try {
            const response = await addToCart(productId, quantity);
            if (response.success) {
                window.showMessage(response.message || 'محصول با موفقیت به سبد خرید اضافه شد.', 'success');
                await loadCart(); // پس از افزودن، سبد خرید را دوباره بارگذاری کنید
            } else {
                window.showMessage(response.message || 'خطا در افزودن محصول.', 'error');
                setCart(prev => ({ ...prev, loading: false, error: response.message || "Failed to add item." }));
            }
        } catch (err) {
            console.error("Failed to add item:", err);
            window.showMessage('خطا در افزودن محصول به سبد خرید.', 'error');
            setCart(prev => ({ ...prev, loading: false, error: "Failed to add item." }));
        }
    }, [loadCart]);

    // تابع برای به‌روزرسانی تعداد آیتم در سبد خرید
    const updateQuantity = useCallback(async (itemId: string, quantity: number) => {
        setCart(prev => ({ ...prev, loading: true }));
        try {
            const response = await updateCartItemQuantity(itemId, quantity);
            if (response.success) {
                window.showMessage(response.message || 'تعداد محصول به‌روزرسانی شد.', 'success');
                await loadCart(); // پس از به‌روزرسانی، سبد خرید را دوباره بارگذاری کنید
            } else {
                window.showMessage(response.message || 'خطا در به‌روزرسانی تعداد محصول.', 'error');
                setCart(prev => ({ ...prev, loading: false, error: response.message || "Failed to update quantity." }));
            }
        } catch (err) {
            console.error("Failed to update quantity:", err);
            window.showMessage('خطا در به‌روزرسانی تعداد محصول.', 'error');
            setCart(prev => ({ ...prev, loading: false, error: "Failed to update quantity." }));
        }
    }, [loadCart]);

    // تابع برای حذف آیتم از سبد خرید
    const removeItem = useCallback(async (itemId: string) => {
        setCart(prev => ({ ...prev, loading: true }));
        try {
            const response = await removeCartItem(itemId);
            if (response.success) {
                window.showMessage(response.message || 'محصول از سبد خرید حذف شد.', 'success');
                await loadCart(); // پس از حذف، سبد خرید را دوباره بارگذاری کنید
            } else {
                window.showMessage(response.message || 'خطا در حذف محصول.', 'error');
                setCart(prev => ({ ...prev, loading: false, error: response.message || "Failed to remove item." }));
            }
        } catch (err) {
            console.error("Failed to remove item:", err);
            window.showMessage('خطا در حذف محصول از سبد خرید.', 'error');
            setCart(prev => ({ ...prev, loading: false, error: "Failed to remove item." }));
        }
    }, [loadCart]);

    const clearAllItems = useCallback(async () => {
        setCart(prev => ({ ...prev, loading: true }));
        try {
            await clearCart();
            window.showMessage('سبد خرید شما خالی شد.', 'success');
            await loadCart(); // پس از پاک کردن، سبد خرید را دوباره بارگذاری کنید
        } catch (err) {
            console.error("Failed to clear cart:", err);
            window.showMessage('خطا در پاک کردن سبد خرید.', 'error');
            setCart(prev => ({ ...prev, loading: false, error: "Failed to clear cart." }));
        }
    }, [loadCart]);

    // توابع مربوط به کوپن را در صورت نیاز اضافه کنید
    // const applyCouponCode = useCallback(async (code: string) => { ... }, [loadCart]);
    // const removeAppliedCoupon = useCallback(async () => { ... }, [loadCart]);


    return {
        cartItems: cart.items,
        cartTotal: cart.total,
        cartSubtotal: cart.subtotal,
        cartDiscount: cart.discount,
        cartShipping: cart.shipping,
        cartTax: cart.tax,
        cartLoading: cart.loading,
        cartError: cart.error,
        addItem,
        updateQuantity,
        removeFromCart: removeItem, // نام تابع را برای وضوح بیشتر
        clearAllItems,
        loadCart // این تابع را نیز برای رفرش دستی MiniCart در AppDebugger اکسپوز می‌کنیم
    };
};
