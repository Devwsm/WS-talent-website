{{--
    Pratinjau replika bagian Tampilan dan merek: kartu contoh yang memakai token warna asli (--ic-aksen, --ic-latar,
    --ic-shell) dan berubah langsung saat warna atau nama program diubah. Logo kustom baru tampil setelah disimpan.
    Variabel: $info (label + pratinjau), $tampilanWarna (warna efektif), $tampilanLogo (alamat logo kustom atau null).
--}}
@props(['info', 'tampilanWarna', 'tampilanLogo' => null])
@php $program = ic_program(); @endphp
<aside class="lg:sticky lg:top-8 min-w-0 rounded-3xl border border-white/10 bg-black overflow-hidden"
    data-replika-tampilan data-form="form-tampilan" data-program-bawaan="{{ config('ic_konten.program') }}"
    aria-label="Pratinjau tampilan dan merek">
    <div
        class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-5 py-3 border-b border-white/10 bg-white/3">
        <p class="text-xs uppercase tracking-widest text-white/60 flex items-center gap-2">
            <i class="bi bi-eye text-white/50" aria-hidden="true"></i> Tampilan di halaman
        </p>
        <span class="text-xs text-white/50">{{ collect($info['pratinjau'] ?? [])->implode(' · ') }}</span>
    </div>

    <div class="p-5 flex flex-col gap-3">
        <div class="ic-app ic-replika ic-replika--tampilan rounded-2xl" data-replika-latar
            style="--ic-aksen: {{ $tampilanWarna['aksen'] }}; --ic-latar: {{ $tampilanWarna['latar'] }}; --ic-shell: {{ $tampilanWarna['kartu'] }};">
            <div class="ic-replika__kartu">
                <img class="ic-logo ic-replika__logo"
                    src="{{ $tampilanLogo ?: asset('aset/inner-circle/logo/wsic-wordmark-kompak.webp') }}"
                    alt="Logo {{ $program }}" width="210" height="92" decoding="async" data-replika-logo>
                <p class="ic-eyebrow" data-replika-program>{{ $program }}</p>
                <h3 class="ic-judul"><em>Halo</em><span>.</span></h3>
                <p class="ic-deskripsi">Contoh kartu dengan warna kartu, latar, dan aksen yang kamu pilih.</p>
                <div class="ic-aksi-baris">
                    <span class="ic-tombol"><span>Tombol utama</span></span>
                    <span class="ic-tombol ic-tombol--garis"><span>Tombol garis</span></span>
                </div>
            </div>
        </div>
        <p class="text-xs text-white/50">Berubah langsung saat warna atau nama program diubah. Logo baru tampil setelah
            disimpan.</p>
    </div>
</aside>
