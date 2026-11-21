@props(['kenangan'])
@php
    $kategori = ic_kategori_kenangan(true)[$kenangan->kategori] ?? ['label' => 'Kenangan', 'ikon' => 'sparkles'];
@endphp
<article class="ic-kenangan">
    {{-- Sampul 4:5; mengetuknya membuka detail kenangan (foto penuh dan komentar). --}}
    <a class="ic-kenangan__sampul ic-warna-{{ $kenangan->warna }}"
        href="{{ route('ic.kenangan.detail', $kenangan->id_memory) }}"
        aria-label="Buka kenangan dari {{ $kenangan->nama_member }}">
        @if ($kenangan->foto)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($kenangan->foto) }}"
                alt="Foto kenangan dari {{ $kenangan->nama_member }}" width="640" height="800" loading="lazy">
        @else
            <x-inner-circle.ikon :nama="$kenangan->ikon" kelas="ic-ikon--sampul" />
            <span class="ic-kenangan__bintang" aria-hidden="true">✳</span>
        @endif
    </a>
    <div class="ic-kenangan__isi">
        <div class="ic-kenangan__atas">
            <x-inner-circle.avatar :member="$kenangan->member" kelas="ic-avatar--kecil" />
            <div>
                <b>{{ $kenangan->nama_member }}</b>
                <small>{{ $kenangan->tempat ? $kenangan->tempat . ' · ' : '' }}{{ $kenangan->tanggal->locale('id')->translatedFormat('j M Y') }}</small>
            </div>
        </div>
        <p class="ic-kenangan__caption">{{ $kenangan->caption }}</p>
        <div class="ic-kenangan__kaki">
            <span class="ic-kenangan__tag"><x-inner-circle.ikon :nama="$kategori['ikon']" kelas="ic-ikon--mini" />
                {{ $kategori['label'] }}</span>
            <span class="ic-kenangan__aksi">
                <button type="button" class="ic-suka {{ $kenangan->disukai_saya ? 'is-suka' : '' }}"
                    aria-pressed="{{ $kenangan->disukai_saya ? 'true' : 'false' }}"
                    data-ic-suka="{{ $kenangan->id_memory }}"
                    data-ic-suka-url="{{ route('ic.kenangan.suka', $kenangan->id_memory) }}">
                    <i class="bi {{ $kenangan->disukai_saya ? 'bi-heart-fill' : 'bi-heart' }}" aria-hidden="true"></i>
                    <span>{{ $kenangan->jumlah_like }}</span>
                </button>
                {{-- Pintasan komentar: langsung ke kolom komentar di halaman detail. --}}
                <a class="ic-suka ic-komentar-pintas"
                    href="{{ route('ic.kenangan.detail', $kenangan->id_memory) }}#komentar"
                    aria-label="Komentar ({{ $kenangan->jumlah_komentar }})">
                    <i class="bi bi-chat" aria-hidden="true"></i>
                    <span>{{ $kenangan->jumlah_komentar }}</span>
                </a>
                @if ($kenangan->milik_saya)
                    {{-- Hapus = form DELETE: jalan tanpa JS; dengan JS minta konfirmasi dulu (data-ic-konfirmasi). --}}
                    <form method="POST" action="{{ route('ic.kenangan.hapus', $kenangan->id_memory) }}"
                        data-ic-konfirmasi data-ic-sekali data-ic-konfirmasi-judul="Hapus kenangan ini?"
                        data-ic-konfirmasi-tombol="Ya, hapus"
                        data-judul="Foto dan ceritanya akan dihapus dari galeri dan tidak bisa dikembalikan.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="ic-hapus">Hapus</button>
                    </form>
                @endif
            </span>
        </div>
    </div>
</article>
