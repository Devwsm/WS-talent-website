/**
 * Membuat resources/css/ic-kontainer.css: CSS pratinjau halaman anggota di CMS Inner Circle yang berskala layar HP.
 *
 * Potongan halaman anggota di tab Konten memakai CSS asli area anggota (resources/css/inner-circle.css). CSS itu
 * menyesuaikan diri dengan lebar LAYAR lewat dua cara: `@media (max-width: N)` dan satuan `vw` (mis. judul hero
 * `clamp(44px, 9vw, 88px)`). Di CMS pada layar lebar, panel pratinjau sempit tetapi layarnya lebar, sehingga tata letak
 * desktop terhimpit dan ukuran huruf memakai nilai maksimum.
 *
 * Panel pratinjau dikunci selebar HP (390 px, class `ic-ponsel`) dan menjadi container bernama `ic-panel`. Berkas ini
 * menyalin aturan tersebut khusus di dalam `.ic-replika--halaman`:
 *   - `@media (max-width: N)`  ->  `@container ic-panel (max-width: N)`
 *   - `Nvw`                    ->  `Ncqw` (1 cqw = 1% lebar panel, sama dengan 1 vw di HP selebar panel)
 * Urutan aturan dipertahankan, jadi hasilnya sama dengan yang dilihat anggota di HP.
 *
 * Dijalankan otomatis oleh `npm run dev` dan `npm run build` (hasilnya juga di-commit supaya tetap ada bila skrip
 * ini dilewati). Jangan edit ic-kontainer.css manual: ubah inner-circle.css lalu jalankan ulang.
 */
import { readFileSync, writeFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { dirname, resolve } from "node:path";

const akar = resolve(dirname(fileURLToPath(import.meta.url)), "..");
const SUMBER = resolve(akar, "resources/css/inner-circle.css");
const TUJUAN = resolve(akar, "resources/css/ic-kontainer.css");

/** Selektor satu aturan -> selektor di dalam panel, atau null bila aturan itu untuk elemen di luar potongan. */
function dalamPanel(selektor) {
    if (/^(html|body|:root)(?![\w-])/.test(selektor)) {
        return null;
    }
    if (/^\.ic-app(?![\w-])/.test(selektor)) {
        const sisa = selektor.replace(/^\.ic-app/, "");
        // `.ic-app.kelas` atau `.ic-app--auth` menunjuk elemen akar halaman, bukan isi potongan.
        return /^\s/.test(sisa) ? ".ic-replika--halaman" + sisa : null;
    }

    return ".ic-replika--halaman " + selektor;
}

const keCqw = (nilai) => nilai.replace(/(\d)vw\b/g, "$1cqw");

/** Memecah "a: b; c: d" menjadi deklarasi; hanya yang memuat `vw` bila $hanyaVw. */
function deklarasi(isi, hanyaVw) {
    return isi
        .split(";")
        .map((d) => d.trim())
        .filter((d) => d !== "" && (!hanyaVw || /\dvw\b/.test(d)))
        .map(keCqw);
}

function aturan(selektorMentah, isi, hanyaVw) {
    const dekl = deklarasi(isi, hanyaVw);
    const selektor = selektorMentah
        .split(",")
        .map((x) => x.trim())
        .filter(Boolean)
        .map(dalamPanel)
        .filter(Boolean);

    if (dekl.length === 0 || selektor.length === 0) {
        return null;
    }

    return `${selektor.join(",\n")} {\n    ${dekl.join(";\n    ")};\n}`;
}

export function bangunKontainer(css) {
    const bersih = css.replace(/\/\*[\s\S]*?\*\//g, "").replace(/\r/g, "");
    const hasil = [];
    let i = 0;

    while (i < bersih.length) {
        const buka = bersih.indexOf("{", i);
        const titikKoma = bersih.indexOf(";", i);
        if (buka === -1) break;
        // Pernyataan tanpa blok (mis. @import ...;) dilewati.
        if (
            titikKoma !== -1 &&
            titikKoma < buka &&
            bersih.slice(i, titikKoma).trim().startsWith("@")
        ) {
            i = titikKoma + 1;
            continue;
        }

        const kepala = bersih.slice(i, buka).trim();
        let dalam = 1;
        let j = buka + 1;
        while (j < bersih.length && dalam > 0) {
            if (bersih[j] === "{") dalam++;
            else if (bersih[j] === "}") dalam--;
            j++;
        }
        const isi = bersih.slice(buka + 1, j - 1);
        i = j;

        const media = kepala.match(/^@media\s*\(\s*max-width:\s*(\d+)px\s*\)$/);
        if (media) {
            const dalamMedia = [];
            for (const r of isi.matchAll(/([^{}]+)\{([^{}]*)\}/g)) {
                const a = aturan(r[1], r[2], false);
                if (a) dalamMedia.push(a.replace(/^/gm, "    "));
            }
            if (dalamMedia.length > 0) {
                hasil.push(
                    `@container ic-panel (max-width: ${media[1]}px) {\n${dalamMedia.join("\n")}\n}`,
                );
            }
        } else if (!kepala.startsWith("@")) {
            const a = aturan(kepala, isi, true);
            if (a) hasil.push(a);
        }
    }

    return hasil.join("\n");
}

const kepalaBerkas = `/* DIBUAT OTOMATIS oleh vite/buat-kontainer.js dari inner-circle.css. Jangan diedit manual. */\n`;

if (
    process.argv[1] &&
    fileURLToPath(import.meta.url) === resolve(process.argv[1])
) {
    const css =
        kepalaBerkas + bangunKontainer(readFileSync(SUMBER, "utf8")) + "\n";
    writeFileSync(TUJUAN, css);
    console.log(`ic-kontainer.css dibuat (${css.length} byte)`);
}
