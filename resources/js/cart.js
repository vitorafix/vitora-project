// cart.js
// This file handles all client-side logic for the shopping cart,
// including adding/removing items, updating quantities,
// displaying cart contents in both mini-cart and main cart page,
// and interacting with backend APIs.

document.addEventListener('DOMContentLoaded', function() {
    // --- DOM Elements Caching ---
    const miniCartBtn = document.getElementById('mini-cart-btn');
    const miniCartDropdown = document.getElementById('mini-cart-dropdown');
    const miniCartItemsContainer = document.getElementById('mini-cart-items-container');
    const miniCartTotalQuantity = document.getElementById('mini-cart-total-quantity');
    const miniCartTotalPrice = document.getElementById('mini-cart-total-price');
    const miniCartEmptyMessage = document.getElementById('mini-cart-empty-message');
    const miniCartCheckoutBtn = document.getElementById('mini-cart-checkout-btn');
    const miniCartViewCartBtn = document.getElementById('mini-cart-view-cart-btn');

    const addProductToCartBtns = document.querySelectorAll('.add-to-cart-btn'); // For product listing page

    // Elements for the main cart page (if present)
    const mainCartItemsContainer = document.getElementById('cart-items-container');
    const mainCartEmptyMessage = document.getElementById('cart-empty-message');
    const mainCartSummary = document.getElementById('cart-summary');
    const mainCartTotalPriceElement = document.getElementById('cart-total-price'); // Assuming this exists in main cart summary

    // --- Helper Functions ---

    // Get CSRF Token from meta tag
    function getCsrfToken() {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        return csrfMeta ? csrfMeta.getAttribute('content') : '';
    }

    // Function to format numbers with commas (Persian locale)
    function formatNumber(num) {
        return new Intl.NumberFormat('fa-IR').format(num);
    }

    /**
     * Shows a message box to the user.
     * @param {string} message - The message to display.
     * @param {string} type - 'success', 'error', 'info', 'warning'.
     * @param {number} duration - Duration in milliseconds for the message to disappear. Default 3000.
     */
    window.showMessage = function(message, type = 'info', duration = 3000) {
        const messageBox = document.getElementById('message-box');
        const messageText = document.getElementById('message-text');
        if (!messageBox || !messageText) {
            console.warn('Message box elements not found.');
            return;
        }

        messageText.innerHTML = message; // Use innerHTML to allow for <br>
        messageBox.className = 'fixed bottom-5 right-5 p-4 rounded-lg shadow-lg text-white z-50 transition-transform transform translate-x-full';

        switch (type) {
            case 'success':
                messageBox.classList.add('bg-green-500');
                break;
            case 'error':
                messageBox.classList.add('bg-red-500');
                break;
            case 'info':
                messageBox.classList.add('bg-blue-500');
                break;
            case 'warning':
                messageBox.classList.add('bg-orange-500');
                break;
            default:
                messageBox.classList.add('bg-gray-700');
        }

        // Show the message box
        setTimeout(() => {
            messageBox.classList.remove('translate-x-full');
            messageBox.classList.add('translate-x-0');
        }, 50); // Small delay for animation

        // Hide the message box after duration
        setTimeout(() => {
            messageBox.classList.remove('translate-x-0');
            messageBox.classList.add('translate-x-full');
        }, duration);
    };


    // --- Cart API Interactions ---

    /**
     * Fetches cart contents from the backend API.
     * @returns {Promise<Object>} A promise that resolves to the cart data.
     */
    async function fetchCartContents() {
        try {
            // *** IMPORTANT: Corrected URL to include /api/ ***
            const response = await fetch('/api/cart/contents', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken() // Include CSRF token for GET if needed by middleware
                }
            });

            if (!response.ok) {
                // If response is not OK, try to parse JSON error message
                const errorData = await response.json().catch(() => ({ message: 'خطای ناشناخته از سرور.' }));
                throw new Error(errorData.message || 'خطا در دریافت محتویات سبد خرید.');
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error fetching cart contents:', error);
            window.showMessage(error.message || 'خطا در بارگذاری سبد خرید.', 'error');
            return { items: [], totalQuantity: 0, totalPrice: 0 }; // Return default empty cart on error
        }
    }

    /**
     * Adds a product to the cart or updates its quantity via API.
     * @param {number} productId - The ID of the product.
     * @param {number} quantity - The quantity to add/update.
     * @returns {Promise<Object>} A promise that resolves to the API response.
     */
    async function addOrUpdateCartItem(productId, quantity = 1) {
        try {
            // *** IMPORTANT: Corrected URL to include /api/ ***
            const response = await fetch('/api/cart/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({ product_id: productId, quantity: quantity })
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'خطا در افزودن محصول به سبد خرید.');
            }

            window.showMessage(result.message || 'محصول به سبد خرید اضافه شد.', 'success');
            return result;
        } catch (error) {
            console.error('Error adding/updating cart item:', error);
            window.showMessage(error.message || 'خطا در افزودن محصول به سبد خرید.', 'error');
            throw error; // Re-throw to allow calling context to handle
        }
    }

    /**
     * Updates the quantity of a specific cart item via API.
     * @param {number} cartItemId - The ID of the cart item.
     * @param {number} newQuantity - The new quantity.
     * @returns {Promise<Object>} A promise that resolves to the API response.
     */
    async function updateCartItemQuantity(cartItemId, newQuantity) {
        try {
            // *** IMPORTANT: Corrected URL to include /api/ ***
            const response = await fetch(`/api/cart/update/${cartItemId}`, {
                method: 'PUT', // Use PUT for updates
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({ quantity: newQuantity })
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'خطا در به‌روزرسانی تعداد محصول.');
            }

            window.showMessage(result.message || 'تعداد محصول به‌روزرسانی شد.', 'success');
            return result;
        } catch (error) {
            console.error('Error updating cart item quantity:', error);
            window.showMessage(error.message || 'خطا در به‌روزرسانی تعداد محصول.', 'error');
            throw error;
        }
    }

    /**
     * Removes a specific cart item via API.
     * @param {number} cartItemId - The ID of the cart item to remove.
     * @returns {Promise<Object>} A promise that resolves to the API response.
     */
    async function removeCartItem(cartItemId) {
        try {
            // *** IMPORTANT: Corrected URL to include /api/ ***
            const response = await fetch(`/api/cart/remove/${cartItemId}`, {
                method: 'DELETE', // Use DELETE for removal
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'خطا در حذف محصول از سبد خرید.');
            }

            window.showMessage(result.message || 'محصول از سبد خرید حذف شد.', 'success');
            return result;
        } catch (error) {
            console.error('Error removing cart item:', error);
            window.showMessage(error.message || 'خطا در حذف محصول از سبد خرید.', 'error');
            throw error;
        }
    }

    /**
     * Clears all items from the cart via API.
     * @returns {Promise<Object>} A promise that resolves to the API response.
     */
    async function clearCart() {
        try {
            // *** IMPORTANT: Corrected URL to include /api/ ***
            const response = await fetch('/api/cart/clear', {
                method: 'POST', // Or DELETE, depending on your backend
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'خطا در پاکسازی سبد خرید.');
            }

            window.showMessage(result.message || 'سبد خرید خالی شد.', 'success');
            return result;
        } catch (error) {
            console.error('Error clearing cart:', error);
            window.showMessage(error.message || 'خطا در پاکسازی سبد خرید.', 'error');
            throw error;
        }
    }


    // --- Rendering Functions ---

    /**
     * Renders the mini-cart dropdown contents.
     * @param {Array} items - Array of cart items.
     * @param {number} totalQuantity - Total quantity of items.
     * @param {number} totalPrice - Total price of items.
     */
    function renderMiniCartDetails(items, totalQuantity, totalPrice) {
        if (!miniCartItemsContainer || !miniCartTotalQuantity || !miniCartTotalPrice || !miniCartEmptyMessage) {
            console.warn('Mini-cart elements not found, skipping mini-cart rendering.');
            return;
        }

        miniCartTotalQuantity.textContent = formatNumber(totalQuantity);
        miniCartTotalPrice.textContent = `${formatNumber(totalPrice)} تومان`;

        miniCartItemsContainer.innerHTML = ''; // Clear previous items

        if (items && items.length > 0) {
            miniCartEmptyMessage.classList.add('hidden');
            miniCartItemsContainer.classList.remove('hidden');
            if (miniCartCheckoutBtn) miniCartCheckoutBtn.classList.remove('hidden');
            if (miniCartViewCartBtn) miniCartViewCartBtn.classList.remove('hidden');


            items.forEach(item => {
                const itemElement = document.createElement('div');
                itemElement.className = 'flex items-center justify-between py-2 border-b border-gray-200 last:border-b-0';
                itemElement.innerHTML = `
                    <div class="flex items-center">
                        <img src="${item.product.thumbnail_url_small || item.product.image_url || 'https://placehold.co/60x60/E5E7EB/4B5563?text=Product'}"
                             onerror="this.onerror=null;this.src='https://placehold.co/60x60/E5E7EB/4B5563?text=Product';"
                             alt="${item.product.title}" class="w-12 h-12 object-cover rounded-md ml-3">
                        <div>
                            <p class="text-sm font-medium text-gray-800">${item.product.title}</p>
                            <p class="text-xs text-gray-600">${formatNumber(item.quantity)} × ${formatNumber(item.product.price)} تومان</p>
                        </div>
                    </div>
                    <div class="text-sm font-semibold text-green-700">
                        ${formatNumber(item.quantity * item.product.price)} تومان
                    </div>
                `;
                miniCartItemsContainer.appendChild(itemElement);
            });
        } else {
            miniCartEmptyMessage.classList.remove('hidden');
            miniCartItemsContainer.classList.add('hidden');
            if (miniCartCheckoutBtn) miniCartCheckoutBtn.classList.add('hidden');
            if (miniCartViewCartBtn) miniCartViewCartBtn.classList.add('hidden');
        }
    }

    /**
     * Renders the main cart page contents.
     * @param {Array} items - Array of cart items.
     * @param {number} totalQuantity - Total quantity of items.
     * @param {number} totalPrice - Total price of items.
     */
    function renderMainCart(items, totalQuantity, totalPrice) {
        if (!mainCartItemsContainer || !mainCartEmptyMessage || !mainCartSummary || !mainCartTotalPriceElement) {
            console.warn('Main cart elements not found, skipping main cart rendering.');
            return;
        }

        mainCartItemsContainer.innerHTML = ''; // Clear previous items

        if (items && items.length > 0) {
            mainCartEmptyMessage.classList.add('hidden');
            mainCartItemsContainer.classList.remove('hidden');
            mainCartSummary.classList.remove('hidden');

            items.forEach(item => {
                const itemElement = document.createElement('div');
                itemElement.className = 'flex justify-between items-center border-b pb-4 last:border-b-0 last:pb-0';
                itemElement.dataset.itemId = item.id; // Use cart item ID
                itemElement.dataset.itemPrice = item.product.price; // Use product price from item.product
                itemElement.dataset.itemQuantity = item.quantity; // Current quantity
                itemElement.dataset.productStock = item.product.stock; // اضافه شده: موجودی محصول

                itemElement.innerHTML = `
                    <div class="flex items-center">
                        <img src="${item.product.thumbnail_url_small || item.product.image_url || 'https://placehold.co/80x80/E5E7EB/4B5563?text=Product'}"
                             onerror="this.onerror=null;this.src='https://placehold.co/80x80/E5E7EB/4B5563?text=Product';"
                             alt="${item.product.title}" class="w-16 h-16 object-cover rounded-lg ml-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">${item.product.title}</h3>
                            <div class="flex items-center mt-1">
                                <button type="button" class="quantity-btn minus-btn bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-full w-6 h-6 flex items-center justify-center text-lg font-bold transition-colors duration-200" aria-label="کاهش تعداد">
                                    -
                                </button>
                                <span class="item-quantity mx-2 text-gray-700 text-base font-medium" data-quantity="${item.quantity}">
                                    ${formatNumber(item.quantity)}
                                </span>
                                <button type="button" class="quantity-btn plus-btn bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-full w-6 h-6 flex items-center justify-center text-lg font-bold transition-colors duration-200" aria-label="افزایش تعداد">
                                    +
                                </button>
                                <span class="mr-2 text-gray-600 text-sm">عدد</span>
                            </div>
                        </div>
                    </div>
                    <span class="item-subtotal text-green-700 font-bold text-lg" data-subtotal="${item.product.price * item.quantity}">
                        ${formatNumber(item.product.price * item.quantity)} تومان
                    </span>
                `;
                mainCartItemsContainer.appendChild(itemElement);
            });

            // Update main cart total price
            mainCartTotalPriceElement.textContent = `${formatNumber(totalPrice)} تومان`;
            mainCartTotalPriceElement.dataset.totalPrice = totalPrice; // Update data attribute
        } else {
            mainCartEmptyMessage.classList.remove('hidden');
            mainCartItemsContainer.classList.add('hidden');
            mainCartSummary.classList.add('hidden');
        }
    }


    // --- Event Listeners ---

    // Toggle mini-cart dropdown
    if (miniCartBtn && miniCartDropdown) {
        miniCartBtn.addEventListener('click', function() {
            miniCartDropdown.classList.toggle('hidden');
            if (!miniCartDropdown.classList.contains('hidden')) {
                // Fetch and render mini-cart contents when opened
                fetchCartContents().then(data => {
                    renderMiniCartDetails(data.items, data.totalQuantity, data.totalPrice);
                });
            }
        });

        // Close mini-cart when clicking outside
        document.addEventListener('click', function(event) {
            if (!miniCartBtn.contains(event.target) && !miniCartDropdown.contains(event.target)) {
                miniCartDropdown.classList.add('hidden');
            }
        });
    }

    // Add to cart button on product listing pages
    addProductToCartBtns.forEach(button => {
        button.addEventListener('click', async function() {
            const productId = this.dataset.productId;
            const productTitle = this.dataset.productTitle; // For message
            const productPrice = this.dataset.productPrice; // For message

            try {
                const response = await addOrUpdateCartItem(productId, 1); // Add 1 quantity
                // After successful add, update both mini-cart and main cart (if on cart page)
                const updatedCartData = await fetchCartContents();
                renderMiniCartDetails(updatedCartData.items, updatedCartData.totalQuantity, updatedCartData.totalPrice);
                // If on the main cart page, re-render it too
                if (mainCartItemsContainer && mainCartSummary) {
                    renderMainCart(updatedCartData.items, updatedCartData.totalQuantity, updatedCartData.totalPrice);
                }
            } catch (error) {
                // Error message already shown by addOrUpdateCartItem
            }
        });
    });

    // Event listener for quantity buttons in main cart page
    if (mainCartItemsContainer) {
        mainCartItemsContainer.addEventListener('click', async function(event) {
            const target = event.target;
            if (target.classList.contains('quantity-btn')) {
                const itemElement = target.closest('[data-item-id]');
                if (!itemElement) return;

                const itemId = itemElement.dataset.itemId; // This is cart_item_id
                const itemPrice = parseFloat(itemElement.dataset.itemPrice);
                const productStock = parseInt(itemElement.dataset.productStock); // اضافه شده: موجودی محصول
                const quantitySpan = itemElement.querySelector('.item-quantity');
                let currentQuantity = parseInt(quantitySpan.dataset.quantity);
                const itemSubtotalElement = itemElement.querySelector('.item-subtotal');

                let oldQuantity = currentQuantity; // Store old quantity for rollback

                if (target.classList.contains('plus-btn')) {
                    if (currentQuantity < productStock) { // اضافه شده: بررسی موجودی
                        currentQuantity++;
                    } else {
                        window.showMessage(`موجودی کافی برای افزودن بیشتر این محصول وجود ندارد. موجودی فعلی: ${formatNumber(productStock)}`, 'warning');
                        return; // جلوگیری از افزایش بیشتر از موجودی
                    }
                } else if (target.classList.contains('minus-btn')) {
                    if (currentQuantity > 1) { // Prevent quantity from going below 1
                        currentQuantity--;
                    } else {
                        // If quantity goes to 0, remove the item
                        const confirmRemove = confirm('آیا مطمئن هستید که می‌خواهید این محصول را از سبد خرید حذف کنید؟');
                        if (confirmRemove) {
                            try {
                                await removeCartItem(itemId);
                                // Re-fetch and re-render entire cart after removal
                                const updatedCartData = await fetchCartContents();
                                renderMainCart(updatedCartData.items, updatedCartData.totalQuantity, updatedCartData.totalPrice);
                                renderMiniCartDetails(updatedCartData.items, updatedCartData.totalQuantity, updatedCartData.totalPrice);
                                return; // Exit function after removal
                            } catch (error) {
                                // Error message already shown by removeCartItem
                                return; // Exit function on error
                            }
                        } else {
                            return; // User cancelled removal, do nothing
                        }
                    }
                }

                // Update quantity display and data attribute locally first for responsiveness
                quantitySpan.textContent = formatNumber(currentQuantity);
                quantitySpan.dataset.quantity = currentQuantity;

                // Update item subtotal locally
                const newSubtotal = itemPrice * currentQuantity;
                itemSubtotalElement.textContent = `${formatNumber(newSubtotal)} تومان`;
                itemSubtotalElement.dataset.subtotal = newSubtotal;

                // Update the overall cart total locally (will be re-calculated by renderMainCart later)
                // updateCartTotal(); // No longer needed directly here as renderMainCart handles it

                // --- Start AJAX call to update quantity on the server ---
                try {
                    await updateCartItemQuantity(itemId, currentQuantity);
                    // After successful update, re-fetch and re-render entire cart to ensure consistency
                    const updatedCartData = await fetchCartContents();
                    renderMainCart(updatedCartData.items, updatedCartData.totalQuantity, updatedCartData.totalPrice);
                    renderMiniCartDetails(updatedCartData.items, updatedCartData.totalQuantity, updatedCartData.totalPrice);
                } catch (error) {
                    console.error('Error updating cart item quantity:', error);
                    window.showMessage('خطا در ارتباط با سرور. لطفاً اتصال اینترنت خود را بررسی کنید.', 'error');
                    // Revert local quantity if server update fails
                    quantitySpan.textContent = formatNumber(oldQuantity);
                    quantitySpan.dataset.quantity = oldQuantity;
                    itemSubtotalElement.textContent = `${formatNumber(itemPrice * oldQuantity)} تومان`;
                    itemSubtotalElement.dataset.subtotal = itemPrice * oldQuantity;
                    // updateCartTotal(); // Re-calculate total with reverted quantity
                    // Re-render main cart to ensure consistency
                    const updatedCartData = await fetchCartContents();
                    renderMainCart(updatedCartData.items, updatedCartData.totalQuantity, updatedCartData.totalPrice);
                    renderMiniCartDetails(updatedCartData.items, updatedCartData.totalQuantity, updatedCartData.totalPrice);
                }
                // --- End AJAX call to update quantity on the server ---
            }
        });
    }

    // --- Initial Load ---

    // Initial fetch and render for mini-cart (always load on page load for header)
    fetchCartContents().then(data => {
        renderMiniCartDetails(data.items, data.totalQuantity, data.totalPrice);
    });

    // Initial fetch and render for main cart page (only if on cart page)
    if (mainCartItemsContainer && mainCartSummary) {
        fetchCartContents().then(data => {
            renderMainCart(data.items, data.totalQuantity, data.totalPrice);
        });
    }
});
