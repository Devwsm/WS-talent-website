/**
 * Kartu GL untuk vendor (CMS Inner Circle > Undian GL > Kartu GL untuk vendor).
 *
 * PDF digambar di browser dari data JSON di halaman (#data-kartu-gl), jadi server tidak butuh pustaka PDF dan sandi
 * acara tidak disimpan di berkas apa pun di server. jsPDF dimuat hanya saat tombol ditekan (impor dinamis).
 * Tombol Bagikan memakai lembar bagikan bawaan HP/komputer (berkas PDF ikut terkirim); bila browser belum
 * mendukungnya, PDF diunduh. Huruf di luar Latin dasar diganti agar PDF tidak berisi karakter rusak.
 */

const BIRU = [23, 98, 154];
const TINTA = [11, 42, 71];
const ABU = [90, 105, 120];
const GARIS = [200, 216, 228];

const L = 15; // margin kiri (mm)
const R = 195; // batas kanan (mm)
const W = R - L;
const TINGGI = 297;
const DASAR = 20; // ruang kosong di bawah untuk catatan kaki

/** Font bawaan PDF hanya paham Latin dasar: buang aksen dan ganti sisanya dengan "?". */
export function bersih(teks) {
    return String(teks ?? "")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/[\u2018\u2019]/g, "'")
        .replace(/[\u201C\u201D]/g, '"')
        .replace(/[^\x20-\x7E]/g, "?");
}

