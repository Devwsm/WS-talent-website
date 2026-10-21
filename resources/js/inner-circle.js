/*
 * WS Inner Circle — perilaku UI dasar (vanilla JS, tanpa framework).
 * Fase 1: modal, notifikasi/konfirmasi bertema lavender, dan penampung tombol "Keluar".
 * Fitur lain (scanner QR, suka, klaim) ditambahkan di fase berikutnya.
 */
import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";

// SweetAlert2 bertema lavender (wrapper di resources/js/sweetalert.js bertema gelap, jadi tidak dipakai di sini).
const ic = Swal.mixin({
    background: "#fcfbff",
    color: "#151522",
    buttonsStyling: false,
    customClass: {
        popup: "ic-swal",
        confirmButton: "ic-tombol",
        cancelButton: "ic-tombol ic-tombol--terang",
        actions: "ic-swal__aksi",
    },
});

const toast = ic.mixin({
    toast: true,
    position: "top",
    showConfirmButton: false,
    timer: 2800,
    timerProgressBar: true,
});

window.icToast = (teks, ikon = "success") =>
    toast.fire({ icon: ikon, title: teks });
window.icKonfirmasi = (opsi) =>
    ic.fire({
        showCancelButton: true,
        confirmButtonText: "Ya",
        cancelButtonText: "Batal",
        ...opsi,
    });

// ---------- Modal ----------
let pemicuTerakhir = null;

function bukaModal(id, pemicu) {
    const modal = document.getElementById(id);
    if (!modal) return;
    pemicuTerakhir = pemicu ?? document.activeElement;
    modal.hidden = false;
    document.body.classList.add("ic-modal-terbuka");
    modal.querySelector(".ic-modal__dialog")?.focus();
}

function tutupModal(modal) {
    if (!modal || modal.hidden) return;
    modal.hidden = true;
    document.body.classList.remove("ic-modal-terbuka");
    pemicuTerakhir?.focus?.();
}

document.addEventListener("click", (e) => {
    const buka = e.target.closest("[data-ic-modal-buka]");
    if (buka) {
        e.preventDefault();
        bukaModal(buka.dataset.icModalBuka, buka);
        return;
    }

    const tutup = e.target.closest("[data-ic-modal-tutup]");
    if (tutup) {
        tutupModal(tutup.closest(".ic-modal"));
        return;
    }

    // Klik di latar gelap (di luar kotak dialog) menutup modal.
    if (e.target.classList?.contains("ic-modal")) {
        tutupModal(e.target);
    }
});

document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
        tutupModal(document.querySelector(".ic-modal:not([hidden])"));
    }
});

// ---------- Keluar (sementara) ----------
// Berfungsi sungguhan setelah login anggota tersedia (Fase 4).
document.querySelector("[data-ic-keluar]")?.addEventListener("click", () => {
    window.icToast("Keluar akan aktif setelah login anggota tersedia.", "info");
});
