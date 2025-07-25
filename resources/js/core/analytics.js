// resources/js/analytics.js

// تابع کمکی برای تولید UUID
function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

// تابع کمکی برای نمایش پیام (این تابع برای مثال UI است، در پروژه واقعی ممکن است متفاوت باشد)
function showMessage(msg, type) {
    const messageDiv = document.getElementById('message');
    if (messageDiv) {
        messageDiv.textContent = msg;
        messageDiv.className = `message ${type}`;
        messageDiv.classList.remove('hidden');
    }
}

// --- مدیریت Guest UUID (با اولویت window.guestUUID از Blade) ---
let guestUuid;
if (typeof window !== 'undefined' && window.guestUUID) {
    guestUuid = window.guestUUID;
} else {
    // Fallback if window.guestUUID is not set by Blade (e.g., standalone test)
    guestUuid = localStorage.getItem('guest_uuid');
    if (!guestUuid) {
        guestUuid = generateUUID();
        localStorage.setItem('guest_uuid', guestUuid);
    }
}
const guestUuidDisplay = document.getElementById('guestUuidDisplay');
if (guestUuidDisplay) {
    guestUuidDisplay.textContent = guestUuid;
}

// --- ردیابی تحلیلی ---
let pageViews = parseInt(localStorage.getItem('page_views') || '0');
let screenTime = parseInt(localStorage.getItem('screen_time') || '0');
let sessionTime = parseInt(localStorage.getItem('session_time') || '0');
let clickCount = parseInt(localStorage.getItem('click_count') || '0');
let jsErrorCount = parseInt(localStorage.getItem('js_error_count') || '0');
let trafficSource = localStorage.getItem('traffic_source') || document.referrer || 'direct';
let maxScrollDepth = 0; // In percentage

// به‌روزرسانی نمایشگرها در UI (برای مثال)
function updateAnalyticsDisplays() {
    try {
        const displays = {
            pageViewsDisplay: pageViews,
            screenTimeDisplay: screenTime,
            sessionTimeDisplay: sessionTime,
            clickCountDisplay: clickCount,
            trafficSourceDisplay: trafficSource,
            jsErrorsDisplay: jsErrorCount,
            scrollDepthDisplay: `${maxScrollDepth}%`
        };
        for (const id in displays) {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = displays[id];
            }
        }
    } catch (error) {
        console.warn('Analytics UI update error:', error);
    }
}
updateAnalyticsDisplays();


// افزایش بازدید صفحه هنگام بارگذاری
pageViews++;
localStorage.setItem('page_views', pageViews);
updateAnalyticsDisplays();

// ردیابی زمان صفحه و نشست
let screenTimeInterval;
let sessionTimeInterval;
let lastActivityTime = Date.now(); // زمان آخرین فعالیت کاربر
let heartbeatTimeout; // برای کنترل Heartbeat هوشمند

// فواصل زمانی برای Heartbeat هوشمند (میلی‌ثانیه)
const HEARTBEAT_INTERVALS = {
    active: 60 * 1000,      // 1 دقیقه زمانی که کاربر فعال است
    idle: 5 * 60 * 1000,    // 5 دقیقه زمانی که کاربر غیرفعال است
    background: 10 * 60 * 1000 // 10 دقیقه زمانی که تب در پس‌زمینه است
};

function startTrackingTime() {
    try {
        screenTimeInterval = setInterval(() => {
            screenTime++;
            localStorage.setItem('screen_time', screenTime);
            updateAnalyticsDisplays();
        }, 1000);

        sessionTimeInterval = setInterval(() => {
            sessionTime++;
            localStorage.setItem('session_time', sessionTime);
            updateAnalyticsDisplays();
        }, 1000);

        // شروع Heartbeat هوشمند
        scheduleNextHeartbeat();

    } catch (error) {
        console.warn('Analytics time tracking start error:', error);
    }
}

