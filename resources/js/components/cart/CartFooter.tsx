// resources/js/components/cart/CartFooter.tsx
import React from 'react';

interface CartFooterProps {
    total: number;
    loading?: boolean; // اضافه شدن پراپ loading
}

const CartFooter: React.FC<CartFooterProps> = ({ total, loading = false }) => {
    // اضافه کردن این console.log برای بررسی مقدار total دریافتی
    console.log("CartFooter received total:", total);

    // بررسی اینکه آیا سبد خرید خالی است یا در حال بارگذاری
    const isDisabled = total === 0 || loading;

    // تابع برای ردیابی آنالیتیکس در زمان کلیک روی دکمه تکمیل سفارش
    const handleCheckoutClick = (e: React.MouseEvent<HTMLAnchorElement>) => {
        if (isDisabled) {
            e.preventDefault(); // جلوگیری از ناوبری اگر دکمه غیرفعال است
            return;
        }
        // اینجا می‌توانید کدهای ردیابی آنالیتیکس خود را اضافه کنید
        // مثال: window.trackEvent('checkout_initiated', { value: total });
        console.log('Checkout initiated with total:', total);
    };

    return (
        <div className="p-4 border-t border-gray-200">
            <div className="flex justify-between items-center mb-4">
                <span className="text-md font-semibold text-gray-700">مجموع کل:</span>
                <span className="text-lg font-bold text-green-700">{total.toLocaleString()} تومان</span>
            </div>
            <div className="flex flex-col space-y-3">
                {/* دکمه مشاهده سبد خرید */}
                <a
                    href="/cart"
                    className={`w-full text-center ${isDisabled ? 'btn-disabled pointer-events-none' : 'btn-secondary'}`}
                    aria-disabled={isDisabled} // برای دسترسی‌پذیری
                    tabIndex={isDisabled ? -1 : 0} // برای دسترسی‌پذیری
                >
                    مشاهده سبد خرید
                </a>
                {/* دکمه ادامه جهت تکمیل سفارش */}
                <a
                    href="/checkout"
                    onClick={handleCheckoutClick}
                    className={`w-full text-center ${isDisabled ? 'btn-disabled pointer-events-none' : 'btn-primary'}`}
                    aria-disabled={isDisabled} // برای دسترسی‌پذیری
                    tabIndex={isDisabled ? -1 : 0} // برای دسترسی‌پذیری
                >
                    ادامه جهت تکمیل سفارش
                </a>
            </div>
        </div>
    );
};

export default CartFooter;
