// Get references to DOM elements for mini cart functionality
const cartIconContainer = document.getElementById('cart-icon-container');
const cartItemCountSpan = document.getElementById('cart-item-count'); // This is the count in the header for both
const miniCartDropdown = document.getElementById('mini-cart-dropdown');
const miniCartContent = document.getElementById('mini-cart-content');
const miniCartEmptyMessage = document.getElementById('mini-cart-empty-message');
const miniCartTotalPriceSpan = document.getElementById('mini-cart-total-price');
const miniCartSummary = document.getElementById('mini-cart-summary');
const miniCartActions = document.getElementById('mini-cart-actions');

// Get references for main cart functionality (only if on cart page)
const cartItemsContainer = document.getElementById('cart-items-container');
const cartTotalPriceSpan = document.getElementById('cart-total-price');
const cartEmptyMessage = document.getElementById('cart-empty-message');
const cartContent = document.getElementById('cart-content');


// Get references for search functionality
const searchAreaWrapper = document.getElementById('search-area-wrapper');
const searchToggleButton = document.getElementById('search-toggle-btn');
const searchIconInitial = document.getElementById('search-icon-initial');
const searchIconClose = document.getElementById('search-icon-close');
const liveSearchInput = document.getElementById('live-search-input');
const liveSearchResultsContainer = document.getElementById('live-search-results-container');
// liveSearchNoResults will be dynamically added inside liveSearchResultsContainer
// and we'll manage its visibility directly through renderSearchResults function

// Get references for auth modal functionality
const authToggleButton = document.getElementById('auth-toggle-btn');
const authModalOverlay = document.getElementById('auth-modal-overlay');
const authModalCloseBtn = document.getElementById('auth-modal-close-btn');

// New Auth Modal Elements
const mobileLoginStep = document.getElementById('mobile-login-step');
const smsVerifyStep = document.getElementById('sms-verify-step');
const mobileNumberInput = document.getElementById('mobile-number');
const otpCodeInput = document.getElementById('otp-code');
const getOtpBtn = document.getElementById('get-otp-btn');
const verifyOtpBtn = document.getElementById('verify-otp-btn');
const resendOtpBtn = document.getElementById('resend-otp-btn');
const changeMobileBtn = document.getElementById('change-mobile-btn');
const displayMobileNumberSpan = document.getElementById('display-mobile-number');

// Initialize cart from Local Storage
let cart = JSON.parse(localStorage.getItem('teaCart')) || [];

// Sample product data for search (expanded from existing products)
const searchableItems = [
    {
        id: 'dabesh',
        name: 'چای دبش ممتاز',
        description: 'چای سیاه قلم ممتاز با طعم و رنگ بی‌نظیر. انتخابی عالی برای دوستداران چای اصیل.',
        price: 110000,
        image: 'https://placehold.co/400x300/F0F4C3/212121?text=محصول+۱'
    },
    {
        id: 'earl-grey',
        name: 'چای ارل گری',
        description: 'چای سیاه معطر با اسانس طبیعی برگاموت. عطری دلنشین و طعمی خاص برای لحظات آرامش.',
        price: 135000,
        image: 'https://placehold.co/400x300/F0F4C3/212121?text=محصول+۲'
    },
    {
        id: 'lemon-balm',
        name: 'دمنوش به لیمو',
        description: 'دمنوش آرام‌بخش با طعم دلپذیر به لیمو. مناسب برای کاهش استرس و بهبود خواب.',
        price: 70000,
        image: 'https://placehold.co/400x300/F0F4C3/212121?text=محصول+۳'
    },
    {
        id: 'black-tea',
        name: 'چای سیاه',
        description: 'عطر و طعم بی‌نظیر چای سیاه ایرانی، مناسب برای هر لحظه روز و پذیرایی از مهمانان.',
        price: 95000,
        image: 'https://placehold.co/500x350/a77a62/fcf8f5?text=چای+سیاه'
    },
    {
        id: 'green-tea',
        name: 'چای سبز',
        description: 'چای سبز سرشار از خواص طبیعی، طراوت و انرژی. انتخابی سالم و دلچسب.',
        price: 120000,
        image: 'https://placehold.co/500x350/789a7f/fcf8f5?text=چای+سبز'
    },
    {
        id: 'herbal-infusion',
        name: 'چای دمنوش‌ها',
        description: 'مجموعه‌ای از دمنوش‌های آرامش‌بخش و مفید، از دل طبیعت. طعمی متفاوت برای سلامتی شما.',
        price: 80000,
        image: 'https://placehold.co/500x350/b08f83/fcf8f5?text=دمنوش‌ها'
    },
    {
        id: 'white-tea',
        name: 'چای سفید',
        description: 'چای سفید، ظریف و کمیاب، تجربه‌ای لوکس و خاص. کمترین فرآوری و بیشترین خواص.',
        price: 250000,
        image: 'https://placehold.co/500x350/EFEFEF/666666?text=چای+سفید'
    },
    {
        id: 'oolong-tea',
        name: 'چای اولانگ',
        description: 'ترکیبی بی‌نظیر از طعم‌های چای سبز و سیاه. حد وسط بین چای سیاه و سبز.',
        price: 180000,
        image: 'https://placehold.co/500x350/EFEFEF/666666?text=چای+اولانگ'
    },
    {
        id: 'fruit-tea',
        name: 'چای میوه‌ای',
        description: 'ترکیب طعم‌های شیرین و تازه میوه با چای. طعمی شاداب‌کننده و معطر.',
        price: 100000,
        image: 'https://placehold.co/500x350/EFEFEF/666666?text=چای+میوه‌ای'
    },
    {
        id: 'aromatic-tea',
        name: 'چای عطری',
        description: 'چای با رایحه‌های دلنشین و آرام‌بخش طبیعی. مناسب برای شروع یک روز دلپذیر.',
        price: 140000,
        image: 'https://placehold.co/500x350/EFEFEF/666666?text=چای+عطری'
    }
];


// Function to format price with commas and "تومان"
function formatPrice(price) {
    return price.toLocaleString('fa-IR') + ' تومان';
}

// Function to update the cart item count displayed in the header and mini-cart
function updateCartCount() {
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    if (cartItemCountSpan) { // Check if element exists before updating
        cartItemCountSpan.textContent = totalItems;
    }
}

