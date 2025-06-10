@extends('layouts.app')

@section('title', 'تکمیل خرید و پرداخت - چای ابراهیم')

@section('content')
    <section class="my-16 p-8">
        <h1 class="text-4xl font-bold text-center text-brown-900 mb-10 section-heading">تکمیل خرید و پرداخت</h1>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
            <!-- Order Summary Section -->
            <div class="bg-gray-50 p-8 rounded-xl shadow-lg border border-gray-100 card-hover-effect">
                <h3 class="text-3xl font-semibold text-brown-900 mb-6 text-center">خلاصه سفارش</h3>
                <div id="order-summary-content">
                    <!-- Order items will be loaded here by JavaScript -->
                    <div id="order-summary-empty-message" class="text-center text-gray-700 text-lg py-4 hidden">
                        <i class="fas fa-box-open text-5xl text-gray-400 mb-4"></i>
                        <p>سبد خرید شما خالی است!</p>
                        <a href="{{ url('/products') }}" class="text-green-800 hover:underline mt-2 inline-block">بازگشت به فروشگاه</a>
                    </div>
                </div>
                
                <div id="order-summary-details" class="mt-8 text-lg hidden">
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span>جمع جزء:</span>
                        <span id="subtotal-price" class="font-semibold text-gray-700">۰ تومان</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <span>هزینه ارسال:</span>
                        <span id="shipping-cost" class="font-semibold text-gray-700">۰ تومان</span>
                    </div>
                    <div class="flex justify-between py-2 total-row rounded-b-lg">
                        <span class="text-brown-900 text-xl">مجموع کل:</span>
                        <span id="total-final-price" class="text-green-800 text-2xl">۰ تومان</span>
                    </div>
                </div>
            </div>

            <!-- Shipping Information & Payment Method Section -->
            <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-100 card-hover-effect">
                <h3 class="text-3xl font-semibold text-brown-900 mb-6 text-center">اطلاعات ارسال</h3>
                <form id="checkout-form" class="space-y-6">
                    <div>
                        <label for="full-name" class="block text-gray-700 text-lg font-medium mb-2">نام و نام خانوادگی:</label>
                        <input type="text" id="full-name" name="full-name" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-800 transition-all duration-300 text-gray-800" placeholder="نام کامل شما" required>
                    </div>
                    <div>
                        <label for="phone" class="block text-gray-700 text-lg font-medium mb-2">شماره تلفن:</label>
                        <input type="tel" id="phone" name="phone" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-800 transition-all duration-300 text-gray-800" placeholder="مثال: 09123456789" required>
                    </div>
                    <div>
                        <label for="address" class="block text-gray-700 text-lg font-medium mb-2">آدرس کامل:</label>
                        <textarea id="address" name="address" rows="3" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-800 transition-all duration-300 text-gray-800 resize-y" placeholder="خیابان، کوچه، پلاک، واحد" required></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="city" class="block text-gray-700 text-lg font-medium mb-2">شهر:</label>
                            <input type="text" id="city" name="city" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-800 transition-all duration-300 text-gray-800" placeholder="شهر شما" required>
                        </div>
                        <div>
                            <label for="postal-code" class="block text-gray-700 text-lg font-medium mb-2">کد پستی:</label>
                            <input type="text" id="postal-code" name="postal-code" class="w-full p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-800 transition-all duration-300 text-gray-800" placeholder="مثال: ۱۲۳۴۵۶۷۸۹۰" required>
                        </div>
                    </div>

                    <h3 class="text-3xl font-semibold text-brown-900 mt-10 mb-6 text-center">روش پرداخت</h3>
                    <div class="bg-green-50 p-6 rounded-xl border border-green-200 text-center text-gray-700">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="payment-method" value="online" class="form-radio text-green-800 h-5 w-5 ml-2" checked>
                            <span class="text-lg font-medium">پرداخت آنلاین (درگاه بانکی)</span>
                        </label>
                        <p class="text-sm text-gray-600 mt-2">به درگاه امن بانکی منتقل خواهید شد.</p>
                    </div>

                    <button type="submit" id="pay-button" class="btn-primary w-full mt-8">
                        <i class="fas fa-credit-card ml-2"></i> پرداخت و تکمیل سفارش
                        <span id="loader" class="loader hidden"></span>
                    </button>
                </form>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const loggedInUserFullData = JSON.parse(localStorage.getItem('loggedInUserFullData'));
            const loggedInUserSession = sessionStorage.getItem('loggedInUser');

            if (loggedInUserSession) {
                // User is logged in via session
                if (!loggedInUserFullData || !loggedInUserFullData.isProfileComplete) {
                    // Profile is not complete, redirect to complete-profile page
                    showMessage('لطفاً ابتدا اطلاعات کاربری خود را تکمیل کنید.', 'info');
                    setTimeout(() => {
                        window.location.href = '{{ url("/complete-profile") }}';
                    }, 2000); // Give user time to see the message
                } else {
                    // Profile is complete, pre-fill checkout form fields
                    const fullNameInput = document.getElementById('full-name');
                    const phoneInput = document.getElementById('phone');
                    const addressInput = document.getElementById('address');
                    const cityInput = document.getElementById('city');
                    const postalCodeInput = document.getElementById('postal-code');
                    
                    if (fullNameInput) fullNameInput.value = loggedInUserFullData.fullName || '';
                    if (phoneInput) phoneInput.value = loggedInUserFullData.phoneNumber || '';
                    if (addressInput && loggedInUserFullData.address) addressInput.value = loggedInUserFullData.address.street || '';
                    if (cityInput && loggedInUserFullData.address) cityInput.value = loggedInUserFullData.address.city || '';
                    if (postalCodeInput && loggedInUserFullData.address) postalCodeInput.value = loggedInUserFullData.address.postalCode || '';
                }
            } else {
                // User is not logged in, redirect to home or show auth modal (for this flow, we'll redirect to home)
                showMessage('برای تکمیل خرید، لطفاً وارد شوید یا ثبت نام کنید.', 'info');
                setTimeout(() => {
                    window.location.href = '{{ url("/") }}'; // Redirect to home, where auth modal can be triggered
                }, 2000);
            }
        });

        // Function to display a temporary message (copied from app.js for consistency)
        function showMessage(message, type = 'success') {
            const existingMessageBox = document.getElementById('temp-message-box');
            if (existingMessageBox) {
                existingMessageBox.remove();
            }

            const messageBox = document.createElement('div');
            messageBox.id = 'temp-message-box';
            messageBox.className = 'message-box fixed top-20 right-20 text-white p-4 rounded-lg shadow-lg flex items-center transform -translate-y-full opacity-0 transition-all duration-300 z-[9999]';

            let iconClass = '';
            let bgColorClass = '';

            if (type === 'success') {
                iconClass = 'fa-check-circle';
                bgColorClass = 'bg-green-800';
            } else if (type === 'error') {
                iconClass = 'fa-times-circle';
                bgColorClass = 'bg-red-600';
            } else if (type === 'info') {
                iconClass = 'fa-info-circle';
                bgColorClass = 'bg-blue-600';
            } else {
                iconClass = 'fa-check-circle';
                bgColorClass = 'bg-green-800';
            }

            messageBox.classList.add(bgColorClass);
            messageBox.innerHTML = `
                <i class="fas ${iconClass} ml-2"></i>
                <span>${message}</span>
            `;

            document.body.appendChild(messageBox);

            setTimeout(() => {
                messageBox.classList.remove('-translate-y-full', 'opacity-0');
                messageBox.classList.add('translate-y-0', 'opacity-100');
            }, 10);

            setTimeout(() => {
                messageBox.classList.remove('translate-y-0', 'opacity-100');
                messageBox.classList.add('-translate-y-full', 'opacity-0');
                messageBox.addEventListener('transitionend', () => messageBox.remove());
            }, type === 'info' ? 5000 : 3000);
        }
    </script>
@endsection