/** 1000 jadi "1.000" (format Indonesia, tanpa bergantung pada ICU browser). */
function angka(n) {
    return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function gambarQr(doc, matriks, x, y, ukuran) {
    const n = matriks.length;
    const tepi = 2; // zona tenang (modul)
    const m = ukuran / (n + tepi * 2);
    doc.setFillColor(255, 255, 255);
    doc.rect(x, y, ukuran, ukuran, "F");
    doc.setFillColor(0, 0, 0);
    for (let r = 0; r < n; r++) {
        for (let c = 0; c < n; c++) {
            if (matriks[r][c] === "1") {
                doc.rect(
                    x + (c + tepi) * m,
                    y + (r + tepi) * m,
                    m + 0.02,
                    m + 0.02,
                    "F",
                );
            }
        }
    }
}

function kepalaHalaman(doc, d, pertama) {
    doc.setFillColor(...BIRU);
    doc.rect(0, 0, 210, pertama ? 30 : 16, "F");
    doc.setTextColor(255, 255, 255);
    doc.setFont("helvetica", "bold");
    doc.setFontSize(pertama ? 11 : 9);
    doc.text("WS INNER CIRCLE", L, pertama ? 12 : 10);
    if (pertama) {
        doc.setFontSize(21);
        doc.text("Daftar GL Gratis", L, 23);
    } else {
        doc.setFont("helvetica", "normal");
        doc.text(bersih(d.acara), R, 10, { align: "right" });
    }
}

const KARTU_GAP = 5; // jarak antar kartu (mm)
const KARTU_LEBAR = (W - KARTU_GAP) / 2;
const KARTU_TINGGI_MIN = 22;

/** Satu kartu pemenang: nomor, nama, Member ID, dan kotak centang Hadir. Nama panjang turun ke baris berikutnya. */
function barisNamaKartu(doc, p) {
    doc.setFont("helvetica", "bold");
    doc.setFontSize(10.5);
    return doc.splitTextToSize(bersih(p.nama), KARTU_LEBAR - 34);
}

function tinggiKartu(baris) {
    return Math.max(KARTU_TINGGI_MIN, 8 + (baris.length - 1) * 4.8 + 5.5 + 7);
}

function gambarKartu(doc, p, baris, x, y, h) {
    doc.setFillColor(247, 251, 254);
    doc.setDrawColor(...GARIS);
    doc.setLineWidth(0.4);
    doc.roundedRect(x, y, KARTU_LEBAR, h, 2.5, 2.5, "FD");

    // nomor urut dalam lingkaran biru
    doc.setFillColor(...BIRU);
    doc.circle(x + 8, y + h / 2, 4.2, "F");
    doc.setTextColor(255, 255, 255);
    doc.setFont("helvetica", "bold");
    doc.setFontSize(9);
    doc.text(String(p.no), x + 8, y + h / 2 + 1.3, { align: "center" });

    // nama dan Member ID
    doc.setFont("helvetica", "bold");
    doc.setFontSize(10.5);
    doc.setTextColor(...TINTA);
    doc.text(baris, x + 16, y + 8.2, { lineHeightFactor: 1.15 });
    doc.setFont("courier", "normal");
    doc.setFontSize(9);
    doc.setTextColor(...ABU);
    doc.text(bersih(p.kode), x + 16, y + 8.2 + (baris.length - 1) * 4.8 + 5.4);

    // kotak centang Hadir
    doc.setDrawColor(100, 120, 140);
    doc.setLineWidth(0.4);
    doc.rect(x + KARTU_LEBAR - 12, y + h / 2 - 5, 6, 6, "S");
    doc.setFont("helvetica", "bold");
    doc.setFontSize(6.5);
    doc.setTextColor(...ABU);
    doc.text("HADIR", x + KARTU_LEBAR - 9, y + h / 2 + 5.2, {
        align: "center",
    });
}

/** Bangun dokumen PDF dari data kartu GL. `JsPDF` = kelas jsPDF (dikirim dari luar supaya mudah diuji). */
export function bangunPdf(JsPDF, d) {
    const doc = new JsPDF({ unit: "mm", format: "a4" });
    kepalaHalaman(doc, d, true);

    // ---- nama acara dan tanggal ----
    let y = 42;
    doc.setTextColor(...TINTA);
    doc.setFont("helvetica", "bold");
    doc.setFontSize(16);
    const baris = doc.splitTextToSize(bersih(d.acara), W);
    doc.text(baris, L, y);
    y += baris.length * 7;
    doc.setFont("helvetica", "normal");
    doc.setFontSize(11);
    doc.setTextColor(...ABU);
    doc.text(bersih(d.tanggal), L, y);
    y += 8;

    // ---- akses acara: username, sandi, QR (satu kali, dipakai semua pemenang) ----
    const tinggiAkses = 50;
    doc.setFillColor(234, 245, 252);
    doc.setDrawColor(...GARIS);
    doc.setLineWidth(0.4);
    doc.roundedRect(L, y, W, tinggiAkses, 3, 3, "FD");

    const lebarTeks = W - 58;
    const label = (teks, ly) => {
        doc.setFont("helvetica", "bold");
        doc.setFontSize(7.5);
        doc.setTextColor(...ABU);
        doc.text(teks, L + 6, ly);
    };
    const nilai = (teks, ly) => {
        doc.setFont("courier", "bold");
        doc.setFontSize(13);
        doc.setTextColor(...TINTA);
        doc.text(
            doc.splitTextToSize(bersih(teks) || "-", lebarTeks),
            L + 6,
            ly,
        );
    };
    label("USERNAME ACARA", y + 10);
    nilai(d.username, y + 17);
    label("SANDI / KODE ACARA", y + 28);
    nilai(d.sandi, y + 35);

    doc.setFont("helvetica", "normal");
    doc.setFontSize(8.5);
    doc.setTextColor(...ABU);
    doc.text(
        doc.splitTextToSize(
            `Scan QR di kanan untuk +${angka(d.poin)} poin.`,
            lebarTeks,
        ),
        L + 6,
        y + 44,
    );

    const ukuranQr = 36;
    const qx = R - ukuranQr - 6;
    gambarQr(doc, d.qr, qx, y + 7, ukuranQr);
    y += tinggiAkses + 8;

    if (d.petunjuk) {
        doc.setFont("helvetica", "bold");
        doc.setFontSize(9);
        doc.setTextColor(...TINTA);
        const p = doc.splitTextToSize("Cara masuk: " + bersih(d.petunjuk), W);
        doc.text(p, L, y);
        y += p.length * 4.5 + 5;
    }

    // ---- daftar pemenang: satu kartu per pemenang, dua kolom ----
    doc.setFont("helvetica", "bold");
    doc.setFontSize(12);
    doc.setTextColor(...TINTA);
    doc.text(
        `Pemenang GL gratis (${d.pemenang.length} dari kuota ${d.kuota})`,
        L,
        y,
    );
    y += 4.5;
    doc.setFont("helvetica", "normal");
    doc.setFontSize(8.5);
    doc.setTextColor(...ABU);
    doc.text(
        "Centang kotak Hadir saat pemenang menunjukkan Kartu GL di pintu.",
        L,
        y,
    );
    y += 5;

    if (d.pemenang.length === 0) {
        doc.setFontSize(10);
        doc.text("Belum ada pemenang.", L, y + 6);
    } else {
        for (let i = 0; i < d.pemenang.length; i += 2) {
            const kiri = d.pemenang[i];
            const kanan = d.pemenang[i + 1];
            const barisKiri = barisNamaKartu(doc, kiri);
            const barisKanan = kanan ? barisNamaKartu(doc, kanan) : null;
            const h = Math.max(
                tinggiKartu(barisKiri),
                barisKanan ? tinggiKartu(barisKanan) : 0,
            );

            if (y + h > TINGGI - DASAR) {
                doc.addPage();
                kepalaHalaman(doc, d, false);
                y = 24;
            }
            gambarKartu(doc, kiri, barisKiri, L, y, h);
            if (kanan) {
                gambarKartu(
                    doc,
                    kanan,
                    barisKanan,
                    L + KARTU_LEBAR + KARTU_GAP,
                    y,
                    h,
                );
            }
            y += h + KARTU_GAP;
        }
    }

    // ---- catatan kaki di semua halaman ----
    const jumlah = doc.getNumberOfPages();
    for (let i = 1; i <= jumlah; i++) {
        doc.setPage(i);
        doc.setFont("helvetica", "normal");
        doc.setFontSize(8);
        doc.setTextColor(...ABU);
        doc.text(
            `Dibuat ${bersih(d.dibuat)}. Memuat sandi acara: bagikan hanya ke vendor acara ini.`,
            L,
            TINGGI - 10,
        );
        doc.text(`Halaman ${i} dari ${jumlah}`, R, TINGGI - 10, {
            align: "right",
        });
    }

    return doc;
}

async function muatJsPdf() {
    return (await import("jspdf")).jsPDF;
}

function mulai() {
    const sumber = document.getElementById("data-kartu-gl");
    if (!sumber) return;
    const data = JSON.parse(sumber.textContent);
    const status = document.getElementById("status-pdf");
    const tombolBagikan = document.getElementById("bagikan-pdf");
    const tombolUnduh = document.getElementById("unduh-pdf");
    const pesan = (teks) => {
        if (status) status.textContent = teks;
    };
    const buat = async () => bangunPdf(await muatJsPdf(), data);

    const sibuk = async (tombol, kerja) => {
        tombolBagikan.disabled = tombolUnduh.disabled = true;
        pesan("Menyiapkan PDF…");
        try {
            await kerja();
        } catch (galat) {
            if (galat?.name === "AbortError") {
                pesan("");
            } else {
                pesan(
                    "PDF belum bisa dibuat. Muat ulang halaman lalu coba lagi.",
                );
            }
        } finally {
            tombolBagikan.disabled = tombolUnduh.disabled = false;
        }
    };

    tombolUnduh.addEventListener("click", () =>
        sibuk(tombolUnduh, async () => {
            (await buat()).save(data.berkas);
            pesan("PDF diunduh.");
        }),
    );

    tombolBagikan.addEventListener("click", () =>
        sibuk(tombolBagikan, async () => {
            const doc = await buat();
            const berkas = new File([doc.output("blob")], data.berkas, {
                type: "application/pdf",
            });
            if (navigator.canShare && navigator.canShare({ files: [berkas] })) {
                await navigator.share({
                    files: [berkas],
                    title: `GL gratis: ${data.acara}`,
                    text: `Daftar GL gratis ${data.acara} (${data.tanggal}).`,
                });
                pesan("");
            } else {
                doc.save(data.berkas);
                pesan(
                    "Browser ini belum bisa membagikan berkas langsung, jadi PDF diunduh. Kirim berkasnya lewat WhatsApp atau email.",
                );
            }
        }),
    );

    tombolBagikan.disabled = tombolUnduh.disabled = false;
}

if (typeof document !== "undefined") mulai();