function stopTrackingTime() {
    try {
        clearInterval(screenTimeInterval);
        clearInterval(sessionTimeInterval);
        clearTimeout(heartbeatTimeout); // توقف Heartbeat هوشمند
    } catch (error) {
        console.warn('Analytics time tracking stop error:', error);
    }
}

// تابع برای به‌روزرسانی زمان آخرین فعالیت
function updateLastActivity() {
    lastActivityTime = Date.now();
    // هر فعالیتی باید Heartbeat را ریست کند تا به حالت فعال برگردد
    scheduleNextHeartbeat();
}

// Event Listeners برای ردیابی فعالیت کاربر
document.addEventListener('mousemove', updateLastActivity);
document.addEventListener('keydown', updateLastActivity);
document.addEventListener('click', updateLastActivity); // این قبلاً هم بود، اما اطمینان حاصل شود که updateLastActivity را فراخوانی می‌کند

// ردیابی تغییر وضعیت تب (فعال/پس‌زمینه) - بهبود یافته طبق پیشنهاد کارشناس
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        // اگر تب به پس‌زمینه رفت، فوراً Heartbeat را برای حالت background زمان‌بندی کن
        scheduleNextHeartbeat();
    } else {
        // اگر تب به پیش‌زمینه برگشت، زمان آخرین فعالیت را به‌روز کن و Heartbeat را برای حالت active زمان‌بندی کن
        updateLastActivity();
    }
});

// تابع زمان‌بندی Heartbeat هوشمند
function scheduleNextHeartbeat() {
    clearTimeout(heartbeatTimeout); // پاک کردن Heartbeat قبلی

    let interval;
    const idleTime = Date.now() - lastActivityTime;

    if (document.hidden) {
        interval = HEARTBEAT_INTERVALS.background;
        console.log('Heartbeat: Tab in background. Next heartbeat in', interval / 1000, 'seconds.');
    } else if (idleTime > HEARTBEAT_INTERVALS.active) {
        interval = HEARTBEAT_INTERVALS.idle;
        console.log('Heartbeat: User idle. Next heartbeat in', interval / 1000, 'seconds.');
    } else {
        interval = HEARTBEAT_INTERVALS.active;
        console.log('Heartbeat: User active. Next heartbeat in', interval / 1000, 'seconds.');
    }

    heartbeatTimeout = setTimeout(() => {
        queueAnalyticsEvent('[INT]_update_session');
        scheduleNextHeartbeat(); // زمان‌بندی Heartbeat بعدی
    }, interval);
}


// --- Debounce Utility Function ---
function debounce(func, delay) {
    let timeout;
    const debounced = function(...args) {
        const context = this;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), delay);
    };
    // Add a flush method to immediately execute the debounced function
    debounced.flush = function() {
        clearTimeout(timeout);
        timeout = null; // Clear timeout to prevent double execution
        func.apply(this, arguments); // Directly call the original function
    };
    return debounced;
}

// --- Batch Sending Analytics Data ---
const analyticsQueue = [];
const SEND_BATCH_INTERVAL = 5000; // Send every 5 seconds

// Re-applied debounce to sendBatchAnalyticsData
const sendBatchAnalyticsData = debounce(async () => {
    console.log('sendBatchAnalyticsData: Function triggered.'); // Added log
    if (analyticsQueue.length === 0) {
        console.log('sendBatchAnalyticsData: Queue is empty, nothing to send.'); // Added log
        return;
    }

    const dataToSend = [...analyticsQueue]; // Copy queue
    analyticsQueue.length = 0; // Clear queue

    // تولید Idempotency Key برای این دسته از رویدادها
    const idempotencyKey = `${guestUuid}_${Date.now()}_${Math.random().toString(36).substring(2, 15)}`;

    try {
        console.log('sendBatchAnalyticsData: Attempting to send batch with', dataToSend.length, 'events.'); // Added log
        console.log('sendBatchAnalyticsData: Payload:', JSON.stringify({ events: dataToSend })); // Added log for payload
        const response = await fetch('/api/analytics/track', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Idempotency-Key': idempotencyKey // افزودن Idempotency Key
            },
            body: JSON.stringify({ events: dataToSend }) // Send an array of events
        });

        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.message || 'خطا در ارسال داده‌های تحلیلی دسته‌ای');
        }
        console.log('Analytics batch data sent:', result.message, dataToSend.length, 'events');
    } catch (error) {
        console.error('Error sending analytics batch data:', error);
        // Optionally, re-add dataToSend to queue if sending failed, but be careful with infinite loops
        // analyticsQueue.unshift(...dataToSend);
    }
}, SEND_BATCH_INTERVAL); // Debounce interval

