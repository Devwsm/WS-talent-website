@extends('template.innerCircleLayout')

@section('title', $judul)

@section('content')
    <p class="ic-eyebrow">{{ $eyebrow }}</p>
    <h1 class="ic-judul">{{ $judul }}<span>.</span></h1>
    <p class="ic-deskripsi">Fondasi tampilan Inner Circle sudah siap. Layar ini diisi pada Fase 2; di bawah ini
        pratinjau komponen yang akan dipakai.</p>

    <h2 class="ic-judul-bagian">Kartu anggota dan stempel</h2>
    <div class="ic-grid ic-grid--2">
        <x-inner-circle.kartu-anggota :member="$member" />
        <x-inner-circle.bar-stempel :member="$member" :berikutnya="$berikutnya" />
    </div>

    <h2 class="ic-judul-bagian">Ubin hadiah</h2>
    <div class="ic-grid ic-grid--2">
        @foreach ($rewards as $reward)
            <x-inner-circle.ubin-hadiah :reward="$reward" :lebar="$loop->last" />
        @endforeach
    </div>

    <h2 class="ic-judul-bagian">Kartu event</h2>
    <div class="ic-daftar">
        @foreach ($events as $event)
            <x-inner-circle.kartu-event :event="$event" :href="'#'" :dipilih="$loop->first" />
        @endforeach
    </div>

    <h2 class="ic-judul-bagian">Aktivitas</h2>
    <div class="ic-panel ic-daftar ic-daftar--rapat">
        @foreach ($aktivitas as $a)
            <x-inner-circle.baris-aktivitas :aktivitas="$a" />
        @endforeach
    </div>

    <h2 class="ic-judul-bagian">Pengumuman</h2>
    <x-inner-circle.pengumuman-unggulan :pengumuman="$unggulan" />
    <div class="ic-daftar">
        @foreach ($pengumuman as $p)
            <x-inner-circle.item-pengumuman :pengumuman="$p" />
        @endforeach
    </div>

    <h2 class="ic-judul-bagian">Kenangan</h2>
    <div class="ic-grid ic-grid--2">
        @foreach ($kenangan as $k)
            <x-inner-circle.kartu-kenangan :kenangan="$k" />
        @endforeach
    </div>

    <p class="ic-aksi-baris">
        <button type="button" class="ic-tombol" data-ic-modal-buka="modal-contoh">Buka modal contoh</button>
    </p>
@endsection

@push('modal')
    <x-inner-circle.modal id="modal-contoh" judul="Contoh modal" eyebrow="Pratinjau komponen">
        <p class="ic-bantuan">Modal ini menutup dengan tombol silang, tombol Esc, atau klik di luar kotak.</p>
        <div class="ic-aksi-form">
            <button type="button" class="ic-tombol ic-tombol--terang" data-ic-modal-tutup>Tutup</button>
        </div>
    </x-inner-circle.modal>
@endpush
