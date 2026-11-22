@props(['kenangan'])
{{-- Ala IG: tumpukan foto profil penyuka + "Disukai oleh Nama dan N lainnya". Mengetuknya membuka popup daftar penyuka
     (#modal-penyuka, resources/js/inner-circle.js). Tersembunyi selama belum ada yang suka; JavaScript menyegarkannya
     setiap kali suka berubah. Bila daftar penyuka belum dimuat (mis. preview CMS) hanya tampil jumlahnya. --}}
@php
    $penyuka = $kenangan->penyuka_tampil;
    $jumlah = (int) $kenangan->jumlah_like;
    $pertama = $penyuka->first();
@endphp
<button type="button" class="ic-penyuka" data-ic-penyuka="{{ $kenangan->id_memory }}"
    data-ic-penyuka-url="{{ route('ic.kenangan.penyuka', $kenangan->id_memory) }}"
    @if ($jumlah < 1) hidden @endif>
    <span class="ic-penyuka__tumpuk" aria-hidden="true">
        @foreach ($penyuka as $p)
            <x-inner-circle.avatar :member="$p" kelas="ic-avatar--mini" />
        @endforeach
    </span>
    <span class="ic-penyuka__teks">
        @if ($pertama)
            Disukai oleh <b>{{ $pertama->nama_singkat }}</b>
            @if ($jumlah > 1)
                dan <b>{{ $jumlah - 1 }} lainnya</b>
            @endif
        @else
            {{ $jumlah }} suka
        @endif
    </span>
</button>
