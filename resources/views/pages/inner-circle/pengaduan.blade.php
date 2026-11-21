@extends('template.innerCircleLayout')

@section('title', 'Pengaduan')

@php
    $kategori = config('inner_circle.pengaduan.kategori');
    $statusPengaduan = config('inner_circle.pengaduan.status');
    $maks = (int) config('inner_circle.pengaduan.isi_maks', 1000);
    $batasHari = (int) config('inner_circle.pengaduan.batas_per_hari', 3);
@endphp

@section('content')
    <p class="ic-eyebrow"><a class="ic-tautan" href="{{ route('ic.profil') }}"><i class="bi bi-arrow-left"
                aria-hidden="true"></i> Profil</a></p>
    <h1 class="ic-judul">Pengaduan<span>.</span></h1>
    <p class="ic-deskripsi">Ada kendala soal check-in, poin, GL gratis, hadiah, atau akunmu? Ceritakan di sini. Tim WSM
        akan menindaklanjuti dan memberi kabar lewat halaman ini dan email.</p>

    <section class="ic-panel ic-jarak-atas" id="form-pengaduan" aria-labelledby="judul-form-pengaduan">
        <h2 class="ic-panel__judul" id="judul-form-pengaduan">Kirim pengaduan</h2>

        @if ($sisaHariIni < 1)
            <p class="ic-bantuan">Batas pengaduan hari ini sudah tercapai ({{ $batasHari }} per hari). Coba lagi besok.
            </p>
        @else
            <form method="POST" action="{{ route('ic.pengaduan.kirim') }}" data-ic-sekali novalidate>
                @csrf

                <label class="ic-label" for="pengaduan-kategori">Soal apa?</label>
                <select class="ic-input" id="pengaduan-kategori" name="kategori" required
                    @error('kategori') aria-invalid="true" @enderror>
                    <option value="" disabled @selected(old('kategori') === null)>Pilih kategori</option>
                    @foreach ($kategori as $kunci => $label)
                        <option value="{{ $kunci }}" @selected(old('kategori') === $kunci)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('kategori')
                    <p class="ic-galat" role="alert">{{ $message }}</p>
                @enderror

                <label class="ic-label" for="pengaduan-acara">Acara terkait (boleh dikosongkan)</label>
                <select class="ic-input" id="pengaduan-acara" name="event_id">
                    <option value="">Tidak terkait acara</option>
                    @foreach ($acara as $a)
                        <option value="{{ $a->id_event }}" @selected((string) old('event_id') === (string) $a->id_event)>
                            {{ $a->tanggal->locale('id')->translatedFormat('j M Y') }} · {{ $a->venue }},
                            {{ $a->kota }}
                        </option>
                    @endforeach
                </select>
                @error('event_id')
                    <p class="ic-galat" role="alert">{{ $message }}</p>
                @enderror

                <label class="ic-label" for="pengaduan-isi">Ceritakan kendalamu</label>
                <textarea class="ic-input ic-input--area" id="pengaduan-isi" name="isi" rows="5"
                    maxlength="{{ $maks }}" required @error('isi') aria-invalid="true" @enderror>{{ old('isi') }}</textarea>
                <p class="ic-bantuan">Maksimal {{ ic_angka($maks) }} karakter, tanpa lampiran. Jangan menulis kata sandi.
                    Sisa pengaduan hari ini: {{ $sisaHariIni }}.</p>
                @error('isi')
                    <p class="ic-galat" role="alert">{{ $message }}</p>
                @enderror

                <div class="ic-aksi-form">
                    <button type="submit" class="ic-tombol">Kirim pengaduan <i class="bi bi-arrow-right"
                            aria-hidden="true"></i></button>
                </div>
            </form>
        @endif
    </section>

    <h2 class="ic-judul-bagian">Pengaduanku</h2>
    <div class="ic-panel ic-klaim-daftar">
        @forelse ($daftar as $p)
            @php $status = $statusPengaduan[$p->status]; @endphp
            <div class="ic-klaim-baris">
                <div>
                    <h3>{{ $kategori[$p->kategori] ?? $p->kategori }}</h3>
                    <p class="ic-bantuan">{{ $p->created_at->locale('id')->translatedFormat('j M Y') }}
                        @if ($p->event)
                            · {{ $p->event->venue }}, {{ $p->event->kota }}
                        @endif
                    </p>
                    <p class="ic-bantuan">{{ $p->isi }}</p>
                    @if ($p->catatan_admin)
                        <p class="ic-bantuan"><b>Catatan WSM:</b> {{ $p->catatan_admin }}</p>
                    @endif
                </div>
                <span class="ic-pil ic-pil--{{ $status['warna'] }}">{{ $status['label'] }}</span>
            </div>
        @empty
            <p class="ic-bantuan">Belum ada pengaduan.</p>
        @endforelse
    </div>
@endsection
