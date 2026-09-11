import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";

const swal = Swal.mixin({
    background: "#090909",
    color: "#ffffff",
    buttonsStyling: false,

    customClass: {
        popup: "rounded-3xl border border-white/10",
        title: "text-white",
        htmlContainer: "text-white/60",

        confirmButton:
            "px-4 py-2 rounded-lg bg-[#5E0006] hover:bg-[#5E0006]/80 text-white font-semibold transition",

        cancelButton:
            "px-4 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-white/70 font-semibold transition ml-2",
    },
});

const Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,

    background: "#090909",
    color: "#ffffff",

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
    const result = await swal.fire({
        title: "Hapus data?",

        text:
            form.dataset.confirmMessage ||
            "Data yang dihapus tidak dapat dikembalikan.",

        icon: "warning",

        showCancelButton: true,

        confirmButtonText: "Ya, hapus",
        cancelButtonText: "Batal",

        reverseButtons: true,
        focusCancel: true,
    });

    if (!result.isConfirmed) {
        return false;
    }

    await swal.fire({
        title: "Menghapus...",

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
 * Initialize SweetAlert confirmation for delete forms.
 */
function initDeleteConfirmation() {
    document.querySelectorAll("form[data-swal-confirm]").forEach((form) => {
        if (form.dataset.swalBound === "true") {
            return;
        }

        form.addEventListener("submit", async (event) => {
            // Allow the second submit after confirmation.
            if (form.dataset.swalConfirmed === "true") {
                return;
            }

            event.preventDefault();

            const confirmed = await window.confirmDelete(form);

            if (!confirmed) {
                return;
            }

            form.dataset.swalConfirmed = "true";

            form.submit();
        });

        form.dataset.swalBound = "true";
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
