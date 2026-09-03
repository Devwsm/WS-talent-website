import "./bootstrap";
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
 * Bikin instance Swiper dengan config dasar yang sama (navigation, pagination,
 * autoplay, a11y) supaya gak diulang-ulang di tiap section. Otomatis di-skip
 * kalau elemennya gak ada di halaman ini (mis. .musicSwiper gak ada di
 * halaman /profile) — sebelumnya ini nyebabin error di console.
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

initSwiper(".videosSwiper", "#header");

initSwiper(".musicSwiper", "#new-music", {
    breakpoints: {
        640: { slidesPerView: 2 },
        1024: { slidesPerView: 4 },
    },
});

initSwiper(".merchSwiper", "#store", {
    breakpoints: {
        640: { slidesPerView: 2 },
        1024: { slidesPerView: 4 },
    },
});
