// resources/js/cart.js

document.addEventListener('DOMContentLoaded', function() {
    // Get reference to the cart item count display element in the navigation (Mini-Cart)
    const cartItemCountSpan = document.getElementById('cart-item-count');
    // Reference to the mini-cart details container that appears on hover
    const miniCartDetailsContainer = document.getElementById('mini-cart-details-container');
    // Reference to the mini-cart icon or button that the hover event is applied to
    const miniCartTrigger = document.getElementById('mini-cart-trigger');

    // References to the custom confirmation modal elements
    const confirmationModalOverlay = document.getElementById('confirmation-modal-overlay');
    const confirmationModalTitle = document.getElementById('confirmation-modal-title');
    const confirmationModalMessage = document.getElementById('confirmation-modal-message');
    const confirmationModalConfirmBtn = document.getElementById('confirmation-modal-confirm-btn');
    const confirmationModalCancelBtn = document.getElementById('confirmation-modal-cancel-btn');
    const confirmationModalCloseBtn = document.getElementById('confirmation-modal-close-btn');

    // Variable to store the callback function for modal confirmation
    let confirmCallback = null;

    // Variable to hold the hover timer (to prevent immediate closing)
    let miniCartHoverTimer;
    const HOVER_DELAY = 300; // milliseconds to show details
    const HIDE_DELAY = 300; // milliseconds to hide details

    /**
     * Displays the mini-cart display (item count in the navigation bar).
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
                window.showMessage(data.message || 'Error loading cart.', 'error');
                return;
            }

            if (!data.items || !Array.isArray(data.items)) { // تغییر از cartItems به items
                console.error('cartItems is undefined or not an array:', data.items);
                window.showMessage('Error receiving cart information.', 'error');
                updateMiniCart(0);
                cartEmptyMessage.classList.remove('hidden');
                cartItemsContainer.classList.add('hidden');
                cartSummaryContainer.classList.add('hidden');
                return;
            }


            // Clear existing items
            cartItemsContainer.innerHTML = '';

            if (data.items.length === 0) { // تغییر از cartItems به items
                cartEmptyMessage.classList.remove('hidden');
                cartItemsContainer.classList.add('hidden');
                cartSummaryContainer.classList.add('hidden');
                updateMiniCart(0); // Update mini-cart if main cart is empty
            } else {
                cartEmptyMessage.classList.add('hidden');
                cartItemsContainer.classList.remove('hidden');
                cartSummaryContainer.classList.remove('hidden');

                // Render each cart item
                data.items.forEach(item => { // تغییر از cartItems به items
                    const itemElement = document.createElement('div');
                    itemElement.classList.add('flex', 'items-center', 'justify-between', 'py-4', 'border-b', 'border-gray-200');
                    itemElement.innerHTML = `
                        <div class="flex items-center w-3/5">
                            <img src="${item.thumbnail_url_small || item.image || 'https://placehold.co/80x80/E5E7EB/4B5563?text=Product'}"
                                 onerror="this.onerror=null;this.src='https://placehold.co/80x80/E5E7EB/4B5563?text=Product';"
                                 alt="${item.product_name}" class="w-20 h-20 object-cover rounded-lg ml-4 shadow-sm">
                            <a href="/products/${item.product_id}" class="text-brown-900 hover:text-green-700 font-semibold text-lg product-title-link">${item.product_name}</a>
                        </div>
                        <div class="flex items-center w-2/5 justify-end space-x-4 space-x-reverse">
                            <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
                                <button class="quantity-btn p-2 bg-gray-100 hover:bg-gray-200 transition-colors"
                                        data-cart-item-id="${item.cart_item_id}" data-action="decrease">-</button>
                                <input type="number" value="${item.quantity}"
                                        class="cart-quantity-input w-16 text-center border-none focus:ring-0 focus:outline-none bg-white text-gray-800"
                                        min="1" data-cart-item-id="${item.cart_item_id}" data-product-stock="${item.stock}">
                                <button class="quantity-btn p-2 bg-gray-100 hover:bg-gray-200 transition-colors"
                                        data-cart-item-id="${item.cart_item_id}" data-action="increase">+</button>
                            </div>
                            <span class="text-brown-800 font-bold w-24 text-center">${new Intl.NumberFormat('fa-IR').format(item.product_price * item.quantity)} Tomans</span>
                            <button class="remove-from-cart-btn text-red-500 hover:text-red-700 transition-colors p-2 rounded-full"
                                    data-cart-item-id="${item.cart_item_id}" data-product-title="${item.product_name}">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    `;
                    cartItemsContainer.appendChild(itemElement);
                });

                // Render summary
                cartSummaryContainer.innerHTML = `
                    <div class="flex justify-between items-center text-xl font-bold text-brown-900 pb-4 border-b-2 border-green-700 mb-4">
                        <span>Total:</span>
                        <span>${new Intl.NumberFormat('fa-IR').format(data.totalPrice)} Tomans</span>
                    </div>
                    <a href="/checkout" class="btn-primary w-full flex items-center justify-center">
                        <i class="fas fa-credit-card ml-2"></i>
                        Proceed to Checkout
                    </a>
                `;

                // Re-attach event listeners for newly rendered elements
                attachCartEventListeners();
                updateMiniCart(data.totalQuantity); // تغییر از totalItemsInCart به totalQuantity
            }

        } catch (error) {
            console.error('Error fetching cart contents:', error);
            window.showMessage('Error loading cart. Please try again.', 'error');
            updateMiniCart(0); // Assume 0 if error in fetching
        }
    }

    /**
     * Fetches current cart contents from the server to update only the mini-cart.
     */
    async function fetchCartContentsForMiniCart() {
        try {
            const response = await fetch('/cart/contents', {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (response.ok) {
                // Ensure data.totalQuantity is not undefined before using it
                updateMiniCart(data.totalQuantity !== undefined ? data.totalQuantity : 0); // تغییر از totalItemsInCart به totalQuantity
                // Also fetch mini-cart details after any update
                // If the details container exists and the mouse is over the trigger, render it
                if (miniCartDetailsContainer && miniCartTrigger && miniCartTrigger.matches(':hover')) {
                    renderMiniCartDetails();
                }
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
     * Renders the detailed view of the mini-cart in a dropdown.
     */
    async function renderMiniCartDetails() {
        if (!miniCartDetailsContainer) return;

        miniCartDetailsContainer.innerHTML = '<div class="p-4 text-center text-gray-600">Loading...</div>';
        miniCartDetailsContainer.classList.add('active'); // Show by adding active class

        try {
            const response = await fetch('/cart/contents', {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();

            if (!response.ok) {
                miniCartDetailsContainer.innerHTML = '<div class="p-4 text-center text-red-500">Error loading cart.</div>';
                return;
            }

            if (!data.items || !Array.isArray(data.items)) { // تغییر از cartItems به items
                console.error('cartItems is undefined or not an array in mini-cart details:', data.items);
                miniCartDetailsContainer.innerHTML = '<div class="p-4 text-center text-red-500">Error receiving cart details.</div>';
                return;
            }


            if (data.items.length === 0) { // تغییر از cartItems به items
                miniCartDetailsContainer.innerHTML = `
                    <div class="p-4 text-center text-gray-600">
                        Your cart is empty.
                    </div>
                `;
            } else {
                let itemsHtml = '';
                data.items.slice(0, 4).forEach(item => { // Display max 3 items // تغییر از cartItems به items
                    itemsHtml += `
                        <div class="flex items-center py-2 border-b border-gray-100 last:border-b-0">
                            <img src="${item.thumbnail_url_small || item.image || 'https://placehold.co/50x50/E5E7EB/4B5563?text=Product'}"
                                 onerror="this.onerror=null;this.src='https://placehold.co/50x50/E5E7EB/4B5563?text=Product';"
                                 alt="${item.product_name}" class="w-12 h-12 object-cover rounded-md ml-2">
                            <div class="flex-grow">
                                <p class="text-sm font-semibold text-gray-800">${item.product_name}</p>
                                <p class="text-xs text-gray-600">${item.quantity} × ${new Intl.NumberFormat('fa-IR').format(item.product_price)} Tomans</p>
                            </div>
                            <span class="text-sm font-bold text-brown-800">${new Intl.NumberFormat('fa-IR').format(item.product_price * item.quantity)} Tomans</span>
                        </div>
                    `;
                });

                miniCartDetailsContainer.innerHTML = `
                    <div class="p-4">
                        ${itemsHtml}
                        <div class="flex justify-between items-center pt-4 mt-2 border-t border-gray-200 mb-4">
                            <span class="text-base font-bold text-brown-900">Total:</span>
                            <span class="text-base font-bold text-brown-900">${new Intl.NumberFormat('fa-IR').format(data.totalPrice)} Tomans</span>
                        </div>
                        <a href="/cart" class="btn-secondary w-full text-center mt-4 py-2 text-sm">
                            View Cart
                        </a>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error fetching mini-cart details:', error);
            miniCartDetailsContainer.innerHTML = '<div class="p-4 text-center text-red-500">Error loading cart details.</div>';
        }
    }

    /**
     * Attaches event listeners to cart quantity buttons, input fields, and remove buttons.
     * (Called after initial render and after any AJAX updates that re-render cart items)
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
     */
    function handleModalConfirm() {
        if (confirmCallback) {
            confirmCallback(true); // Pass true to indicate confirmation
        }
        hideConfirmationModal();
    }

    /**
     * Handler for cancel button or close button in custom modal.
     */
    function handleModalCancel() {
        if (confirmCallback) {
            confirmCallback(false); // Pass false to indicate cancellation
        }
        hideConfirmationModal();
    }

    /**
     * Handles Escape key press to close modal.
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
            window.showMessage(`Insufficient stock for this quantity. Current stock: ${productStock}`, 'error');
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
            window.showMessage('Invalid quantity. Minimum 1.', 'error');
            return;
        }

        if (newQuantity > productStock) {
            window.showMessage(`Insufficient stock for this quantity. Current stock: ${productStock}`, 'error');
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
                window.showMessage(result.message || 'Error updating cart.', 'error');
                // If there's an error (e.g., stock issue), re-render to revert quantity
                renderMainCart();
            }
        } catch (error) {
            console.error('Error updating cart item:', error);
            window.showMessage('Server communication error. Please check your internet connection.', 'error');
            renderMainCart(); // Re-render on network error to show original state
        }
    }

    /**
     * Handles removing a cart item using the custom confirmation modal.
     * @param {Event} event
     */
    async function showRemoveConfirmationModal(event) {
        const cartItemId = this.dataset.cartItemId;
        
        // Find the main container for the current cart item (the one with 'flex' at the top level for the row)
        const itemMainContainer = this.closest('.flex.items-center.justify-between.py-4.border-b.border-gray-200');

        let itemTitle = 'Product'; // Fallback value
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
            'Remove Product from Cart', // Title
            `Are you sure you want to remove "${itemTitle}" from your cart?`, // Message
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
                            window.showMessage(result.message || 'Error removing product from cart.', 'error');
                        }
                    } catch (error) {
                        console.error('Error removing cart item:', error);
                        window.showMessage('Server communication error. Please check your internet connection.', 'error');
                    }
                }
            }
        );
    }


    // Event listener for "Add to Cart" buttons on product pages (or anywhere a product is displayed)
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', async function() {
            const productId = this.dataset.productId;
            const productTitle = this.dataset.productTitle;
            const productPrice = this.dataset.productPrice;

            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> Adding...';
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
                    window.showMessage(`"${productTitle}" added to cart successfully.`, 'success');
                    // Changed: Directly update mini-cart using the count from the response
                    if (result.totalQuantity !== undefined) { // تغییر از totalItemsInCart به totalQuantity
                        updateMiniCart(result.totalQuantity); // تغییر از totalItemsInCart به totalQuantity
                    } else {
                        // Fallback: If totalQuantity is not in response, fetch it
                        fetchCartContentsForMiniCart(); 
                    }
                    
                    // If on cart page, re-render it
                    if (document.getElementById('cart-items-container')) {
                        renderMainCart();
                    }
                } else {
                    window.showMessage(result.message || 'An error occurred while adding the product to the cart.', 'error');
                }

            } catch (error) {
                window.showMessage('Server communication error. Please check your internet connection.', 'error');
                console.error('Network or parsing error:', error);
            } finally {
                this.innerHTML = originalText;
                this.disabled = false;
            }
        });
    });

    // Event listener for "Place Order" button on the checkout page
    const placeOrderForm = document.getElementById('place-order-form');
    if (placeOrderForm) {
        placeOrderForm.addEventListener('submit', async function(event) {
            event.preventDefault(); // Prevent default form submission

            const placeOrderBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = placeOrderBtn.innerHTML;
            placeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> Placing order...';
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
                        let errorMessage = 'Please check the input information: <br>';
                        for (const field in result.errors) {
                            errorMessage += `- ${result.errors[field].join(', ')}<br>`;
                        }
                        window.showMessage(errorMessage, 'error', 5000); // Show for longer
                    } else {
                        window.showMessage(result.message || 'Error placing order.', 'error');
                    }
                    console.error('Order placement error:', result);
                }
            } catch (error) {
                console.error('Error placing order (network/parsing):', error);
                window.showMessage('Server communication error. Please check your internet connection.', 'error');
            } finally {
                placeOrderBtn.innerHTML = originalBtnText;
                placeOrderBtn.disabled = false;
            }
        });
    }

    // Event listeners for mini-cart hover functionality
    if (miniCartTrigger && miniCartDetailsContainer) {
        miniCartTrigger.addEventListener('mouseenter', () => {
            clearTimeout(miniCartHoverTimer);
            miniCartHoverTimer = setTimeout(() => {
                renderMiniCartDetails();
            }, HOVER_DELAY);
        });

        miniCartTrigger.addEventListener('mouseleave', () => {
            clearTimeout(miniCartHoverTimer);
            miniCartHoverTimer = setTimeout(() => {
                miniCartDetailsContainer.classList.remove('active');
            }, HIDE_DELAY);
        });

        // Keep mini-cart details open if mouse enters the details container itself
        miniCartDetailsContainer.addEventListener('mouseenter', () => {
            clearTimeout(miniCartHoverTimer);
        });

        miniCartDetailsContainer.addEventListener('mouseleave', () => {
            clearTimeout(miniCartHoverTimer);
            miniCartHoverTimer = setTimeout(() => {
                miniCartDetailsContainer.classList.remove('active');
            }, HIDE_DELAY);
        });
    }


    // Initial render of main cart if on cart page, and mini-cart everywhere
    if (document.getElementById('cart-items-container')) {
        renderMainCart();
    } else {
        fetchCartContentsForMiniCart(); // Fetch for mini-cart only if not on full cart page
    }
});
