@props(['member'])
<div class="ic-kartu-anggota">
    <div>
        <x-inner-circle.logo ukuran="lg" />
        <p class="ic-sub">KARTU KEANGGOTAAN DIGITAL</p>
    </div>
    <div>
        <p class="ic-kartu-anggota__nama">{{ $member->nama }}</p>
        <small>ID ANGGOTA: {{ $member->kode_member }}</small>
    </div>
</div>
