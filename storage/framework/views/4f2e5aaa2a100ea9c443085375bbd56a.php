<nav class="bg-white p-4 shadow-lg rounded-b-xl sticky top-0 z-50">
    <div class="container mx-auto flex justify-between items-center flex-wrap">
        
        <div class="flex items-center flex-wrap md:flex-nowrap">
            <a href="<?php echo e(url('/')); ?>" class="text-brown-900 flex items-center mb-2 md:mb-0">
                <i class="fas fa-leaf text-green-800 ml-2"></i> 
                <span class="text-3xl font-bold">چای ابراهیم</span>
            </a>
            
            
            
            <ul class="flex flex-wrap justify-center gap-4 md:flex-nowrap md:justify-end md:space-x-4 md:space-x-reverse md:mr-8 mt-2 md:mt-0">
                <li><a href="<?php echo e(url('/')); ?>" class="text-gray-700 hover:text-green-800 px-3 py-1 rounded-md transition-colors duration-300 whitespace-nowrap">خانه</a></li>
                <li><a href="<?php echo e(url('/products')); ?>" class="text-gray-700 hover:text-green-800 px-3 py-1 rounded-md transition-colors duration-300 whitespace-nowrap">محصولات</a></li>
                <li><a href="<?php echo e(url('/about')); ?>" class="text-gray-700 hover:text-green-800 px-3 py-1 rounded-md transition-colors duration-300 whitespace-nowrap">درباره ما</a></li>
                <li><a href="<?php echo e(url('/contact')); ?>" class="text-gray-700 hover:text-green-800 px-3 py-1 rounded-md transition-colors duration-300 whitespace-nowrap">تماس با ما</a></li>
                <li><a href="<?php echo e(url('/blog')); ?>" class="text-gray-700 hover:text-green-800 px-3 py-1 rounded-md transition-colors duration-300 whitespace-nowrap">بلاگ</a></li>
                <li><a href="<?php echo e(url('/faq')); ?>" class="text-gray-700 hover:text-green-800 px-3 py-1 rounded-md transition-colors duration-300 whitespace-nowrap">سوالات متداول</a></li>
            </ul>
        </div>

        
        <div class="flex items-center space-x-4 space-x-reverse mt-2 md:mt-0 w-full md:w-auto justify-center md:justify-end">
            
            <div id="search-area-wrapper" class="relative">
                <button id="search-toggle-btn" class="text-gray-700 hover:text-green-800 p-2 rounded-full transition-colors duration-300 flex items-center justify-center">
                    <i id="search-icon-initial" class="fas fa-search text-xl"></i>
                    <i id="search-icon-close" class="fas fa-times text-xl hidden"></i>
                </button>
            </div>

            
            <div id="cart-icon-container" class="relative group">
                <a href="<?php echo e(url('/cart')); ?>" class="text-gray-700 hover:text-green-800 p-2 rounded-full transition-colors duration-300 relative">
                    <i class="fas fa-shopping-basket text-xl"></i>
                    <span id="cart-item-count" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">0</span>
                </a>
                
                <div id="mini-cart-dropdown" class="mini-cart-dropdown absolute left-0 mt-2 w-72 bg-white rounded-lg shadow-xl py-2 z-30 opacity-0 scale-95 origin-top-right transition-all duration-200 pointer-events-none">
                    <div id="mini-cart-content" class="px-4 py-2 border-b border-gray-200 max-h-60 overflow-y-auto">
                        
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
                        <a href="<?php echo e(url('/cart')); ?>" class="btn-secondary">
                            <i class="fas fa-shopping-basket"></i> مشاهده سبد خرید
                        </a>
                        
                        <a href="<?php echo e(url('/checkout')); ?>" class="btn-primary">
                            <i class="fas fa-credit-card"></i> تکمیل خرید
                        </a>
                    </div>
                </div>
            </div>

            
            <div id="user-area-wrapper" class="relative group">
                <button id="user-area-main-btn" class="bg-green-800 text-white font-semibold px-5 py-2 rounded-full shadow-lg hover:bg-green-700 transition-colors duration-300 transform hover:scale-105 w-full md:w-auto flex items-center justify-center">
                    <i id="user-area-icon" class="fas fa-user-circle ml-2"></i>
                    <span id="user-area-text">ورود/ثبت نام</span> 
                    <i id="user-area-arrow-icon" class="fas fa-chevron-down ml-2 hidden"></i> 
                </button>
                
                <div id="user-area-dropdown" class="absolute left-0 mt-2 w-56 bg-white rounded-lg shadow-xl py-2 z-30 opacity-0 scale-95 origin-top-right transition-all duration-200 pointer-events-none hidden">
                    <div class="px-4 py-2 text-gray-800 text-sm border-b border-gray-200">
                        <p id="dropdown-username" class="font-bold"></p>
                    </div>
                    
                    <a id="dropdown-profile-link" href="<?php echo e(url('/complete-profile')); ?>" class="block px-4 py-2 text-gray-800 hover:bg-gray-100 flex items-center">
                        <i class="fas fa-user-edit ml-2"></i> اطلاعات حساب کاربری
                    </a>
                    <a href="<?php echo e(url('/profile/orders')); ?>" class="block px-4 py-2 text-gray-800 hover:bg-gray-100 flex items-center">
                        <i class="fas fa-box ml-2"></i> سفارش‌ها
                    </a>
                    <a href="<?php echo e(url('/profile/addresses')); ?>" class="block px-4 py-2 text-gray-800 hover:bg-gray-100 flex items-center">
                        <i class="fas fa-map-marker-alt ml-2"></i> آدرس‌ها
                    </a>
                    <a href="<?php echo e(url('/profile/wishlist')); ?>" class="block px-4 py-2 text-gray-800 hover:bg-gray-100 flex items-center">
                        <i class="fas fa-heart ml-2"></i> لیست علاقه‌مندی
                    </a>
                    <a href="<?php echo e(url('/profile/reviews')); ?>" class="block px-4 py-2 text-gray-800 hover:bg-gray-100 flex items-center">
                        <i class="fas fa-comment-dots ml-2"></i> دیدگاه‌ها و پرسش‌ها
                    </a>
                    <div class="border-t border-gray-200 my-1"></div>
                    <button id="user-logout-btn" class="block w-full text-right px-4 py-2 text-red-600 hover:bg-red-50 hover:text-red-700 flex items-center">
                        <i class="fas fa-sign-out-alt ml-2"></i> خروج از حساب کاربری
                    </button>
                </div>
            </div>
        </div>
    </div>
</nav>
<?php /**PATH C:\xampp\htdocs\myshop\resources\views\partials\nav.blade.php ENDPATH**/ ?>