// Function to display a temporary message (IMPROVED VERSION)
function showMessage(message, type = 'success') {
    // Attempt to remove any existing message box to prevent duplicates
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
        iconClass = 'fa-times-circle'; // Changed to times-circle for error
        bgColorClass = 'bg-red-600';
    } else if (type === 'info') {
        iconClass = 'fa-info-circle';
        bgColorClass = 'bg-blue-600';
    } else { // Default to success
        iconClass = 'fa-check-circle';
        bgColorClass = 'bg-green-800';
    }

    messageBox.classList.add(bgColorClass);
    messageBox.innerHTML = `
        <i class="fas ${iconClass} ml-2"></i>
        <span>${message}</span>
    `;

    document.body.appendChild(messageBox);

    // Trigger animation
    setTimeout(() => {
        messageBox.classList.remove('-translate-y-full', 'opacity-0');
        messageBox.classList.add('translate-y-0', 'opacity-100');
    }, 10); // Small delay to allow reflow

    // Hide and remove after a few seconds
    setTimeout(() => {
        messageBox.classList.remove('translate-y-0', 'opacity-100');
        messageBox.classList.add('-translate-y-full', 'opacity-0');
        messageBox.addEventListener('transitionend', () => messageBox.remove());
    }, type === 'info' ? 5000 : 3000); // Keep info messages longer
}

// Function to render mini cart items
function renderMiniCart() {
    if (!miniCartContent) return; // Ensure miniCartContent exists

    miniCartContent.innerHTML = ''; // Clear existing mini cart items
    let miniCartTotalPrice = 0;

    if (cart.length === 0) {
        miniCartEmptyMessage.classList.remove('hidden');
        miniCartSummary.classList.add('hidden');
        miniCartActions.classList.add('hidden');
    } else {
        miniCartEmptyMessage.classList.add('hidden');
        miniCartSummary.classList.remove('hidden');
        miniCartActions.classList.remove('hidden');

        cart.forEach(item => {
            const miniItemDiv = document.createElement('div');
            miniItemDiv.classList.add('mini-cart-item');

            const itemSubtotal = item.price * item.quantity;
            miniCartTotalPrice += itemSubtotal;

            miniItemDiv.innerHTML = `
                <img src="${item.image}" alt="${item.name}" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2248%22%20height%3D%2248%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ccc%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2212%22%20fill%3D%22%23666%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%3E%D8%B9%DA%A9%D8%B3%3C%2Ftext%3E%3C%2Fsvg%3E';">
                <div class="mini-cart-details">
                    <div class="mini-cart-name">${item.name}</div>
                    <div class="mini-cart-price">${formatPrice(item.price)}</div>
                </div>
                <div class="mini-cart-quantity-control">
                    <button data-id="${item.id}" data-action="decrease">-</button>
                    <span>${item.quantity}</span>
                    <button data-id="${item.id}" data-action="increase">+</button>
                </div>
                <button data-id="${item.id}" data-action="remove" class="mini-cart-remove-btn">
                    <i class="fas fa-times"></i>
                </button>
            `;
            miniCartContent.appendChild(miniItemDiv);
        });
    }
    if (miniCartTotalPriceSpan) { // Ensure miniCartTotalPriceSpan exists
        miniCartTotalPriceSpan.textContent = formatPrice(miniCartTotalPrice);
    }
    updateCartCount(); // Ensure main cart count is updated too
}

// Function to render main cart items (for cart.blade.php)
function renderCart() {
    // Only execute if we are on the cart page (i.e., cartItemsContainer exists)
    if (!cartItemsContainer) {
        console.log("Not on cart page, skipping renderCart.");
        return;
    }

    console.log("renderCart() called. Current cart:", cart); // Debugging line

    cartItemsContainer.innerHTML = ''; // Clear existing items
    let totalPrice = 0;

    if (cart.length === 0) {
        console.log("Cart is empty. Hiding cart content, showing empty message."); // Debugging line
        cartEmptyMessage.classList.remove('hidden');
        cartContent.classList.add('hidden');
    } else {
        console.log("Cart has items. Showing cart content, hiding empty message."); // Debugging line
        cartEmptyMessage.classList.add('hidden');
        cartContent.classList.remove('hidden'); // This is the key line to show the table

        cart.forEach(item => {
            const row = document.createElement('tr');
            row.classList.add('border-b', 'border-gray-200'); // Ensure border for each row

            const itemSubtotal = item.price * item.quantity;
            totalPrice += itemSubtotal;

            row.innerHTML = `
                <td class="py-4 px-3 flex items-center">
                    <img src="${item.image}" alt="${item.name}" class="cart-product-image" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2264%22%20height%3D%2264%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ccc%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2215%22%20fill%3D%22%23666%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%3E%D8%B9%DA%A9%D8%B3%3C%2Ftext%3E%3C%2Fsvg%3E';">
                    <span class="font-semibold text-brown-900 text-base md:text-lg">${item.name}</span>
                </td>
                <td class="py-4 px-3 text-sm md:text-base text-gray-800">${formatPrice(item.price)}</td>
                <td class="py-4 px-3">
                    <div class="quantity-control">
                        <button data-id="${item.id}" data-action="decrease">-</button>
                        <span class="text-gray-800">${item.quantity}</span>
                        <button data-id="${item.id}" data-action="increase">+</button>
                    </div>
                </td>
                <td class="py-4 px-3 text-sm md:text-base font-bold text-green-800">${formatPrice(itemSubtotal)}</td>
                <td class="py-4 px-3 text-center">
                    <button data-id="${item.id}" data-action="remove" class="text-red-600 hover:text-red-800 transition-colors duration-200 p-2 rounded-full hover:bg-red-50">
                        <i class="fas fa-trash-alt text-lg"></i>
                    </button>
                </td>
            `;
            cartItemsContainer.appendChild(row);
        });
    }
    cartTotalPriceSpan.textContent = formatPrice(totalPrice);
    updateCartCount(); // Update header cart count as well
}

// Function to update cart item quantity (shared by both mini and main cart)
function updateQuantity(productId, action) {
    const itemIndex = cart.findIndex(item => item.id === productId);
    if (itemIndex > -1) {
        if (action === 'increase') {
            cart[itemIndex].quantity += 1;
        } else if (action === 'decrease') {
            cart[itemIndex].quantity -= 1;
            if (cart[itemIndex].quantity <= 0) {
                cart.splice(itemIndex, 1); // Remove item if quantity is 0 or less
            }
        }
        localStorage.setItem('teaCart', JSON.stringify(cart));
        renderMiniCart(); // Re-render mini cart
        // Also re-render main cart if we are on the cart page
        if (cartItemsContainer) {
            renderCart();
        }
    }
}

