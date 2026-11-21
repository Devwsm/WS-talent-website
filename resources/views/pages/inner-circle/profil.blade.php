@extends('template.innerCircleLayout')

@section('title', 'Profil')

@section('content')
    <p class="ic-eyebrow">{{ ic_teks('profil.eyebrow') }}</p>
    <h1 class="ic-judul">Profil Saya<span>.</span></h1>

    {{-- Kartu member: nama (bisa diganti, boleh berspasi), Member ID, email, dan saldo poin. --}}
    <x-inner-circle.kartu-anggota :member="$member" detail />

    <section class="ic-profil-editor" aria-labelledby="judul-foto">
        <span class="ic-profil-foto" id="ic-foto-pratinjau"
            data-ic-foto-awal="{{ $member->avatar ? \Illuminate\Support\Facades\Storage::url($member->avatar) : '' }}"
            data-ic-emoji="{{ $member->avatar_emoji }}">
            @if ($member->avatar)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($member->avatar) }}" alt="Foto profilmu saat ini">
            @else
                <span aria-hidden="true">{{ $member->avatar_emoji }}</span>
            @endif
        </span>
        <div class="ic-profil-editor__isi">
            <h2 class="ic-panel__judul" id="judul-foto">{{ ic_teks('profil.foto.judul') }}</h2>
            <p class="ic-bantuan">{{ ic_html('profil.foto.deskripsi', ['maks' => $maksFotoMb]) }}</p>

            {{-- Dua form tanpa isi tampak; tombolnya di bawah memakai atribut form="..." supaya tetap satu baris. --}}
            <form method="POST" action="{{ route('ic.profil.foto') }}" enctype="multipart/form-data" id="ic-foto-form"
                data-ic-sekali>
                @csrf
                <input type="file" id="ic-foto-input" name="foto" accept="image/jpeg,image/png,image/webp"
                    data-ic-maks-mb="{{ $maksFotoMb }}" hidden>
            </form>
            @if ($member->avatar)
                <form method="POST" action="{{ route('ic.profil.foto.hapus') }}" id="ic-foto-hapus-form" data-ic-konfirmasi
                    data-ic-sekali data-ic-konfirmasi-judul="Hapus foto profil?" data-ic-konfirmasi-tombol="Ya, hapus"
                    data-judul="Kamu bisa mengunggah foto baru kapan saja.">
                    @csrf
                    @method('DELETE')
                </form>
            @endif

            <div class="ic-aksi-baris ic-aksi-baris--rapat">
                <button type="button" class="ic-tombol ic-tombol--kecil" id="ic-foto-pilih"><i class="bi bi-camera-fill"
                        aria-hidden="true"></i> Pilih foto</button>
                <button type="submit" form="ic-foto-form" class="ic-tombol ic-tombol--kecil" id="ic-foto-simpan"
                    disabled>Simpan foto</button>
                <button type="submit" form="ic-foto-hapus-form" class="ic-tombol ic-tombol--kecil ic-tombol--terang"
                    @disabled(!$member->avatar)>Hapus</button>
            </div>
            <p class="ic-bantuan ic-umpan" id="ic-foto-umpan" role="status" aria-live="polite"></p>
            @error('foto')
                <p class="ic-galat" role="alert">{{ $message }}</p>
            @enderror
        </div>
    </section>

    <div class="ic-metrik-grid">
        <div class="ic-metrik">
            <strong>{{ ic_angka($metrik->total_poin) }}</strong>
            <small>Saldo poin</small>
        </div>
        <div class="ic-metrik">
            <strong>{{ $metrik->tahap_terbuka }}</strong>
            <small>Hadiah bisa ditukar</small>
        </div>
        <div class="ic-metrik">
            <strong>{{ $metrik->jumlah_klaim }}</strong>
            <small>Penukaran hadiah</small>
        </div>
    </div>

    <h2 class="ic-judul-bagian" id="riwayat">{{ ic_teks('profil.riwayat.judul') }}</h2>
    <div class="ic-panel ic-daftar ic-daftar--rapat">
        @forelse ($riwayat as $a)
            <x-inner-circle.baris-aktivitas :aktivitas="$a" />
        @empty
            <p class="ic-bantuan">{{ ic_html('profil.riwayat.kosong') }}</p>
        @endforelse
    </div>

    <h2 class="ic-judul-bagian">{{ ic_teks('profil.klaim.judul') }}</h2>
    <div class="ic-panel ic-klaim-daftar">
        @forelse ($klaim as $k)
            @php $status = config('inner_circle.status_klaim.' . $k->status); @endphp
            <div class="ic-klaim-baris">
                <div>
                    <h3>{{ ic_angka($k->harga_poin) }} poin · {{ $k->judul }}</h3>
                    <p class="ic-bantuan">{{ $k->opsi ?? 'Penukaran hadiah' }} ·
                        {{ $k->diajukan_pada->locale('id')->translatedFormat('j M Y') }}</p>
                    @if ($k->catatan_admin)
                        <p class="ic-bantuan"><b>Catatan WSM:</b> {{ $k->catatan_admin }}</p>
                    @endif
                </div>
                <span class="ic-pil ic-pil--{{ $status['warna'] }}">{{ $status['label'] }}</span>
            </div>
        @empty
            <p class="ic-bantuan">{{ ic_html('profil.klaim.kosong') }}</p>
        @endforelse
    </div>

    @php
        $punyaSandi = $member->password !== null;
        $klaimMenunggu = app(\App\Services\InnerCircle\HapusAkunService::class)->adaKlaimMenunggu($member);
    @endphp

    <h2 class="ic-judul-bagian">{{ ic_teks('profil.akun.judul') }}</h2>
    <div class="ic-panel ic-akun">
        <p class="ic-bantuan">{{ ic_html('profil.akun.deskripsi') }}</p>
        @error('akun')
            <p class="ic-galat ic-galat--blok" role="alert">{{ $message }}</p>
        @enderror

        {{-- Pengaduan ada di sini (Akun dan privasi), bukan lagi kartu terpisah di atas. --}}
        <div class="ic-akun__bagian" id="pengaduan">
            <h3 class="ic-panel__judul">Ada kendala atau masukan?</h3>
            <p class="ic-bantuan">Ceritakan kendala soal check-in, poin, GL gratis, hadiah, atau akunmu. Tim WSM akan
                menindaklanjuti dan memberi kabar.</p>
            <a class="ic-tombol ic-tombol--kecil" href="{{ route('ic.pengaduan') }}">Kirim pengaduan <i
                    class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>

        <div class="ic-akun__bagian">
            <button type="button" class="ic-tombol ic-tombol--bahaya" data-ic-modal-buka="modal-hapus-akun">Hapus
                akunku</button>
        </div>
    </div>

    <x-inner-circle.modal id="modal-nama" judul="Ganti nama" eyebrow="Kartu member" :terbuka="$errors->has('nama')">
        <form method="POST" action="{{ route('ic.profil.nama') }}" data-ic-sekali novalidate>
            @csrf
            @method('PATCH')
            <label class="ic-label" for="ganti-nama">Nama</label>
            <input class="ic-input" type="text" id="ganti-nama" name="nama" value="{{ old('nama', $member->nama) }}"
                maxlength="100" autocomplete="name" required @error('nama') aria-invalid="true" @enderror>
            <p class="ic-bantuan">Boleh memakai spasi, mis. “Ancha Maulana”. Nama tampil di kartu member dan, dalam
                bentuk singkat, di kenangan dan komentarmu.</p>
            @error('nama')
                <p class="ic-galat" role="alert">{{ $message }}</p>
            @enderror
            <div class="ic-aksi-form">
                <button type="button" class="ic-tombol ic-tombol--terang" data-ic-modal-tutup>Batal</button>
                <button type="submit" class="ic-tombol">Simpan nama</button>
            </div>
        </form>
    </x-inner-circle.modal>

    <x-inner-circle.modal id="modal-hapus-akun" judul="Hapus akunmu?" eyebrow="Tidak bisa dibatalkan" :terbuka="$errors->hasAny(['konfirmasi', 'sandi'])">
        @if ($klaimMenunggu)
            <p class="ic-bantuan">Kamu masih punya pengajuan hadiah yang belum selesai. Akun bisa dihapus setelah
                pengajuannya selesai atau ditolak, supaya hadiahmu tidak hilang di tengah proses.</p>
            <div class="ic-aksi-form">
                <button type="button" class="ic-tombol ic-tombol--terang" data-ic-modal-tutup>Mengerti</button>
            </div>
        @else
            <form method="POST" action="{{ route('ic.akun.hapus') }}" data-ic-sekali novalidate>
                @csrf
                @method('DELETE')
                <p class="ic-bantuan">Yang ikut terhapus permanen: akun dan foto profilmu, riwayat check-in dan poin,
                    pengajuan hadiah, pengaduan, serta kenangan beserta fotonya dan tanda suka.</p>

                @if ($punyaSandi)
                    <label class="ic-label" for="hapus-sandi">Kata sandi saat ini</label>
                    <input class="ic-input" type="password" id="hapus-sandi" name="sandi"
                        autocomplete="current-password" required @error('sandi') aria-invalid="true" @enderror>
                    @error('sandi')
                        <p class="ic-galat" role="alert">{{ $message }}</p>
                    @enderror
                @endif

                <label class="ic-label" for="hapus-konfirmasi">Ketik <b>HAPUS</b> untuk melanjutkan</label>
                <input class="ic-input" type="text" id="hapus-konfirmasi" name="konfirmasi" autocomplete="off"
                    autocapitalize="characters" required @error('konfirmasi') aria-invalid="true" @enderror>
                @error('konfirmasi')
                    <p class="ic-galat" role="alert">{{ $message }}</p>
                @enderror

                <div class="ic-aksi-form">
                    <button type="button" class="ic-tombol ic-tombol--terang" data-ic-modal-tutup>Batal</button>
                    <button type="submit" class="ic-tombol ic-tombol--bahaya-penuh">Hapus akun permanen</button>
                </div>
            </form>
        @endif
    </x-inner-circle.modal>
@endsection
