// resources/js/cart.js

document.addEventListener('DOMContentLoaded', function() {
    // گرفتن مرجع به عنصر نمایش تعداد آیتم‌های سبد خرید در ناوبری (Mini-Cart)
    const cartItemCountSpan = document.getElementById('cart-item-count');

    // References to the custom confirmation modal elements
    // مراجع به عناصر مدال تأیید سفارشی
    const confirmationModalOverlay = document.getElementById('confirmation-modal-overlay');
    const confirmationModalTitle = document.getElementById('confirmation-modal-title');
    const confirmationModalMessage = document.getElementById('confirmation-modal-message');
    const confirmationModalConfirmBtn = document.getElementById('confirmation-modal-confirm-btn');
    const confirmationModalCancelBtn = document.getElementById('confirmation-modal-cancel-btn');
    const confirmationModalCloseBtn = document.getElementById('confirmation-modal-close-btn');

    // Variable to store the callback function for modal confirmation
    // متغیری برای ذخیره تابع callback برای تأیید مدال
    let confirmCallback = null;

    /**
     * Displays the mini-cart display (item count in the navigation bar).
     * تعداد آیتم‌های سبد خرید را در نوار ناوبری (مینی‌کارت) به‌روزرسانی می‌کند.
     * @param {number} count - The total number of items in the cart.
     */
    function updateMiniCart(count) {
        if (cartItemCountSpan) {
            cartItemCountSpan.textContent = count;
            if (count > 0) {
                cartItemCountSpan.classList.remove('hidden');
            } else {
                cartItemCountSpan.classList.add('hidden');
            }
        }
    }

    /**
     * Fetches current cart contents from the server and renders the main cart page.
     * محتویات فعلی سبد خرید را از سرور واکشی کرده و صفحه اصلی سبد خرید را رندر می‌کند.
     */
    async function renderMainCart() {
        const cartItemsContainer = document.getElementById('cart-items-container');
        const cartSummaryContainer = document.getElementById('cart-summary');
        const cartEmptyMessage = document.getElementById('cart-empty-message');

        if (!cartItemsContainer || !cartSummaryContainer || !cartEmptyMessage) {
            // If these elements are not on the current page (e.g., product-single, home),
            // we only update the mini-cart.
            fetchCartContentsForMiniCart();
            return;
        }

        try {
            const response = await fetch('/cart/contents', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();

            if (!response.ok) {
                // Handle API error
                window.showMessage(data.message || 'خطا در بارگذاری سبد خرید.', 'error');
                return;
            }

            // Clear existing items
            cartItemsContainer.innerHTML = '';

            if (data.cartItems.length === 0) {
                cartEmptyMessage.classList.remove('hidden');
                cartItemsContainer.classList.add('hidden');
                cartSummaryContainer.classList.add('hidden');
                updateMiniCart(0); // Update mini-cart if main cart is empty
            } else {
                cartEmptyMessage.classList.add('hidden');
                cartItemsContainer.classList.remove('hidden');
                cartSummaryContainer.classList.remove('hidden');

                // Render each cart item
                data.cartItems.forEach(item => {
                    const itemElement = document.createElement('div');
                    itemElement.classList.add('flex', 'items-center', 'justify-between', 'py-4', 'border-b', 'border-gray-200');
                    itemElement.innerHTML = `
                        <div class="flex items-center w-3/5">
                            <img src="${item.product.image || 'https://placehold.co/80x80/E5E7EB/4B5563?text=Product'}"
                                 onerror="this.onerror=null;this.src='https://placehold.co/80x80/E5E7EB/4B5563?text=Product';"
                                 alt="${item.product.title}" class="w-20 h-20 object-cover rounded-lg ml-4 shadow-sm">
                            <a href="/products/${item.product.id}" class="text-brown-900 hover:text-green-700 font-semibold text-lg product-title-link">${item.product.title}</a>
                        </div>
                        <div class="flex items-center w-2/5 justify-end space-x-4 space-x-reverse">
                            <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
                                <button class="quantity-btn p-2 bg-gray-100 hover:bg-gray-200 transition-colors"
                                        data-cart-item-id="${item.id}" data-action="decrease">-</button>
                                <input type="number" value="${item.quantity}"
                                       class="cart-quantity-input w-16 text-center border-none focus:ring-0 focus:outline-none bg-white text-gray-800"
                                       min="1" data-cart-item-id="${item.id}" data-product-stock="${item.product.stock}">
                                <button class="quantity-btn p-2 bg-gray-100 hover:bg-gray-200 transition-colors"
                                        data-cart-item-id="${item.id}" data-action="increase">+</button>
                            </div>
                            <span class="text-brown-800 font-bold w-24 text-center">${new Intl.NumberFormat('fa-IR').format(item.price * item.quantity)} تومان</span>
                            <button class="remove-from-cart-btn text-red-500 hover:text-red-700 transition-colors p-2 rounded-full"
                                    data-cart-item-id="${item.id}" data-product-title="${item.product.title}"> <!-- Added data-product-title here -->
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    `;
                    cartItemsContainer.appendChild(itemElement);
                });

                // Render summary
                cartSummaryContainer.innerHTML = `
                    <div class="flex justify-between items-center text-xl font-bold text-brown-900 pb-4 border-b-2 border-green-700 mb-4">
                        <span>جمع کل:</span>
                        <span>${new Intl.NumberFormat('fa-IR').format(data.totalPrice)} تومان</span>
                    </div>
                    <a href="/checkout" class="btn-primary w-full flex items-center justify-center">
                        <i class="fas fa-credit-card ml-2"></i>
                        ادامه جهت تسویه حساب
                    </a>
                `;

                // Re-attach event listeners for newly rendered elements
                attachCartEventListeners();
                updateMiniCart(data.totalItemsInCart);
            }

        } catch (error) {
            console.error('Error fetching cart contents:', error);
            window.showMessage('خطا در بارگذاری سبد خرید. لطفاً دوباره تلاش کنید.', 'error');
            updateMiniCart(0); // Assume 0 if error in fetching
        }
    }

    /**
     * Fetches current cart contents from the server to update only the mini-cart.
     * محتویات فعلی سبد خرید را از سرور واکشی کرده و تنها مینی‌کارت را به‌روزرسانی می‌کند.
     */
    async function fetchCartContentsForMiniCart() {
        try {
            const response = await fetch('/cart/contents', {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (response.ok) {
                updateMiniCart(data.totalItemsInCart);
            } else {
                console.error('Error fetching mini-cart contents:', data.message);
                updateMiniCart(0);
            }
        } catch (error) {
            console.error('Network error fetching mini-cart contents:', error);
            updateMiniCart(0);
        }
    }


    /**
     * Attaches event listeners to cart quantity buttons, input fields, and remove buttons.
     * (Called after initial render and after any AJAX updates that re-render cart items)
     * به دکمه‌های تغییر تعداد، فیلدهای ورودی و دکمه‌های حذف سبد خرید، Event Listener اضافه می‌کند.
     */
    function attachCartEventListeners() {
        // Event listeners for quantity buttons (+/-)
        document.querySelectorAll('.quantity-btn').forEach(button => {
            button.removeEventListener('click', handleQuantityChange); // Prevent duplicate listeners
            button.addEventListener('click', handleQuantityChange);
        });

        // Event listeners for quantity input field (direct input)
        document.querySelectorAll('.cart-quantity-input').forEach(input => {
            input.removeEventListener('change', handleQuantityInputChange); // Prevent duplicate listeners
            input.addEventListener('change', handleQuantityInputChange);
        });

        // Event listeners for remove buttons
        document.querySelectorAll('.remove-from-cart-btn').forEach(button => {
            button.removeEventListener('click', showRemoveConfirmationModal); // Changed to use custom modal
            button.addEventListener('click', showRemoveConfirmationModal); // Changed to use custom modal
        });
    }

    /**
     * Shows a custom confirmation modal.
     * یک مدال تأیید سفارشی را نمایش می‌دهد.
     * @param {string} title - The title of the modal.
     * @param {string} message - The message to display.
     * @param {Function} onConfirm - Callback function to execute if 'Confirm' is clicked.
     */
    function showConfirmationModal(title, message, onConfirm) {
        if (!confirmationModalOverlay || !confirmationModalTitle || !confirmationModalMessage || !confirmationModalConfirmBtn || !confirmationModalCancelBtn || !confirmationModalCloseBtn) {
            console.error("Confirmation modal elements not found in app.blade.php. Falling back to browser's confirm.");
            // Fallback to browser's confirm if elements are missing
            if (window.confirm(message)) {
                onConfirm(true); // Pass true for confirmation if fallback is used
            } else {
                onConfirm(false); // Pass false for cancellation if fallback is used
            }
            return;
        }

        confirmationModalTitle.textContent = title;
        confirmationModalMessage.textContent = message;
        confirmationModalOverlay.classList.add('active'); // Show modal

        // Store the callback
        confirmCallback = onConfirm;

        // Clear previous listeners to prevent multiple executions
        confirmationModalConfirmBtn.removeEventListener('click', handleModalConfirm);
        confirmationModalCancelBtn.removeEventListener('click', handleModalCancel);
        confirmationModalCloseBtn.removeEventListener('click', handleModalCancel);

        // Add new listeners
        confirmationModalConfirmBtn.addEventListener('click', handleModalConfirm);
        confirmationModalCancelBtn.addEventListener('click', handleModalCancel);
        confirmationModalCloseBtn.addEventListener('click', handleModalCancel);

        // Allow closing with Escape key
        document.addEventListener('keydown', handleEscapeKey);
    }

    /**
     * Hides the custom confirmation modal.
     * مدال تأیید سفارشی را پنهان می‌کند.
     */
    function hideConfirmationModal() {
        if (confirmationModalOverlay) {
            confirmationModalOverlay.classList.remove('active');
        }
        confirmCallback = null; // Clear callback
        // Remove escape key listener
        document.removeEventListener('keydown', handleEscapeKey);
    }

    /**
     * Handler for confirm button in custom modal.
     * هندلر برای دکمه تأیید در مدال سفارشی.
     */
    function handleModalConfirm() {
        if (confirmCallback) {
            confirmCallback(true); // Pass true to indicate confirmation
        }
        hideConfirmationModal();
    }

    /**
     * Handler for cancel button or close button in custom modal.
     * هندلر برای دکمه لغو یا بستن در مدال سفارشی.
     */
    function handleModalCancel() {
        if (confirmCallback) {
            confirmCallback(false); // Pass false to indicate cancellation
        }
        hideConfirmationModal();
    }

    /**
     * Handles Escape key press to close modal.
     * مدیریت فشار کلید Escape برای بستن مدال.
     */
    function handleEscapeKey(event) {
        if (event.key === 'Escape') {
            handleModalCancel();
        }
    }


    /**
     * Handles quantity change when '+' or '-' buttons are clicked.
     * @param {Event} event
     */
    async function handleQuantityChange(event) {
        const cartItemId = this.dataset.cartItemId;
        const action = this.dataset.action;
        const inputElement = this.closest('.flex').querySelector('.cart-quantity-input');
        let currentQuantity = parseInt(inputElement.value);
        const productStock = parseInt(inputElement.dataset.productStock);

        let newQuantity = currentQuantity;
        if (action === 'increase') {
            newQuantity = currentQuantity + 1;
        } else if (action === 'decrease') {
            newQuantity = currentQuantity - 1;
        }

        // Validate new quantity against stock
        if (newQuantity > productStock) {
            window.showMessage(`موجودی کافی برای این تعداد وجود ندارد. موجودی فعلی: ${productStock}`, 'error');
            return;
        }
        if (newQuantity < 0) { // Should not happen with min="1" but as a safeguard
            newQuantity = 0;
        }

        // Only send request if quantity actually changes
        if (newQuantity !== currentQuantity) {
            await updateCartItem(cartItemId, newQuantity);
        }
    }

    /**
     * Handles quantity change when user directly inputs a value.
     * @param {Event} event
     */
    async function handleQuantityInputChange(event) {
        const cartItemId = this.dataset.cartItemId;
        let newQuantity = parseInt(this.value);
        const productStock = parseInt(this.dataset.productStock);

        // Basic validation for input field
        if (isNaN(newQuantity) || newQuantity < 0) {
            newQuantity = 1; // Default to 1 if invalid
            this.value = newQuantity; // Update input field
            window.showMessage('تعداد نامعتبر است. حداقل 1 عدد.', 'error');
            return;
        }

        if (newQuantity > productStock) {
            window.showMessage(`موجودی کافی برای این تعداد وجود ندارد. موجودی فعلی: ${productStock}`, 'error');
            this.value = productStock; // Set to max available stock
            newQuantity = productStock;
        }

        // Only send request if quantity actually changes
        if (newQuantity !== parseInt(this.dataset.previousQuantity || this.value)) { // Use dataset for previous if needed
             await updateCartItem(cartItemId, newQuantity);
        }
    }


    /**
     * Sends an AJAX request to update a cart item's quantity.
     * درخواست AJAX برای به‌روزرسانی تعداد یک آیتم سبد خرید.
     * @param {number} cartItemId - The ID of the cart item.
     * @param {number} quantity - The new quantity.
     */
    async function updateCartItem(cartItemId, quantity) {
        try {
            const response = await fetch(`/cart/update/${cartItemId}`, {
                method: 'PUT', // Changed to PUT as per backend route
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ quantity: quantity })
            });
            const result = await response.json();

            if (response.ok) {
                window.showMessage(result.message, 'success');
                renderMainCart(); // Re-render cart to show updated totals/items
            } else {
                window.showMessage(result.message || 'خطا در به‌روزرسانی سبد خرید.', 'error');
                // If there's an error (e.g., stock issue), re-render to revert quantity
                renderMainCart();
            }
        } catch (error) {
            console.error('Error updating cart item:', error);
            window.showMessage('خطا در ارتباط با سرور. لطفاً اتصال اینترنت خود را بررسی کنید.', 'error');
            renderMainCart(); // Re-render on network error to show original state
        }
    }

    /**
     * Handles removing a cart item using the custom confirmation modal.
     * مدیریت حذف یک آیتم سبد خرید با استفاده از مدال تأیید سفارشی.
     * @param {Event} event
     */
    async function showRemoveConfirmationModal(event) {
        const cartItemId = this.dataset.cartItemId;
        
        // Find the main container for the current cart item (the one with 'flex' at the top level for the row)
        // این عنصر همان 'itemElement' است که در renderMainCart ساخته می‌شود.
        const itemMainContainer = this.closest('.flex.items-center.justify-between.py-4.border-b.border-gray-200');

        let itemTitle = 'محصول'; // Fallback value
        if (itemMainContainer) {
            // Query within this main container for the product title link, which is inside the first flex child div
            const titleLinkElement = itemMainContainer.querySelector('.w-3\\/5 a'); 
            if (titleLinkElement) {
                itemTitle = titleLinkElement.textContent.trim();
            } else {
                console.warn("Could not find product title link within the item container.");
            }
        } else {
            console.warn("Could not find the main item container for the delete button.");
        }

        showConfirmationModal(
            'حذف محصول از سبد خرید', // Title
            `آیا مطمئن هستید که می‌خواهید "${itemTitle}" را از سبد خرید حذف کنید؟`, // Message
            async (confirmed) => {
                if (confirmed) {
                    try {
                        const response = await fetch(`/cart/remove/${cartItemId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        const result = await response.json();

                        if (response.ok) {
                            window.showMessage(result.message, 'success');
                            renderMainCart(); // Re-render cart after removal
                        } else {
                            window.showMessage(result.message || 'خطا در حذف محصول از سبد خرید.', 'error');
                        }
                    } catch (error) {
                        console.error('Error removing cart item:', error);
                        window.showMessage('خطا در ارتباط با سرور. لطفاً اتصال اینترنت خود را بررسی کنید.', 'error');
                    }
                }
            }
        );
    }


    // Event listener for "Add to Cart" buttons on product pages (or anywhere a product is displayed)
    // Event listener برای دکمه‌های "افزودن به سبد" در صفحات محصول
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', async function() {
            const productId = this.dataset.productId;
            const productTitle = this.dataset.productTitle;
            const productPrice = this.dataset.productPrice;

            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال افزودن...';
            this.disabled = true;

            try {
                const response = await fetch('/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1, // Default to 1 for quick add
                        price: productPrice
                    })
                });

                const result = await response.json();

                if (response.ok) {
                    window.showMessage(`"${productTitle}" با موفقیت به سبد خرید اضافه شد.`, 'success');
                    // Changed: Directly update mini-cart using the count from the response
                    if (result.totalItemsInCart !== undefined) {
                        updateMiniCart(result.totalItemsInCart); 
                    } else {
                        // Fallback: If totalItemsInCart is not in response, fetch it
                        fetchCartContentsForMiniCart(); 
                    }
                    
                    // If on cart page, re-render it
                    if (document.getElementById('cart-items-container')) {
                        renderMainCart();
                    }
                } else {
                    window.showMessage(result.message || 'خطایی در افزودن محصول به سبد رخ داد.', 'error');
                }

            } catch (error) {
                window.showMessage('خطا در ارتباط با سرور. لطفاً اتصال اینترنت خود را بررسی کنید.', 'error');
                console.error('Network or parsing error:', error);
            } finally {
                this.innerHTML = originalText;
                this.disabled = false;
            }
        });
    });

    // Event listener for "Place Order" button on the checkout page
    // Event listener برای دکمه "ثبت سفارش" در صفحه تسویه حساب
    const placeOrderForm = document.getElementById('place-order-form');
    if (placeOrderForm) {
        placeOrderForm.addEventListener('submit', async function(event) {
            event.preventDefault(); // Prevent default form submission

            const placeOrderBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = placeOrderBtn.innerHTML;
            placeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال ثبت سفارش...';
            placeOrderBtn.disabled = true;

            const formData = new FormData(placeOrderForm);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('/order/place', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (response.ok) {
                    window.showMessage(result.message, 'success');
                    // Redirect to order confirmation page
                    window.location.href = `/order/confirmation/${result.orderId}`;
                } else {
                    // Handle validation errors from the backend (status 422)
                    if (response.status === 422 && result.errors) {
                        let errorMessage = 'لطفاً اطلاعات ورودی را بررسی کنید: <br>';
                        for (const field in result.errors) {
                            errorMessage += `- ${result.errors[field].join(', ')}<br>`;
                        }
                        window.showMessage(errorMessage, 'error', 5000); // Show for longer
                    } else {
                        window.showMessage(result.message || 'خطا در ثبت سفارش.', 'error');
                    }
                    console.error('Order placement error:', result);
                }
            } catch (error) {
                console.error('Error placing order (network/parsing):', error);
                window.showMessage('خطا در ارتباط با سرور. لطفاً اتصال اینترنت خود را بررسی کنید.', 'error');
            } finally {
                placeOrderBtn.innerHTML = originalBtnText;
                placeOrderBtn.disabled = false;
            }
        });
    }

    // Initial render of main cart if on cart page, and mini-cart everywhere
    // رندر اولیه سبد خرید اصلی (اگر در صفحه سبد خرید هستیم) و مینی‌کارت (در همه صفحات)
    if (document.getElementById('cart-items-container')) {
        renderMainCart();
    } else {
        fetchCartContentsForMiniCart(); // Fetch for mini-cart only if not on full cart page
    }
});