// تابع اصلی برای اضافه کردن رویداد به صف و فراخوانی ارسال دسته‌ای
function queueAnalyticsEvent(eventName = '[INT]_update_session', customEventData = {}) {
    try {
        console.log('queueAnalyticsEvent: Queuing event:', eventName, 'with data:', customEventData); // Added log
        const eventPayload = {
            user_id: window.currentUser ? window.currentUser.id : null, // Add user_id if available globally
            guest_uuid: guestUuid,
            eventName: eventName,
            eventData: {
                session_duration: sessionTime,
                ...customEventData
            },
            screenData: {
                _int_href: window.location.href,
                _int_pathname: window.location.pathname,
                _int_hash: window.location.hash,
                _int_search: window.location.search,
            },
            currentUrl: window.location.href,
            pageTitle: document.title,
            trafficSource: trafficSource,
            screenViews: pageViews,
            screenTime: screenTime,
            sessionTime: sessionTime,
            scrollDepth: maxScrollDepth,
            deviceInfo: getDeviceInfo(),
            performanceMetrics: {
                // Ensure pageLoadTime is non-negative
                page_load_time_ms: Math.max(0, window.performance.timing.loadEventEnd - window.performance.timing.navigationStart),
            },
            interactionDetails: {
                click_count: clickCount,
                js_error_count: jsErrorCount,
            },
            timestamp: new Date().toISOString(), // Add timestamp for server
        };

        // اگر رویداد جستجو است، searchQuery را اضافه کن
        if (eventName === '[INT]_internal_search' && customEventData.search_query) {
            eventPayload.searchQuery = customEventData.search_query;
        }

        analyticsQueue.push(eventPayload);
        sendBatchAnalyticsData(); // Call debounced send
    } catch (error) {
        console.warn('Error queuing analytics event:', error);
    }
}

// ردیابی کلیک
document.addEventListener('click', (event) => { // Capture event object
    try {
        clickCount++;
        localStorage.setItem('click_count', clickCount);
        updateAnalyticsDisplays();
        updateLastActivity(); // به‌روزرسانی زمان آخرین فعالیت
        // Optional: track specific clicks with more details
        // queueAnalyticsEvent('[INT]_click', {
        //     target_id: event.target.id || null,
        //     target_class: event.target.className || null,
        //     target_tag: event.target.tagName || null,
        //     x: event.clientX,
        //     y: event.clientY
        // });
    } catch (error) {
        console.warn('Error tracking click:', error);
    }
});

// ردیابی اسکرول (با debounce)
const scrollableContent = document.querySelector('.scrollable-content');
if (scrollableContent) {
    const handleScroll = () => {
        try {
            const scrollTop = scrollableContent.scrollTop;
            const scrollHeight = scrollableContent.scrollHeight;
            const clientHeight = scrollableContent.clientHeight;

            console.log('Scroll event debug:', {
                scrollTop: scrollTop,
                scrollHeight: scrollHeight,
                clientHeight: clientHeight,
                isScrollable: scrollHeight > clientHeight
            });

            if (scrollHeight > clientHeight) { // اطمینان از اینکه محتوا واقعاً قابل اسکرول است
                const currentScrollDepth = Math.floor((scrollTop / (scrollHeight - clientHeight)) * 100);
                if (currentScrollDepth > maxScrollDepth) {
                    maxScrollDepth = currentScrollDepth;
                    updateAnalyticsDisplays();
                    queueAnalyticsEvent('[INT]_scroll_depth', { scroll_depth: maxScrollDepth });
                }
            }
            updateLastActivity(); // به‌روزرسانی زمان آخرین فعالیت
        } catch (error) {
            console.warn('Error tracking scroll:', error);
        }
    };
    scrollableContent.addEventListener('scroll', debounce(handleScroll, 200)); // Debounce scroll event
}

