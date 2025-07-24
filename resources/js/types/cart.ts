// resources/js/types/cart.ts

// تعریف تایپ برای یک آیتم سبد خرید
export interface CartItem {
    id: string;
    name: string;
    price: number;
    quantity: number;
    image?: string; // آدرس تصویر محصول (اختیاری)
}

// تعریف تایپ برای وضعیت کلی سبد خرید
export interface CartState {
    items: CartItem[];
    total: number;
    subtotal: number;
    discount: number;
    shipping: number;
    tax: number;
    loading: boolean;
    error: string | null;
}
