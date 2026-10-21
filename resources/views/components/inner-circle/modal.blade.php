@props(['id', 'judul', 'eyebrow' => null])
<div class="ic-modal" id="{{ $id }}" hidden>
    <div class="ic-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-judul"
        tabindex="-1">
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
