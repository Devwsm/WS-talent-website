import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";

// CMS Inner Circle memakai tema biru terang (class cms-ic di <body>); CMS Artis memakai tema gelap emas.
const temaIc = document.body.classList.contains("cms-ic");
const latarPopup = temaIc ? "#ffffff" : "#151515";
const warnaTeks = temaIc ? "#0b4778" : "#f5f5f5";

// Kelas popup dipakai bersama; tombol aksi berbahaya (hapus, undi, keluar) merah supaya tidak tertukar dengan Batal.
// Tombol: tinggi minimal 44 px supaya mudah ditekan di HP.
const kelasPopup = {
    popup: "rounded-3xl border border-white/10 max-w-[92vw]",
    title: "text-white",
    htmlContainer: "text-white/80",

    confirmButton:
        "min-h-11 px-5 py-2 rounded-lg bg-(--cms-aksen) hover:bg-(--cms-aksen)/80 text-(--cms-aksen-teks) font-semibold transition",

    cancelButton:
        "min-h-11 px-5 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-white/85 font-semibold transition ml-2",
};
const kelasBahaya = {
    ...kelasPopup,
    confirmButton:
        "min-h-11 px-5 py-2 rounded-lg bg-red-700 hover:bg-red-600 text-[#ffffff] font-semibold transition",
};

const swal = Swal.mixin({
    background: latarPopup,
    color: warnaTeks,
    buttonsStyling: false,

    // Penguncian scroll halaman (dan penjagaan lebar scrollbar) ditangani CSS di app.css; padding-right bawaan
    // SweetAlert dimatikan supaya tidak dobel dan konten tidak bergeser.
    scrollbarPadding: false,

    customClass: kelasPopup,
});

/**
 * Popup yang dipanggil dari dalam <dialog> modal (mis. popup Atur acara) harus ikut berada di dalam dialog itu:
 * dialog modal tampil di "top layer" browser, di atas apa pun yang ada di <body>, sehingga popup di <body> akan
 * tertutup dan tidak bisa ditekan. Di luar dialog tidak ada yang berubah.
 */
function dialogTerbuka() {
    const daftar = document.querySelectorAll("dialog[open]");

    return daftar.length ? daftar[daftar.length - 1] : null;
}
const tampilkanAsli = swal.fire.bind(swal);
swal.fire = (...args) => {
    const opsi = args[0];
    const dialog = dialogTerbuka();
    if (
        dialog &&
        opsi &&
        typeof opsi === "object" &&
        !opsi.toast &&
        !opsi.target
    ) {
        return tampilkanAsli({ ...opsi, target: dialog });
    }

    return tampilkanAsli(...args);
};

const Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,

    background: latarPopup,
    color: warnaTeks,

    customClass: {
        popup: "rounded-2xl border border-white/10",
    },
});

// Make SweetAlert available globally.
window.Swal = swal;
window.Toast = Toast;

/**
 * Escape HTML before inserting validation messages.
 */
function escapeHtml(value) {
    const div = document.createElement("div");

    div.textContent = value ?? "";

    return div.innerHTML;
}

/**
 * Show delete confirmation dialog.
 */
window.confirmDelete = async function (form) {
    // `form` boleh juga tombol kirim yang membawa atribut data-confirm-* (lihat initDeleteConfirmation).
    const result = await swal.fire({
        // Judul dan tombol bisa diganti per form (data-confirm-title / -button / -loading) untuk aksi yang bukan hapus.
        title: form.dataset.confirmTitle || "Hapus data?",

        text:
            form.dataset.confirmMessage ||
            "Data yang dihapus tidak dapat dikembalikan.",

        icon: form.dataset.confirmIcon || "warning",

        showCancelButton: true,

        confirmButtonText: form.dataset.confirmButton || "Ya, hapus",
        cancelButtonText: form.dataset.confirmBatal || "Batal",

        // data-confirm-tone="bahaya" = tombol konfirmasi merah (hapus, undi, keluar, tarik poin).
        customClass:
            form.dataset.confirmTone === "bahaya" ? kelasBahaya : kelasPopup,

        reverseButtons: true,
        // Fokus awal di Batal, jadi Enter yang tidak sengaja tidak menjalankan aksinya.
        focusCancel: true,
    });

    if (!result.isConfirmed) {
        return false;
    }

    await swal.fire({
        title: form.dataset.confirmLoading || "Menghapus...",

        text: "Mohon tunggu.",

        allowOutsideClick: false,
        allowEscapeKey: false,

        showConfirmButton: false,

        didOpen: () => {
            Swal.showLoading();
        },
    });

    return true;
};

