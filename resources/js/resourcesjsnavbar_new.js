// resources/js/navbar_new.js

document.addEventListener('DOMContentLoaded', function() {
    // === Mini Cart Logic ===
    const miniCartContent = document.getElementById('mini-cart-content');
    const miniCartEmptyMessage = document.getElementById('mini-cart-empty-message');
    const miniCartTotalPriceElement = document.getElementById('mini-cart-total-price');
    const miniCartCountElement = document.getElementById('mini-cart-count'); // Badge for desktop item count
    const mobileCartCountElement = document.getElementById('mobile-cart-count'); // Badge for mobile item count

    async function renderMiniCart() {
        try {
            const response = await fetch('/cart/contents');
            if (!response.ok) {
                throw new Error('Failed to fetch mini cart contents.');
            }
            const data = await response.json();

            const cartItems = data.cartItems;
            const totalPrice = data.totalPrice;
            const totalItemsInCart = data.totalItemsInCart;

            if (miniCartContent) { // Check if miniCartContent exists
                miniCartContent.innerHTML = ''; // Clear current content
            }
            

            if (cartItems.length === 0) {
                if (miniCartEmptyMessage) miniCartEmptyMessage.classList.remove('hidden');
                if (miniCartContent) miniCartContent.classList.add('hidden');
                const miniCartSummary = document.getElementById('mini-cart-summary');
                const miniCartActions = document.getElementById('mini-cart-actions');
                if (miniCartSummary) miniCartSummary.classList.add('hidden');
                if (miniCartActions) miniCartActions.classList.add('hidden');
            } else {
                if (miniCartEmptyMessage) miniCartEmptyMessage.classList.add('hidden');
                if (miniCartContent) miniCartContent.classList.remove('hidden');
                const miniCartSummary = document.getElementById('mini-cart-summary');
                const miniCartActions = document.getElementById('mini-cart-actions');
                if (miniCartSummary) miniCartSummary.classList.remove('hidden');
                if (miniCartActions) miniCartActions.classList.remove('hidden');

                cartItems.forEach(item => {
                    const cartItemDiv = document.createElement('div');
                    cartItemDiv.classList.add('flex', 'items-center', 'p-3', 'border-b', 'border-gray-100', 'last:border-b-0');
                    cartItemDiv.innerHTML = `
                        <img src="${item.product.image ? '/uploads/' + item.product.image : 'https://placehold.co/50x50/E5E7EB/4B5563?text=Product'}" alt="${item.product.title}" class="w-12 h-12 object-cover rounded-md ml-3">
                        <div class="flex-1 text-right">
                            <p class="text-sm font-semibold text-gray-800">${item.product.title}</p>
                            <p class="text-xs text-gray-500">${item.quantity} x ${Number(item.price).toLocaleString()} تومان</p>
                        </div>
                        <button class="remove-item-btn text-red-400 hover:text-red-600 transition-colors duration-200" data-id="${item.id}">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    `;
                    if (miniCartContent) { // Check before appending
                        miniCartContent.appendChild(cartItemDiv);
                    }
                });

                if (miniCartTotalPriceElement) {
                    miniCartTotalPriceElement.textContent = `${Number(totalPrice).toLocaleString()} تومان`;
                }
            }

            // Update mini cart count badge (desktop)
            if (miniCartCountElement) {
                miniCartCountElement.textContent = totalItemsInCart;
                if (totalItemsInCart > 0) {
                    miniCartCountElement.classList.remove('hidden');
                } else {
                    miniCartCountElement.classList.add('hidden');
                }
            }
            // Update mobile cart count badge
            if (mobileCartCountElement) {
                mobileCartCountElement.textContent = totalItemsInCart;
                if (totalItemsInCart > 0) {
                    mobileCartCountElement.style.display = 'block'; // Show if count > 0
                } else {
                    mobileCartCountElement.style.display = 'none'; // Hide if count is 0
                }
            }

        } catch (error) {
            console.error('Error rendering mini cart:', error);
            // window.showMessage('خطا در بارگذاری سبد خرید کوچک.', 'error'); // Uncomment if you want to show messages
        }
    }

    // Attach event listener for removing items from mini cart
    if (miniCartContent) {
        miniCartContent.addEventListener('click', async function(event) {
            const removeButton = event.target.closest('.remove-item-btn');
            if (removeButton) {
                const itemId = removeButton.dataset.id;
                if (!confirm('آیا مطمئن هستید که می‌خواهید این محصول را حذف کنید؟')) {
                    return;
                }

                try {
                    const response = await fetch(`/cart/remove/${itemId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    const result = await response.json();

                    if (response.ok) {
                        window.showMessage(result.message, 'success');
                        renderMiniCart(); // Re-render mini cart
                        // If main cart page is open, refresh it as well
                        if (window.location.pathname === '/cart') {
                            window.location.reload(); // Simple refresh for main cart
                        }
                    } else {
                        window.showMessage(result.message || 'خطا در حذف محصول.', 'error');
                    }
                } catch (error) {
                    console.error('Error removing cart item from mini cart:', error);
                    window.showMessage('خطا در ارتباط با سرور.', 'error');
                }
            }
        });
    }

    // Initial render of mini cart on page load
    renderMiniCart();

    // === Add to Cart Button Integration ===
    // This part ensures that when you add to cart from home/products page,
    // the mini cart in the navbar also updates.
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', async function() {
            // Your existing add to cart logic (from home.blade.php/products.blade.php)
            // After successful addition:
            // window.showMessage(result.message, 'success'); // This is already there
            renderMiniCart(); // Call to update the mini cart
        });
    });

    // === Ensure main cart updates mini cart too ===
    // This is a workaround to ensure when the main cart (cart.blade.php) changes,
    // the mini cart in the navbar updates.
    // It assumes renderMiniCart is global or can be accessed.
    // A more robust solution involves a shared event bus or state management.
    if (window.renderMainCart) { // If renderMainCart is globally accessible from cart.blade.php
        const originalRenderMainCart = window.renderMainCart;
        window.renderMainCart = async function() {
            await originalRenderMainCart();
            renderMiniCart(); // Also update the mini cart after main cart re-renders
        };
    }
});
