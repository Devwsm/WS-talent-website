@props(['member', 'detail' => false])
{{-- Kartu member. Beranda memakai bentuk ringkas; Profil memakai `detail`: satu kartu berisi foto, nama, email, Member ID,
     tanggal bergabung, dan status akun, dengan satu tombol pensil yang membuka popup "Ubah profil" (foto dan nama). --}}
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

    @if ($detail)
        <div class="ic-kartu-anggota__profil">
            <button type="button" class="ic-kartu-anggota__foto" data-ic-modal-buka="modal-profil"
                aria-label="Ubah foto profil">
                <x-inner-circle.avatar :member="$member" kelas="ic-avatar--kartu" />
                <span class="ic-kartu-anggota__lencana" aria-hidden="true"><i class="bi bi-camera-fill"></i></span>
            </button>
            <div class="ic-kartu-anggota__data">
                <p class="ic-kartu-anggota__nama">{{ $member->nama }}</p>
                <small class="ic-kartu-anggota__email">{{ $member->email }}</small>
            </div>
        </div>
        <dl class="ic-kartu-anggota__statistik">
            <div>
                <dt>ID anggota</dt>
                <dd class="ic-kartu-anggota__id">{{ $member->kode_member }}</dd>
            </div>
            <div>
                <dt>Bergabung</dt>
                <dd>{{ $member->created_at?->locale('id')->translatedFormat('M Y') }}</dd>
            </div>
            <div>
                <dt>Status</dt>
                <dd>
                    @if ($member->email_verified_at)
                        <i class="bi bi-patch-check-fill" aria-hidden="true"></i> Terverifikasi
                    @else
                        Belum verifikasi
                    @endif
                </dd>
            </div>
        </dl>
    @else
        <div class="ic-kartu-anggota__bawah">
            <div class="ic-kartu-anggota__data">
                <p class="ic-kartu-anggota__nama">{{ $member->nama }}</p>
                <small>ID ANGGOTA: {{ $member->kode_member }}</small>
            </div>
        </div>
    @endif
</div>
