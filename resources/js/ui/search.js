// resources/js/search/search.js

// Assuming window.axios and window.showMessage are globally available from app.js
// If debounce is moved to core/events.js or core/utils.js, you might need to import it.
// For now, we'll assume it's either global or a local helper.
// Since you have a debounce in events.js, it's better to import it from there.
import { debounce } from '../core/events.js'; // Assuming events.js is in core

/**
 * Displays the search results in the results container.
 * نتایج جستجو را در کانتینر نتایج نمایش می‌دهد.
 * @param {Array<Object>} results - An array of product objects to display.
 */
function displayResults(results) {
    const resultsContainer = document.getElementById('search-results-container');
    if (!resultsContainer) {
        console.error('Search results container not found.');
        return;
    }

    resultsContainer.innerHTML = ''; // Clear previous search results
    resultsContainer.classList.remove('hidden'); // Show the container when results are available

    if (results.length === 0) {
        resultsContainer.innerHTML = `
            <p class="text-gray-500 p-4 text-center text-sm">محصولی یافت نشد.</p>
        `;
        return;
    }

    // Iterate over each product and create its HTML representation
    results.forEach(product => {
        const resultItem = document.createElement('a'); // Changed to <a> tag for clickable results
        resultItem.href = `/products/${product.id}`; // Link to product page
        // Add Tailwind CSS classes for styling each result item
        resultItem.classList.add('flex', 'items-center', 'p-4', 'border-b', 'border-gray-200', 'last:border-b-0', 'hover:bg-gray-50', 'transition-colors', 'duration-200', 'rounded-lg', 'cursor-pointer');

        // Determine the image URL. Use a placeholder if no image URL is provided.
        const imageUrl = product.image || `https://placehold.co/100x100/A7F3D0/10B981?text=No+Image`;

        // Set the inner HTML for the result item
        resultItem.innerHTML = `
            <img src="${imageUrl}"
                 onerror="this.onerror=null;this.src='https://placehold.co/100x100/E5E7EB/4B5563?text=No+Image';"
                 alt="${product.title}"
                 class="w-16 h-16 rounded-lg object-cover ml-4 shadow-sm">
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-green-700">${product.title}</h3>
                <p class="text-gray-600 text-sm mt-1">${product.description ? product.description.substring(0, 80) + '...' : ''}</p>
                <p class="text-amber-600 font-bold mt-2">${new Intl.NumberFormat('fa-IR').format(product.price)} تومان</p>
            </div>
        `;
        // Append the created result item to the results container
        resultsContainer.appendChild(resultItem);
    });
}

/**
 * Initializes the live search functionality.
 * این تابع قابلیت جستجوی زنده را مقداردهی اولیه می‌کند.
 * This function is exported to be called by app.js after dynamic import.
 */
export function initSearch() {
    console.log('Search module initializing...');
    const searchInput = document.getElementById('live-search-input');
    const resultsContainer = document.getElementById('search-results-container');

    // Check if the elements exist to prevent errors on pages without them
    if (!searchInput || !resultsContainer) {
        console.warn("Search elements not found. Skipping live search initialization.");
        return;
    }

    let searchTimeout; // Moved local timeout variable inside initSearch

    // Add an event listener to the search input field for 'input' events (when user types)
    searchInput.addEventListener('input', debounce(async function() {
        const query = this.value.trim();

        // If the query is empty or less than 2 characters, hide the results container and display initial message
        if (query.length < 2) {
            resultsContainer.classList.add('hidden');
            resultsContainer.innerHTML = '<p class="text-gray-500 text-center py-4 text-sm" id="initial-message">شروع به تایپ کنید تا نتایج را مشاهده کنید.</p>';
            return;
        }

        // Display a loading message while waiting for search results and ensure container is visible
        resultsContainer.innerHTML = '<p class="text-center py-4 text-green-600"><i class="fas fa-spinner fa-spin ml-2"></i>در حال جستجو...</p>';
        resultsContainer.classList.remove('hidden');

        try {
            // استفاده از window.axios برای ارسال درخواست جستجو
            const response = await window.axios.get(`/search?q=${encodeURIComponent(query)}`);

            // Axios به طور خودکار خطاها را با throw می‌کند، نیازی به بررسی response.ok نیست.
            // داده‌ها مستقیماً در response.data قرار دارند.
            const data = response.data;

            // Display the results
            displayResults(data);
        } catch (error) {
            // Log and display an error message if the fetch fails
            console.error('Search error:', error);
            // Use window.showMessage for network/parsing errors
            const errorMessage = error.response?.data?.message || 'خطا در ارتباط با سرور. لطفا اتصال اینترنت خود را بررسی کنید.';
            if (typeof window.showMessage === 'function') { // Ensure showMessage exists
                window.showMessage(errorMessage, 'error');
            }
            resultsContainer.innerHTML = `<p class="text-red-500 p-4 text-center">خطا در ارتباط با سرور</p>`;
            resultsContainer.classList.remove('hidden'); // Ensure container is visible for error message
        }
    }, 300)); // 300 milliseconds delay

    // Add event listener to hide results when clicking outside the search input or results container
    document.addEventListener('click', (event) => {
        // Check if the clicked element is NOT the search input AND NOT inside the results container
        if (event.target !== searchInput && !resultsContainer.contains(event.target)) {
            resultsContainer.classList.add('hidden'); // Hide the results container
        }
    });

    // Add event listener to show results when the search input is focused and has content
    searchInput.addEventListener('focus', () => {
        // Only show if there's content OR if it's currently showing initial message
        if (searchInput.value.trim().length > 0) {
            // If there's content, and results were previously loaded, show them
            if (resultsContainer.children.length > 0 && resultsContainer.firstElementChild.id !== 'initial-message') {
                resultsContainer.classList.remove('hidden');
            } else { // If it was hidden and has content, re-trigger search (or show initial message if empty)
                 if (searchInput.value.trim().length < 2) {
                    resultsContainer.innerHTML = '<p class="text-gray-500 text-center py-4 text-sm" id="initial-message">شروع به تایپ کنید تا نتایج را مشاهده کنید.</p>';
                 } else {
                    // Re-trigger the input event to show search results if focus gained and query exists
                    searchInput.dispatchEvent(new Event('input'));
                 }
                 resultsContainer.classList.remove('hidden'); // Ensure container is visible
            }
        } else {
             resultsContainer.innerHTML = '<p class="text-gray-500 text-center py-4 text-sm" id="initial-message">شروع به تایپ کنید تا نتایج را مشاهده کنید.</p>';
             resultsContainer.classList.remove('hidden'); // Show with initial message
        }
    });
    console.log('Search module initialized successfully.');
}

// The document.addEventListener('DOMContentLoaded') block is removed from here.
// app.js will dynamically import this module and call initSearch().
