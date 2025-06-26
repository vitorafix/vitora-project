        // resources/js/app.js

        import './bootstrap';
        import Alpine from 'alpinejs';

        window.Alpine = Alpine;
        Alpine.start();

        // تابع سراسری برای نمایش پیام‌ها
        window.showMessage = function(message, type = 'info', duration = 3000) {
            const messageBox = document.createElement('div');
            messageBox.className = `fixed bottom-5 left-1/2 transform -translate-x-1/2 px-6 py-3 rounded-lg shadow-lg text-white z-50 transition-all duration-300 ease-out message-box ${type === 'success' ? 'bg-green-600' : (type === 'error' ? 'bg-red-600' : 'bg-gray-800')}`;
            messageBox.textContent = message;
            document.body.appendChild(messageBox);

            setTimeout(() => {
                messageBox.classList.add('opacity-0', 'translate-y-full');
                messageBox.addEventListener('transitionend', () => messageBox.remove());
            }, duration);
        };

        // ایمپورت کردن منطق سبد خرید (اگر قبلاً این خط را اضافه کرده‌اید)
        import './cart';

        // خط زیر را حذف کنید (اگر قبلاً اضافه کرده بودید)
        // import './hero-carousel';
        