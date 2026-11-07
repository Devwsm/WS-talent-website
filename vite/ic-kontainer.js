/**
 * Responsif pratinjau CMS Inner Circle mengikuti lebar PANEL, bukan lebar layar.
 *
 * Potongan halaman anggota di tab Konten memakai CSS asli area anggota (resources/css/inner-circle.css), yang
 * aturan responsifnya memakai `@media (max-width: ...)` terhadap lebar layar. Di CMS layar lebar, panel pratinjau
 * sempit tetapi layarnya lebar, sehingga tata letak desktop terhimpit di dalam panel kecil (judul pecah per suku kata,
 * kolom terlalu sempit). Fungsi ini menyalin setiap blok `@media (max-width: N)` menjadi
 * `@container ic-panel (max-width: N)` yang berlaku hanya di dalam `.ic-replika--halaman`, sehingga tata letak HP
 * dipakai bila panelnya selebar HP. Salinan dibuat saat build (tanpa berkas yang harus dijaga manual), jadi ikut
 * berubah otomatis bila aturan responsif di inner-circle.css berubah.
 */

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

export function bangunKontainer(css) {
    const bersih = css.replace(/\/\*[\s\S]*?\*\//g, "");
    const pola = /@media\s*\(\s*max-width:\s*(\d+)px\s*\)\s*\{/g;
    const hasil = [];
    let cocok;

    while ((cocok = pola.exec(bersih)) !== null) {
        let dalam = 1;
        let i = pola.lastIndex;
        while (i < bersih.length && dalam > 0) {
            if (bersih[i] === "{") dalam++;
            else if (bersih[i] === "}") dalam--;
            i++;
        }
        const isi = bersih.slice(pola.lastIndex, i - 1);
        pola.lastIndex = i;

        const aturan = [];
        const satu = /([^{}]+)\{([^{}]*)\}/g;
        let r;
        while ((r = satu.exec(isi)) !== null) {
            const selektor = r[1]
                .split(",")
                .map((x) => x.trim())
                .filter(Boolean)
                .map(dalamPanel)
                .filter(Boolean);
            if (selektor.length > 0 && r[2].trim() !== "") {
                aturan.push(`    ${selektor.join(",\n    ")} {${r[2]}}`);
            }
        }

        if (aturan.length > 0) {
            hasil.push(
                `@container ic-panel (max-width: ${cocok[1]}px) {\n${aturan.join("\n")}\n}`,
            );
        }
    }

    return hasil.join("\n");
}