// Function to add a product to the cart
function addProductToCart(product) {
    const existingProductIndex = cart.findIndex(item => item.id === product.id);

    if (existingProductIndex > -1) {
        cart[existingProductIndex].quantity += 1;
    } else {
        cart.push({ ...product, quantity: 1 });
    }
    localStorage.setItem('teaCart', JSON.stringify(cart));
    renderMiniCart();
    // If on the cart page, also re-render the main cart
    if (cartItemsContainer) {
        renderCart();
    }
    showMessage(`"${product.name}" به سبد خرید اضافه شد!`);
}

// Event delegation for "افزودن" buttons (Add to Cart)
// This listener will now also capture clicks from dynamically added search results
document.body.addEventListener('click', (event) => {
    const targetButton = event.target.closest('.add-to-cart-btn');
    if (targetButton) {
        const productId = targetButton.dataset.productId;
        const productName = targetButton.dataset.productName;
        const productPrice = parseInt(targetButton.dataset.productPrice);
        const productImage = targetButton.dataset.productImage;

        const productToAdd = {
            id: productId,
            name: productName,
            price: productPrice,
            image: productImage
        };
        addProductToCart(productToAdd);
    }
});

// Event delegation for quantity and remove buttons in the mini cart
if (miniCartDropdown) { // Check if miniCartDropdown exists before adding listener
    miniCartDropdown.addEventListener('click', (event) => {
        const target = event.target;
        const clickedButton = target.closest('button');

        if (clickedButton) {
            const productId = clickedButton.dataset.id;
            const action = clickedButton.dataset.action;

            if (productId && action) {
                if (action === 'remove') {
                    const itemIndex = cart.findIndex(item => item.id === productId);
                    if (itemIndex > -1) {
                        cart.splice(itemIndex, 1);
                        localStorage.setItem('teaCart', JSON.stringify(cart));
                        renderMiniCart(); // Only re-render mini cart initially
                        // Also update main cart if we are on the cart page
                        if (cartItemsContainer) {
                            renderCart();
                        }
                        showMessage('محصول از سبد خرید حذف شد.', 'error');
                    }
                } else if (action === 'increase' || action === 'decrease') {
                    updateQuantity(productId, action);
                }
            }
        }
    });
}

// Event delegation for quantity and remove buttons in the main cart
if (cartItemsContainer) { // Only add listener if main cart container exists (i.e., on cart page)
    cartItemsContainer.addEventListener('click', (event) => {
        const target = event.target;
        const clickedButton = target.closest('button');

        if (clickedButton) {
            const productId = clickedButton.dataset.id;
            const action = clickedButton.dataset.action;

            if (productId && action) {
                if (action === 'remove') {
                    // Replace browser's confirm with a custom message box
                    showMessage('آیا از حذف این محصول از سبد خرید مطمئن هستید؟', 'info');
                    // For a real custom modal, you would show the modal here
                    // and handle the actual removal inside the modal's confirm callback.
                    // For now, we'll proceed directly as per simplified showMessage behavior.

                    const itemIndex = cart.findIndex(item => item.id === productId);
                    if (itemIndex > -1) {
                        cart.splice(itemIndex, 1);
                        localStorage.setItem('teaCart', JSON.stringify(cart));
                        renderCart();
                        renderMiniCart(); // Update mini cart as well
                        showMessage('محصول از سبد خرید حذف شد.', 'success'); // Change to success after removal
                    }
                } else if (action === 'increase' || action === 'decrease') {
                    updateQuantity(productId, action);
                }
            }
        }
    });
}


// Mini Cart Hover Logic
let hideTimeout;

if (cartIconContainer) { // Check if cartIconContainer exists before adding listener
    cartIconContainer.addEventListener('mouseenter', () => {
        clearTimeout(hideTimeout);
        renderMiniCart(); // Render mini cart content when mouse enters
        if (miniCartDropdown) { // Ensure miniCartDropdown exists
            miniCartDropdown.classList.add('show');
        }
    });

    cartIconContainer.addEventListener('mouseleave', () => {
        hideTimeout = setTimeout(() => {
            if (miniCartDropdown) { // Ensure miniCartDropdown exists
                miniCartDropdown.classList.remove('show');
            }
        }, 300); // Delay hiding to allow for quick re-entry or slight mouse deviation
    });
}

if (miniCartDropdown) { // Check if miniCartDropdown exists before adding listener
    miniCartDropdown.addEventListener('mouseenter', () => {
        clearTimeout(hideTimeout); // Keep mini cart open if mouse re-enters it
    });

    miniCartDropdown.addEventListener('mouseleave', () => {
        hideTimeout = setTimeout(() => {
            if (miniCartDropdown) { // Ensure miniCartDropdown exists
                miniCartDropdown.classList.remove('show');
            }
        }, 300); // Delay hiding
    });
}

// Event listeners for carousel (if carousel is on home page)
const carousel = document.getElementById('product-carousel');
const prevButton = document.getElementById('carousel-prev');
const nextButton = document.getElementById('carousel-next');

let scrollInterval;
let effectiveItemWidth = 0;
const gapSize = 24;

function calculateEffectiveItemWidth() {
    if (!carousel) return; // Ensure carousel exists

    const firstItem = carousel.querySelector('.carousel-item');
    if (firstItem) {
        const style = window.getComputedStyle(firstItem);
        const marginRight = parseInt(style.marginRight) || gapSize;
        effectiveItemWidth = firstItem.offsetWidth + marginRight;
    } else {
        effectiveItemWidth = 350; // Fallback
    }
}

function scrollCarousel(amount) {
    if (!carousel || !prevButton || !nextButton) return; // Ensure elements exist

    const maxScrollLeft = carousel.scrollWidth - carousel.clientWidth;

    if (maxScrollLeft <= 1) { // No scrollbar or not enough content to scroll
        stopAutoScroll();
        prevButton.disabled = true;
        nextButton.disabled = true;
        return;
    } else {
        prevButton.disabled = false;
        nextButton.disabled = false;
    }

    let needsInstantJump = false;
    let targetScrollLeft = carousel.scrollLeft + amount;

    if (amount > 0) { // Scrolling right (next)
        if (targetScrollLeft >= maxScrollLeft - 10) { // Near end, jump to start
            carousel.style.scrollBehavior = 'auto';
            carousel.scrollLeft = 0;
            needsInstantJump = true;
        } else {
            carousel.scrollLeft = targetScrollLeft;
        }
    } else { // Scrolling left (previous)
        if (targetScrollLeft <= 10) { // Near start, jump to end
            carousel.style.scrollBehavior = 'auto';
            carousel.scrollLeft = maxScrollLeft;
            needsInstantJump = true;
        } else {
            carousel.scrollLeft = targetScrollLeft;
        }
    }

    if (needsInstantJump) {
        requestAnimationFrame(() => {
            carousel.style.scrollBehavior = 'smooth';
        });
    }
}

