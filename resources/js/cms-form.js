// Kenyamanan form CMS Artis (halaman dengan <body class="dash-theme">; CMS Inner Circle tidak terpengaruh):
//  1. MediaPreview: pratinjau berkas yang baru dipilih di modal edit (gambar atau video) + cek ukuran.
//  2. Penjaga "perubahan belum disimpan": peringatan saat menutup halaman atau modal yang isiannya sudah diubah.
//  3. Pratinjau jadwal tayang di panel pratinjau.

const MAKS_GAMBAR = 1 * 1024 * 1024; // sama dengan batas server
const MAKS_VIDEO = 50 * 1024 * 1024;

/**
 * Dipakai lewat onchange="new MediaPreview(this).handle(event)". Markup yang dibutuhkan:
 * pembungkus [data-media-input] berisi input dengan data-preview-target="X", elemen #preview-X berisi
 * img.preview-el (dan boleh video.preview-el), serta p.error-el.
 */
class MediaPreview {
    constructor(input) {
        this.input = input;
        this.wrapper = document.getElementById(
            "preview-" + input.dataset.previewTarget,
        );
        this.img = this.wrapper?.querySelector("img.preview-el") ?? null;
        this.video = this.wrapper?.querySelector("video.preview-el") ?? null;
        this.errorEl =
            input.closest("[data-media-input]")?.querySelector(".error-el") ??
            null;
    }

    reset() {
        this.wrapper?.classList.add("hidden");
        this.img?.classList.add("hidden");
        this.video?.classList.add("hidden");
        if (this.errorEl) {
            this.errorEl.classList.add("hidden");
            this.errorEl.textContent = "";
        }
    }

    handle(event) {
        const file = event.target.files[0];
        this.reset();
        if (!file || !this.wrapper) return;

        const gambar = file.type.startsWith("image/");
        const video = file.type.startsWith("video/") && this.video !== null;

        if (!gambar && !video) return this.gagal("Format file tidak didukung.");
        if (gambar && file.size > MAKS_GAMBAR)
            return this.gagal("Ukuran gambar tidak boleh lebih dari 1MB.");
        if (video && file.size > MAKS_VIDEO)
            return this.gagal("Ukuran video tidak boleh lebih dari 50MB.");

        this.wrapper.classList.remove("hidden");

        if (gambar && this.img) {
            if (this.img.dataset.url) URL.revokeObjectURL(this.img.dataset.url);
            this.img.dataset.url = URL.createObjectURL(file);
            this.img.src = this.img.dataset.url;
            this.img.classList.remove("hidden");
        } else if (video) {
            if (this.video.dataset.url)
                URL.revokeObjectURL(this.video.dataset.url);
            this.video.dataset.url = URL.createObjectURL(file);
            this.video.src = this.video.dataset.url;
            this.video.classList.remove("hidden");
        }
    }

    gagal(pesan) {
        if (this.errorEl) {
            this.errorEl.textContent = pesan;
            this.errorEl.classList.remove("hidden");
        }
        this.input.value = "";
    }
}
window.MediaPreview = MediaPreview;

// ---------- Penjaga perubahan belum disimpan ----------

const PESAN_BUANG =
    "Ada perubahan yang belum disimpan. Tutup dan buang perubahan itu?";

/** Form yang dijaga: POST, punya isian yang bisa diubah, dan tidak menandai data-tanpa-jaga. */
function formDijaga(form) {
    if (
        form.method.toLowerCase() !== "post" ||
        form.hasAttribute("data-tanpa-jaga") ||
        form.hasAttribute("data-swal-confirm")
    ) {
        return false;
    }
    return (
        form.querySelector(
            'input:not([type="hidden"]):not([type="submit"]), textarea, select',
        ) !== null
    );
}

function pasangPenjaga() {
    const kotor = new Set();

    document.querySelectorAll("form").forEach((form) => {
        if (!formDijaga(form)) return;

        const tandai = (event) => {
            if (event.target?.closest?.("[data-tanpa-jaga]")) return;
            kotor.add(form);
        };
        form.addEventListener("input", tandai);
        form.addEventListener("change", tandai);
        form.addEventListener("jaga:ubah", () => kotor.add(form)); // dikirim editor teks kaya (Quill)
        form.addEventListener("submit", () => kotor.delete(form));
        form.addEventListener("reset", () => {
            kotor.delete(form);
            form.querySelectorAll("input[data-preview-target]").forEach((i) => {
                try {
                    new MediaPreview(i).reset();
                } catch {
                    /* pratinjau bukan alasan reset gagal */
                }
            });
        });

        // Modal edit: tombol X, Batal, dan Esc menanyakan dulu bila isiannya sudah diubah.
        const dialog = form.closest("dialog");
        if (dialog && !dialog.dataset.jagaTerpasang) {
            dialog.dataset.jagaTerpasang = "1";
            const tutupAsli = dialog.close.bind(dialog);
            const tutup = (hasil) => {
                if (kotor.has(form)) {
                    if (!window.confirm(PESAN_BUANG)) return;
                    form.reset();
                }
                tutupAsli(hasil);
            };
            dialog.close = tutup; // tombol bawaan memanggil dialog.close()
            dialog.addEventListener("cancel", (event) => {
                event.preventDefault(); // Esc: lewat jalur yang sama dengan tombol
                tutup();
            });
        }
    });

    window.addEventListener("beforeunload", (event) => {
        if (kotor.size > 0) {
            event.preventDefault();
            event.returnValue = ""; // browser menampilkan pesannya sendiri
        }
    });
}

// ---------- Pratinjau jadwal tayang ----------

function pasangPratinjauJadwal() {
    document.querySelectorAll("input[data-jadwal-target]").forEach((input) => {
        const target = document.querySelector(input.dataset.jadwalTarget);
        if (!target) return;

        const perbarui = () => {
            const tanggal = input.value ? new Date(input.value) : null;
            target.textContent =
                tanggal && !isNaN(tanggal)
                    ? "Tampil mulai " +
                      tanggal
                          .toLocaleString("id-ID", {
                              day: "numeric",
                              month: "long",
                              year: "numeric",
                              hour: "2-digit",
                              minute: "2-digit",
                          })
                          .replace(/\./g, ".") +
                      " WIB"
                    : "Langsung tampil";
        };
        input.addEventListener("input", perbarui);
        input.addEventListener("change", perbarui);
        perbarui();
    });
}

function mulai() {
    if (!document.body.classList.contains("dash-theme")) return;
    pasangPenjaga();
    pasangPratinjauJadwal();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", mulai, { once: true });
} else {
    mulai();
}
