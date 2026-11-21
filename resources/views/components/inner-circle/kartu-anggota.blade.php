@props(['member', 'detail' => false])
{{-- Kartu member. Beranda memakai bentuk ringkas; Profil memakai `detail` (foto, email, saldo poin, tombol Ganti nama). --}}
<div class="ic-kartu-anggota {{ $detail ? 'ic-kartu-anggota--detail' : '' }}">
    <div>
        <x-inner-circle.logo ukuran="lg" />
        <p class="ic-sub">{{ ic_teks('beranda.kartu.label') }}</p>
    </div>
    <div class="ic-kartu-anggota__bawah">
        @if ($detail)
            <x-inner-circle.avatar :member="$member" kelas="ic-avatar--kartu" />
        @endif
        <div class="ic-kartu-anggota__data">
            <p class="ic-kartu-anggota__nama">{{ $member->nama }}</p>
            <small>ID ANGGOTA: {{ $member->kode_member }}</small>
            @if ($detail)
                <small>{{ $member->email }}</small>
                <small>Saldo: {{ ic_angka($member->poin_total) }} poin · Bergabung
                    {{ $member->created_at?->locale('id')->translatedFormat('F Y') }}</small>
            @endif
        </div>
    </div>
    @if ($detail)
        <button type="button" class="ic-tombol ic-tombol--kecil ic-tombol--terang ic-kartu-anggota__ubah"
            data-ic-modal-buka="modal-nama"><i class="bi bi-pencil" aria-hidden="true"></i> Ganti nama</button>
    @endif
</div>
