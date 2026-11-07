{{--
    Pratinjau replika di samping form tab Konten. Sama seperti panel "Tampilan di halaman Profile" di CMS Artis:
    digambar di browser memakai class asli area anggota dan diperbarui langsung saat mengetik (tanpa permintaan ke
    server dan tanpa iframe). Teks yang diubah diberi garis putus-putus; isian kosong tampil sebagai teks bawaan.
    Variabel: $kodeGrup, $info (label + pratinjau), $m (modul), $kunciDef (definisi semua kunci), $tersimpan, $idForm,
    $potongan (potongan halaman ASLI dari IcPotongan: html, halaman, hilang; null = pakai replika sederhana di bawah).
--}}
@props(['kodeGrup', 'info', 'm', 'kunciDef', 'tersimpan' => [], 'idForm' => null, 'potongan' => null])
@php
    $program = ic_program();
    $peta = app(\App\Services\InnerCircle\IcPotongan::class)->peta($program);
    $namaHalaman = collect($info['pratinjau'] ?? [])->implode(' · ');
    $kunciTampil = !empty($m['kunci']) ? $m['kunci'] : array_values(array_filter([$m['fokus'] ?? null]));
    // Dengan potongan halaman asli, replika sederhana hanya dipakai untuk isian yang tidak tampil di potongan itu.
    if ($potongan !== null) {
        $kunciTampil = $potongan['hilang'];
    }
    $langsung = !empty($m['kunci']);
    $warna = app(\App\Services\InnerCircle\IcTampilan::class)->warna();
    $kodeDaftar = $m['daftar'] ?? null;
    $butir = $kodeDaftar
        ? app(\App\Services\InnerCircle\IcDaftar::class)->semua($kodeDaftar)->where('aktif', true)->values()
        : collect();