// ردیابی فوکس روی input ها
const sampleInput = document.getElementById('sampleInput');
if (sampleInput) {
    sampleInput.addEventListener('focus', () => {
        try {
            console.log('Input Focused');
            queueAnalyticsEvent('[INT]_input_focus', { elementId: 'sampleInput' });
            updateLastActivity(); // به‌روزرسانی زمان آخرین فعالیت
        } catch (error) {
            console.warn('Error tracking input focus:', error);
        }
    });
    sampleInput.addEventListener('blur', () => {
        try {
            console.log('Input Blurred');
            queueAnalyticsEvent('[INT]_input_blur', { elementId: 'sampleInput' });
            updateLastActivity(); // به‌روزرسانی زمان آخرین فعالیت
        } catch (error) {
            console.warn('Error tracking input blur:', error);
        }
    });
}

// ردیابی کپی متن
document.addEventListener('copy', (event) => {
    try {
        const copiedText = event.clipboardData.getData('text/plain');
        console.log('Text Copied:', copiedText.substring(0, Math.min(copiedText.length, 50)) + (copiedText.length > 50 ? '...' : ''));
        queueAnalyticsEvent('[INT]_text_copied', { text_length: copiedText.length });
        updateLastActivity(); // به‌روزرسانی زمان آخرین فعالیت
    } catch (error) {
        console.warn('Error tracking text copy:', error);
    }
});

// ردیابی تغییر سایز پنجره (با debounce)
window.addEventListener('resize', debounce(() => {
    try {
        console.log('Window Resized:', window.innerWidth, 'x', window.innerHeight);
        queueAnalyticsEvent('[INT]_window_resized', {
            width: window.innerWidth,
            height: window.innerHeight
        });
        updateLastActivity(); // به‌روزرسانی زمان آخرین فعالیت
    } catch (error) {
        console.warn('Error tracking window resize:', error);
    }
}, 500)); // Debounce resize event

// ردیابی خطاهای JS (با graceful degradation)
window.onerror = (message, source, lineno, colno, error) => {
    try {
        jsErrorCount++;
        localStorage.setItem('js_error_count', jsErrorCount);
        updateAnalyticsDisplays();
        console.error('JS Error Captured:', message, source, lineno, colno, error);
        queueAnalyticsEvent('[INT]_js_error', {
            message: message,
            source: source,
            lineno: lineno,
            colno: colno,
            error_stack: error ? error.stack : 'N/A' // Capture stack trace for better debugging
        });
        updateLastActivity(); // به‌روزرسانی زمان آخرین فعالیت
    } catch (err) {
        console.warn('Error reporting JS error:', err);
    }
    return false; // اجازه می‌دهد خطا به کنسول مرورگر نیز برسد
};

// ردیابی افزودن/حذف از سبد خرید
document.querySelectorAll('.addToCartBtn').forEach(button => {
    button.addEventListener('click', (event) => {
        try {
            const productId = button.dataset.productId;
            const quantity = parseInt(button.dataset.quantity || '1'); // Default to 1 if not specified
            const price = parseFloat(button.dataset.price || '0'); // Get price from data-attribute
            const variantId = button.dataset.variantId || null; // Get variant ID from data-attribute

            console.log(`Product ${productId} added to cart. Qty: ${quantity}, Price: ${price}, Variant: ${variantId}`);
            queueAnalyticsEvent('[INT]_add_to_cart', {
                product_id: productId,
                quantity: quantity,
                price: price,
                variant_id: variantId
            });
            updateLastActivity(); // به‌روزرسانی زمان آخرین فعالیت
        } catch (error) {
            console.warn('Error tracking add to cart:', error);
        }
    });
});

