@props(['member', 'berikutnya'])
<div class="ic-bar-stempel">
    <p class="ic-bar-stempel__angka"><strong>{{ $member->stempel_total }}</strong> / {{ $berikutnya->target_stempel }}
        stempel</p>
    <div class="ic-progres" role="progressbar" aria-label="Progres stempel" aria-valuemin="0"
        aria-valuemax="{{ $berikutnya->target_stempel }}" aria-valuenow="{{ $member->stempel_total }}">
        <span style="width: {{ $berikutnya->persen }}%"></span>
    </div>
    <div class="ic-bar-stempel__catatan">
        <x-inner-circle.ikon nama="gift" kelas="ic-ikon--kecil" />
        <p>
            @if ($berikutnya->sisa > 0)
                <b>{{ $berikutnya->sisa }}</b> {{ ic_teks('beranda.stempel.sisa') }}
            @else
                {{ ic_teks('beranda.stempel.selesai') }}
            @endif
            <small>{{ ic_teks('beranda.stempel.catatan') }}</small>
        </p>
    </div>
</div>
