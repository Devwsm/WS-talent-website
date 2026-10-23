@extends('template.innerCircleLayout')

@section('title', 'Profil')

@section('content')
    <p class="ic-eyebrow">Perjalanan Inner Circle-mu</p>
    <h1 class="ic-judul">Profil Saya<span>.</span></h1>
    <p class="ic-deskripsi">{{ $member->nama }} · {{ $member->kode_member }} · {{ $member->email }}</p>

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
            <h2 class="ic-panel__judul" id="judul-foto">Foto profilmu ✦</h2>
            <p class="ic-bantuan">Sesuaikan akun Inner Circle-mu. Pilih foto JPG, PNG, atau WebP (maksimal 5 MB).</p>
            <input type="file" id="ic-foto-input" accept="image/jpeg,image/png,image/webp" data-ic-maks-mb="5" hidden>
            <div class="ic-aksi-baris ic-aksi-baris--rapat">
                <button type="button" class="ic-tombol ic-tombol--kecil" id="ic-foto-pilih"><i class="bi bi-camera-fill"
                        aria-hidden="true"></i> Pilih foto</button>
                <button type="button" class="ic-tombol ic-tombol--kecil" id="ic-foto-simpan" disabled>Simpan foto</button>
                <button type="button" class="ic-tombol ic-tombol--kecil ic-tombol--terang"
                    data-ic-segera="Hapus foto segera aktif." @disabled(!$member->avatar)>Hapus</button>
            </div>
            <p class="ic-bantuan ic-umpan" id="ic-foto-umpan" role="status" aria-live="polite"></p>
        </div>
    </section>

    <div class="ic-metrik-grid">
        <div class="ic-metrik">
            <strong>{{ $metrik->total_stempel }}</strong>
            <small>Total stempel</small>
        </div>
        <div class="ic-metrik">
            <strong>{{ $metrik->tahap_terbuka }}</strong>
            <small>Tahapan hadiah terbuka</small>
        </div>
        <div class="ic-metrik">
            <strong>{{ $metrik->jumlah_klaim }}</strong>
            <small>Pengajuan hadiah</small>
        </div>
    </div>

    <h2 class="ic-judul-bagian" id="riwayat">Riwayat stempel</h2>
    <div class="ic-panel ic-daftar ic-daftar--rapat">
        @forelse ($riwayat as $a)
            <x-inner-circle.baris-aktivitas :aktivitas="$a" />
        @empty
            <p class="ic-bantuan">Belum ada stempel. Datang ke acara dan check-in untuk mulai mengumpulkan.</p>
        @endforelse
    </div>

    <h2 class="ic-judul-bagian">Pengajuan hadiah</h2>
    <div class="ic-panel ic-klaim-daftar">
        @forelse ($klaim as $k)
            @php $status = config('inner_circle.status_klaim.' . $k->status); @endphp
            <div class="ic-klaim-baris">
                <div>
                    <h3>{{ $k->target_stempel }} stempel · {{ $k->judul }}</h3>
                    <p class="ic-bantuan">{{ $k->opsi ?? 'Hadiah tahapan' }} ·
                        {{ $k->diajukan_pada->locale('id')->translatedFormat('j M Y') }}</p>
                    @if ($k->catatan_admin)
                        <p class="ic-bantuan"><b>Catatan WSM:</b> {{ $k->catatan_admin }}</p>
                    @endif
                </div>
                <span class="ic-pil ic-pil--{{ $status['warna'] }}">{{ $status['label'] }}</span>
            </div>
        @empty
            <p class="ic-bantuan">Belum ada pengajuan. Buka <a class="ic-tautan" href="{{ route('ic.hadiah') }}">Hadiah</a>
                untuk mengajukan tahapan yang sudah terbuka.</p>
        @endforelse
    </div>
@endsection