document.querySelectorAll('.removeFromCartBtn').forEach(button => {
    button.addEventListener('click', (event) => {
        try {
            const productId = button.dataset.productId;
            // remaining_quantity might need to be fetched dynamically after removal
            const remainingQuantity = parseInt(button.dataset.remainingQuantity || '-1'); // Placeholder for remaining quantity

            console.log(`Product ${productId} removed from cart. Remaining: ${remainingQuantity}`);
            queueAnalyticsEvent('[INT]_remove_from_cart', {
                product_id: productId,
                remaining_quantity: remainingQuantity
            });
            updateLastActivity(); // به‌روزرسانی زمان آخرین فعالیت
        } catch (error) {
            console.warn('Error tracking remove from cart:', error);
        }
    });
});

// --- ردیابی مشاهده سبد خرید ---
document.addEventListener('DOMContentLoaded', () => {
    const cartPageContainer = document.getElementById('cart-page-container'); // Assuming this ID exists on your cart page
    const cartItemsContainer = document.getElementById('cart-items-container'); // Or this one

    if (cartPageContainer || cartItemsContainer) {
        try {
            // Fetch cart contents dynamically if possible, or get from DOM
            const totalItemsElement = document.getElementById('cart-total-items'); // You might need to add this element
            const totalValueElement = document.getElementById('cart-total-price'); // Assuming this ID exists

            const totalItems = totalItemsElement ? parseInt(totalItemsElement.textContent || '0') : 0;
            const totalValue = totalValueElement ? parseFloat(totalValueElement.textContent.replace(/[^0-9.-]+/g,"") || '0') : 0; // Remove non-numeric chars for price parsing

            console.log(`Cart View: Total Items: ${totalItems}, Total Value: ${totalValue}`);
            queueAnalyticsEvent('[INT]_cart_view', {
                total_items: totalItems,
                total_value: totalValue
            });
        } catch (error) {
            console.warn('Error tracking cart view:', error);
        }
    }
});


// ردیابی جستجوی داخلی
const internalSearchInput = document.getElementById('internalSearchInput');
const searchBtn = document.getElementById('searchBtn');
if (searchBtn && internalSearchInput) {
    searchBtn.addEventListener('click', () => {
        try {
            const query = internalSearchInput.value;
            if (query) {
                // Assuming search results count and filters are available in the DOM or a global variable
                const resultsCount = parseInt(document.getElementById('search-results-count')?.textContent || '0'); // Add an element with this ID
                const filtersApplied = document.getElementById('search-filters-applied')?.dataset.filters || null; // Add an element with this ID and data-filters

                console.log(`Internal Search: Query: ${query}, Results: ${resultsCount}, Filters: ${filtersApplied}`);
                queueAnalyticsEvent('[INT]_search_query', {
                    query: query,
                    results_count: resultsCount,
                    filters_applied: filtersApplied ? JSON.parse(filtersApplied) : null
                });
            }
            updateLastActivity(); // به‌روزرسانی زمان آخرین فعالیت
        } catch (error) {
            console.warn('Error tracking internal search:', error);
        }
    });
}

// --- ردیابی کلیک روی نتایج جستجو ---
document.querySelectorAll('.search-result-item').forEach(item => { // Assuming search results have this class
    item.addEventListener('click', (event) => {
        try {
            const query = internalSearchInput ? internalSearchInput.value : null;
            const clickedProductId = item.dataset.productId || null; // Assuming product ID is in data-product-id
            const position = item.dataset.position || null; // Assuming position in results is in data-position

            console.log(`Search Result Click: Query: ${query}, Product: ${clickedProductId}, Position: ${position}`);
            queueAnalyticsEvent('[INT]_search_result_click', {
                query: query,
                clicked_product: clickedProductId,
                position: position
            });
            updateLastActivity();
        } catch (error) {
            console.warn('Error tracking search result click:', error);
        }
    });
});