/**
 * Show Laravel flash messages.
 */
function showFlashMessages() {
    // Success
    if (window.__swalSuccess) {
        Toast.fire({
            icon: "success",
            title: window.__swalSuccess,
        });
    }

    // Validation errors
    if (Array.isArray(window.__swalErrors) && window.__swalErrors.length) {
        swal.fire({
            icon: "error",

            title: "Data belum valid",

            html: `
                <ul class="text-left list-disc pl-5 space-y-1">
                    ${window.__swalErrors
                        .map((error) => `<li>${escapeHtml(error)}</li>`)
                        .join("")}
                </ul>
            `,
        });
    }

    // General error
    if (window.__swalError) {
        swal.fire({
            icon: "error",

            title: "Terjadi kesalahan",

            text: window.__swalError,
        });
    }

    // Warning
    if (window.__swalWarning) {
        swal.fire({
            icon: "warning",

            title: "Perhatian",

            text: window.__swalWarning,
        });
    }

    // Info
    if (window.__swalInfo) {
        Toast.fire({
            icon: "info",

            title: window.__swalInfo,
        });
    }
}

/**
 * Konfirmasi untuk semua form bertanda data-swal-confirm. Memakai delegasi event di document, jadi form yang baru
 * muncul belakangan (mis. isi popup yang dimuat lewat fetch) ikut terlindungi tanpa perlu dipasang ulang.
 *
 * Atribut (di <form>, atau di tombol kirimnya bila satu form punya beberapa aksi): data-swal-confirm,
 * data-confirm-title, data-confirm-message, data-confirm-button, data-confirm-loading, data-confirm-icon
 * (warning|question|info), data-confirm-tone (bahaya = tombol merah), data-confirm-batal.
 */
function initDeleteConfirmation() {
    document.addEventListener("submit", async (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        // Teks konfirmasi boleh ada di form, atau di tombol kirimnya (form dengan beberapa tombol beraksi beda,
        // mis. "Tarik poin" di sebelah "Tandai aman"); yang di tombol menang.
        const pengirimKonfirmasi =
            event.submitter instanceof HTMLElement &&
            event.submitter.hasAttribute("data-swal-confirm")
                ? event.submitter
                : null;
        const sumberKonfirmasi =
            pengirimKonfirmasi ??
            (form.hasAttribute("data-swal-confirm") ? form : null);

        if (!sumberKonfirmasi) {
            return;
        }

        // Kiriman kedua setelah dikonfirmasi lolos begitu saja.
        if (form.dataset.swalConfirmed === "true") {
            return;
        }

        event.preventDefault();

        // Sedang menunggu jawaban popup: tekan ganda diabaikan.
        if (form.dataset.swalMenunggu === "true") {
            return;
        }
        form.dataset.swalMenunggu = "true";

        let confirmed = false;
        try {
            confirmed = await window.confirmDelete(sumberKonfirmasi);
        } finally {
            delete form.dataset.swalMenunggu;
        }

        if (!confirmed) {
            return;
        }

        form.dataset.swalConfirmed = "true";

        // requestSubmit() menjaga tombol yang menekan (name/value) ikut terkirim; cadangan: submit().
        const pengirim =
            event.submitter instanceof HTMLElement
                ? event.submitter
                : undefined;
        try {
            form.requestSubmit(pengirim);
        } catch {
            form.submit();
        }
    });
}

/**
 * Initialize all SweetAlert features.
 */
function initSweetAlert() {
    showFlashMessages();
    initDeleteConfirmation();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initSweetAlert, {
        once: true,
    });
} else {
    initSweetAlert();
}