function startAutoScroll() {
    if (!carousel) return; // Ensure carousel exists
    if (scrollInterval) clearInterval(scrollInterval);
    scrollInterval = setInterval(() => {
        scrollCarousel(effectiveItemWidth);
    }, 3000);
}

function stopAutoScroll() {
    clearInterval(scrollInterval);
}

if (prevButton) { // Check if prevButton exists before adding listener
    prevButton.addEventListener('click', () => {
        stopAutoScroll();
        scrollCarousel(-effectiveItemWidth);
        setTimeout(startAutoScroll, 2000);
    });
}

if (nextButton) { // Check if nextButton exists before adding listener
    nextButton.addEventListener('click', () => {
        stopAutoScroll();
        scrollCarousel(effectiveItemWidth);
        setTimeout(startAutoScroll, 2000);
    });
}

if (carousel) { // Check if carousel exists before adding listeners
    carousel.addEventListener('mouseenter', stopAutoScroll);
    carousel.addEventListener('mouseleave', startAutoScroll);
}


window.addEventListener('load', () => {
    setTimeout(() => {
        calculateEffectiveItemWidth();
        if (carousel) { // Ensure carousel exists
            carousel.scrollLeft = 0; // Ensure it starts from the beginning (RTL direction)
        }
        startAutoScroll();
    }, 100);
    updateAuthButtonState(); // Check auth status on load
});

window.addEventListener('resize', () => {
    calculateEffectiveItemWidth();
    if (carousel) { // Ensure carousel exists
        const maxScrollLeft = carousel.scrollWidth - carousel.clientWidth;
        if (maxScrollLeft > 0) {
            // Adjust scroll position to snap to an item if necessary
            const currentScrollPosition = carousel.scrollLeft;
            const closestItemIndex = Math.round(currentScrollPosition / effectiveItemWidth);
            carousel.scrollLeft = closestItemIndex * effectiveItemWidth;
        }
    }
});


// Initial render of mini cart on page load
document.addEventListener('DOMContentLoaded', () => {
    renderMiniCart(); // Render mini cart content
    updateAuthButtonState(); // Update authentication button state

    // Also render the main cart if we are on the cart page
    if (cartItemsContainer) {
        renderCart();
    }

    // Log warnings if critical search elements are missing
    if (!searchAreaWrapper) {
        console.warn('Search area wrapper element with ID "search-area-wrapper" not found. Search functionality may not work.');
    }
    if (!searchToggleButton) {
        console.warn('Search toggle button element with ID "search-toggle-btn" not found. Search functionality may not work.');
    }
    if (!liveSearchInput) {
        console.warn('Live search input element with ID "live-search-input" not found. Search functionality may not work.');
    }
    if (!liveSearchResultsContainer) {
        console.warn('Live search results container element with ID "live-search-results-container" not found. Search functionality may not work.');
    }
});

// --- Search Functionality ---

if (searchToggleButton) { // Check if searchToggleButton exists before adding listener
    searchToggleButton.addEventListener('click', (event) => {
        event.preventDefault(); // Prevent default button behavior
        // Toggle the 'active' class on the search area wrapper
        if (searchAreaWrapper) { // Ensure searchAreaWrapper exists
            searchAreaWrapper.classList.toggle('active');
        }

        // Toggle visibility of magnify and close icons
        if (searchIconInitial) { searchIconInitial.classList.toggle('hidden'); }
        if (searchIconClose) { searchIconClose.classList.toggle('hidden'); }

        if (liveSearchResultsContainer) { // Ensure liveSearchResultsContainer exists
             // Toggle the 'show' class to control visibility
            liveSearchResultsContainer.classList.toggle('show');
            // If it's now shown, focus on the input and potentially render initial prompt
            if (liveSearchResultsContainer.classList.contains('show')) {
                if (liveSearchInput) {
                    liveSearchInput.focus();
                }
                renderSearchResults([], ''); // Show initial prompt when container becomes visible
            } else {
                // If it's hidden, clear input and results
                if (liveSearchInput) {
                    liveSearchInput.value = '';
                }
                liveSearchResultsContainer.innerHTML = ''; // Clear results when hidden
            }
        }
    });
}


// Function to perform search
function performSearch() {
    if (!liveSearchInput || !liveSearchResultsContainer) return; // Ensure elements exist

    const query = liveSearchInput.value.trim().toLowerCase();
    let results = [];

    if (query.length > 0) {
        const queryWords = query.split(/\s+/).filter(word => word.length > 0);
        results = searchableItems.filter(item => {
            const name = item.name.toLowerCase();
            const description = item.description.toLowerCase();
            return queryWords.every(word => name.includes(word) || description.includes(word));
        });
    }
    renderSearchResults(results, query);
}

