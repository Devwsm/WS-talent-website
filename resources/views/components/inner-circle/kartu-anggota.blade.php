@props(['member', 'detail' => false])
{{-- Kartu member. Beranda memakai bentuk ringkas; Profil memakai `detail`: satu kartu berisi foto, nama, ID, email, saldo,
     dan satu tombol pensil yang membuka popup "Ubah profil" (ganti foto dan nama sekaligus). --}}
<div class="ic-kartu-anggota {{ $detail ? 'ic-kartu-anggota--detail' : '' }}">
    <div class="ic-kartu-anggota__kepala">
        <div>
            <x-inner-circle.logo ukuran="lg" />
            <p class="ic-sub">{{ ic_teks('beranda.kartu.label') }}</p>
        </div>
        @if ($detail)
            <button type="button" class="ic-kartu-anggota__pensil" data-ic-modal-buka="modal-profil"
                aria-label="Ubah foto dan nama"><i class="bi bi-pencil-fill" aria-hidden="true"></i></button>
        @endif
    </div>
    <div class="ic-kartu-anggota__bawah">
        @if ($detail)
            <button type="button" class="ic-kartu-anggota__foto" data-ic-modal-buka="modal-profil"
                aria-label="Ubah foto profil">
                <x-inner-circle.avatar :member="$member" kelas="ic-avatar--kartu" />
                <span class="ic-kartu-anggota__lencana" aria-hidden="true"><i class="bi bi-camera-fill"></i></span>
            </button>
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
</div>
