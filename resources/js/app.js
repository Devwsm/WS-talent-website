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
    // autoplayDelay = jeda antar slide (ms), bukan opsi bawaan Swiper — dipisah dulu.
    const { autoplayDelay = 3000, ...swiperOptions } = extraOptions;

    if (!el) return null;

    // Orang yang sudah nyalain "reduce motion" di HP/laptopnya (biasanya
    // karena vertigo/motion sensitivity) nggak boleh dapet autoplay & animasi
    // slide penuh — matikan autoplay sama sekali buat mereka.
    const prefersReducedMotion = window.matchMedia(
        "(prefers-reduced-motion: reduce)",
    ).matches;

    // Loop cuma masuk akal kalau jumlah slide > slide yang tampil sekaligus.
    // Merch cuma 4 slide sedangkan desktop menampilkan 4 sekaligus → loop bikin
    // carousel patah/kedip. Hitung tampilan terbanyak dari breakpoint, lalu putuskan.
    const slideCount = el.querySelectorAll(
        ":scope > .swiper-wrapper > .swiper-slide",
    ).length;
    const maxPerView = Math.max(
        swiperOptions.slidesPerView ?? 1,
        ...Object.values(swiperOptions.breakpoints ?? {}).map(
            (b) => b.slidesPerView ?? 1,
        ),
    );
    const canLoop = slideCount > maxPerView;

    const swiper = new Swiper(el, {
        modules: [Navigation, Pagination, Autoplay, A11y, Keyboard],

        slidesPerView: 1,
        spaceBetween: 20,
        loop: canLoop,
        // tanpa loop: autoplay balik ke slide pertama di akhir, dan kalau semua slide
        // sudah muat di layar, swiper dikunci (panah & bullet otomatis hilang)
        rewind: !canLoop,
        watchOverflow: !canLoop,
        speed: prefersReducedMotion ? 0 : 300,

        autoplay: prefersReducedMotion
            ? false
            : {
                  delay: autoplayDelay,
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

        ...swiperOptions,
    });

    // "pauseOnMouseEnter" cuma jalan buat mouse — di HP (touch) nggak ada
    // event mouse enter, jadi carousel tetap auto-geser walau user lagi coba
    // baca/liat foto. Pause manual selama user nyentuh slide-nya.
    if (swiper.autoplay) {
        el.addEventListener("touchstart", () => swiper.autoplay.stop(), {
            passive: true,
        });
        el.addEventListener("touchend", () => swiper.autoplay.start(), {
            passive: true,
        });
    }

    return swiper;
}

// Header / Videos
initSwiper(".videosSwiper", "#header");

// Banner — geser/seret saja (tanpa panah & pagination). Kalau cuma 1 banner,
// Swiper otomatis terkunci (nggak geser, nggak autoplay).
initSwiper(".bannerSwiper", "#banner-slider", {
    navigation: false,
    pagination: false,
    spaceBetween: 0,
    autoHeight: true,
    grabCursor: true,
    autoplayDelay: 5000,
});

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
