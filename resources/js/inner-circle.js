/*
 * WS Inner Circle — perilaku UI dasar (vanilla JS, tanpa framework).
 * Fase 1: modal dan notifikasi/konfirmasi bertema lavender.
 * Fase 2: toast dari flash sesi, tombol "segera hadir", tampil/sembunyi kata sandi, filter otomatis,
 *         pratinjau foto profil, validasi ukuran berkas, tombol klaim, dan suka (belum disimpan).
 * Fase 4: pemindai QR check-in (kamera dan unggah gambar) memakai pustaka qr-scanner yang dimuat
 *         hanya saat dibutuhkan.
 * Fase 5: klaim hadiah sungguhan: form POST dengan konfirmasi (data-ic-konfirmasi) dan pengaman
 *         kirim ganda (data-ic-sekali), serta toast galat dari flash sesi.
 * Fase 6: Komunitas sungguhan: suka lewat fetch (JSON), modal yang dibuka otomatis dari server (galat
 *         validasi), konfirmasi hapus yang judulnya bisa diatur, dan foto profil yang benar-benar terkirim.
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
// Modal bisa bertumpuk (lembar check-in membuka pemindai QR di atasnya): yang dicatat adalah urutan buka beserta
// pemicunya, kunci gulir halaman baru dilepas saat modal terakhir tertutup, dan Esc/Tab hanya berlaku di yang paling atas.
const tumpukModal = [];

function bukaModal(id, pemicu) {
    const modal = document.getElementById(id);
    if (!modal || !modal.hidden) return;
    tumpukModal.push({ modal, pemicu: pemicu ?? document.activeElement });
    modal.hidden = false;
    document.body.classList.add("ic-modal-terbuka");
    modal.querySelector(".ic-modal__dialog")?.focus();
    modal.dispatchEvent(new CustomEvent("ic:modal-buka"));
}

function tutupModal(modal) {
    if (!modal || modal.hidden) return;
    modal.hidden = true;
    const urutan = tumpukModal.findIndex((m) => m.modal === modal);
    const entri = urutan >= 0 ? tumpukModal.splice(urutan, 1)[0] : null;
    if (modal.id === "modal-pindai") hentikanPemindai();
    if (tumpukModal.length === 0) {
        document.body.classList.remove("ic-modal-terbuka");
    }
    entri?.pemicu?.focus?.();
    modal.dispatchEvent(new CustomEvent("ic:modal-tutup"));
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
    const atas = tumpukModal[tumpukModal.length - 1]?.modal;
    if (!atas) return;

    if (e.key === "Escape") {
        tutupModal(atas);
        return;
    }

    // Fokus keyboard berputar di dalam modal paling atas, tidak lari ke halaman di belakangnya.
    if (e.key === "Tab") {
        const dialog = atas.querySelector(".ic-modal__dialog");
        const dapatFokus = Array.from(
            atas.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled])',
            ),
        ).filter((el) => !el.hidden && el.offsetParent !== null);
        if (dapatFokus.length === 0) return;
        const pertama = dapatFokus[0];
        const terakhir = dapatFokus[dapatFokus.length - 1];
        if (
            e.shiftKey &&
            (document.activeElement === pertama ||
                document.activeElement === dialog)
        ) {
            e.preventDefault();
            terakhir.focus();
        } else if (!e.shiftKey && document.activeElement === terakhir) {
            e.preventDefault();
            pertama.focus();
        }
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
    // Bawaan untuk pengajuan hadiah; form lain (hapus kenangan, hapus foto) mengatur sendiri lewat data-ic-konfirmasi-*.
    const hasil = await window.icKonfirmasi({
        title: form.dataset.icKonfirmasiJudul || "Ajukan hadiah ini?",
        text: form.dataset.judul,
        confirmButtonText: form.dataset.icKonfirmasiTombol || "Ya, ajukan",
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
    // form.elements ikut menghitung tombol di luar form yang memakai atribut form="id".
    Array.from(form.elements)
        .filter((t) => t.type === "submit")
        .forEach((t) => {
            // Ditunda satu tick supaya nilai tombol tetap ikut terkirim.
            setTimeout(() => (t.disabled = true), 0);
        });
});

// Kembali lewat tombol "back" memulihkan halaman dari cache apa adanya: buka kembali kunci kirim
// dan tanda konfirmasi supaya form bisa dipakai lagi.
window.addEventListener("pageshow", (e) => {
    if (!e.persisted) return;
    document.querySelectorAll("form[data-ic-sekali]").forEach((f) =>
        Array.from(f.elements)
            .filter((t) => t.type === "submit")
            .forEach((t) => (t.disabled = f.id === "ic-foto-form")),
    );
    document
        .querySelectorAll("form[data-ic-konfirmasi]")
        .forEach((f) => delete f.dataset.icSudahKonfirmasi);
});

// ---------- Suka kenangan ----------
// Server yang menentukan hasilnya (toggle di KenanganService); tampilan mengikuti jawaban JSON
// {disukai, jumlah}, jadi angka selalu benar walau ada klik ganda atau anggota lain ikut menyukai.
const sedangSuka = new WeakSet();

document.addEventListener("click", async (e) => {
    const tombol = e.target.closest("[data-ic-suka]");
    if (!tombol?.dataset.icSukaUrl || sedangSuka.has(tombol)) return;
    sedangSuka.add(tombol);
    tombol.setAttribute("aria-busy", "true");
    try {
        const respons = await fetch(tombol.dataset.icSukaUrl, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content ?? "",
            },
        });
        // Sesi habis: muat ulang, server mengarahkan ke halaman masuk.
        if (respons.status === 401 || respons.status === 419) {
            window.location.reload();
            return;
        }
        if (respons.status === 403) {
            window.icToast(
                "Verifikasi emailmu dulu untuk menyukai kenangan.",
                "info",
            );
            return;
        }
        if (respons.status === 404) {
            window.icToast("Kenangan ini sudah tidak tersedia.", "info");
            return;
        }
        if (respons.status === 429) {
            window.icToast("Terlalu cepat. Coba lagi sebentar.", "info");
            return;
        }
        if (!respons.ok) throw new Error("gagal");

        const { disukai, jumlah } = await respons.json();
        tombol.setAttribute("aria-pressed", String(disukai));
        tombol.classList.toggle("is-suka", disukai);
        tombol
            .querySelector("i")
            ?.setAttribute(
                "class",
                `bi ${disukai ? "bi-heart-fill" : "bi-heart"}`,
            );
        const angka = tombol.querySelector("span");
        if (angka) angka.textContent = String(jumlah);
    } catch {
        window.icToast("Suka belum tersimpan. Coba lagi.", "error");
    } finally {
        sedangSuka.delete(tombol);
        tombol.removeAttribute("aria-busy");
    }
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
}

// ---------- Pindai QR check-in ----------
// QR berisi URL .../innercircle/check-in?kode=WS-XXXXXXXXXXXX. Yang diambil hanya tokennya; anggota tetap
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
    if (!token) {
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

    const lembar = document.getElementById("modal-checkin");
    const input = document.getElementById("kode");
    if (lembar && !lembar.hidden && input) {
        // Lembar check-in sudah terbuka untuk satu acara: cukup isi kodenya.
        input.value = token;
        window.icToast("QR terbaca. Tekan Check-in sekarang.");
        document.getElementById("ic-checkin-kirim")?.focus();
        return true;
    }

    // Belum ada acara terpilih: server mencari acaranya dari token, lalu lembar check-in terbuka sendiri.
    const dasar = lembar?.dataset.icUrlCheckin;
    if (dasar) {
        window.location.assign(`${dasar}?kode=${encodeURIComponent(token)}`);
        return true;
    }
    return false;
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

// ---------- Check-in: lembar bawah + lokasi ----------
// Anggota memilih acara (kartu) atau memindai QR; lembar check-in naik dari bawah dengan acaranya sudah terisi.
// Acara yang punya titik lokasi (data-lokasi="1") memeriksa posisi HP: browser membaca GPS, server yang menilai jaraknya.
// Titik acara tidak pernah dikirim ke browser anggota, dan koordinat anggota tidak disimpan (hanya jarak dan akurasi).
const LOKASI_TARGET_M = 40; // berhenti mencari bila akurasi sudah sebaik ini
const LOKASI_BATAS_MS = 12000; // paling lama mencari sinyal
const LOKASI_UMUR_MAKS_MS = 90000; // lebih tua dari ini, posisi dibaca ulang sebelum dikirim

const lembarCheckin = document.getElementById("modal-checkin");
const formCheckin = document.getElementById("ic-checkin-form");
let lokasiTerbaca = null;
let lokasiJanji = null;
let hentikanBaca = null;

function perluLokasi() {
    return (
        lembarCheckin?.dataset.lokasi === "1" &&
        lembarCheckin?.dataset.status === "siap"
    );
}

function lokasiSegar() {
    return (
        lokasiTerbaca !== null &&
        Date.now() - lokasiTerbaca.waktu < LOKASI_UMUR_MAKS_MS
    );
}

function isiLokasiTersembunyi() {
    const nilai = lokasiTerbaca;
    document.getElementById("ic-checkin-lat").value = nilai ? nilai.lat : "";
    document.getElementById("ic-checkin-lng").value = nilai ? nilai.lng : "";
    document.getElementById("ic-checkin-akurasi").value = nilai
        ? nilai.akurasi
        : "";
}

const IKON_LOKASI = {
    diam: "bi-geo-alt",
    cari: "bi-geo-alt",
    ok: "bi-geo-alt-fill",
    gagal: "bi-exclamation-triangle-fill",
    ditolak: "bi-slash-circle-fill",
};

function tampilLokasi(keadaan, judul, detail = "") {
    const kotak = document.getElementById("ic-lokasi");
    if (!kotak) return;
    kotak.dataset.state = keadaan;
    document.getElementById("ic-lokasi-judul").textContent = judul;
    document.getElementById("ic-lokasi-detail").textContent = detail;
    const ikon = kotak.querySelector(".ic-lokasi__ikon i");
    if (ikon) ikon.className = `bi ${IKON_LOKASI[keadaan] ?? "bi-geo-alt"}`;
    const ulang = kotak.querySelector("[data-ic-lokasi-ulang]");
    if (ulang) ulang.hidden = keadaan === "cari";
}

function pesanGalatLokasi(kode) {
    switch (kode) {
        case "aman":
            return [
                "Lokasi butuh koneksi aman (HTTPS)",
                "Buka halaman ini lewat alamat https:// resmi WS Inner Circle.",
            ];
        case "tidak-ada":
            return [
                "Browser ini tidak bisa membaca lokasi",
                "Coba Chrome atau Safari versi terbaru.",
            ];
        case "ditolak":
            return [
                "Izin lokasi ditolak",
                "Ketuk ikon gembok di kolom alamat, pilih Izin lalu Lokasi, izinkan, kemudian tekan Cek lokasi lagi.",
            ];
        case "waktu":
            return [
                "Lokasi belum terbaca",
                "Sinyal GPS lemah. Pindah ke area terbuka atau dekat jendela, lalu tekan Cek lokasi lagi.",
            ];
        default:
            return [
                "Lokasi tidak tersedia",
                "Nyalakan GPS (layanan lokasi) di HP, lalu tekan Cek lokasi lagi.",
            ];
    }
}

// Mengumpulkan beberapa bacaan GPS dan memakai yang paling akurat: bacaan pertama sering kasar (berbasis jaringan).
function bacaPosisi(progres) {
    return new Promise((selesai, gagal) => {
        if (!window.isSecureContext) return gagal({ kode: "aman" });
        if (!navigator.geolocation) return gagal({ kode: "tidak-ada" });

        let terbaik = null;
        let id = null;
        let batas = null;
        let usai = false;

        const akhiri = (aksi) => {
            if (usai) return;
            usai = true;
            if (id !== null) navigator.geolocation.clearWatch(id);
            clearTimeout(batas);
            hentikanBaca = null;
            aksi();
        };
        hentikanBaca = () => akhiri(() => gagal({ kode: "batal" }));
        batas = setTimeout(
            () =>
                akhiri(() =>
                    terbaik ? selesai(terbaik) : gagal({ kode: "waktu" }),
                ),
            LOKASI_BATAS_MS,
        );

        id = navigator.geolocation.watchPosition(
            (posisi) => {
                const c = posisi.coords;
                const kini = {
                    lat: c.latitude,
                    lng: c.longitude,
                    akurasi: Math.max(1, Math.round(c.accuracy)),
                    waktu: Date.now(),
                };
                if (!terbaik || kini.akurasi < terbaik.akurasi) terbaik = kini;
                progres?.(terbaik);
                if (terbaik.akurasi <= LOKASI_TARGET_M) {
                    akhiri(() => selesai(terbaik));
                }
            },
            (galat) =>
                akhiri(() =>
                    terbaik && galat.code !== 1
                        ? selesai(terbaik)
                        : gagal({
                              kode:
                                  galat.code === 1
                                      ? "ditolak"
                                      : galat.code === 3
                                        ? "waktu"
                                        : "tidak-tersedia",
                          }),
                ),
            {
                enableHighAccuracy: true,
                maximumAge: 0,
                timeout: LOKASI_BATAS_MS,
            },
        );
    });
}

async function izinLokasiDitolak() {
    try {
        const status = await navigator.permissions?.query({
            name: "geolocation",
        });
        return status?.state === "denied";
    } catch {
        return false;
    }
}

function jalankanLokasi() {
    if (lokasiJanji) return lokasiJanji;
    tampilLokasi(
        "cari",
        "Mencari lokasimu…",
        "Setujui bila browser meminta izin lokasi. Pastikan GPS menyala.",
    );

    lokasiJanji = (async () => {
        if (await izinLokasiDitolak()) throw { kode: "ditolak" };
        return bacaPosisi((p) =>
            tampilLokasi(
                "cari",
                "Mencari lokasimu…",
                `Akurasi saat ini sekitar ${p.akurasi} m. Menunggu sinyal yang lebih baik.`,
            ),
        );
    })()
        .then((posisi) => {
            lokasiTerbaca = posisi;
            isiLokasiTersembunyi();
            tampilLokasi(
                "ok",
                "Lokasi terbaca",
                `Akurasi sekitar ${posisi.akurasi} m. Lokasimu hanya dipakai untuk memeriksa jarak ke venue, koordinatnya tidak disimpan.`,
            );
            return true;
        })
        .catch((galat) => {
            lokasiTerbaca = null;
            isiLokasiTersembunyi();
            if (galat?.kode === "batal") return false;
            const [judul, detail] = pesanGalatLokasi(galat?.kode);
            tampilLokasi(
                galat?.kode === "ditolak" ? "ditolak" : "gagal",
                judul,
                detail,
            );
            return false;
        })
        .finally(() => {
            lokasiJanji = null;
        });

    return lokasiJanji;
}

function setelUlangLokasi() {
    hentikanBaca?.();
    lokasiTerbaca = null;
    isiLokasiTersembunyi();
    tampilLokasi(
        "diam",
        "Lokasi belum dicek",
        "Lokasi dicek saat kamu menekan Check-in sekarang.",
    );
}

// Mengisi lembar dari atribut data-* kartu acara (atau dari #ic-checkin-awal bila server yang membukanya).
function isiCheckin(d, { awal = false } = {}) {
    if (!lembarCheckin) return;
    const acaraBerganti = lembarCheckin.dataset.kode !== d.kode;

    lembarCheckin.dataset.kode = d.kode;
    lembarCheckin.dataset.status = d.status;
    lembarCheckin.dataset.lokasi = d.lokasi;

    lembarCheckin
        .querySelectorAll("[data-ic-isi]")
        .forEach((el) => (el.textContent = d[el.dataset.icIsi] ?? ""));
    document.getElementById("modal-checkin-judul").textContent =
        d.venue || "Check-in";
    document.getElementById("ic-checkin-acara").value = d.kode;
    document.getElementById("ic-checkin-pesan").dataset.status = d.status;

    const siap = d.status === "siap";
    document.getElementById("ic-checkin-isi").hidden = !siap;
    document.getElementById("ic-checkin-tutup").hidden = siap;
    document.getElementById("ic-lokasi").hidden = d.lokasi !== "1";

    if (acaraBerganti && !awal) {
        // Kode dan posisi milik acara sebelumnya tidak boleh terbawa.
        document.getElementById("kode").value = "";
        document.getElementById("kode-galat-js").hidden = true;
        document.getElementById("ic-checkin-galat")?.remove();
        setelUlangLokasi();
    }
}

document.addEventListener("click", (e) => {
    const kartu = e.target.closest("[data-ic-checkin-buka]");
    if (kartu && lembarCheckin) {
        // Ctrl/Cmd+klik dan klik tengah tetap membuka tautan biasa di tab baru.
        if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey) return;
        e.preventDefault();
        isiCheckin(kartu.dataset);
        bukaModal("modal-checkin", kartu);
        return;
    }

    if (e.target.closest("[data-ic-lokasi-ulang]")) {
        setelUlangLokasi();
        if (perluLokasi()) jalankanLokasi();
    }
});

if (lembarCheckin) {
    lembarCheckin.addEventListener("ic:modal-buka", () => {
        // Izin lokasi diminta tepat saat lembar terbuka: anggota baru saja memilih acara, jadi konteksnya jelas.
        if (perluLokasi() && !lokasiSegar()) jalankanLokasi();
    });
    lembarCheckin.addEventListener("ic:modal-tutup", () => {
        hentikanBaca?.();
    });
}

function kunciKirimCheckin() {
    const tombol = document.getElementById("ic-checkin-kirim");
    if (tombol) setTimeout(() => (tombol.disabled = true), 0);
}

// Satu pintu pengiriman: kode diperiksa dulu, lalu posisi (bila acara memeriksa lokasi), baru form dikirim.
// Tombol dikunci hanya saat benar-benar terkirim, jadi klik ganda tidak mengirim dua kali.
formCheckin?.addEventListener("submit", (e) => {
    const kode = document.getElementById("kode");
    const galatKode = document.getElementById("kode-galat-js");
    if (!kode.value.trim()) {
        e.preventDefault();
        galatKode.textContent =
            "Masukkan kode check-in dari panitia atau pindai QR di lokasi.";
        galatKode.hidden = false;
        kode.setAttribute("aria-invalid", "true");
        kode.focus();
        return;
    }
    galatKode.hidden = true;
    kode.removeAttribute("aria-invalid");

    if (!perluLokasi()) {
        kunciKirimCheckin();
        return;
    }
    if (lokasiSegar()) {
        isiLokasiTersembunyi();
        kunciKirimCheckin();
        return;
    }

    e.preventDefault();
    jalankanLokasi().then((berhasil) => {
        if (berhasil) formCheckin.requestSubmit();
    });
});

// Kembali lewat tombol "back": halaman dipulihkan dari cache dengan tombol masih terkunci.
window.addEventListener("pageshow", (e) => {
    if (!e.persisted) return;
    const tombol = document.getElementById("ic-checkin-kirim");
    if (tombol) tombol.disabled = false;
});

// Modal yang diminta terbuka oleh server (atribut data-ic-buka-awal), mis. form unggah dengan galat validasi, atau
// lembar check-in yang acaranya sudah terpilih lewat QR. Dijalankan paling akhir supaya semua fungsi di atas sudah siap.
const awalCheckin = document.getElementById("ic-checkin-awal");
if (awalCheckin) isiCheckin(awalCheckin.dataset, { awal: true });
document
    .querySelectorAll("[data-ic-buka-awal]")
    .forEach((modal) => bukaModal(modal.id));
