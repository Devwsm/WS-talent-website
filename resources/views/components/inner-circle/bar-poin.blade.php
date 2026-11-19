@props(['member', 'berikutnya'])
<div class="ic-bar-poin">
    <p class="ic-bar-poin__angka"><strong>{{ ic_angka($member->poin_total) }}</strong> /
        {{ ic_angka($berikutnya->harga_poin) }}
        poin</p>
    <div class="ic-progres" role="progressbar" aria-label="Progres poin" aria-valuemin="0"
        aria-valuemax="{{ $berikutnya->harga_poin }}" aria-valuenow="{{ $member->poin_total }}">
        <span style="width: {{ $berikutnya->persen }}%"></span>
    </div>
    <div class="ic-bar-poin__catatan">
        <x-inner-circle.ikon nama="gift" kelas="ic-ikon--kecil" />
        <p>
            @if ($berikutnya->sisa > 0)
                <b>{{ ic_angka($berikutnya->sisa) }}</b> {{ ic_teks('beranda.poin.sisa') }}
            @else
                {{ ic_teks('beranda.poin.selesai') }}
            @endif
            <small>{{ ic_teks('beranda.poin.catatan') }}</small>
        </p>
    </div>
</div>
