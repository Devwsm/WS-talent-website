@props(['kenangan'])
@php
    $kategori = config('inner_circle.kategori_kenangan.' . $kenangan->kategori);
@endphp
<article class="ic-kenangan">
    <div class="ic-kenangan__sampul ic-warna-{{ $kenangan->warna }}">
        @if ($kenangan->foto)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($kenangan->foto) }}"
                alt="Foto kenangan dari {{ $kenangan->nama_member }}" loading="lazy">
        @else
            <x-inner-circle.ikon :nama="$kenangan->ikon" kelas="ic-ikon--sampul" />
            <span class="ic-kenangan__bintang" aria-hidden="true">✳</span>
        @endif
    </div>
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