// --- ردیابی حرکت موس (Mouse Movement) ---
// این ردیابی می‌تواند داده‌های بسیار زیادی تولید کند، بنابراین از debounce قوی و آستانه تغییر استفاده می‌شود.
const MOUSE_MOVE_THRESHOLD = 5; // Pixels to move before logging a new event
let lastMouseX = -1;
let lastMouseY = -1;

const debouncedMouseMove = debounce((event) => {
    try {
        // Only queue if mouse position has changed significantly
        if (Math.abs(event.clientX - lastMouseX) > MOUSE_MOVE_THRESHOLD ||
            Math.abs(event.clientY - lastMouseY) > MOUSE_MOVE_THRESHOLD) {

            queueAnalyticsEvent('[INT]_mouse_move', {
                x: event.clientX,
                y: event.clientY,
                target_id: event.target.id || null,
                target_class: event.target.className || null,
                target_tag: event.target.tagName || null,
            });

            lastMouseX = event.clientX;
            lastMouseY = event.clientY;
        }
        updateLastActivity(); // به‌روزرسانی زمان آخرین فعالیت
    } catch (error) {
        console.warn('Error tracking mouse move:', error);
    }
}, 100); // ارسال هر 100 میلی‌ثانیه یک بار

document.addEventListener('mousemove', debouncedMouseMove);


// --- ردیابی هاور (Hover) روی عناصر قابل تعامل ---
// این ردیابی می‌تواند روی عناصر خاص (مثل دکمه‌ها، لینک‌ها) یا به صورت کلی انجام شود.
// برای سادگی، روی دکمه‌ها و لینک‌ها ردیابی می‌کنیم.
const debouncedMouseOverInteractive = debounce((event) => {
    try {
        const target = event.target;
        if (target.tagName === 'BUTTON' || target.tagName === 'A' || target.closest('[data-track-hover]')) {
            queueAnalyticsEvent('[INT]_hover', {
                target_id: target.id || null,
                target_class: target.className || null,
                target_tag: target.tagName,
                text: target.textContent ? target.textContent.trim().substring(0, 100) : null, // Capture text content
                x: event.clientX,
                y: event.clientY
            });
        }
        updateLastActivity(); // به‌روزرسانی زمان آخرین فعالیت
    } catch (error) {
        console.warn('Error tracking hover:', error);
    }
}, 300); // ارسال هر 300 میلی‌ثانیه یک بار برای هاور

document.addEventListener('mouseover', debouncedMouseOverInteractive);


// --- ردیابی مشاهده محصول ---
document.addEventListener('DOMContentLoaded', () => {
    const productViewElement = document.querySelector('[data-product-id]');
    if (productViewElement) {
        try {
            const productId = productViewElement.dataset.productId;
            if (productId) {
                console.log(`Product View: Product ID ${productId}`);
                queueAnalyticsEvent('[INT]_product_view', { product_id: productId });
            }
        } catch (error) {
            console.warn('Error tracking product view:', error);
        }
    }
});

// --- ردیابی افزودن به لیست علاقه‌مندی‌ها ---
document.querySelectorAll('.addToWishlistBtn').forEach(button => {
    button.addEventListener('click', (event) => {
        try {
            const productId = button.dataset.productId;
            if (productId) {
                console.log(`Product ${productId} added to wishlist.`);
                queueAnalyticsEvent('[INT]_add_to_wishlist', { product_id: productId });
            }
            updateLastActivity();
        } catch (error) { // FIX: Moved catch block to correctly wrap the try block
            console.warn('Error tracking add to wishlist:', error);
        }
    });
});

// --- ردیابی کلیک روی آیتم‌های منو ---
document.querySelectorAll('.nav-menu-item').forEach(menuItem => { // Assuming menu items have this class
    menuItem.addEventListener('click', (event) => {
        try {
            const itemText = menuItem.textContent ? menuItem.textContent.trim() : null;
            const itemHref = menuItem.getAttribute('href') || null;
            const itemLevel = menuItem.dataset.level || '1'; // Assuming data-level attribute for menu depth

            console.log(`Menu Click: Item: ${itemText}, Path: ${itemHref}, Level: ${itemLevel}`);
            queueAnalyticsEvent('[INT]_menu_click', {
                menu_item: itemText,
                level: parseInt(itemLevel),
                path: itemHref
            });
            updateLastActivity();
        } catch (error) {
            console.warn('Error tracking menu click:', error);
        }
    });
});

