/*
 * Penjelasan sebelum izin perangkat diminta (lokasi/GPS dan kamera).
 *
 * Browser hanya menampilkan kotak izin bawaannya satu kali; kalau anggota menolak karena tidak paham alasannya,
 * izin terblokir dan sulit dipulihkan. Karena itu fitur yang butuh izin memanggil pastikanIzin() dulu:
 *
 * - izin sudah diberikan: lanjut tanpa pop-up apa pun;
 * - izin belum pernah ditanyakan: pop-up menjelaskan kenapa izin dibutuhkan, baru izin sungguhan diminta;
 * - izin diblokir: pop-up berisi langkah membukanya lagi (Android, iPhone, komputer), lalu dicek ulang.
 *
 * `fire` adalah fungsi SweetAlert2 yang bertema halaman pemanggil (tema Inner Circle atau CMS). Bila tidak ada
 * (SweetAlert tidak termuat), fungsi ini tidak menghalangi: izin langsung diminta oleh browser seperti biasa.
 */

const JENIS = {
    lokasi: {
        izin: "geolocation",
        objek: "lokasi",
        judul: "Izinkan akses lokasi?",
        isi: "Lokasi HP dipakai untuk memeriksa apakah kamu berada di venue. Setelah menekan Lanjut, browser akan meminta izin: pilih Izinkan, dan pastikan GPS HP menyala. Lokasimu tidak disimpan.",
        blokirJudul: "Lokasi belum diizinkan",
    },
    kamera: {
        izin: "camera",
        objek: "kamera",
        judul: "Izinkan akses kamera?",
        isi: "Kamera dipakai hanya untuk memindai QR check-in di lokasi, gambarnya tidak direkam atau disimpan. Setelah menekan Lanjut, browser akan meminta izin: pilih Izinkan. Tanpa kamera, kamu tetap bisa mengetik kode atau mengunggah gambar QR.",
        blokirJudul: "Akses kamera sedang diblokir",
    },
};

const kunciSesi = (jenis) => `ws-izin-dijelaskan-${jenis}`;

function bacaSesi(jenis) {
    try {
        return sessionStorage.getItem(kunciSesi(jenis)) === "1";
    } catch {
        return false;
    }
}

function tulisSesi(jenis) {
    try {
        sessionStorage.setItem(kunciSesi(jenis), "1");
    } catch {
        // Mode privat: penjelasan boleh muncul lagi.
    }
}

/** 'granted' | 'prompt' | 'denied', atau null bila browser tidak bisa memberi tahu (mis. kamera di Firefox/Safari). */
export async function statusIzin(jenis) {
    try {
        const s = await navigator.permissions?.query({
            name: JENIS[jenis].izin,
        });
        return s?.state ?? null;
    } catch {
        return null;
    }
}

function langkahBuka(objek) {
    const O = objek.charAt(0).toUpperCase() + objek.slice(1);

    return (
        `<ol style="text-align:left;padding-left:1.25rem;margin:.5rem 0 0;line-height:1.6;font-size:.9rem">` +
        `<li><b>Android (Chrome):</b> ketuk ikon di kiri alamat, pilih <b>Izin</b>, lalu <b>${O}</b> dan <b>Izinkan</b>.</li>` +
        `<li><b>iPhone (Safari):</b> ketuk <b>aA</b> di kolom alamat, pilih <b>Pengaturan Situs Web</b>, lalu izinkan <b>${O}</b>.</li>` +
        `<li><b>Komputer:</b> klik ikon gembok di kolom alamat, lalu izinkan <b>${O}</b>.</li>` +
        `</ol>`
    );
}

/**
 * @param {'lokasi'|'kamera'} jenis
 * @param {Function} fire fungsi SweetAlert2 (opsi) => Promise<{isConfirmed, isDenied}>
 * @param {{isi?: string, bantuLain?: string, tombolTutup?: string}} opsi teks khusus halaman: `isi` (penjelasan sebelum izin
 *        diminta), `bantuLain` (kalimat bantuan di bawah langkah membuka izin yang diblokir) dan `tombolTutup` (label tombol batal)
 * @returns {Promise<boolean>} true bila boleh lanjut meminta izin sungguhan
 */
export async function pastikanIzin(jenis, fire, opsi = {}) {
    const t = JENIS[jenis];
    if (!t || typeof fire !== "function") return true;

    let status = await statusIzin(jenis);
    if (status === "granted") return true;
    // Browser tidak bisa memberi tahu statusnya: jelaskan sekali per sesi saja.
    if (status === null && bacaSesi(jenis)) return true;

    if (status === "denied") {
        for (let putaran = 0; ; putaran++) {
            const h = await fire({
                icon: "warning",
                title: t.blokirJudul,
                html:
                    (putaran > 0
                        ? `<p style="margin:0 0 .25rem"><b>Masih diblokir.</b> Kalau sudah diubah, muat ulang halaman ini.</p>`
                        : `<p style="margin:0">Browser-mu masih memblokir ${t.objek} untuk situs ini. Izinkan lagi lewat langkah berikut:</p>`) +
                    langkahBuka(t.objek) +
                    (opsi.bantuLain
                        ? `<p style="margin:.75rem 0 0;font-size:.85rem;opacity:.8">${opsi.bantuLain}</p>`
                        : ""),
                showCancelButton: true,
                showDenyButton: putaran > 0,
                confirmButtonText: "Sudah, coba lagi",
                denyButtonText: "Muat ulang",
                cancelButtonText: opsi.tombolTutup ?? "Tutup",
                allowOutsideClick: false,
            });
            if (!h) return true;
            if (h.isDenied) {
                location.reload();
                return false;
            }
            if (!h.isConfirmed) return false;

            status = await statusIzin(jenis);
            if (status !== "denied") return true;
        }
    }

    const h = await fire({
        icon: "info",
        title: t.judul,
        text: opsi.isi ?? t.isi,
        showCancelButton: true,
        confirmButtonText: "Lanjut",
        cancelButtonText: "Nanti saja",
        allowOutsideClick: false,
    });
    if (!h) return true;
    if (!h.isConfirmed) return false;

    if (status === null) tulisSesi(jenis);

    return true;
}

// Dipakai halaman CMS yang memakai skrip biasa (bukan modul).
window.pastikanIzin = pastikanIzin;
