/*
 * WS Inner Circle — perilaku UI dasar (vanilla JS, tanpa framework).
 * Fase 1: modal dan notifikasi/konfirmasi bertema lavender.
 * Fase 2: toast dari flash sesi, tombol "segera hadir", tampil/sembunyi kata sandi, filter otomatis,
 *         pratinjau foto profil, validasi ukuran berkas, tombol klaim, dan suka (belum disimpan).
 * Fase 4: pemindai QR check-in (kamera dan unggah gambar) memakai pustaka qr-scanner yang dimuat
 *         hanya saat dibutuhkan.
 * Fase 5: klaim hadiah sungguhan: form POST dengan konfirmasi (data-ic-konfirmasi) dan pengaman
 *         kirim ganda (data-ic-sekali), serta toast galat dari flash sesi.
 *         Simpan suka ditambahkan di fase berikutnya.
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

// Toast galat tampil lebih lama supaya pesan yang panjang sempat terbaca.
window.icToast = (teks, ikon = "success") =>
    toast.fire({
        icon: ikon,
        title: teks,
        timer: ikon === "error" ? 6000 : 2800,
    });
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
    if (modal.id === "modal-pindai") hentikanPemindai();
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

// ---------- Toast dari flash sesi ----------
// Layout menaruh pesan flash di <body data-ic-toast="...">, mis. setelah masuk.
const pesanAwal = document.body.dataset.icToast;
if (pesanAwal) {
    window.icToast(pesanAwal);
}
// Pesan galat dari server (mis. pengajuan hadiah ditolak aturan) memakai flash terpisah.
const pesanGalat = document.body.dataset.icToastGalat;
if (pesanGalat) {
    window.icToast(pesanGalat, "error");
}

// ---------- Fitur yang belum aktif ----------
// Tombol atau form dengan data-ic-segera="pesan" hanya menampilkan toast (Fase 2: tampilan dulu).
const PESAN_SEGERA = "Fitur ini segera hadir.";

document.addEventListener("click", (e) => {
    const tombol = e.target.closest("button[data-ic-segera]");
    if (!tombol || tombol.type === "submit") return;
    window.icToast(tombol.dataset.icSegera || PESAN_SEGERA, "info");
});

document.addEventListener("submit", (e) => {
    const form = e.target.closest("form[data-ic-segera]");
    if (!form) return;
    e.preventDefault();
    window.icToast(form.dataset.icSegera || PESAN_SEGERA, "info");
    tutupModal(form.closest(".ic-modal"));
});

// ---------- Tampilkan kata sandi ----------
document.addEventListener("change", (e) => {
    const centang = e.target.closest("[data-ic-tampil-sandi]");
    if (!centang) return;
    document
        .querySelectorAll(centang.dataset.icTampilSandi)
        .forEach(
            (input) => (input.type = centang.checked ? "text" : "password"),
        );
});

// ---------- Filter Check-in: kirim otomatis saat pilihan berubah ----------
// Tanpa JS tombol "Terapkan" tetap berfungsi.
document.addEventListener("change", (e) => {
    const pilihan = e.target.closest("select[data-ic-otomatis]");
    pilihan?.form?.submit();
});

// ---------- Validasi ukuran berkas ----------
// Input dengan data-ic-maks-mb menolak berkas yang terlalu besar sebelum dikirim.
document.addEventListener("change", (e) => {
    const input = e.target.closest("input[type=file][data-ic-maks-mb]");
    const berkas = input?.files?.[0];
    if (!berkas) return;
    const maks = Number(input.dataset.icMaksMb);
    if (berkas.size > maks * 1024 * 1024) {
        input.value = "";
        window.icToast(`Ukuran foto maksimal ${maks} MB.`, "error");
    }
});

// ---------- Klaim hadiah ----------
// Hadiah tanpa pilihan: form POST yang minta konfirmasi dulu (data-ic-konfirmasi). Tanpa JS form
// langsung terkirim, jadi aturannya (stempel cukup, sekali per tahap) selalu dicek di server.
document.addEventListener("submit", async (e) => {
    const form = e.target.closest("form[data-ic-konfirmasi]");
    if (!form || form.dataset.icSudahKonfirmasi) return;
    e.preventDefault();
    const hasil = await window.icKonfirmasi({
        title: "Ajukan hadiah ini?",
        text: form.dataset.judul,
        confirmButtonText: "Ya, ajukan",
    });
    if (!hasil.isConfirmed) return;
    form.dataset.icSudahKonfirmasi = "1";
    form.requestSubmit();
});

// Pengaman kirim ganda: setelah form data-ic-sekali terkirim, tombolnya dikunci. Klik kedua tidak
// pernah sampai ke server; bila sampai juga (dua tab), kunci unik di database yang menolaknya.
document.addEventListener("submit", (e) => {
    const form = e.target.closest("form[data-ic-sekali]");
    if (!form || e.defaultPrevented) return;
    form.querySelectorAll("button[type=submit]").forEach((t) => {
        // Ditunda satu tick supaya nilai tombol tetap ikut terkirim.
        setTimeout(() => (t.disabled = true), 0);
    });
});

// Kembali lewat tombol "back" memulihkan halaman dari cache apa adanya: buka kembali kunci kirim
// dan tanda konfirmasi supaya form bisa dipakai lagi.
window.addEventListener("pageshow", (e) => {
    if (!e.persisted) return;
    document
        .querySelectorAll("form[data-ic-sekali] button[type=submit]")
        .forEach((t) => (t.disabled = false));
    document
        .querySelectorAll("form[data-ic-konfirmasi]")
        .forEach((f) => delete f.dataset.icSudahKonfirmasi);
});

// ---------- Suka kenangan ----------
// Hanya mengubah tampilan di halaman ini; tersimpan ke server di Fase 6.
document.addEventListener("click", (e) => {
    const tombol = e.target.closest("[data-ic-suka]");
    if (!tombol) return;
    const aktif = tombol.getAttribute("aria-pressed") !== "true";
    const angka = tombol.querySelector("span");
    tombol.setAttribute("aria-pressed", String(aktif));
    tombol.classList.toggle("is-suka", aktif);
    tombol
        .querySelector("i")
        ?.setAttribute("class", `bi ${aktif ? "bi-heart-fill" : "bi-heart"}`);
    if (angka)
        angka.textContent = String(
            Math.max(0, Number(angka.textContent) + (aktif ? 1 : -1)),
        );
});

// ---------- Foto profil: pilih dan pratinjau ----------
const inputFoto = document.getElementById("ic-foto-input");
if (inputFoto) {
    const pratinjau = document.getElementById("ic-foto-pratinjau");
    const tombolPilih = document.getElementById("ic-foto-pilih");
    const tombolSimpan = document.getElementById("ic-foto-simpan");
    const umpan = document.getElementById("ic-foto-umpan");
    let urlSementara = null;

    tombolPilih.addEventListener("click", () => inputFoto.click());

    inputFoto.addEventListener("change", () => {
        const berkas = inputFoto.files?.[0];
        // Berkas yang terlalu besar sudah dikosongkan oleh validasi ukuran di atas.
        if (!berkas) {
            tombolSimpan.disabled = true;
            return;
        }
        if (urlSementara) URL.revokeObjectURL(urlSementara);
        urlSementara = URL.createObjectURL(berkas);

        const gambar = document.createElement("img");
        gambar.src = urlSementara;
        gambar.alt = "Pratinjau foto profil baru";
        pratinjau.replaceChildren(gambar);

        tombolSimpan.disabled = false;
        umpan.textContent = "Foto dipilih. Tekan Simpan foto untuk memakainya.";
    });

    tombolSimpan.addEventListener("click", () => {
        window.icToast("Simpan foto segera aktif.", "info");
    });
}

// ---------- Pindai QR check-in ----------
// QR berisi URL .../inner-circle/check-in?kode=WS-XXXXXXXXXXXX. Yang diambil hanya tokennya; anggota tetap
// menekan Verifikasi sendiri. Kamera butuh HTTPS (atau localhost) dan izin dari pengguna.
let pemindai = null;
let galatTerakhirPada = 0;

const muatQrScanner = async () => (await import("qr-scanner")).default;

function ambilToken(teks) {
    const cocok = String(teks).match(/WS-[A-Za-z0-9]{12}/i);
    return cocok ? cocok[0].toUpperCase() : null;
}

function terapkanHasilQr(teks) {
    const token = ambilToken(teks);
    const input = document.getElementById("kode");
    if (!token || !input) {
        // Kamera membaca banyak bingkai per detik: batasi pemberitahuan galat agar tidak menumpuk.
        if (Date.now() - galatTerakhirPada > 3000) {
            galatTerakhirPada = Date.now();
            window.icToast(
                "QR ini bukan QR check-in WS Inner Circle.",
                "error",
            );
        }
        return false;
    }
    input.value = token;
    window.icToast("QR terbaca. Tekan Verifikasi untuk mendapat stempel.");
    input.form?.querySelector("button[type=submit]")?.focus();
    return true;
}

async function mulaiPemindai() {
    const video = document.getElementById("ic-pindai-video");
    const umpan = document.getElementById("ic-pindai-umpan");
    if (!video || !umpan) return;
    umpan.textContent = "Menyalakan kamera…";
    try {
        const QrScanner = await muatQrScanner();
        if (!(await QrScanner.hasCamera())) {
            umpan.textContent =
                "Kamera tidak ditemukan. Ketik kodenya atau unggah gambar QR.";
            return;
        }
        pemindai = new QrScanner(
            video,
            (hasil) => {
                if (terapkanHasilQr(hasil.data)) {
                    tutupModal(document.getElementById("modal-pindai"));
                }
            },
            {
                preferredCamera: "environment",
                returnDetailedScanResult: true,
                highlightScanRegion: true,
                maxScansPerSecond: 8,
            },
        );
        await pemindai.start();
        umpan.textContent = "Arahkan kamera ke QR di lokasi.";
    } catch {
        umpan.textContent =
            "Kamera tidak bisa dipakai. Izinkan akses kamera di browser, atau ketik kode / unggah gambar QR.";
    }
}

function hentikanPemindai() {
    pemindai?.stop();
    pemindai?.destroy();
    pemindai = null;
}

document.addEventListener("click", (e) => {
    const buka = e.target.closest("[data-ic-pindai-buka]");
    if (buka) {
        bukaModal("modal-pindai", buka);
        mulaiPemindai();
        return;
    }
    if (e.target.closest("[data-ic-pindai-berkas]")) {
        document.getElementById("ic-qr-berkas")?.click();
    }
});

document.addEventListener("change", async (e) => {
    const input = e.target.closest("#ic-qr-berkas");
    const berkas = input?.files?.[0];
    if (!berkas) return;
    try {
        const QrScanner = await muatQrScanner();
        const hasil = await QrScanner.scanImage(berkas, {
            returnDetailedScanResult: true,
        });
        terapkanHasilQr(hasil.data);
    } catch {
        window.icToast(
            "QR tidak terbaca dari gambar ini. Coba foto yang lebih jelas.",
            "error",
        );
    } finally {
        input.value = "";
    }
});
