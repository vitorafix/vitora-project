// resources/js/ui/hero.js

/**
 * Initializes the hero carousel if slides are found.
 * کاروسل قهرمان را در صورت یافتن اسلایدها مقداردهی اولیه می‌کند.
 * This function is exported to be called by app.js after dynamic import.
 */
export function initHeroCarousel() {
    const heroSlides = document.querySelectorAll('.hero-slide');

    if (heroSlides.length === 0) {
        console.warn('No hero slides found. Hero carousel will not be initialized.');
        return; // از ادامه مقداردهی اولیه جلوگیری می‌کند
    }

    // اینجا کد مربوط به مقداردهی اولیه کاروسل (مثلاً با یک کتابخانه jQuery یا Vanilla JS) قرار می‌گیرد.
    // مثال:
    // $(.hero-carousel).slick({ /* options */ });
    // یا
    // new Carousel(document.querySelector('.hero-carousel'), { /* options */ });
    console.log('Hero carousel initialized with', heroSlides.length, 'slides.');
}

// The document.addEventListener('DOMContentLoaded') block is removed from here.
// app.js will dynamically import this module and call initHeroCarousel().
