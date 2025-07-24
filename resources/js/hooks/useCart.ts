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

import { CartItem, CartState } from '../types/cart'; // وارد کردن تایپ‌های مشترک

// هوک سفارشی useCart
export const useCart = () => {
    // استفاده از تایپ CartState که از types/cart.ts وارد شده است
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
        setCart(prev => ({ ...prev, loading: true, error: null }));
        try {
            // فرض می‌کنیم fetchCartContents یک آبجکت شامل items و total برمی‌گرداند
            const response = await fetchCartContents();
            // ساختار پاسخ API شما ممکن است متفاوت باشد، آن را مطابق با API خود تنظیم کنید
            // استفاده از تایپ CartItem که از types/cart.ts وارد شده است
            const fetchedItems: CartItem[] = response.cart_items.map((item: any) => ({
                id: item.id.toString(), // اطمینان از اینکه id رشته است
                name: item.product_name,
                price: parseFloat(item.price),
                quantity: parseInt(item.quantity),
                image: item.product_image_url || undefined, // اگر تصویر ندارید
            }));

            setCart({
                items: fetchedItems,
                total: parseFloat(response.total_price),
                subtotal: parseFloat(response.subtotal_price || response.total_price), // فرض می‌کنیم subtotal هم هست
                discount: parseFloat(response.discount_amount || 0),
                shipping: parseFloat(response.shipping_cost || 0),
                tax: parseFloat(response.tax_amount || 0),
                loading: false,
                error: null,
            });
        } catch (err) {
            console.error("Failed to fetch cart contents:", err);
            setCart(prev => ({ ...prev, loading: false, error: "Failed to load cart. Please try again." }));
        }
    }, []);

    // بارگذاری سبد خرید هنگام mount شدن کامپوننت
    useEffect(() => {
        loadCart();
        // می‌توانیم یک event listener برای به‌روزرسانی سبد خرید در صورت تغییرات خارجی اضافه کنیم
        // مثلاً وقتی کاربر از صفحه محصولی به سبد خرید اضافه می‌کند
        window.addEventListener('cartUpdated', loadCart);

        return () => {
            window.removeEventListener('cartUpdated', loadCart);
        };
    }, [loadCart]);

    // توابع مدیریت سبد خرید
    const addItem = useCallback(async (productId: string, quantity: number = 1) => {
        setCart(prev => ({ ...prev, loading: true }));
        try {
            await addToCart(productId, quantity);
            window.showMessage('محصول به سبد خرید اضافه شد.', 'success');
            await loadCart(); // پس از افزودن، سبد خرید را دوباره بارگذاری کنید
        } catch (err) {
            console.error("Failed to add item to cart:", err);
            window.showMessage('خطا در افزودن محصول به سبد خرید.', 'error');
            setCart(prev => ({ ...prev, loading: false, error: "Failed to add item." }));
        }
    }, [loadCart]);

    const updateQuantity = useCallback(async (itemId: string, quantity: number) => {
        if (quantity < 1) {
            // اگر تعداد به صفر یا کمتر رسید، آیتم را حذف کنید
            await removeItem(itemId);
            return;
        }
        setCart(prev => ({ ...prev, loading: true }));
        try {
            await updateCartItemQuantity(itemId, quantity);
            window.showMessage('تعداد آیتم به‌روزرسانی شد.', 'success');
            await loadCart(); // پس از به‌روزرسانی، سبد خرید را دوباره بارگذاری کنید
        } catch (err) {
            console.error("Failed to update item quantity:", err);
            window.showMessage('خطا در به‌روزرسانی تعداد آیتم.', 'error');
            setCart(prev => ({ ...prev, loading: false, error: "Failed to update quantity." }));
        }
    }, [loadCart]);

    const removeItem = useCallback(async (itemId: string) => {
        setCart(prev => ({ ...prev, loading: true }));
        try {
            await removeCartItem(itemId);
            window.showMessage('محصول از سبد خرید حذف شد.', 'success');
            await loadCart(); // پس از حذف، سبد خرید را دوباره بارگذاری کنید
        } catch (err) {
            console.error("Failed to remove item from cart:", err);
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
        removeFromCart: removeItem, // نام تابع را برای وضوح بیشتر تغییر دادیم
        clearCart: clearAllItems, // نام تابع را برای وضوح بیشتر تغییر دادیم
        loadCart // برای بارگذاری دستی سبد خرید
    };
};
