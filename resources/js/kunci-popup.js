/**
 * Penjaga sentuhan untuk popup (<dialog> modal dan popup konfirmasi SweetAlert).
 *
 * Kunci scroll di CSS (app.css) menahan halaman di belakang popup. Penjaga ini menutup sisi lainnya: seretan jari
 * (atau mouse pada emulasi sentuh) di dalam popup tidak boleh menggeser apa pun, termasuk popup itu sendiri. Seretan
 * hanya diteruskan bila ada area di bawah jari yang memang masih bisa digulir ke arah seretan; selebihnya dibatalkan,
 * sehingga tidak ada gulir sisa, efek memantul, rantai scroll ke halaman, maupun tarik-untuk-muat-ulang.
 *
 * Dipasang sekali di document (delegasi), jadi berlaku juga untuk popup yang dibuat belakangan.
 */
const AKAR_POPUP = "dialog[open], .swal2-container";

// Elemen ini perlu menerima seretan sendiri (geser slider, dsb.).
const KECUALI = 'input[type="range"]';

const gulirPenuh = (nilai) =>
    nilai === "auto" || nilai === "scroll" || nilai === "overlay";

/** Apakah `el` masih bisa digulir ke arah seretan jari (dx, dy)? Jari turun = isi bergerak turun = gulir ke atas. */
function bisaDigulir(el, dx, dy) {
    const gaya = getComputedStyle(el);

    if (Math.abs(dy) >= Math.abs(dx)) {
        if (
            !gulirPenuh(gaya.overflowY) ||
            el.scrollHeight <= el.clientHeight + 1
        )
            return false;

        return dy > 0
            ? el.scrollTop > 0
            : el.scrollTop + el.clientHeight < el.scrollHeight - 1;
    }

    if (!gulirPenuh(gaya.overflowX) || el.scrollWidth <= el.clientWidth + 1)
        return false;

    return dx > 0
        ? el.scrollLeft > 0
        : el.scrollLeft + el.clientWidth < el.scrollWidth - 1;
}

let awal = null;

document.addEventListener(
    "touchstart",
    (e) => {
        awal =
            e.touches.length === 1
                ? { x: e.touches[0].clientX, y: e.touches[0].clientY }
                : null;
    },
    { passive: true },
);

document.addEventListener(
    "touchmove",
    (e) => {
        // Cubit dua jari (zoom) dan seretan tanpa titik awal dibiarkan.
        if (e.touches.length !== 1 || !awal || !(e.target instanceof Element))
            return;

        const akar = e.target.closest(AKAR_POPUP);
        if (!akar) return;

        // Toast SweetAlert bukan popup modal: halaman di belakangnya tetap bebas.
        if (
            akar.matches(".swal2-container") &&
            document.body.classList.contains("swal2-toast-shown")
        )
            return;

        if (e.target.closest(KECUALI)) return;

        const dx = e.touches[0].clientX - awal.x;
        const dy = e.touches[0].clientY - awal.y;
        if (dx === 0 && dy === 0) return;

        for (
            let el = e.target;
            el && el !== akar.parentElement;
            el = el.parentElement
        ) {
            if (bisaDigulir(el, dx, dy)) return;
        }

        if (e.cancelable) e.preventDefault();
    },
    { passive: false },
);