// Function to render search results
function renderSearchResults(results, query) {
    if (!liveSearchResultsContainer) return; // Ensure liveSearchResultsContainer exists

    liveSearchResultsContainer.innerHTML = ''; // Clear previous results

    if (results.length === 0 && query.length > 0) {
        liveSearchResultsContainer.innerHTML = `
            <div class="no-results text-center py-4 text-gray-500">
                <i class="fas fa-box-open text-gray-400 text-3xl mb-2 block"></i>
                <p>نتیجه‌ای یافت نشد.</p>
                <p class="text-gray-500 mt-1">لطفاً کلمه کلیدی دیگری را امتحان کنید.</p>
            </div>
        `;
    } else if (results.length === 0 && query.length === 0) {
        // Initial state or cleared input, show prompt
        liveSearchResultsContainer.innerHTML = `
            <div class="no-results text-center py-4 text-gray-500">
                <i class="fas fa-search text-gray-400 text-3xl mb-2 block"></i>
                <p>برای شروع جستجو، چیزی تایپ کنید.</p>
                <p class="text-gray-500 mt-1">محصولات مورد نظر خود را در اینجا پیدا کنید.</p>
            </div>
        `;
    } else {
        results.forEach(item => {
            const itemDiv = document.createElement('div');
            itemDiv.classList.add('search-result-item');
            itemDiv.innerHTML = `
                <img src="${item.image}" alt="${item.name}" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2250%22%20height%3D%2250%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23ccc%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2212%22%20fill%3D%22%23666%22%20text-anchor%3D%22middle%22%20dominant-baseline%3D%22middle%22%3E%D8%AA%D8%B5%D9%88%DB%8C%D8%B1%3C%2Ftext%3E%3C%2Fsvg%3E';">
                <div class="search-result-item-details">
                    <h4>${item.name}</h4>
                    <span class="price">${formatPrice(item.price)}</span>
                </div>
                <button class="add-to-cart-btn" data-product-id="${item.id}" data-product-name="${item.name}" data-product-price="${item.price}" data-product-image="${item.image}">
                    <i class="fas fa-plus-circle ml-1"></i> افزودن
                </button>
            `;
            liveSearchResultsContainer.appendChild(itemDiv);
        });
    }
}

// Event listener for search input
if (liveSearchInput) { // Check if liveSearchInput exists before adding listener
    liveSearchInput.addEventListener('input', performSearch);
}


// Close results if clicked outside search area or if search input loses focus (except when clicking a result)
document.addEventListener('click', (event) => {
    const isClickInsideSearchArea = searchAreaWrapper && searchAreaWrapper.contains(event.target);
    const isClickInsideResults = liveSearchResultsContainer && liveSearchResultsContainer.contains(event.target);

    if (!isClickInsideSearchArea && !isClickInsideResults && searchAreaWrapper && searchAreaWrapper.classList.contains('active')) {
        searchAreaWrapper.classList.remove('active');
        if (searchIconInitial) { searchIconInitial.classList.remove('hidden'); }
        if (searchIconClose) { searchIconClose.classList.add('hidden'); }
        if (liveSearchInput) { liveSearchInput.value = ''; }
        if (liveSearchResultsContainer) { liveSearchResultsContainer.classList.remove('show'); }
    }
});

// Close search if Escape is pressed
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && searchAreaWrapper && searchAreaWrapper.classList.contains('active')) {
        searchAreaWrapper.classList.remove('active');
        if (searchIconInitial) { searchIconInitial.classList.remove('hidden'); }
        if (searchIconClose) { searchIconClose.classList.add('hidden'); }
        if (liveSearchInput) { liveSearchInput.value = ''; }
        if (liveSearchResultsContainer) { liveSearchResultsContainer.classList.remove('show'); }
    }
});

// --- Auth Modal Functionality ---
let loggedInUser = sessionStorage.getItem('loggedInUser'); // Check if a user is already logged in

// Function to update the auth button text (ورود/ثبت نام or خروج)
function updateAuthButtonState() {
    if (!authToggleButton) return; // Ensure authToggleButton exists

    loggedInUser = sessionStorage.getItem('loggedInUser');
    if (loggedInUser) {
        authToggleButton.textContent = 'خروج';
        authToggleButton.classList.remove('bg-green-800');
        authToggleButton.classList.add('bg-red-600');
        authToggleButton.classList.add('hover:bg-red-700');
    } else {
        authToggleButton.textContent = 'ورود/ثبت نام';
        authToggleButton.classList.remove('bg-red-600');
        authToggleButton.classList.remove('hover:bg-red-700');
        authToggleButton.classList.add('bg-green-800');
        authToggleButton.classList.add('hover:bg-green-700');
    }
}

// Show Auth Modal
function showAuthModal() {
    if (!authModalOverlay) return; // Ensure authModalOverlay exists

    authModalOverlay.classList.add('show');
    // Initially show the mobile login step
    if (mobileLoginStep) {
        mobileLoginStep.classList.remove('hidden');
    }
    if (smsVerifyStep) {
        smsVerifyStep.classList.add('hidden');
    }
    if (mobileNumberInput) { // Clear input on modal show
        mobileNumberInput.value = '';
    }
}

// Hide Auth Modal
function hideAuthModal() {
    if (authModalOverlay) { authModalOverlay.classList.remove('show'); }
    if (mobileLoginStep) { mobileLoginStep.reset(); }
    if (smsVerifyStep) { smsVerifyStep.reset(); }
    // Ensure only one step is visible when modal is shown next time
    if (mobileLoginStep) { mobileLoginStep.classList.remove('hidden'); }
    if (smsVerifyStep) { smsVerifyStep.classList.add('hidden'); }
}

// Event listener for Auth Toggle Button
if (authToggleButton) { // Check if authToggleButton exists before adding listener
    authToggleButton.addEventListener('click', (event) => {
        event.preventDefault();
        if (loggedInUser) {
            // Perform Logout
            sessionStorage.removeItem('loggedInUser');
            // Remove user data from localStorage that was stored by registration form
            localStorage.removeItem('loggedInUserFullData'); // Clear full user data too
            updateAuthButtonState();
            showMessage('با موفقیت از حساب کاربری خود خارج شدید.', 'success');
        } else {
            // Show Auth Modal
            showAuthModal();
        }
    });
}


// Event listener for closing modal
if (authModalCloseBtn) { // Check if authModalCloseBtn exists before adding listener
    authModalCloseBtn.addEventListener('click', hideAuthModal);
}
if (authModalOverlay) { // Check if authModalOverlay exists before adding listener
    authModalOverlay.addEventListener('click', (event) => {
        if (event.target === authModalOverlay) { // Close only if clicked on overlay itself, not content
            hideAuthModal();
        }
    });
}

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && authModalOverlay && authModalOverlay.classList.contains('show')) {
        hideAuthModal();
    }
});

// --- New Multi-step Auth Modal Logic ---

// Simulated OTP (One-Time Password) for demonstration
let simulatedOtp = '';
let currentMobileNumber = ''; // Store mobile number during OTP process

// Mock User Data (in a real app, this would be handled by a backend database)
// We'll use localStorage for persistent storage across sessions for demo purposes.
// isProfileComplete will track if user has filled out address, etc.
const defaultUsers = [
    { username: "testuser", password: "password", role: "کاربر" }, // Legacy user
    { username: "admin", password: "adminpassword", role: "مدیر کل" }, // Legacy admin
];

