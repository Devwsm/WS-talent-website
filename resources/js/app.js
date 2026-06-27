import './bootstrap';
import Swiper from 'swiper';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';

// CSS
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/autoplay';

const videosEl = document.querySelector(".videosSwiper");
new Swiper(videosEl, {
    modules: [Navigation, Pagination, Autoplay],

    slidesPerView: 1,
    spaceBetween: 20,
    loop: true,
    watchOverflow: false,

    // breakpoints: {
    //     640: { slidesPerView: 2 },
    //     1024: { slidesPerView: 4 },
    // },

    autoplay: {
        delay: 3000, // ⏱️ 3 detik
        disableOnInteraction: false, // tetap jalan walau di klik
    },

    navigation: {
        nextEl: "#header .swiper-button-next",
        prevEl: "#header .swiper-button-prev",
    },

    pagination: {
        el: "#header .swiper-pagination",
        clickable: true,
    },
});

const musichEl = document.querySelector(".musicSwiper");
new Swiper(musichEl, {
    modules: [Navigation, Pagination, Autoplay],

    slidesPerView: 1,
    spaceBetween: 20,
    loop: true,
    watchOverflow: false,

    breakpoints: {
        640: { slidesPerView: 2 },
        1024: { slidesPerView: 4 },
    },

    autoplay: {
        delay: 3000, // ⏱️ 3 detik
        disableOnInteraction: false, // tetap jalan walau di klik
    },

    navigation: {
        nextEl: "#new-music .swiper-button-next",
        prevEl: "#new-music .swiper-button-prev",
    },

    pagination: {
        el: "#new-music .swiper-pagination",
        clickable: true,
    },
});

const merchEl = document.querySelector(".merchSwiper");
new Swiper(merchEl, {
    modules: [Navigation, Pagination, Autoplay],

    slidesPerView: 1,
    spaceBetween: 20,
    loop: true,
    watchOverflow: false,

    breakpoints: {
        640: { slidesPerView: 2 },
        1024: { slidesPerView: 4 },
    },

    autoplay: {
        delay: 3000, // ⏱️ 3 detik
        disableOnInteraction: false, // tetap jalan walau di klik
    },

    navigation: {
        nextEl: "#store .swiper-button-next",
        prevEl: "#store .swiper-button-prev",
    },

    pagination: {
        el: "#store .swiper-pagination",
        clickable: true,
    },
});