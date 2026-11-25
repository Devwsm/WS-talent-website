import L from "leaflet";
import "leaflet/dist/leaflet.css";
import { pastikanIzin } from "./izin-perangkat.js";

/**
 * Popup "Atur acara" (CMS Inner Circle > Acara > Pengaturan).
 *
 * - Isi popup dimuat lewat fetch saat tombol [data-atur-buka] ditekan (atau sudah dirender server bila form gagal
 *   divalidasi), lalu dipasangi perilaku di bawah.
 * - Empat tab (Data acara, GL gratis, Check-in dan poin, Lokasi check-in). Hanya satu form pengaturan + satu form lokasi.
 * - Penjaga "belum disimpan": menutup popup (X, Tutup, Esc) saat ada isian yang berubah meminta konfirmasi, supaya
 *   tidak hilang karena tersentuh, terutama di HP. Latar gelap sengaja tidak menutup popup.
 * - Peta titik + radius (Leaflet) dan cari alamat dengan saran saat mengetik. Penanda memakai bentuk vektor.
 */
const dialog = document.getElementById("dialogAtur");
const wadah = document.getElementById("atur-isi");

const TEKS_MUAT =
    '<div class="flex-1 flex items-center justify-center p-10 text-sm text-white/72" role="status">Memuat pengaturan acara…</div>';

const ikon = (nama) => {
    const i = document.createElement("i");
    i.className = "bi " + nama;
    i.setAttribute("aria-hidden", "true");
    return i;
};

