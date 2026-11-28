/*
 * Mambo Jambo: Pineapple Catcher. Mesin permainan MURNI dan DETERMINISTIK (tanpa DOM, tanpa Math.random, tanpa waktu).
 *
 * Server menjalankan ulang ronde dengan aturan yang SAMA PERSIS (app/Services/InnerCircle/GameSimulasi.php) dari
 * seed + catatan input, lalu menghitung sendiri skornya. Karena itu skor yang dikirim browser tidak pernah dipercaya.
 * Seluruh hitungan memakai bilangan bulat (posisi dalam 1/60 piksel, 60 langkah per detik) supaya JS dan PHP
 * menghasilkan angka yang identik. JANGAN mengubah angka atau urutan hitungan di sini tanpa mengubah GameSimulasi.php
 * dan tes GameSimulasiTest (angka emasnya dibuat dari berkas ini).
 */

export const K = Object.freeze({
    LEBAR: 320,
    TINGGI: 280,
    LANGKAH_PER_DETIK: 60,
    SKALA: 60, // 1 piksel = 60 satuan; kecepatan px/detik jadi bilangan bulat satuan/langkah
    NYAWA: 5,
    POIN_NANAS: 10,
    POIN_PER_LEVEL: 250,
    KECEPATAN_AWAL: 104,
    KECEPATAN_PER_LEVEL: 30,
    KECEPATAN_KERANJANG: 340, // px/detik saat tombol panah ditahan
    BATAS_TEPI: 38,
    JARAK_JATUH: 26,
    MULUT_Y: 211,
    MULUT_SETENGAH: 19,
    PELUANG_BIR: 24, // persen
    TINGGI_NANAS: 26,
    TINGGI_BIR: 29,
    KODE_PANAH: 1001, // 1000 = kiri, 1001 = diam, 1002 = kanan
});

const S = K.SKALA;

/** Satu ronde. `acak` memakai xorshift32 dari seed; hanya angka bulat 32 bit. */
export function buatSimulasi(seed) {
    let s = seed >>> 0 || 1;
    const acak = (n) => {
        s = (s ^ (s << 13)) >>> 0;
        s = (s ^ (s >>> 17)) >>> 0;
        s = (s ^ (s << 5)) >>> 0;
        return s % n;
    };

    const keadaan = {
        bx: (K.LEBAR / 2) * S,
        arah: 0,
        skor: 0,
        level: 1,
        nyawa: K.NYAWA,
        langkah: 0,
        nanas: 0,
        bir: 0,
        lewat: 0,
        selesai: false,
        drop: null,
        kejadian: null, // efek visual terakhir (tidak ikut hitungan)
    };

    function jatuhBaru(paksaNanas) {
        const jenis = paksaNanas ? 0 : acak(100) < K.PELUANG_BIR ? 1 : 0;
        const x = K.JARAK_JATUH + acak(K.LEBAR - 2 * K.JARAK_JATUH + 1);
        const tinggi = jenis === 1 ? K.TINGGI_BIR : K.TINGGI_NANAS;
        return { jenis, x, y: -(tinggi + 1) * S };
    }

    const geser = (x) =>
        Math.min((K.LEBAR - K.BATAS_TEPI) * S, Math.max(K.BATAS_TEPI * S, x));

    keadaan.drop = jatuhBaru(true);

    /** 0..320 = keranjang diarahkan ke kolom itu (px). 1000/1001/1002 = panah kiri/diam/kanan. */
    function masuk(kode) {
        if (kode <= K.LEBAR) keadaan.bx = geser(kode * S);
        else keadaan.arah = kode - K.KODE_PANAH;
    }

    function langkah() {
        if (keadaan.selesai) return;
        keadaan.kejadian = null;
        if (keadaan.arah !== 0)
            keadaan.bx = geser(
                keadaan.bx + keadaan.arah * K.KECEPATAN_KERANJANG,
            );

        const d = keadaan.drop;
        const tinggi = d.jenis === 1 ? K.TINGGI_BIR : K.TINGGI_NANAS;
        const selisih = (tinggi - 1) * S;
        const bawahLama = d.y + selisih;
        d.y += K.KECEPATAN_AWAL + (keadaan.level - 1) * K.KECEPATAN_PER_LEVEL;
        const bawahBaru = d.y + selisih;
        const kolomKeranjang = Math.floor((keadaan.bx + S / 2) / S);
        const melewatiMulut =
            bawahLama < K.MULUT_Y * S && bawahBaru >= K.MULUT_Y * S;
        const masukMulut =
            Math.abs(d.x - kolomKeranjang) + 4 <= K.MULUT_SETENGAH;
        keadaan.langkah += 1;

        if (melewatiMulut && masukMulut) {
            const nanas = d.jenis === 0;
            keadaan.skor += nanas ? K.POIN_NANAS : -K.POIN_NANAS;
            keadaan.level = Math.max(
                keadaan.level,
                1 + Math.floor(keadaan.skor / K.POIN_PER_LEVEL),
            );
            if (nanas) keadaan.nanas += 1;
            else keadaan.bir += 1;
            keadaan.kejadian = { jenis: nanas ? "nanas" : "bir", x: d.x };
            keadaan.drop = jatuhBaru(false);
        } else if (bawahBaru >= K.TINGGI * S) {
            if (d.jenis === 0) {
                keadaan.nyawa -= 1;
                keadaan.lewat += 1;
                if (keadaan.nyawa === 0) {
                    keadaan.selesai = true;
                    keadaan.drop = null;
                    keadaan.kejadian = { jenis: "habis", x: d.x };
                    return;
                }
                keadaan.kejadian = { jenis: "lewat", x: d.x };
            }
            keadaan.drop = jatuhBaru(false);
        }
    }

    return { keadaan, masuk, langkah };
}

/** Jalankan ulang ronde dari catatan (dipakai tes lintas bahasa). `log` = [selisihLangkah, kode, ...]. */
export function ulang(seed, log, langkahTotal) {
    const sim = buatSimulasi(seed);
    let i = 0;
    let waktuKejadian = log.length ? log[0] : Infinity;
    for (let t = 0; t < langkahTotal; t += 1) {
        while (i < log.length && waktuKejadian === t) {
            sim.masuk(log[i + 1]);
            i += 2;
            waktuKejadian = i < log.length ? t + log[i] : Infinity;
        }
        sim.langkah();
        if (sim.keadaan.selesai) break;
    }
    const k = sim.keadaan;
    return {
        skor: k.skor,
        level: k.level,
        nyawa: k.nyawa,
        langkah: k.langkah,
        nanas: k.nanas,
        bir: k.bir,
        lewat: k.lewat,
        selesai: k.selesai,
    };
}