// --- ردیابی مشاهده دسته بندی محصولات ---
document.addEventListener('DOMContentLoaded', () => {
    const categoryViewElement = document.querySelector('[data-category-id]'); // Assuming category page container has this attribute
    if (categoryViewElement) {
        try {
            const categoryId = categoryViewElement.dataset.categoryId;
            const productsCount = parseInt(categoryViewElement.dataset.productsCount || '0'); // Assuming total products in category

            if (categoryId) {
                console.log(`Category View: Category ID ${categoryId}, Products Count: ${productsCount}`);
                queueAnalyticsEvent('[INT]_category_view', {
                    category_id: categoryId,
                    products_count: productsCount
                });
            }
        } catch (error) {
            console.warn('Error tracking category view:', error);
        }
    }
});


// دریافت اطلاعات دستگاه
function getDeviceInfo() {
    try {
        const userAgent = navigator.userAgent;
        let deviceType = 'Desktop';
        if (/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i.test(userAgent)) {
            deviceType = 'Tablet';
        } else if (/Mobile|iP(hone|od)|Android|BlackBerry|IEMobile|Kindle|Silk-Source|Jasmine|Blazer|Opera Mini|CriOS|Firefox|FxiOS/i.test(userAgent)) {
            deviceType = 'Mobile';
        }

        let os = 'Unknown OS';
        if (userAgent.indexOf("Win") != -1) os = "Windows";
        if (userAgent.indexOf("Mac") != -1) os = "macOS";
        if (userAgent.indexOf("Linux") != -1) os = "Linux";
        if (userAgent.indexOf("Android") != -1) os = "Android";
        if (userAgent.indexOf("iOS") != -1 || /(iPhone|iPad|iPod)/i.test(userAgent)) os = "iOS";

        let browser = 'Unknown Browser';
        if (userAgent.indexOf("Chrome") != -1 && !userAgent.includes("Edg") && !userAgent.includes("OPR")) browser = "Chrome";
        else if (userAgent.indexOf("Firefox") != -1) browser = "Firefox";
        else if (userAgent.indexOf("Safari") != -1 && !userAgent.includes("Chrome")) browser = "Safari";
        else if (userAgent.indexOf("Edg") != -1) browser = "Edge";
        else if (userAgent.indexOf("Opera") != -1 || userAgent.indexOf("OPR") != -1) browser = "Opera";
        else if (userAgent.indexOf("MSIE") != -1 || userAgent.indexOf("Trident") != -1) browser = "IE";

        return {
            deviceType: deviceType,
            os: os,
            browser: browser,
            resolution: `${window.screen.width}x${window.screen.height}`
        };
    } catch (error) {
        console.warn('Error getting device info:', error);
        return { deviceType: 'N/A', os: 'N/A', browser: 'N/A', resolution: 'N/A' };
    }
}

// نمایش اطلاعات دستگاه
const deviceInfo = getDeviceInfo();
const deviceTypeDisplay = document.getElementById('deviceTypeDisplay');
const osDisplay = document.getElementById('osDisplay');
const browserDisplay = document.getElementById('browserDisplay');
const resolutionDisplay = document.getElementById('resolutionDisplay');

if (deviceTypeDisplay) deviceTypeDisplay.textContent = deviceInfo.deviceType;
if (osDisplay) osDisplay.textContent = deviceInfo.os;
if (browserDisplay) browserDisplay.textContent = deviceInfo.browser;
if (resolutionDisplay) resolutionDisplay.textContent = deviceInfo.resolution;