// Function to get users from localStorage, merge with default, or return default
function getStoredUsers() {
    const storedUsers = JSON.parse(localStorage.getItem('registeredUsers')) || {};
    // Merge default users (like admin) with dynamically registered users
    const allUsers = { ...storedUsers };
    defaultUsers.forEach(user => {
        if (!allUsers[user.username]) { // Add if not already present by username
            allUsers[user.username] = user;
        }
    });
    return allUsers;
}


// Step 1: Mobile Number Submission
if (mobileLoginStep) {
    mobileLoginStep.addEventListener('submit', async (e) => {
        e.preventDefault();
        const mobileNumber = mobileNumberInput.value.trim();

        if (!mobileNumber) {
            showMessage('لطفاً شماره موبایل را وارد کنید.', 'error');
            return;
        }
        const phoneRegex = /^09[0-9]{9}$/;
        if (!phoneRegex.test(mobileNumber)) {
            showMessage('فرمت شماره موبایل صحیح نیست. (مثال: 09123456789)', 'error');
            return;
        }

        // Simulate sending OTP
        getOtpBtn.disabled = true;
        getOtpBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال ارسال...';
        showMessage(`در حال ارسال کد تایید به ${mobileNumber}...`, 'info');

        try {
            // In a real app, you would send a request to your backend here
            // const response = await fetch('/api/send-otp', { method: 'POST', body: JSON.stringify({ mobileNumber }) });
            // const data = await response.json();
            await new Promise(resolve => setTimeout(resolve, 1500)); // Simulate API call delay

            // For demo: Generate a fixed OTP or a random one
            simulatedOtp = '1234'; // Fixed OTP for easy testing
            // simulatedOtp = Math.floor(1000 + Math.random() * 9000).toString(); // Random 4-digit OTP

            currentMobileNumber = mobileNumber; // Store for verification step
            displayMobileNumberSpan.textContent = mobileNumber;
            
            mobileLoginStep.classList.add('hidden');
            smsVerifyStep.classList.remove('hidden');
            otpCodeInput.value = ''; // Clear previous OTP input
            otpCodeInput.focus();

            showMessage(`کد تایید ${simulatedOtp} به شماره ${mobileNumber} ارسال شد.`, 'success');

        } catch (error) {
            console.error('Error sending OTP:', error);
            showMessage('خطا در ارسال کد تایید. لطفاً دوباره تلاش کنید.', 'error');
        } finally {
            getOtpBtn.disabled = false;
            getOtpBtn.innerHTML = 'دریافت کد تایید';
        }
    });
}

// Step 2: SMS Verification
if (smsVerifyStep) {
    smsVerifyStep.addEventListener('submit', async (e) => {
        e.preventDefault();
        const otpCode = otpCodeInput.value.trim();

        if (!otpCode) {
            showMessage('لطفاً کد تایید را وارد کنید.', 'error');
            return;
        }

        verifyOtpBtn.disabled = true;
        verifyOtpBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال تایید...';

        try {
            // In a real app, send mobileNumber and otpCode to backend for verification
            // const response = await fetch('/api/verify-otp', { method: 'POST', body: JSON.stringify({ mobileNumber: currentMobileNumber, otpCode }) });
            // const data = await response.json();
            await new Promise(resolve => setTimeout(resolve, 1500)); // Simulate API call delay

            if (otpCode === simulatedOtp) {
                showMessage('تایید کد با موفقیت انجام شد.', 'success');

                // Simulate user login/registration based on mobile number
                let users = getStoredUsers(); // Get all users, including those registered via the registration form

                // Check if this mobile number already has a profile (from registration page)
                let foundUser = Object.values(users).find(user => user.phoneNumber === currentMobileNumber);

                // For testing admin login via mobile, using a specific mock mobile number
                if (currentMobileNumber === '09121234567') { // Mock mobile for admin
                    foundUser = { username: 'admin', role: 'مدیر کل', phoneNumber: currentMobileNumber, isProfileComplete: true };
                } else if (!foundUser) {
                    // If mobile number is new, simulate a new user account
                    foundUser = {
                        username: `user_${currentMobileNumber}`, // Or a generated username
                        role: 'کاربر',
                        phoneNumber: currentMobileNumber,
                        isProfileComplete: false, // New users need to complete profile
                        id: Date.now().toString() // Simple unique ID
                    };
                    // Store the new user in localStorage (simulated backend)
                    users[foundUser.username] = foundUser;
                    localStorage.setItem('registeredUsers', JSON.stringify(users));
                }

                sessionStorage.setItem('loggedInUser', JSON.stringify({ username: foundUser.username, role: foundUser.role, phoneNumber: foundUser.phoneNumber }));
                localStorage.setItem('loggedInUserFullData', JSON.stringify(foundUser)); // Store full user data for profile completion check
                updateAuthButtonState();
                hideAuthModal();

                // *** تغییر مهم: همیشه به صفحه اصلی هدایت شود، مگر اینکه مدیر باشد ***
                if (foundUser.role === 'مدیر کل') {
                    setTimeout(() => {
                        window.location.href = '/admin/dashboard'; // Redirect admin to admin panel
                    }, 500);
                } else {
                    // برای کاربران عادی (جدید یا موجود)، همیشه پس از ورود به صفحه اصلی هدایت شود
                    setTimeout(() => {
                        window.location.href = '/'; 
                    }, 500);
                }

            } else {
                showMessage('کد تایید اشتباه است. لطفاً دوباره امتحان کنید.', 'error');
                otpCodeInput.value = '';
                otpCodeInput.focus();
            }

        } catch (error) {
            console.error('Error verifying OTP:', error);
            showMessage('خطا در تایید کد. لطفاً دوباره تلاش کنید.', 'error');
        } finally {
            verifyOtpBtn.disabled = false;
            verifyOtpBtn.innerHTML = 'تایید و ورود';
        }
    });
}


// Event listeners for resend and change mobile buttons
if (resendOtpBtn) {
    resendOtpBtn.addEventListener('click', async () => {
        showMessage('در حال ارسال مجدد کد تایید...', 'info');
        resendOtpBtn.disabled = true;
        try {
            await new Promise(resolve => setTimeout(resolve, 1500)); // Simulate API call delay
            simulatedOtp = '1234'; // Regenerate OTP or keep same for demo
            showMessage(`کد تایید جدید ${simulatedOtp} ارسال شد.`, 'success');
        } catch (error) {
            console.error('Error resending OTP:', error);
            showMessage('خطا در ارسال مجدد کد.', 'error');
        } finally {
            resendOtpBtn.disabled = false;
        }
    });
}

