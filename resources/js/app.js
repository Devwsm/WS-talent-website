import "./bootstrap";
import "./sweetalert";

import Swiper from "swiper";
import {
    Navigation,
    Pagination,
    Autoplay,
    A11y,
    Keyboard,
} from "swiper/modules";

// CSS
import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/pagination";
import "swiper/css/autoplay";

/**
 * Initialize a Swiper instance only when its target exists.
 */
function initSwiper(selector, scopeSelector, extraOptions = {}) {
    const el = document.querySelector(selector);

    if (!el) return null;

    return new Swiper(el, {
        modules: [Navigation, Pagination, Autoplay, A11y, Keyboard],

        slidesPerView: 1,
        spaceBetween: 20,
        loop: true,
        watchOverflow: false,

        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
            pauseOnMouseEnter: true,
        },

        keyboard: {
            enabled: true,
        },

        a11y: {
            prevSlideMessage: "Slide sebelumnya",
            nextSlideMessage: "Slide berikutnya",
            paginationBulletMessage: "Ke slide {{index}}",
        },

        navigation: {
            nextEl: `${scopeSelector} .swiper-button-next`,
            prevEl: `${scopeSelector} .swiper-button-prev`,
        },

        pagination: {
            el: `${scopeSelector} .swiper-pagination`,
            clickable: true,
        },

        ...extraOptions,
    });
}

// Header / Videos
initSwiper(".videosSwiper", "#header");

// New Music
initSwiper(".musicSwiper", "#new-music", {
    breakpoints: {
        640: {
            slidesPerView: 2,
        },
        1024: {
            slidesPerView: 4,
        },
    },
});

// Merchandise
initSwiper(".merchSwiper", "#store", {
    breakpoints: {
        640: {
            slidesPerView: 2,
        },
        1024: {
            slidesPerView: 4,
        },
    },
});