if (dialog && wadah) {
    let sesi = null; // keadaan popup yang sedang terbuka
    let nomorMuat = 0;

    const tanya = (opsi) => window.Swal.fire(opsi);

    // ---------------------------------------------------------------- buka / tutup

    const urlBersih = () => {
        const u = new URL(window.location.href);
        if (u.searchParams.has("atur")) {
            u.searchParams.delete("atur");
            window.history.replaceState(null, "", u);
        }
    };

    async function buka(url, tab) {
        const nomor = ++nomorMuat;
        wadah.innerHTML = TEKS_MUAT;
        if (!dialog.open) dialog.showModal();

        try {
            const r = await fetch(url, {
                credentials: "same-origin",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "text/html",
                },
            });
            const html = await r.text();
            if (nomor !== nomorMuat) return; // popup sudah ditutup atau dibuka untuk acara lain
            if (!r.ok || r.redirected || !html.includes("data-atur-akar")) {
                throw new Error("status " + r.status);
            }
            wadah.innerHTML = html;
            pasang(tab);
        } catch {
            if (nomor !== nomorMuat) return;
            wadah.innerHTML = "";
            const kotak = document.createElement("div");
            kotak.className =
                "flex-1 flex flex-col items-center justify-center gap-4 p-10 text-center";
            const t = document.createElement("p");
            t.className = "text-sm text-white/85";
            t.textContent =
                "Pengaturan acara tidak bisa dimuat. Sesi mungkin habis atau koneksi terputus.";
            const muatUlang = document.createElement("button");
            muatUlang.type = "button";
            muatUlang.className =
                "min-h-11 px-5 rounded-lg border border-white/20 text-sm font-semibold hover:bg-white/10";
            muatUlang.textContent = "Muat ulang halaman";
            muatUlang.addEventListener("click", () => window.location.reload());
            const tutup = document.createElement("button");
            tutup.type = "button";
            tutup.dataset.aturTutup = "";
            tutup.className = "min-h-11 px-5 text-sm text-white/72 underline";
            tutup.textContent = "Tutup";
            kotak.append(t, muatUlang, tutup);
            wadah.append(kotak);
        }
    }

    async function mintaTutup() {
        if (sesi?.kotor()) {
            const jawab = await tanya({
                title: "Tutup tanpa menyimpan?",
                text: "Perubahan yang belum disimpan akan hilang.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Ya, tutup",
                cancelButtonText: "Lanjut mengedit",
                reverseButtons: true,
                focusCancel: true,
            });
            if (!jawab.isConfirmed) return;
        }
        dialog.close();
    }

    dialog.addEventListener("cancel", (e) => {
        // Esc: tutup daftar saran dulu bila terbuka; selain itu lewat penjaga "belum disimpan".
        e.preventDefault();
        if (sesi?.saranTerbuka()) {
            sesi.tutupSaran();
            return;
        }
        mintaTutup();
    });

    dialog.addEventListener("close", () => {
        nomorMuat++;
        sesi?.bersihkan();
        sesi = null;
        wadah.replaceChildren();
        urlBersih();
    });

    document.addEventListener("click", (e) => {
        const pembuka = e.target.closest("[data-atur-buka]");
        if (pembuka) {
            e.preventDefault();
            buka(pembuka.dataset.url, pembuka.dataset.tab);
            return;
        }
        if (e.target.closest("[data-atur-tutup]") && dialog.open) {
            mintaTutup();
        }
    });

    // ---------------------------------------------------------------- pasang perilaku isi popup

    function pasang(tabAwal) {
        const akar = wadah.querySelector("[data-atur-akar]");
        if (!akar) return;

        const formAtur = akar.querySelector("#form-atur");
        const formLokasi = akar.querySelector("#form-lokasi");
        const tabs = [...akar.querySelectorAll("[data-atur-tab]")];
        const panel = (nama) =>
            akar.querySelector('[data-atur-panel="' + nama + '"]');
        const lokasi = formLokasi ? pasangLokasi(akar, formLokasi) : null;

        // ---- penjaga "belum disimpan"
        const ambil = (form) =>
            form
                ? JSON.stringify(
                      [...new FormData(form)]
                          .filter(([k]) => k !== "_token" && k !== "sumber")
                          .map(([k, v]) => [
                              k,
                              v instanceof File ? v.name + ":" + v.size : v,
                          ]),
                  )
                : "";
        const awalAtur = ambil(formAtur);
        const awalLokasi = ambil(formLokasi);
        const kotorAtur = () => ambil(formAtur) !== awalAtur;
        const kotorLokasi = () => ambil(formLokasi) !== awalLokasi;
        const catatKotor = () => {
            akar.querySelector("[data-atur-kotor]")?.toggleAttribute(
                "hidden",
                !(kotorAtur() || kotorLokasi()),
            );
            akar.querySelector("[data-atur-kotor-lokasi]")?.toggleAttribute(
                "hidden",
                !kotorAtur(),
            );
        };
        akar.addEventListener("input", catatKotor);
        akar.addEventListener("change", catatKotor);

        // ---- tab
        function pilihTab(nama, fokus = false) {
            tabs.forEach((t) => {
                const aktif = t.dataset.aturTab === nama;
                t.setAttribute("aria-selected", aktif ? "true" : "false");
                t.tabIndex = aktif ? 0 : -1;
                if (aktif && fokus) t.focus();
            });
            akar.querySelectorAll("[data-atur-panel]").forEach((p) =>
                p.toggleAttribute("hidden", p.dataset.aturPanel !== nama),
            );
            const dilokasi = nama === "lokasi";
            akar.querySelector(
                '[data-atur-simpan="pengaturan"]',
            )?.toggleAttribute("hidden", dilokasi);
            akar.querySelector('[data-atur-simpan="lokasi"]')?.toggleAttribute(
                "hidden",
                !dilokasi,
            );
            if (dilokasi) lokasi?.tampil();
        }
        tabs.forEach((t, i) => {
            t.addEventListener("click", () => pilihTab(t.dataset.aturTab));
            t.addEventListener("keydown", (e) => {
                const geser = { ArrowRight: 1, ArrowLeft: -1 }[e.key];
                if (!geser) return;
                e.preventDefault();
                pilihTab(
                    tabs[(i + geser + tabs.length) % tabs.length].dataset
                        .aturTab,
                    true,
                );
            });
        });

        // Isian bermasalah (validasi browser atau server) di tab yang sedang tersembunyi: buka tab itu.
        const tabDari = (el) =>
            el.closest("[data-atur-panel]")?.dataset.aturPanel;
        formAtur?.addEventListener(
            "invalid",
            (e) => {
                const nama = tabDari(e.target);
                if (nama) pilihTab(nama);
            },
            true,
        );

        // ---- kirim: cegah tekan ganda; menyimpan lokasi membuang perubahan tab lain, jadi tanya dulu
        let dikonfirmasi = false;
        formLokasi?.addEventListener("submit", async (e) => {
            if (dikonfirmasi || !kotorAtur()) return;
            e.preventDefault();
            const jawab = await tanya({
                title: "Ada perubahan yang belum disimpan",
                text: "Menyimpan lokasi akan membuang perubahan di tab Data acara, GL gratis, dan Check-in. Simpan lokasi saja?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Ya, simpan lokasi saja",
                cancelButtonText: "Kembali",
                reverseButtons: true,
                focusCancel: true,
            });
            if (!jawab.isConfirmed) return;
            dikonfirmasi = true;
            formLokasi.requestSubmit();
        });
        [formAtur, formLokasi].forEach((f) =>
            f?.addEventListener("submit", (e) => {
                if (e.defaultPrevented) return;
                akar.querySelectorAll("[data-atur-simpan]").forEach((b) => {
                    b.disabled = true;
                    b.setAttribute("aria-busy", "true");
                });
                if (sesi) sesi.mengirim = true;
            }),
        );

        // ---- tombol "Buat kode" (sandi acara): kode acak 8 karakter tanpa huruf/angka yang mirip (0 O 1 I L).
        // Memakai crypto.getRandomValues, bukan Math.random, karena kode ini dipakai sebagai sandi.
        akar.querySelectorAll("[data-buat-kode]").forEach((tombol) => {
            tombol.addEventListener("click", () => {
                const isian = akar.querySelector(tombol.dataset.buatKode);
                if (!isian) return;
                const huruf = "ABCDEFGHJKMNPQRSTUVWXYZ23456789";
                const acak = new Uint32Array(8);
                crypto.getRandomValues(acak);
                isian.value = Array.from(
                    acak,
                    (n) => huruf[n % huruf.length],
                ).join("");
                isian.dispatchEvent(new Event("input", { bubbles: true }));
                isian.focus();
            });
        });

        sesi = {
            mengirim: false,
            kotor: () => !sesi.mengirim && (kotorAtur() || kotorLokasi()),
            saranTerbuka: () => lokasi?.saranTerbuka() ?? false,
            tutupSaran: () => lokasi?.tutupSaran(),
            bersihkan: () => lokasi?.bersihkan(),
        };

        // Tab pertama: yang diminta, atau yang berisi isian bermasalah (setelah gagal divalidasi), atau Data acara.
        const bermasalah = akar.querySelector('[aria-invalid="true"]');
        const dariGalatLokasi =
            bermasalah && formLokasi?.contains(bermasalah) ? "lokasi" : null;
        const mulai =
            (tabAwal && panel(tabAwal) ? tabAwal : null) ??
            (bermasalah ? (dariGalatLokasi ?? tabDari(bermasalah)) : null) ??
            "data";
        pilihTab(mulai);
        if (bermasalah && !bermasalah.closest("[hidden]"))
            bermasalah.focus({ preventScroll: false });
    }

    // ---------------------------------------------------------------- peta, titik, dan cari alamat

    function pasangLokasi(akar, form) {
        const $ = (id) => akar.querySelector("#" + id);
        const lat = $("lokasi-lat");
        const lng = $("lokasi-lng");
        const radius = $("lokasi-radius");
        const sumber = $("lokasi-sumber");
        const hasil = $("lokasi-hasil");
        const alamat = $("lokasi-alamat");
        const daftar = $("lokasi-saran");
        const wadahPeta = $("peta-acara");
        const kartu = akar.querySelector("[data-atur-peta]");
        const gmaps = akar.querySelector("[data-lokasi-gmaps]");
        if (!lat || !lng || !radius || !wadahPeta || !kartu) return null;

        const angka = (el) => parseFloat(String(el.value).replace(",", "."));
        const valid = (a, b) =>
            Number.isFinite(a) &&
            Number.isFinite(b) &&
            Math.abs(a) <= 90 &&
            Math.abs(b) <= 180;
        const pesan = (t) =>
            hasil.replaceChildren(
                Object.assign(document.createElement("p"), { textContent: t }),
            );

        let peta = null;
        let titik = null;
        let lingkaran = null;

        const linkGmaps = () => {
            if (!gmaps) return;
            const a = angka(lat);
            const b = angka(lng);
            const kueri = valid(a, b) ? a + "," + b : alamat.value.trim();
            gmaps.href = kueri
                ? "https://www.google.com/maps/search/?api=1&query=" +
                  encodeURIComponent(kueri)
                : "https://www.google.com/maps";
        };

        const gambar = (geser = false) => {
            linkGmaps();
            if (!peta) return;
            const a = angka(lat);
            const b = angka(lng);
            if (!valid(a, b)) {
                titik?.remove();
                lingkaran?.remove();
                titik = lingkaran = null;
                return;
            }
            const r = Math.max(10, parseInt(radius.value, 10) || 150);
            if (!titik) {
                titik = L.circleMarker([a, b], {
                    radius: 7,
                    color: "#ffffff",
                    weight: 2,
                    fillColor: "#e11d48",
                    fillOpacity: 1,
                }).addTo(peta);
                lingkaran = L.circle([a, b], {
                    radius: r,
                    color: "#e11d48",
                    weight: 2,
                    fillOpacity: 0.15,
                }).addTo(peta);
            } else {
                titik.setLatLng([a, b]);
                lingkaran.setLatLng([a, b]).setRadius(r);
            }
            if (geser) peta.setView([a, b], Math.max(peta.getZoom(), 17));
        };

        const isi = (a, b, dari) => {
            lat.value = Number(a).toFixed(7);
            lng.value = Number(b).toFixed(7);
            sumber.value = dari;
            lat.dispatchEvent(
                new Event("input", { bubbles: true, composed: true }),
            );
            sumber.value = dari; // input di atas menandai "manual"; kembalikan sumber sebenarnya
            gambar(true);
        };

        [lat, lng].forEach((el) =>
            el.addEventListener("input", () => {
                sumber.value = "manual";
                gambar(true);
            }),
        );
        radius.addEventListener("input", () => gambar(false));

        // Peta dibuat saat tab Lokasi pertama kali tampil (di tab tersembunyi ukurannya nol).
        const tampil = () => {
            if (!peta) {
                const a0 = angka(lat);
                const b0 = angka(lng);
                const ada = valid(a0, b0);
                peta = L.map(wadahPeta, { scrollWheelZoom: false }).setView(
                    ada ? [a0, b0] : [-2.5, 118],
                    ada ? 17 : 5,
                );
                L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
                    maxZoom: 19,
                    attribution:
                        '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
                }).addTo(peta);
                peta.on("click", (e) => {
                    isi(e.latlng.lat, e.latlng.lng, "manual");
                    pesan(
                        "Titik diisi dari peta. Periksa posisinya, atur radius, lalu tekan Simpan lokasi.",
                    );
                });
                gambar(false);
            }
            setTimeout(() => peta?.invalidateSize(), 60);
        };
        linkGmaps();

        // ---- pakai lokasi HP
        kartu
            .querySelector("[data-lokasi-hp]")
            ?.addEventListener("click", async () => {
                if (!navigator.geolocation)
                    return pesan("Browser ini tidak mendukung lokasi.");
                // Jelaskan dulu kenapa lokasi dibutuhkan (atau cara membuka izin yang diblokir) sebelum browser meminta izin.
                const lanjut = await pastikanIzin(
                    "lokasi",
                    (o) => window.Swal?.fire(o),
                    {
                        isi: "Titik venue akan diisi dari GPS HP ini, jadi pastikan kamu sedang berada di venue. Setelah menekan Lanjut, browser akan meminta izin lokasi: pilih Izinkan dan nyalakan GPS HP. Koordinat hanya dipakai untuk mengisi titik.",
                    },
                );
                if (!lanjut)
                    return pesan(
                        "Izin lokasi belum diberikan. Kamu tetap bisa klik peta atau mengisi koordinat manual.",
                    );
                pesan("Mengambil lokasi HP…");
                navigator.geolocation.getCurrentPosition(
                    (p) => {
                        isi(p.coords.latitude, p.coords.longitude, "petugas");
                        pesan(
                            "Titik diisi dari GPS HP (akurasi sekitar " +
                                Math.round(p.coords.accuracy) +
                                " m). Pastikan kamu sedang di venue.",
                        );
                    },
                    () =>
                        pesan(
                            "Lokasi tidak bisa diambil. Izinkan akses lokasi di browser, atau klik peta.",
                        ),
                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
                );
            });

        // ---- cari alamat: saran saat mengetik (Photon lewat server) + tombol Cari (pencarian lengkap, Nominatim)
        let item = [];
        let aktif = -1;
        let pewaktu = null;
        let batal = null;
        const simpanan = new Map();
        const urlSaran = akar.dataset.urlSaran;
        const urlCari = akar.dataset.urlCari;
        const csrf = akar.dataset.csrf ?? "";

        const saranTerbuka = () => !daftar.hasAttribute("hidden");
        const tutupSaran = () => {
            daftar.setAttribute("hidden", "");
            daftar.replaceChildren();
            alamat.setAttribute("aria-expanded", "false");
            alamat.removeAttribute("aria-activedescendant");
            item = [];
            aktif = -1;
        };
        const tandai = (i) => {
            aktif = i;
            [...daftar.querySelectorAll('[role="option"]')].forEach((li, n) => {
                li.setAttribute("aria-selected", n === i ? "true" : "false");
                if (n === i) {
                    alamat.setAttribute("aria-activedescendant", li.id);
                    li.scrollIntoView({ block: "nearest" });
                }
            });
        };
        const pilih = (h) => {
            isi(h.lat, h.lng, h.dari ?? "alamat");
            if (h.nama) alamat.value = h.nama;
            tutupSaran();
            linkGmaps();
            pesan(
                (h.dari === "manual"
                    ? "Titik diisi dari koordinat yang ditempel."
                    : "Titik diisi dari alamat.") +
                    " Periksa posisinya di peta, lalu tekan Simpan lokasi.",
            );
        };
        const tampilSaran = (daftarHasil, catatan) => {
            item = daftarHasil;
            aktif = -1;
            daftar.replaceChildren();
            daftarHasil.forEach((h, i) => {
                const li = document.createElement("li");
                li.id = "saran-alamat-" + i;
                li.setAttribute("role", "option");
                li.setAttribute("aria-selected", "false");
                li.className =
                    "flex items-start gap-2.5 px-3 py-2.5 rounded-lg cursor-pointer hover:bg-white/10 aria-selected:bg-white/15";
                const teks = document.createElement("div");
                teks.className = "min-w-0";
                const judul = document.createElement("p");
                judul.className =
                    "text-sm font-semibold text-white wrap-break-word";
                judul.textContent = h.judul;
                teks.append(judul);
                if (h.detail) {
                    const d = document.createElement("p");
                    d.className = "text-xs text-white/72 wrap-break-word";
                    d.textContent = h.detail;
                    teks.append(d);
                }
                const pin = ikon("bi-geo-alt-fill");
                pin.classList.add("mt-0.5", "shrink-0", "text-white/60");
                li.append(pin, teks);
                // mousedown dicegah supaya isian alamat tidak kehilangan fokus sebelum klik terbaca
                li.addEventListener("mousedown", (e) => e.preventDefault());
                li.addEventListener("click", () => pilih(h));
                daftar.append(li);
            });
            if (!daftarHasil.length) {
                const kosong = document.createElement("li");
                kosong.setAttribute("role", "presentation");
                kosong.className = "px-3 py-3 text-sm text-white/72";
                kosong.textContent =
                    catatan ||
                    "Tidak ada saran. Tekan Cari untuk pencarian lebih lengkap, atau klik langsung di peta.";
                daftar.append(kosong);
            } else {
                const kaki = document.createElement("li");
                kaki.setAttribute("role", "presentation");
                kaki.className = "px-3 pt-1.5 pb-1 text-[11px] text-white/60";
                kaki.textContent = catatan || "Data peta © OpenStreetMap";
                daftar.append(kaki);
            }
            daftar.removeAttribute("hidden");
            alamat.setAttribute("aria-expanded", "true");
        };

        // Koordinat yang ditempel ("-6.2088, 106.8456") atau tautan Google Maps panjang yang memuat koordinat.
        const bacaKoordinat = (teks) => {
            const sah = (a, b) => (valid(a, b) ? { lat: a, lng: b } : null);
            if (/^https?:\/\//i.test(teks)) {
                const pola = [
                    /@(-?\d{1,2}\.\d+),(-?\d{1,3}\.\d+)/,
                    /!3d(-?\d{1,2}\.\d+)!4d(-?\d{1,3}\.\d+)/,
                    /[?&](?:q|query|ll|center)=(-?\d{1,2}\.\d+)(?:,|%2C)(-?\d{1,3}\.\d+)/i,
                ];
                for (const p of pola) {
                    const m = teks.match(p);
                    if (m) return sah(parseFloat(m[1]), parseFloat(m[2]));
                }
                return null;
            }
            const m = teks.match(
                /^(-?\d{1,2}(?:[.,]\d+)?)\s*[,;\s]\s*(-?\d{1,3}(?:[.,]\d+)?)$/,
            );

            return m
                ? sah(
                      parseFloat(m[1].replace(",", ".")),
                      parseFloat(m[2].replace(",", ".")),
                  )
                : null;
        };

        const ambilSaran = async (q) => {
            batal?.abort();
            if (simpanan.has(q)) return tampilSaran(simpanan.get(q));
            batal = new AbortController();
            try {
                const r = await fetch(
                    urlSaran + "?q=" + encodeURIComponent(q),
                    {
                        credentials: "same-origin",
                        signal: batal.signal,
                        headers: {
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    },
                );
                const data = await r.json().catch(() => ({}));
                if (alamat.value.trim() !== q) return; // sudah mengetik lagi
                if (!r.ok)
                    return tampilSaran(
                        [],
                        data.pesan ||
                            "Saran alamat tidak bisa dimuat. Tekan Cari.",
                    );
                simpanan.set(q, data.hasil ?? []);
                tampilSaran(data.hasil ?? []);
            } catch (e) {
                if (e.name !== "AbortError")
                    tampilSaran(
                        [],
                        "Saran alamat tidak bisa dimuat. Periksa koneksi, atau tekan Cari.",
                    );
            }
        };

        const cari = async () => {
            const q = alamat.value.trim();
            if (!q) return pesan("Ketik nama venue atau alamat dulu.");
            clearTimeout(pewaktu);
            batal?.abort();
            pesan("Mencari alamat…");
            try {
                const r = await fetch(urlCari, {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrf,
                    },
                    body: JSON.stringify({ alamat: q }),
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok)
                    return pesan(
                        data.pesan ||
                            "Pencarian alamat gagal (status " + r.status + ").",
                    );
                if (!data.hasil?.length)
                    return pesan(
                        'Tidak ada hasil untuk "' +
                            data.kueri +
                            '". Coba alamat yang lebih lengkap, klik peta, atau tempel koordinat dari Google Maps.',
                    );
                hasil.replaceChildren();
                tampilSaran(
                    data.hasil.map((h) => {
                        const [judul, ...sisa] = String(h.nama).split(", ");
                        return {
                            judul,
                            detail: sisa.join(", "),
                            nama: h.nama,
                            lat: h.lat,
                            lng: h.lng,
                        };
                    }),
                    "Hasil pencarian lengkap · © OpenStreetMap",
                );
                pesan("Pilih yang benar, lalu periksa posisinya di peta.");
            } catch {
                pesan(
                    "Pencarian alamat gagal. Periksa koneksi lalu coba lagi.",
                );
            }
        };

        alamat.addEventListener("input", () => {
            clearTimeout(pewaktu);
            linkGmaps();
            const q = alamat.value.trim();
            if (/goo\.gl|maps\.app\./i.test(q)) {
                tutupSaran();
                return pesan(
                    "Tautan pendek Google Maps tidak bisa dibaca. Buka tautannya, lalu salin alamat lengkap dari bilah alamat browser.",
                );
            }
            const koordinat = bacaKoordinat(q);
            if (koordinat) {
                return tampilSaran([
                    {
                        judul:
                            "Pakai koordinat " +
                            koordinat.lat +
                            ", " +
                            koordinat.lng,
                        detail: "Dari teks yang ditempel",
                        lat: koordinat.lat,
                        lng: koordinat.lng,
                        dari: "manual",
                    },
                ]);
            }
            if (q.length < 3) return tutupSaran();
            // Menunggu jeda mengetik supaya layanan gratis ini tidak dibanjiri satu permintaan per huruf.
            pewaktu = setTimeout(() => ambilSaran(q), 350);
        });
        alamat.addEventListener("keydown", (e) => {
            if (e.key === "ArrowDown" || e.key === "ArrowUp") {
                if (!saranTerbuka() || !item.length) return;
                e.preventDefault();
                const n = item.length;
                tandai(
                    e.key === "ArrowDown"
                        ? (aktif + 1) % n
                        : (aktif - 1 + n) % n,
                );
            } else if (e.key === "Enter") {
                e.preventDefault(); // Enter di sini tidak boleh mengirim form
                if (saranTerbuka() && aktif >= 0 && item[aktif])
                    pilih(item[aktif]);
                else cari();
            } else if (e.key === "Escape" && saranTerbuka()) {
                e.preventDefault();
                tutupSaran();
            }
        });
        alamat.addEventListener("focus", () => {
            const q = alamat.value.trim();
            if (q.length >= 3 && simpanan.has(q) && !saranTerbuka())
                tampilSaran(simpanan.get(q));
        });
        kartu
            .querySelector("[data-lokasi-cari]")
            ?.addEventListener("click", cari);
        const tutupBilaDiluar = (e) => {
            if (
                saranTerbuka() &&
                !e.target.closest("#lokasi-alamat, #lokasi-saran")
            )
                tutupSaran();
        };
        akar.addEventListener("click", tutupBilaDiluar);

        return {
            tampil,
            saranTerbuka,
            tutupSaran,
            bersihkan: () => {
                clearTimeout(pewaktu);
                batal?.abort();
                peta?.remove();
                peta = titik = lingkaran = null;
            },
        };
    }

    // ---------------------------------------------------------------- buka otomatis dari server

    // ?atur=KODE (tautan dari halaman lain, atau setelah menambah acara) atau form yang gagal divalidasi.
    if (dialog.dataset.bukaUrl) {
        if (dialog.dataset.inline === "1") {
            dialog.showModal();
            pasang();
        } else {
            buka(dialog.dataset.bukaUrl);
        }
    }
}
