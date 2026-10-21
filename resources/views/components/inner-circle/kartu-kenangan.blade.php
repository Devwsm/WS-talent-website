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
            <span class="ic-avatar ic-avatar--kecil" aria-hidden="true">{{ $kenangan->avatar_emoji }}</span>
            <div>
                <b>{{ $kenangan->nama_member }}</b>
                <small>{{ $kenangan->tempat }} ·
                    {{ $kenangan->tanggal->locale('id')->translatedFormat('j M Y') }}</small>
            </div>
        </div>
        <p class="ic-kenangan__caption">{{ $kenangan->caption }}</p>
        <div class="ic-kenangan__kaki">
            <span class="ic-kenangan__tag"><x-inner-circle.ikon :nama="$kategori['ikon']" kelas="ic-ikon--mini" />
                {{ $kategori['label'] }}</span>
            <span class="ic-kenangan__aksi">
                <button type="button" class="ic-suka {{ $kenangan->disukai_saya ? 'is-suka' : '' }}"
                    aria-pressed="{{ $kenangan->disukai_saya ? 'true' : 'false' }}"
                    data-ic-suka="{{ $kenangan->id_memory }}">
                    <i class="bi {{ $kenangan->disukai_saya ? 'bi-heart-fill' : 'bi-heart' }}" aria-hidden="true"></i>
                    <span>{{ $kenangan->jumlah_like }}</span>
                </button>
                @if ($kenangan->milik_saya)
                    <button type="button" class="ic-hapus" data-ic-hapus="{{ $kenangan->id_memory }}">Hapus</button>
                @endif
            </span>
        </div>
    </div>
</article>
