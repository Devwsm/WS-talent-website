/*
 * Mambo Jambo: Pineapple Catcher (halaman Game anggota Inner Circle).
 *
 * Browser hanya MEMAINKAN ronde. Skor resmi dihitung server dengan menjalankan ulang ronde dari seed + catatan input
 * (lihat GameService). Karena itu setiap input dicatat per langkah tetap (60 per detik) dan dikirim saat ronde selesai.
 * Ringan: satu kanvas 320x280, tanpa pustaka, loop gambar berhenti saat tab/kanvas tidak terlihat atau ronde tidak jalan.
 */
import { K, buatSimulasi } from "./game-mambo-sim.js";

const akar = document.querySelector("[data-gm]");
if (akar) jalankan(akar);

function jalankan(akar) {
    const q = (nama) => akar.querySelector(`[data-gm-${nama}]`);
    const d = akar.dataset;
    const csrf =
        document.querySelector('meta[name="csrf-token"]')?.content ?? "";
    const langkahMaks = Number(d.langkahMaks) || 14400;
    const detikPerLangkah = 1 / K.LANGKAH_PER_DETIK;

    const kanvas = q("kanvas");
    const konteks = kanvas.getContext("2d", { alpha: false });
    konteks.imageSmoothingEnabled = false;
    const arena = q("arena");
    const hamburan = q("overlay");
    const tombolMain = q("main");
    const labelMain = q("main-label");
    const judulHasil = q("judul");
    const teksHasil = q("hasil");
    const catatan = q("catatan");
    const tampilSkor = q("skor");
    const tampilRekor = q("rekor");
    const tampilNyawa = q("nyawa");
    const ikonNyawa = [...akar.querySelectorAll(".gm-nyawa i")];
    const tampilStatus = q("status");
    const tampilLevel = q("level");
    const tampilPoin = q("poin-ronde");
    const chipRonde = q("chip-ronde");
    const chipPoin = q("chip-poin");
    const tombolEfek = q("efek");
    const labelEfek = q("efek-label");
    const reduksiGerak = matchMedia("(prefers-reduced-motion: reduce)");

    // ---------- Aset ----------
    const muat = (nama) => {
        const gambar = new Image();
        gambar.decoding = "async";
        gambar.src = `${d.aset}/${nama}`;
        return gambar;
    };
    const seni = {
        latar: muat("cafe-beach.webp"),
        kiri: muat("dj-left.webp"),
        kanan: muat("dj-right.webp"),
        keranjang: muat("basket.webp"),
        nanas: muat("pineapple.webp"),
        bir: muat("beer.webp"),
    };
    const ukuran = {
        0: { g: seni.nanas, w: 21, h: 26 },
        1: { g: seni.bir, w: 19, h: 29 },
    };
    const siap = (g) => g.complete && g.naturalWidth > 0;
    const pasang = (g, x, y) =>
        siap(g) && konteks.drawImage(g, Math.round(x), Math.round(y));
    const kotak = (warna, x, y, w, h) => {
        konteks.fillStyle = warna;
        konteks.fillRect(Math.round(x), Math.round(y), w, h);
    };
    const kilau = (x, y, warna) => {
        kotak(warna, x - 3, y, 7, 1);
        kotak(warna, x, y - 3, 1, 7);
        kotak("#fff1bf", x, y, 1, 1);
    };

    // ---------- Keadaan ----------
    const BATAS_TEPI_ACTOR = { atas: 124, setengah: 50 };
    let keadaan = "siap"; // siap | bersiap | main | jeda | menghitung | selesai | galat
    let sim = null;
    let kode = null;
    let catatanInput = [];
    let langkahTerakhirCatat = 0;
    let kodeTerakhir = -1;
    let arahTerakhir = 0;
    let kodePointer = null;
    let sentuhAktif = null;
    let menghadap = "kiri";
    let bxSebelum = 0;
    let umpan = null; // efek teks melayang
    let rekor = Number(d.rekor) || 0;
    let rondeSisa = Number(d.rondeSisa) || 0;
    let poinHariIni = Number(d.poinHariIni) || 0;
    const poinBatasHari = Number(d.poinBatasHari) || 0;
    const poinMaksRonde = Number(d.poinMaksRonde) || 0;
    const skorPerPoin = Math.max(1, Number(d.skorPerPoin) || 10);
    let bisaMain = d.bisaMain === "1";
    let kirimanTertunda = null;
    let hitungMundur = 0;
    let rafId = 0;
    let terakhir;
    let sisaWaktu = 0;
    let waktuAnim = 0;
    let gambarTerakhir = 0;
    let terlihat = true;
    const tombolPanah = new Set();

    const angka = (n, panjang = 3) => String(n).padStart(panjang, "0");

    // ---------- Suara (opsional, WebAudio kecil) ----------
    let efekAktif = false;
    let audio;
    try {
        efekAktif = localStorage.getItem("ic-game-efek") === "1";
    } catch {
        /* penyimpanan dimatikan: tidak masalah */
    }
    function nada(frekuensi, lama, jenis = "square", tunda = 0) {
        if (!efekAktif || !audio || audio.state !== "running") return;
        const mulai = audio.currentTime + tunda;
        const osilator = audio.createOscillator();
        const volume = audio.createGain();
        osilator.type = jenis;
        osilator.frequency.value = frekuensi;
        volume.gain.setValueAtTime(0.035, mulai);
        volume.gain.exponentialRampToValueAtTime(0.001, mulai + lama);
        osilator.connect(volume).connect(audio.destination);
        osilator.start(mulai);
        osilator.stop(mulai + lama);
    }
    function aturLabelEfek() {
        tombolEfek.setAttribute("aria-pressed", String(efekAktif));
        labelEfek.textContent = efekAktif ? "EFEK ON" : "EFEK OFF";
    }
    tombolEfek.addEventListener("click", async () => {
        efekAktif = !efekAktif;
        if (efekAktif) {
            try {
                audio ??= new (
                    window.AudioContext || window.webkitAudioContext
                )();
                await audio.resume();
            } catch {
                efekAktif = false;
            }
        }
        try {
            localStorage.setItem("ic-game-efek", efekAktif ? "1" : "0");
        } catch {
            /* abaikan */
        }
        aturLabelEfek();
        nada(523, 0.1);
    });
    aturLabelEfek();

    // ---------- Tampilan ----------
    function perbaruiHud() {
        const k = sim?.keadaan;
        tampilSkor.textContent = angka(k ? k.skor : 0);
        tampilRekor.textContent = angka(Math.max(rekor, k ? k.skor : 0));
        tampilLevel.textContent = "LEVEL " + angka(k ? k.level : 1, 2);
        const nyawa = k ? k.nyawa : K.NYAWA;
        tampilNyawa.textContent = `${nyawa} / ${K.NYAWA}`;
        ikonNyawa.forEach((ikon, i) =>
            ikon.classList.toggle("spent", i >= nyawa),
        );
        const poinRonde = Math.min(
            poinMaksRonde,
            Math.floor(Math.max(0, k ? k.skor : 0) / skorPerPoin),
        );
        const penuh =
            Math.floor(Math.max(0, k ? k.skor : 0) / skorPerPoin) >=
            poinMaksRonde;
        tampilPoin.textContent = penuh
            ? `POIN RONDE ${poinMaksRonde} (MAKS)`
            : `POIN RONDE ${poinRonde}/${poinMaksRonde}`;
    }
    function perbaruiChip() {
        chipRonde.textContent = `${Math.max(0, rondeSisa)} ronde tersisa`;
        chipPoin.textContent = `${poinHariIni}/${poinBatasHari} poin hari ini`;
    }
    function aturOverlay({
        judul = "",
        hasil = "",
        info = "",
        tombol = null,
        aktif = true,
    }) {
        hamburan.hidden = false;
        judulHasil.hidden = !judul;
        judulHasil.textContent = judul;
        teksHasil.hidden = !hasil;
        teksHasil.textContent = hasil;
        catatan.hidden = !info;
        catatan.textContent = info;
        tombolMain.hidden = tombol === null;
        if (tombol !== null) labelMain.textContent = tombol;
        tombolMain.disabled = !aktif;
    }
    function aturStatus(teks) {
        tampilStatus.textContent = teks;
    }
    function tampilkanSiap(info = "") {
        keadaan = "siap";
        arena.dataset.state = "ready";
        aturStatus("SIAP MAIN");
        if (!bisaMain) {
            aturOverlay({
                info: "Verifikasi email dulu untuk main dan dapat poin.",
                tombol: "MULAI MAIN",
                aktif: false,
            });
        } else if (rondeSisa <= 0) {
            aturOverlay({
                info: info || "Jatah hari ini sudah habis. Main lagi besok ya!",
                tombol: "MULAI MAIN",
                aktif: false,
            });
        } else {
            aturOverlay({ info, tombol: "MULAI MAIN" });
        }
        mulaiLoop();
    }

    // ---------- Jaringan ----------
    async function kirimJson(url, badan) {
        const jawab = await fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrf,
                "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify(badan ?? {}),
        });
        const isi = await jawab.json().catch(() => ({}));
        if (!jawab.ok) {
            const galat = new Error(
                isi.pesan || "Ada kendala. Coba lagi sebentar.",
            );
            galat.status = jawab.status;
            throw galat;
        }
        return isi;
    }

    async function mintaRonde() {
        if (keadaan !== "siap" && keadaan !== "selesai" && keadaan !== "galat")
            return;
        keadaan = "bersiap";
        aturOverlay({ info: "Menyiapkan ronde..." });
        try {
            const ronde = await kirimJson(d.urlMulai);
            kode = ronde.kode;
            bersiap(Number(ronde.seed));
        } catch (galat) {
            if (galat.status === 429 && /Jatah/.test(galat.message))
                rondeSisa = 0;
            perbaruiChip();
            if (galat.status === 419) {
                keadaan = "galat";
                aturOverlay({
                    judul: "SESI HABIS",
                    info: "Muat ulang halaman, lalu coba lagi.",
                    tombol: "MUAT ULANG",
                });
                tombolMain.dataset.aksi = "muat";
                return;
            }
            tampilkanSiap(galat.message);
        }
    }

    function bersiap(seed) {
        sim = buatSimulasi(seed);
        catatanInput = [];
        langkahTerakhirCatat = 0;
        kodeTerakhir = -1;
        arahTerakhir = 0;
        kodePointer = null;
        tombolPanah.clear();
        menghadap = "kiri";
        bxSebelum = sim.keadaan.bx;
        umpan = null;
        kirimanTertunda = null;
        rondeSisa = Math.max(0, rondeSisa - 1);
        perbaruiChip();
        perbaruiHud();
        arena.dataset.state = "playing";
        hitungMundur = 3;
        aturOverlay({});
        hamburan.hidden = true;
        aturStatus("BERSIAP");
        const tik = () => {
            if (keadaan !== "bersiap") return;
            hitungMundur -= 1;
            nada(hitungMundur ? 440 : 660, 0.08);
            if (hitungMundur <= 0) {
                keadaan = "main";
                aturStatus("SEDANG MAIN");
                terakhir = undefined;
                sisaWaktu = 0;
                fokusArena();
            } else {
                setTimeout(tik, 600);
            }
        };
        nada(440, 0.08);
        setTimeout(tik, 600);
        mulaiLoop();
    }

    async function kirimHasil() {
        const { muatan, sebab } = kirimanTertunda;
        keadaan = "menghitung";
        arena.dataset.state = "over";
        aturStatus("MENGHITUNG");
        aturOverlay({ info: "Menghitung poinmu..." });
        try {
            const hasil = await kirimJson(d.urlSelesai, muatan);
            kirimanTertunda = null;
            tampilkanHasil(hasil, sebab);
        } catch (galat) {
            const sementara =
                !galat.status || galat.status >= 500 || galat.status === 429;
            if (sementara) {
                keadaan = "galat";
                aturOverlay({
                    judul: "KONEKSI TERPUTUS",
                    info: "Skor belum terkirim. Coba kirim ulang.",
                    tombol: "KIRIM ULANG",
                });
                tombolMain.dataset.aksi = "kirim";
            } else if (galat.status === 419) {
                keadaan = "galat";
                aturOverlay({
                    judul: "SESI HABIS",
                    info: "Muat ulang halaman. Ronde ini tidak dihitung.",
                    tombol: "MUAT ULANG",
                });
                tombolMain.dataset.aksi = "muat";
            } else {
                kirimanTertunda = null;
                keadaan = "selesai";
                aturOverlay({
                    judul: "RONDE TIDAK DIHITUNG",
                    info: galat.message,
                    tombol: rondeSisa > 0 ? "MAIN LAGI" : null,
                });
                aturStatus("RONDE SELESAI");
            }
        }
    }

    function tampilkanHasil(hasil, penyebab) {
        keadaan = "selesai";
        rekor = Math.max(rekor, hasil.rekor);
        rondeSisa = hasil.ronde_sisa;
        poinHariIni = hasil.poin_hari_ini;
        perbaruiChip();
        perbaruiHud();
        aturStatus("RONDE SELESAI");
        const baris = [`Skor akhir ${hasil.skor}`];
        baris.push(
            hasil.poin > 0
                ? `+${hasil.poin} poin Inner Circle`
                : "Belum ada poin kali ini",
        );
        if (hasil.rekor_baru) baris.push("Rekor baru!");

        // Jelaskan SELISIH skor dan poin supaya tidak membingungkan (mis. skor 650 tapi poin 200).
        const catatan = [];
        if (penyebab === "waktu")
            catatan.push(
                `Waktu habis (${Math.round(langkahMaks / K.LANGKAH_PER_DETIK / 60)} menit). Skor tetap dihitung.`,
            );
        if (hasil.alasan_batas === "ronde") {
            catatan.push(
                `Skor ${hasil.skor} = ${hasil.poin_mentah} poin, tapi maksimal ${poinMaksRonde} per ronde. Rekor ${hasil.rekor} tetap tercatat.`,
            );
        } else if (hasil.alasan_batas === "harian") {
            catatan.push(
                hasil.poin > 0
                    ? `Batas harian (${poinBatasHari} poin) tercapai, jadi poin ronde ini ${hasil.poin}.`
                    : `Batas harian (${poinBatasHari} poin) sudah tercapai, ronde ini tanpa poin. Rekor tetap tercatat.`,
            );
        } else if (hasil.poin === 0) {
            catatan.push("Skor belum cukup jadi poin. Coba lagi!");
        }
        if (rondeSisa <= 0) catatan.push("Jatah hari ini habis. Sampai besok!");
        aturOverlay({
            judul: penyebab === "waktu" ? "WAKTU HABIS" : "GAME OVER",
            hasil: baris.join("\n"),
            info: catatan.join(" "),
            tombol: rondeSisa > 0 ? "MAIN LAGI" : null,
        });
        nada(
            hasil.poin > 0 ? 880 : 200,
            0.2,
            hasil.poin > 0 ? "square" : "sawtooth",
        );
    }

    // ---------- Permainan ----------
    function fokusArena() {
        arena.tabIndex = -1;
        arena.focus({ preventScroll: true });
    }

    function catat(langkah, kodeBaru) {
        catatanInput.push(langkah - langkahTerakhirCatat, kodeBaru);
        langkahTerakhirCatat = langkah;
        sim.masuk(kodeBaru);
    }

    function langkahPermainan() {
        const k = sim.keadaan;
        const t = k.langkah;
        // Input diambil di awal langkah, dicatat, lalu dipakai mesin: mesin server melihat urutan yang sama persis.
        if (kodePointer !== null && kodePointer !== kodeTerakhir) {
            kodeTerakhir = kodePointer;
            catat(t, kodePointer);
        }
        const arah =
            Number(tombolPanah.has("ArrowRight")) -
            Number(tombolPanah.has("ArrowLeft"));
        if (arah !== arahTerakhir) {
            arahTerakhir = arah;
            catat(t, K.KODE_PANAH + arah);
        }
        sim.langkah();

        const bergerak =
            Math.round(k.bx / K.SKALA) - Math.round(bxSebelum / K.SKALA);
        if (bergerak !== 0) menghadap = bergerak > 0 ? "kanan" : "kiri";
        bxSebelum = k.bx;

        const e = k.kejadian;
        if (e) {
            const x = Math.min(K.LEBAR - 29, Math.max(29, e.x));
            if (e.jenis === "nanas") {
                umpan = { x, sisa: 0.66, teks: "+10", warna: "#ffe66c" };
                nada(660, 0.095);
                nada(990, 0.12, "square", 0.07);
            } else if (e.jenis === "bir") {
                umpan = { x, sisa: 0.66, teks: "−10", warna: "#ff8e70" };
                nada(240, 0.15, "triangle");
            } else if (e.jenis === "lewat") {
                umpan = { x, sisa: 0.66, teks: "−1 NYAWA", warna: "#ff8e70" };
                nada(290, 0.12, "triangle");
            }
            perbaruiHud();
        }

        if (k.selesai) akhiriRonde("habis");
        else if (k.langkah >= langkahMaks) akhiriRonde("waktu");
    }

    function akhiriRonde(sebab) {
        const k = sim.keadaan;
        tombolPanah.clear();
        kirimanTertunda = {
            sebab, // hanya untuk tampilan
            muatan: {
                kode,
                langkah: k.langkah,
                log: catatanInput.join(","),
                skor: k.skor,
            },
        };
        nada(160, 0.24, "sawtooth");
        perbaruiHud();
        kirimHasil();
    }

    function jeda() {
        if (keadaan !== "main") return;
        keadaan = "jeda";
        tombolPanah.clear();
        aturStatus("JEDA");
        aturOverlay({
            judul: "JEDA",
            info: "Ronde dijeda.",
            tombol: "LANJUT",
        });
    }
    function lanjut() {
        keadaan = "main";
        hamburan.hidden = true;
        aturStatus("SEDANG MAIN");
        terakhir = undefined;
        sisaWaktu = 0;
        fokusArena();
        mulaiLoop();
    }

    tombolMain.addEventListener("click", () => {
        const aksi = tombolMain.dataset.aksi;
        tombolMain.dataset.aksi = "";
        if (aksi === "muat") {
            keadaan = "selesai"; // supaya tidak ada peringatan "tinggalkan halaman"
            location.reload();
        } else if (aksi === "kirim") kirimHasil();
        else if (keadaan === "jeda") lanjut();
        else mintaRonde();
    });

    // ---------- Input ----------
    function titik(e) {
        const kotakKanvas = kanvas.getBoundingClientRect();
        const x =
            ((e.clientX - kotakKanvas.left) * K.LEBAR) / kotakKanvas.width;
        kodePointer = Math.round(Math.min(K.LEBAR, Math.max(0, x)));
    }
    arena.addEventListener("pointerdown", (e) => {
        if (keadaan !== "main" || !e.isPrimary) return;
        if (e.pointerType !== "mouse") {
            sentuhAktif = e.pointerId;
            arena.setPointerCapture(e.pointerId);
        }
        titik(e);
    });
    arena.addEventListener("pointermove", (e) => {
        if (
            (keadaan === "main" ||
                (keadaan === "bersiap" && e.pointerType === "mouse")) &&
            (e.pointerType === "mouse" || e.pointerId === sentuhAktif)
        )
            titik(e);
    });
    for (const nama of ["pointerup", "pointercancel", "lostpointercapture"]) {
        arena.addEventListener(nama, (e) => {
            if (e.pointerId === sentuhAktif) sentuhAktif = null;
        });
    }
    window.addEventListener("keydown", (e) => {
        if (keadaan !== "main" || !["ArrowLeft", "ArrowRight"].includes(e.key))
            return;
        e.preventDefault();
        tombolPanah.add(e.key);
    });
    window.addEventListener("keyup", (e) => tombolPanah.delete(e.key));
    window.addEventListener("blur", () => {
        tombolPanah.clear();
        jeda();
    });
    document.addEventListener("visibilitychange", () => {
        tombolPanah.clear();
        if (document.hidden) jeda();
        else mulaiLoop();
    });
    window.addEventListener("beforeunload", (e) => {
        // Ronde yang berjalan tidak bisa dilanjutkan setelah halaman ditutup (jatah tetap terhitung).
        if (
            keadaan === "main" ||
            keadaan === "jeda" ||
            keadaan === "bersiap" ||
            keadaan === "galat"
        ) {
            e.preventDefault();
            e.returnValue = "";
        }
    });
    if ("IntersectionObserver" in window) {
        new IntersectionObserver(
            ([masuk]) => {
                terlihat = masuk.isIntersecting;
                if (!terlihat) jeda();
                else mulaiLoop();
            },
            { threshold: 0.35 },
        ).observe(arena);
    }

    // ---------- Gambar ----------
    function gambarSemua() {
        const waktu = reduksiGerak.matches ? 0.8 : waktuAnim;
        if (siap(seni.latar)) konteks.drawImage(seni.latar, 0, 0);
        else kotak("#08a7d6", 0, 0, K.LEBAR, K.TINGGI);
        for (const [x, y, fase] of [
            [97, 184, 0],
            [127, 187, 1],
            [109, 200, 2],
            [71, 202, 3],
        ]) {
            if (Math.sin(waktu * 2.6 + fase) > 0.15)
                kotak("#fff0b8ae", x, y, 8, 1);
        }

        const k = sim?.keadaan;
        const ax = Math.round((k ? k.bx : (K.LEBAR / 2) * K.SKALA) / K.SKALA);
        const goyang =
            keadaan === "siap" ? Math.round(Math.sin(waktu * 2) * 1.5) : 0;
        kotak("#713f3470", ax - 26, 269, 52, 3);
        kotak("#713f3470", ax - 19, 272, 38, 2);
        pasang(
            menghadap === "kanan" ? seni.kanan : seni.kiri,
            ax - BATAS_TEPI_ACTOR.setengah,
            BATAS_TEPI_ACTOR.atas + goyang,
        );

        if (!k || keadaan === "siap") {
            pasang(seni.nanas, 73, 110 + Math.sin(waktu * 2.1) * 5);
            pasang(seni.bir, 188, 93 + Math.sin(waktu * 2.1 + 1.6) * 5);
        }
        if (k?.drop && keadaan !== "siap") {
            const u = ukuran[k.drop.jenis];
            pasang(u.g, k.drop.x - u.w / 2, k.drop.y / K.SKALA);
        }

        // Keranjang di depan DJ; saat menghadap kanan dicerminkan pada titik tumpu yang sama.
        const pasangKeranjang = () =>
            pasang(
                seni.keranjang,
                ax - BATAS_TEPI_ACTOR.setengah,
                BATAS_TEPI_ACTOR.atas + goyang,
            );
        if (menghadap === "kanan") {
            konteks.save();
            konteks.translate(2 * ax, 0);
            konteks.scale(-1, 1);
            pasangKeranjang();
            konteks.restore();
        } else {
            pasangKeranjang();
        }

        if (umpan) {
            const lewat = 0.66 - umpan.sisa;
            const y = K.MULUT_Y - 15 - lewat * 32;
            konteks.globalAlpha = Math.min(1, umpan.sisa * 4);
            kilau(umpan.x - 14 - lewat * 17, y + 3, umpan.warna);
            kilau(umpan.x + 12 + lewat * 18, y + 8, "#ffeea7");
            konteks.font = "bold 11px monospace";
            konteks.textAlign = "center";
            konteks.fillStyle = "#12363c";
            konteks.fillText(umpan.teks, umpan.x + 1, y + 2);
            konteks.fillStyle = umpan.warna;
            konteks.fillText(umpan.teks, umpan.x, y);
            konteks.globalAlpha = 1;
        }

        if (keadaan === "bersiap" && hitungMundur > 0) {
            konteks.font = "bold 54px monospace";
            konteks.textAlign = "center";
            konteks.fillStyle = "#12363c";
            konteks.fillText(String(hitungMundur), K.LEBAR / 2 + 2, 112);
            konteks.fillStyle = "#ffe66c";
            konteks.fillText(String(hitungMundur), K.LEBAR / 2, 110);
        }
    }

    // ---------- Loop ----------
    // Loop hanya hidup saat ada yang bergerak: ronde berjalan (60 fps) atau layar siap (animasi pelan, ~12 fps).
    function perluLoop() {
        if (document.hidden || !terlihat) return false;
        return (
            keadaan === "main" ||
            keadaan === "bersiap" ||
            keadaan === "siap" ||
            keadaan === "jeda"
        );
    }
    function mulaiLoop() {
        if (rafId || !perluLoop()) return;
        terakhir = undefined;
        rafId = requestAnimationFrame(bingkai);
    }
    function bingkai(sekarang) {
        rafId = 0;
        const dt =
            terakhir === undefined
                ? 0
                : Math.min((sekarang - terakhir) / 1000, 0.1);
        terakhir = sekarang;
        waktuAnim += dt;
        if (umpan) {
            umpan.sisa -= dt;
            if (umpan.sisa <= 0) umpan = null;
        }

        if (keadaan === "main") {
            sisaWaktu += dt;
            let n = 0;
            while (
                sisaWaktu >= detikPerLangkah &&
                keadaan === "main" &&
                n < 8
            ) {
                langkahPermainan();
                sisaWaktu -= detikPerLangkah;
                n += 1;
            }
            if (n >= 8) sisaWaktu = 0;
        }

        const perlahan = keadaan === "siap" || keadaan === "jeda";
        if (!perlahan || sekarang - gambarTerakhir > 80) {
            gambarSemua();
            gambarTerakhir = sekarang;
        }
        if (perluLoop()) rafId = requestAnimationFrame(bingkai);
        else gambarSemua(); // satu gambar terakhir supaya kanvas tidak kosong
    }

    // ---------- Pemutar lagu (dimuat hanya bila diminta) ----------
    const tombolLagu = q("lagu");
    tombolLagu?.addEventListener("click", () => {
        const bingkaiLagu = document.createElement("iframe");
        bingkaiLagu.className = "gm-lagu__bingkai";
        bingkaiLagu.title = "Mambo Jambo di Spotify";
        bingkaiLagu.src = d.urlLagu;
        bingkaiLagu.width = "100%";
        bingkaiLagu.height = "152";
        bingkaiLagu.loading = "lazy";
        bingkaiLagu.allow =
            "autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture";
        tombolLagu.replaceWith(bingkaiLagu);
    });

    // ---------- Mulai ----------
    perbaruiHud();
    perbaruiChip();
    tampilkanSiap();
}