// ردیابی زمان لود صفحه
window.addEventListener('load', () => {
    try {
        const performanceTiming = window.performance.timing;
        const pageLoadTime = Math.max(0, performanceTiming.loadEventEnd - performanceTiming.navigationStart); // Ensure non-negative
        const pageLoadTimeDisplay = document.getElementById('pageLoadTimeDisplay');
        if (pageLoadTimeDisplay) {
            pageLoadTimeDisplay.textContent = `${pageLoadTime}ms`;
        }
        queueAnalyticsEvent('[INT]_page_load_time', { load_time_ms: pageLoadTime });
        updateLastActivity(); // به‌روزرسانی زمان آخرین فعالیت
    } catch (error) {
        console.warn('Error tracking page load time:', error);
    }
});


// شروع ردیابی زمان هنگام بارگذاری صفحه
window.onload = startTrackingTime;

// ارسال داده‌های تحلیلی هنگام ترک صفحه (قبل از بسته شدن یا رفرش)
window.addEventListener('beforeunload', () => {
    try {
        stopTrackingTime();
        queueAnalyticsEvent('[INT]_session_end'); // ارسال یک رویداد نهایی پایان نشست
        sendBatchAnalyticsData.flush(); // Force send any pending data immediately
    } catch (error) {
        console.warn('Error on beforeunload analytics:', error);
    }
});

// ارسال داده‌های تحلیلی به صورت دوره‌ای (هر 30 ثانیه) - این خط حذف می‌شود و با Heartbeat هوشمند جایگزین می‌شود
// setInterval(() => {
//     try {
//         queueAnalyticsEvent('[INT]_update_session'); // رویداد به‌روزرسانی نشست
//     } catch (error) {
//         console.warn('Error queuing periodic session update:', error);
//     }
// }, 30000); // 30 ثانیه

// --- مدیریت احراز هویت (JWT) ---
// این بخش از کد برای مثال UI است و باید با سیستم احراز هویت موجود شما ادغام شود.
// در پروژه واقعی، این منطق در فایل‌های auth-modules.js یا مشابه آن قرار می‌گیرد.
document.addEventListener('DOMContentLoaded', () => {
    const loginBtn = document.getElementById('loginBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    const getUserBtn = document.getElementById('getUserBtn');
    const generateErrorBtn = document.getElementById('generateErrorBtn');

    if (loginBtn) {
        loginBtn.addEventListener('click', async () => {
            try {
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;

                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ email, password })
                });

                const result = await response.json();
                if (response.ok) {
                    showMessage(result.message, 'success');
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                showMessage('خطا در ورود: ' + error.message, 'error');
            }
        });
    }

    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            try {
                const response = await fetch('/api/auth/logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const result = await response.json();
                if (response.ok) {
                    showMessage(result.message, 'success');
                    const userInfoDiv = document.getElementById('userInfo');
                    const userDataPre = document.getElementById('userData');
                    if (userInfoDiv) userInfoDiv.classList.add('hidden');
                    if (userDataPre) userDataPre.textContent = '';
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                showMessage('خطا در خروج: ' + error.message, 'error');
            }
        });
    }

    if (getUserBtn) {
        getUserBtn.addEventListener('click', async () => {
            try {
                const response = await fetch('/api/user', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const result = await response.json();
                const userInfoDiv = document.getElementById('userInfo');
                const userDataPre = document.getElementById('userData');

                if (response.ok) {
                    if (userDataPre) userDataPre.textContent = JSON.stringify(result, null, 2);
                    if (userInfoDiv) userInfoDiv.classList.remove('hidden');
                    showMessage('اطلاعات کاربر با موفقیت دریافت شد.', 'success');
                } else {
                    showMessage(result.message || 'خطا در دریافت اطلاعات کاربر', 'error');
                    if (userInfoDiv) userInfoDiv.classList.add('hidden');
                }
            } catch (error) {
                showMessage('خطا در دریافت اطلاعات کاربر: ' + error.message, 'error');
            }
        });
    }

    // برای تولید خطای JS برای تست ردیابی خطا
    if (generateErrorBtn) {
        generateErrorBtn.addEventListener('click', () => {
            // این خط یک خطای عمدی ایجاد می کند
            console.log(undefinedVariable.property);
        });
    }
});
