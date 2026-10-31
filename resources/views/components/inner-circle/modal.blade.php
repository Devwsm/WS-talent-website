@props(['id', 'judul', 'eyebrow' => null, 'terbuka' => false, 'lembar' => false])
{{-- `terbuka`: dibuka otomatis saat halaman dimuat (inner-circle.js), mis. supaya galat validasi form terlihat.
     `lembar`: di HP naik dari bawah layar (bottom sheet), di layar lebar tetap kotak di tengah. --}}
<div {{ $attributes->merge(['class' => 'ic-modal' . ($lembar ? ' ic-modal--lembar' : '')]) }} id="{{ $id }}"
    hidden @if ($terbuka) data-ic-buka-awal @endif>
    <div class="ic-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-judul"
        tabindex="-1">
        @if ($lembar)
            <span class="ic-modal__pegangan" aria-hidden="true"></span>
        @endif
        <div class="ic-modal__kepala">
            <div>
                @if ($eyebrow)
                    <p class="ic-eyebrow">{{ $eyebrow }}</p>
                @endif
                <h2 class="ic-modal__judul" id="{{ $id }}-judul">{{ $judul }}</h2>
            </div>
            <button type="button" class="ic-modal__tutup" data-ic-modal-tutup aria-label="Tutup">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
        {{ $slot }}
    </div>
</div>