@endphp
<aside class="lg:sticky lg:top-8 min-w-0 rounded-3xl border border-white/10 bg-black overflow-hidden" data-replika
    @if ($langsung && $idForm) data-form="{{ $idForm }}" @endif
    data-ganti="{{ json_encode($peta, JSON_UNESCAPED_UNICODE) }}" aria-label="Pratinjau {{ $m['label'] }}">
    <div
        class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-5 py-3 border-b border-white/10 bg-white/3">
        <p class="text-xs uppercase tracking-widest text-white/60 flex items-center gap-2">
            <i class="bi bi-eye text-white/50" aria-hidden="true"></i> Tampilan di halaman
        </p>
        @if ($potongan !== null)
            <span class="text-xs text-white/50">{{ $info['label'] }} <i class="bi bi-chevron-right" aria-hidden="true"></i>
                {{ $m['label'] }}</span>
        @elseif ($namaHalaman !== '')
            <span class="text-xs text-white/50">{{ $namaHalaman }}</span>
        @endif
    </div>

    <div class="p-5 flex flex-col gap-3">
        @if ($potongan !== null)
            {{-- Bagian halaman asli (view yang sama dengan halaman anggota), dirender sekali di server dan diperbarui langsung saat mengetik. `inert` = tautan dan tombol tidak bisa diklik. --}}
            <p class="text-xs text-white/50">Halaman: <span class="text-white/80">{{ $potongan['halaman'] }}</span>
                &middot; Tampil seperti di layar HP &middot; bagian yang kamu ubah diberi garis.
            </p>
            <div class="ic-app ic-ponsel ic-replika ic-replika--halaman rounded-2xl" inert
                style="--ic-aksen: {{ $warna['aksen'] }}; --ic-latar: {{ $warna['latar'] }}; --ic-shell: {{ $warna['kartu'] }};">
                {!! $potongan['html'] !!}
            </div>
        @endif

        @if ($potongan === null || $kunciTampil !== [])
            @if ($potongan !== null)
                <p class="text-xs text-white/50">Isian di bawah hanya tampil pada kondisi tertentu, jadi tidak ada di
                    contoh di atas:</p>
            @endif
            <div class="ic-app ic-ponsel ic-replika rounded-2xl"
                style="--ic-aksen: {{ $warna['aksen'] }}; --ic-latar: {{ $warna['latar'] }}; --ic-shell: {{ $warna['kartu'] }};">
                @foreach ($kunciTampil as $k)
                    @php
                        $def = $kunciDef[$k];
                        $kolom = str_replace('.', '__', $k);
                        $nilai = $tersimpan[$k] ?? '';
                        $teks = trim((string) $nilai) !== '' ? $nilai : $def['bawaan'];
                        $tipe = \App\Support\IcReplika::tipe($k);
                        $tampil = \App\Support\IcReplika::ganti($teks, $peta);
                        $bawaan = \App\Support\IcReplika::ganti($def['bawaan'], $peta);
                    @endphp
                    <div class="ic-replika__blok" data-replika-blok="{{ $kolom }}">
                        <p class="ic-replika__label">{{ $def['label'] }}</p>
                        @switch($tipe)
                            @case('judul')
                                <h3 class="ic-judul"><em data-replika-teks="{{ $kolom }}"
                                        data-bawaan="{{ $bawaan }}">{{ $tampil }}</em><span>.</span></h3>
                            @break

                            @case('judul-bagian')
                                <h3 class="ic-judul-bagian" data-replika-teks="{{ $kolom }}"
                                    data-bawaan="{{ $bawaan }}">{{ $tampil }}</h3>
                            @break

                            @case('eyebrow')
                                <p class="ic-eyebrow" data-replika-teks="{{ $kolom }}"
                                    data-bawaan="{{ $bawaan }}">
                                    {{ $tampil }}</p>
                            @break

                            @case('tombol')
                            @case('tombol-garis')
                                <span class="ic-tombol {{ $tipe === 'tombol-garis' ? 'ic-tombol--garis' : '' }}"><span
                                        data-replika-teks="{{ $kolom }}"
                                        data-bawaan="{{ $bawaan }}">{{ $tampil }}</span></span>
                            @break

                            @case('tautan')
                                <span class="ic-tautan" data-replika-teks="{{ $kolom }}"
                                    data-bawaan="{{ $bawaan }}">{{ $tampil }}</span>
                            @break

                            @case('meta-judul')
                                <p class="ic-replika__meta-judul" data-replika-teks="{{ $kolom }}"
                                    data-bawaan="{{ $bawaan }}">{{ $tampil }}</p>
                            @break

                            @case('meta-deskripsi')
                                <p class="ic-replika__meta-deskripsi" data-replika-teks="{{ $kolom }}"
                                    data-bawaan="{{ $bawaan }}">{{ $tampil }}</p>
                            @break

                            @default
                                <p class="ic-deskripsi" data-replika-teks="{{ $kolom }}"
                                    data-bawaan="{{ $bawaan }}">
                                    {{ $tampil }}</p>
                        @endswitch
                    </div>
                @endforeach

                @if ($kodeDaftar && $potongan === null)
                    <div class="ic-replika__blok">
                        <p class="ic-replika__label">{{ $info['label'] }}: daftar yang tampil</p>
                        @if ($butir->isEmpty())
                            <p class="ic-deskripsi">Belum ada butir yang tampil.</p>
                        @elseif ($kodeDaftar === 'teaser_poin')
                            <div class="ic-replika__pil-baris">
                                @foreach ($butir as $b)
                                    <span class="ic-replika__pil"><i class="bi bi-{{ $b->ikon ?: 'stars' }}"
                                            aria-hidden="true"></i>
                                        {{ \App\Support\IcReplika::ganti((string) $b->judul, $peta) }}</span>
                                @endforeach
                            </div>
                        @else
                            <ol class="ic-replika__daftar">
                                @foreach ($butir as $i => $b)
                                    <li>
                                        @if ($kodeDaftar === 'langkah')
                                            <b aria-hidden="true">{{ $i + 1 }}</b>
                                        @endif
                                        <div>
                                            <strong>{{ \App\Support\IcReplika::ganti((string) $b->judul, $peta) }}</strong>
                                            @if ($b->isi)
                                                <span>{{ \App\Support\IcReplika::ganti((string) $b->isi, $peta) }}</span>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                    <p class="ic-replika__catatan">Daftar ikut berubah setelah butirnya disimpan.</p>
                @endif
            </div>
        @endif

        @if ($langsung)
            <p class="text-xs text-white/50">Berubah langsung saat kamu mengetik. Garis putus-putus = teks yang kamu
                ubah; isian kosong tampil sebagai teks
                bawaan.{{ $potongan !== null ? '' : ' Susunan di halaman asli bisa sedikit berbeda.' }}</p>
        @endif
    </div>
</aside>