if (changeMobileBtn) {
    changeMobileBtn.addEventListener('click', () => {
        mobileLoginStep.classList.remove('hidden');
        smsVerifyStep.classList.add('hidden');
        mobileNumberInput.value = '';
        mobileNumberInput.focus();
        showMessage('شماره موبایل را تغییر دهید.', 'info');
    });
}

// --- Registration/Profile Completion Logic (for complete-profile.blade.php) ---
const completeProfileForm = document.getElementById('complete-profile-form');

if (completeProfileForm) { // This block only executes if the complete-profile-form element is present
    const fullNameInput = document.getElementById('full-name');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password'); // Can be hidden if user registered with OTP
    const confirmPasswordInput = document.getElementById('confirm-password'); // Can be hidden
    const phoneNumberInput = document.getElementById('phone-number'); // Should be pre-filled
    const nationalCodeInput = document.getElementById('national-code');
    const streetAddressInput = document.getElementById('street-address');
    const provinceInput = document.getElementById('province');
    const cityInput = document.getElementById('city');
    const postalCodeInput = document.getElementById('postal-code');
    const togglePassword = document.getElementById('toggle-password'); // If password fields are shown

    const formInputs = [
        fullNameInput, emailInput, passwordInput, confirmPasswordInput,
        phoneNumberInput, nationalCodeInput, streetAddressInput, provinceInput, cityInput, postalCodeInput
    ];

    // Function to add error border to an input
    function addErrorBorder(inputElement) {
        if (inputElement) {
            inputElement.classList.remove('border-gray-300', 'focus:ring-green-800', 'focus:border-transparent');
            inputElement.classList.add('border-red-500', 'focus:ring-red-500', 'focus:border-red-500');
        }
    }

    // Function to remove error border from an input
    function removeErrorBorder(inputElement) {
        if (inputElement) {
            inputElement.classList.remove('border-red-500', 'focus:ring-red-500', 'focus:border-red-500');
            inputElement.classList.add('border-gray-300', 'focus:ring-green-800', 'focus:border-transparent');
        }
    }

    // Clear all error borders from all inputs in the form
    function clearAllErrorBorders() {
        formInputs.forEach(input => {
            if (input) { // Ensure the input element exists
                removeErrorBorder(input);
            }
        });
    }

    // Toggle password visibility for register page
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            togglePassword.querySelector('i').classList.toggle('fa-eye');
            togglePassword.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }

    // Add input event listeners to clear error on typing for each input
    formInputs.forEach(input => {
        if (input) { // Ensure the input element exists
            input.addEventListener('input', () => {
                removeErrorBorder(input);
            });
        }
    });

    // Pre-fill phone number if available from loggedInUserFullData
    const loggedInUserFullData = JSON.parse(localStorage.getItem('loggedInUserFullData'));
    if (phoneNumberInput && loggedInUserFullData && loggedInUserFullData.phoneNumber) {
        phoneNumberInput.value = loggedInUserFullData.phoneNumber;
        phoneNumberInput.readOnly = true; // Make it read-only
        phoneNumberInput.classList.add('bg-gray-100', 'cursor-not-allowed');
    }

    // Pre-fill existing data if user is editing their profile
    if (loggedInUserFullData) {
        if (fullNameInput) fullNameInput.value = loggedInUserFullData.fullName || '';
        if (emailInput) emailInput.value = loggedInUserFullData.email || '';
        // Passwords should never be pre-filled for security
        if (nationalCodeInput) nationalCodeInput.value = loggedInUserFullData.nationalCode || '';
        if (streetAddressInput && loggedInUserFullData.address) streetAddressInput.value = loggedInUserFullData.address.street || '';
        if (provinceInput && loggedInUserFullData.address) provinceInput.value = loggedInUserFullData.address.province || '';
        if (cityInput && loggedInUserFullData.address) cityInput.value = loggedInUserFullData.address.city || '';
        if (postalCodeInput && loggedInUserFullData.address) postalCodeInput.value = loggedInUserFullData.address.postalCode || '';
    }

    // Handle Complete Profile Form Submission
    completeProfileForm.addEventListener('submit', async (event) => {
        event.preventDefault(); // Prevent default form submission

        // Clear all existing error borders before new validation
        clearAllErrorBorders();

        // Get current values from the form fields
        const fullName = fullNameInput.value.trim();
        const email = emailInput.value.trim();
        // Password fields might be absent for OTP registered users
        const password = passwordInput ? passwordInput.value.trim() : '';
        const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value.trim() : '';

        const phoneNumber = phoneNumberInput.value.trim(); // This should be pre-filled
        const nationalCode = nationalCodeInput.value.trim();
        const streetAddress = streetAddressInput.value.trim();
        const province = provinceInput.value.trim();
        const city = cityInput.value.trim();
        const postalCode = postalCodeInput.value.trim();

        const submitBtn = completeProfileForm.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;

        // Set loading state for the submit button
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال ثبت‌نام...';
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-70', 'cursor-not-allowed');

        let isValid = true; // Flag to track overall form validity

        // Client-side Validation Checks and applying error borders
        if (!fullName) {
            showMessage('لطفاً نام کامل را پر کنید.', 'error');
            addErrorBorder(fullNameInput);
            isValid = false;
        } else if (fullName.length < 3 || /\d/.test(fullName)) {
            showMessage('نام کامل باید حداقل 3 کاراکتر و بدون عدد باشد.', 'error');
            addErrorBorder(fullNameInput);
            isValid = false;
        }

        if (!email) {
            showMessage('لطفاً ایمیل را پر کنید.', 'error');
            addErrorBorder(emailInput);
            isValid = false;
        } else {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showMessage('فرمت ایمیل صحیح نیست.', 'error');
                addErrorBorder(emailInput);
                isValid = false;
            }
        }

        // Only validate password if fields are present and not empty
        if (passwordInput && passwordInput.offsetParent !== null) { // Check if element is visible
            if (!password) {
                showMessage('لطفاً رمز عبور را پر کنید.', 'error');
                addErrorBorder(passwordInput);
                isValid = false;
            } else if (password.length < 8) {
                showMessage('رمز عبور باید حداقل 8 کاراکتر باشد.', 'error');
                addErrorBorder(passwordInput);
                isValid = false;
            } else {
                const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/; // At least one letter and one number
                if (!passwordRegex.test(password)) {
                    showMessage('رمز عبور باید شامل حروف و اعداد باشد.', 'error');
                    addErrorBorder(passwordInput);
                    isValid = false;
                }
            }

            if (!confirmPassword) {
                showMessage('لطفاً تأیید رمز عبور را پر کنید.', 'error');
                addErrorBorder(confirmPasswordInput);
                isValid = false;
            } else if (password !== confirmPassword) {
                showMessage('رمز عبور و تایید آن مطابقت ندارند.', 'error');
                addErrorBorder(passwordInput); // Highlight both if mismatch
                addErrorBorder(confirmPasswordInput);
                isValid = false;
            }
        }


        // Phone Number is pre-filled, so just validate format if it was editable
        if (!phoneNumber) {
            showMessage('شماره تلفن از قبل وارد شده است.', 'error'); // Should not happen if pre-filled
            addErrorBorder(phoneNumberInput);
            isValid = false;
        } else {
            const phoneRegex = /^09[0-9]{9}$/;
            if (!phoneRegex.test(phoneNumber)) {
                showMessage('فرمت شماره تلفن صحیح نیست. (مثال: 09123456789)', 'error');
                addErrorBorder(phoneNumberInput);
                isValid = false;
            }
        }

        if (!nationalCode) {
            showMessage('لطفاً کد ملی را پر کنید.', 'error');
            addErrorBorder(nationalCodeInput);
            isValid = false;
        } else {
            const nationalCodeRegex = /^[0-9]{10}$/;
            if (!nationalCodeRegex.test(nationalCode)) {
                showMessage('کد ملی باید ۱۰ رقمی باشد.', 'error');
                addErrorBorder(nationalCodeInput);
                isValid = false;
            }
        }

        if (!streetAddress) {
            showMessage('لطفاً آدرس خیابان را پر کنید.', 'error');
            addErrorBorder(streetAddressInput);
            isValid = false;
        }

        if (!province) {
            showMessage('لطفاً استان را پر کنید.', 'error');
            addErrorBorder(provinceInput);
            isValid = false;
        }

        if (!city) {
            showMessage('لطفاً شهر را پر کنید.', 'error');
            addErrorBorder(cityInput);
            isValid = false;
        }

        if (!postalCode) {
            showMessage('لطفاً کد پستی را پر کنید.', 'error');
            addErrorBorder(postalCodeInput);
            isValid = false;
        } else {
            const postalCodeRegex = /^[0-9]{10}$/;
            if (!postalCodeRegex.test(postalCode)) {
                showMessage('کد پستی باید ۱۰ رقمی باشد.', 'error');
                addErrorBorder(postalCodeInput);
                isValid = false;
            }
        }

        // If any client-side validation failed, stop the process
        if (!isValid) {
            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
            return; // Exit here if not valid
        }

        // Simulate checking for existing users in localStorage (backend simulation)
        let users = getStoredUsers(); // Get all users from storage
        let isExistingUser = false;
        let currentUserData = null;

        if (loggedInUserFullData && loggedInUserFullData.phoneNumber === phoneNumber) {
            isExistingUser = true;
            currentUserData = loggedInUserFullData;
        }

        // Check for duplicate data if email/nationalCode are being set for a new user
        // Or if an existing user is changing their email/nationalCode to a conflicting one
        if (!isExistingUser || (isExistingUser && currentUserData.email !== email)) {
            const existingUserByEmail = Object.values(users).find(user => user.email === email && user.phoneNumber !== phoneNumber);
            if (existingUserByEmail) {
                showMessage('این ایمیل قبلاً توسط کاربر دیگری ثبت شده است.', 'error');
                addErrorBorder(emailInput);
                isValid = false;
            }
        }
        
        if (!isExistingUser || (isExistingUser && currentUserData.nationalCode !== nationalCode)) {
             const existingUserByNationalCode = Object.values(users).find(user => user.nationalCode === nationalCode && user.phoneNumber !== phoneNumber);
            if (existingUserByNationalCode) {
                showMessage('این کد ملی قبلاً توسط کاربر دیگری ثبت شده است.', 'error');
                addErrorBorder(nationalCodeInput);
                isValid = false;
            }
        }

        // If any duplicate check failed, stop
        if (!isValid) {
            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
            return; // Exit here if not valid
        }

        // If all validations pass, proceed with simulated registration/profile update
        try {
            // Simulate an async backend call (e.g., sending data to Laravel)
            await new Promise(resolve => setTimeout(resolve, 1500)); // Simulate network delay

            // Update user data for the current logged-in user in localStorage
            if (!isExistingUser) {
                 // This path should ideally not be hit if user is logged in via OTP
                 // but as a fallback, create a new user if somehow not found
                 loggedInUserFullData = { // This is a new user registering fully
                    username: email.split('@')[0], // Simple username from email
                    role: 'کاربر',
                    phoneNumber: phoneNumber,
                    isProfileComplete: true, // Now complete
                    id: Date.now().toString(),
                    fullName, email, password, nationalCode,
                    address: { street: streetAddress, province, city, postalCode }
                };
            } else {
                // Update existing user data
                loggedInUserFullData.fullName = fullName;
                loggedInUserFullData.email = email;
                if (password) loggedInUserFullData.password = password; // Only update if new password provided
                loggedInUserFullData.nationalCode = nationalCode;
                loggedInUserFullData.address = {
                    street: streetAddress,
                    province: province,
                    city: city,
                    postalCode: postalCode
                };
                loggedInUserFullData.isProfileComplete = true; // Mark as complete
            }

            // Update the user in the main 'registeredUsers' object in localStorage
            users[loggedInUserFullData.username] = loggedInUserFullData;
            localStorage.setItem('registeredUsers', JSON.stringify(users));
            localStorage.setItem('loggedInUserFullData', JSON.stringify(loggedInUserFullData)); // Update full data in session storage

            showMessage('اطلاعات با موفقیت ثبت/بروزرسانی شد.', 'success');

            // Simulate redirection to home page after a short delay
            setTimeout(() => {
                window.location.href = '/'; // Redirect to home page
            }, 2000);

            // No form reset here as it's a profile update, data should remain

        } catch (error) {
            console.error('Profile completion submission error:', error);
            showMessage('خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.', 'error');
        } finally {
            // Reset button state regardless of success or failure
            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
        }
    });
}
