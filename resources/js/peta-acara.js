import L from "leaflet";
import "leaflet/dist/leaflet.css";

/**
 * Peta titik dan radius check-in di halaman Atur acara (CMS Inner Circle).
 * Klik peta untuk menaruh titik, geser radius lewat isian, atau isi dari GPS HP / pencarian alamat.
 * Koordinat yang diketik tetap bisa dipakai sebagai cadangan. Penanda memakai bentuk vektor (tanpa gambar ikon),
 * supaya tidak ada berkas gambar tambahan yang perlu dimuat.
 */
const kartu = document.querySelector("[data-peta-kartu]");

if (kartu) {
    const $ = (id) => document.getElementById(id);
    const lat = $("lokasi-lat");
    const lng = $("lokasi-lng");
    const radius = $("lokasi-radius");
    const sumber = $("lokasi-sumber");
    const hasil = $("lokasi-hasil");
    const csrf = kartu.querySelector("input[name=_token]")?.value ?? "";

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

    const awalLat = angka(lat);
    const awalLng = angka(lng);
    const ada = valid(awalLat, awalLng);

    const peta = L.map($("peta-acara"), { scrollWheelZoom: false }).setView(
        ada ? [awalLat, awalLng] : [-2.5, 118],
        ada ? 17 : 5,
    );
    L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
    }).addTo(peta);

    let titik = null;
    let lingkaran = null;

    const gambar = (geser = false) => {
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
        gambar(true);
    };

    peta.on("click", (e) => {
        isi(e.latlng.lat, e.latlng.lng, "manual");
        pesan(
            "Titik diisi dari peta. Periksa posisinya, atur radius, lalu tekan Simpan lokasi.",
        );
    });
    [lat, lng].forEach((el) =>
        el.addEventListener("input", () => {
            sumber.value = "manual";
            gambar(true);
        }),
    );
    radius.addEventListener("input", () => gambar(false));
    gambar(false);
    setTimeout(() => peta.invalidateSize(), 200);

    kartu.querySelector("[data-lokasi-hp]")?.addEventListener("click", () => {
        if (!navigator.geolocation)
            return pesan("Browser ini tidak mendukung lokasi.");
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

    kartu
        .querySelector("[data-lokasi-cari]")
        ?.addEventListener("click", async () => {
            pesan("Mencari alamat…");
            try {
                const r = await fetch(kartu.dataset.urlCari, {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrf,
                    },
                    body: JSON.stringify({ alamat: $("lokasi-alamat").value }),
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok)
                    return pesan(
                        data.pesan ||
                            "Pencarian alamat gagal (status " + r.status + ").",
                    );
                if (!data.hasil.length)
                    return pesan(
                        'Tidak ada hasil untuk "' +
                            data.kueri +
                            '". Coba alamat yang lebih lengkap, atau klik peta.',
                    );

                const daftar = document.createElement("div");
                daftar.className = "flex flex-col gap-2";
                daftar.appendChild(
                    Object.assign(document.createElement("p"), {
                        textContent:
                            "Pilih yang benar, lalu periksa posisinya di peta:",
                    }),
                );
                data.hasil.forEach((h) => {
                    const tombol = document.createElement("button");
                    tombol.type = "button";
                    tombol.className =
                        "text-left text-sm text-white hover:underline break-words rounded-xl border border-white/10 p-3";
                    tombol.textContent = h.nama;
                    tombol.addEventListener("click", () => {
                        isi(h.lat, h.lng, "alamat");
                        pesan(
                            "Titik diisi dari alamat. Periksa posisinya di peta, lalu tekan Simpan lokasi.",
                        );
                    });
                    daftar.appendChild(tombol);
                });
                hasil.replaceChildren(daftar);
            } catch {
                pesan(
                    "Pencarian alamat gagal. Periksa koneksi lalu coba lagi.",
                );
            }
        });
}

// Tombol "Buat kode": mengisi kolom sandi acara dengan kode acak 8 karakter (tanpa huruf/angka yang mirip: 0 O 1 I L).
// Memakai crypto.getRandomValues, bukan Math.random, karena kode ini dipakai sebagai sandi.
document.querySelectorAll("[data-buat-kode]").forEach((tombol) => {
    tombol.addEventListener("click", () => {
        const isian = document.querySelector(tombol.dataset.buatKode);
        if (!isian) return;
        const huruf = "ABCDEFGHJKMNPQRSTUVWXYZ23456789";
        const acak = new Uint32Array(8);
        crypto.getRandomValues(acak);
        isian.value = Array.from(acak, (n) => huruf[n % huruf.length]).join("");
        isian.dispatchEvent(new Event("input", { bubbles: true }));
        isian.focus();
    });
});
