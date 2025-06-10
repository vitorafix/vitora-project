<nav class="bg-white p-4 shadow-lg rounded-b-xl sticky top-0 z-50">
    <div class="container mx-auto flex justify-between items-center flex-wrap">
        <a href="{{ url('/') }}" class="text-brown-900 flex items-center mb-2 md:mb-0">
            <i class="fas fa-leaf text-green-800 ml-2"></i> {{-- Changed mr-2 to ml-2 for RTL icon spacing --}}
            <span class="text-3xl font-bold">چای ابراهیم</span>
        </a>
        
        {{-- Main Navigation Links --}}
        {{-- Removed flex-grow here to allow custom CSS margin-right: auto to work effectively --}}
        <ul class="flex flex-wrap justify-center gap-4 md:flex-nowrap md:justify-end md:space-x-4 md:space-x-reverse">
            {{-- Updated to ul/li structure for better styling control --}}
            <li><a href="{{ url('/') }}" class="text-gray-700 hover:text-green-800 px-3 py-1 rounded-md transition-colors duration-300 whitespace-nowrap">خانه</a></li>
            <li><a href="{{ url('/products') }}" class="text-gray-700 hover:text-green-800 px-3 py-1 rounded-md transition-colors duration-300 whitespace-nowrap">انواع چای</a></li>
            <li><a href="{{ url('/about') }}" class="text-gray-700 hover:text-green-800 px-3 py-1 rounded-md transition-colors duration-300 whitespace-nowrap">درباره ما</a></li>
            <li><a href="{{ url('/contact') }}" class="text-gray-700 hover:text-green-800 px-3 py-1 rounded-md transition-colors duration-300 whitespace-nowrap">تماس با ما</a></li>
            <li><a href="{{ url('/blog') }}" class="text-gray-700 hover:text-green-800 px-3 py-1 rounded-md transition-colors duration-300 whitespace-nowrap">بلاگ</a></li>
        </ul>

        {{-- Right-aligned Utility Icons and Auth Button --}}
        <div class="flex flex-wrap justify-center gap-4 md:flex-nowrap md:justify-end md:space-x-4 md:space-x-reverse">
            {{-- Search Area: Icon transforms to Input --}}
            <div class="relative flex items-center" id="search-area-wrapper">
                <input type="text" id="live-search-input"
                       class="absolute top-0 right-0 h-full w-0 opacity-0 transition-all duration-300 ease-in-out
                              bg-white rounded-full border-2 border-green-800 focus:outline-none focus:ring-2 focus:ring-brown-900
                              text-brown-900 text-base text-right pl-4 pr-10 z-10"
                       placeholder="جستجو...">
                <button id="search-toggle-btn"
                        class="relative text-gray-700 hover:text-green-800 text-xl px-3 py-1 rounded-full transition-colors duration-300 whitespace-nowrap z-20">
                    <i class="fas fa-search" id="search-icon-initial"></i>
                    <i class="fas fa-times hidden" id="search-icon-close"></i>
                </button>
                {{-- Live Search Results Container --}}
                <div id="live-search-results-container" class="absolute top-full right-0 w-full bg-white border border-gray-200 rounded-lg shadow-xl mt-2 p-4 hidden z-40 max-h-80 overflow-y-auto">
                    {{-- Search results will be rendered here by JavaScript --}}
                    <div class="no-results text-center py-4 text-gray-500">
                        <i class="fas fa-search text-gray-400 text-3xl mb-2 block"></i>
                        <p class="text-gray-500 mt-1">محصولات مورد نظر خود را در اینجا پیدا کنید.</p>
                    </div>
                </div>
            </div>

            {{-- Shopping Cart Icon with Mini Cart Dropdown --}}
            <div class="relative group" id="cart-icon-container">
                <a href="{{ url('/cart') }}" class="text-gray-700 hover:text-green-800 text-xl relative flex items-center">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cart-item-count" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">0</span>
                </a>
                {{-- Mini Cart Dropdown --}}
                <div id="mini-cart-dropdown" class="mini-cart-dropdown">
                    <div id="mini-cart-content">
                        {{-- Mini cart items will be loaded here by JavaScript --}}
                    </div>
                    <div id="mini-cart-empty-message" class="mini-cart-empty hidden">
                        <i class="fas fa-shopping-basket block mb-2"></i>
                        <p>سبد خرید شما خالی است.</p>
                    </div>
                    <div id="mini-cart-summary" class="mini-cart-total hidden">
                        <span>جمع کل:</span>
                        <span id="mini-cart-total-price">۰ تومان</span>
                    </div>
                    <div id="mini-cart-actions" class="mini-cart-actions hidden">
                        <a href="{{ url('/cart') }}" class="btn-secondary">
                            <i class="fas fa-shopping-basket"></i> مشاهده سبد خرید
                        </a>
                        {{-- Changed from <button> to <a> to enable direct navigation to checkout --}}
                        <a href="{{ url('/checkout') }}" class="btn-primary">
                            <i class="fas fa-credit-card"></i> تکمیل خرید
                        </a>
                    </div>
                </div>
            </div>

            <button id="auth-toggle-btn" class="bg-green-800 text-white font-semibold px-5 py-2 rounded-full shadow-lg hover:bg-green-700 transition-colors duration-300 transform hover:scale-105 w-full md:w-auto mt-2 md:mt-0">ورود/ثبت نام</button>
        </div>
    </div>
</nav>
