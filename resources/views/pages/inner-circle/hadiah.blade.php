@extends('template.innerCircleLayout')

@section('title', 'Hadiah')

@php
    $semua = collect($rewards);
    $statusKlaim = config('inner_circle.status_klaim');
@endphp

@section('content')
    <p class="ic-eyebrow">Kumpulkan • Buka • Rasakan</p>
    <h1 class="ic-judul">Hadiah<span>.</span></h1>
    <p class="ic-deskripsi">Setiap stempel membuatmu lebih dekat dengan Whisnu Santika. Buka merchandise eksklusif,
        pengalaman personal, dan akses spesial.</p>

    <div class="ic-grid ic-grid--2 ic-jarak-atas">
        @foreach ($rewards as $reward)
            <x-inner-circle.ubin-hadiah :reward="$reward" :lebar="$loop->last" href="#klaim" />
        @endforeach
    </div>

    <div class="ic-bagian-kepala" id="klaim">
        <h2 class="ic-judul-bagian">Klaim hadiahmu ↗</h2>
        <span class="ic-pil">{{ $member->stempel_total }} stempel terkumpul</span>
    </div>
    <div class="ic-panel ic-klaim-daftar">
        @foreach ($rewards as $reward)
            <div class="ic-klaim">
                <span class="ic-klaim__bulat ic-warna-{{ $reward->warna }}">
                    <x-inner-circle.ikon :nama="$reward->ikon" kelas="ic-ikon--kecil" />
                </span>
                <div>
                    <h3>{{ $reward->target_stempel }} stempel · {{ $reward->judul }}</h3>
                    <p class="ic-bantuan">{{ $reward->deskripsi }}</p>
                    @if ($reward->catatan_admin)
                        <p class="ic-bantuan"><b>Catatan WSM:</b> {{ $reward->catatan_admin }}</p>
                    @endif
                </div>

                @if ($reward->status_member === 'terbuka')
                    @if ($reward->opsi)
                        {{-- Hadiah dengan pilihan A/B: buka modal pilihan (bukan prompt() seperti di prototype). --}}
                        <button type="button" class="ic-tombol ic-tombol--kecil"
                            data-ic-modal-buka="modal-opsi-{{ $reward->target_stempel }}">Klaim <i class="bi bi-arrow-right"
                                aria-hidden="true"></i></button>
                    @else
                        {{-- Konfirmasi lewat JS (data-ic-konfirmasi); tanpa JS form tetap terkirim dan dicek di server. --}}
                        <form method="POST" action="{{ route('ic.hadiah.klaim', $reward->target_stempel) }}"
                            data-ic-konfirmasi data-ic-sekali
                            data-judul="{{ $reward->target_stempel }} stempel · {{ $reward->judul }}">
                            @csrf
                            <button type="submit" class="ic-tombol ic-tombol--kecil">Klaim <i class="bi bi-arrow-right"
                                    aria-hidden="true"></i></button>
                        </form>
                    @endif
                @elseif ($reward->status_member === 'terkunci')
                    <span class="ic-pil ic-pil--redup"><i class="bi bi-lock-fill" aria-hidden="true"></i> Terkunci</span>
                @else
                    @php $status = $statusKlaim[$reward->status_member]; @endphp
                    <span class="ic-pil ic-pil--{{ $status['warna'] }}">{{ $status['label'] }}</span>
                @endif
            </div>
        @endforeach
    </div>

    <section class="ic-panel ic-jarak-atas" aria-labelledby="cara-kerja">
        <h2 class="ic-panel__judul" id="cara-kerja">✦ Cara kerja hadiah</h2>
        <p class="ic-bantuan">Stempel bersifat kumulatif dan tidak berkurang setelah klaim. Setiap tahapan hanya dapat
            diklaim satu kali. Benefit pengalaman bergantung pada kapasitas, jadwal artis, dan izin promotor atau
            venue.</p>
    </section>
@endsection

@push('modal')
    @foreach ($semua->whereNotNull('opsi') as $reward)
        <x-inner-circle.modal :id="'modal-opsi-' . $reward->target_stempel" :judul="$reward->judul" :eyebrow="$reward->target_stempel . ' stempel'">
            <form method="POST" action="{{ route('ic.hadiah.klaim', $reward->target_stempel) }}" data-ic-sekali>
                @csrf
                <fieldset class="ic-pilihan-daftar">
                    <legend class="ic-bantuan">Pilih salah satu hadiah:</legend>
                    @foreach ($reward->opsi as $opsi)
                        <label class="ic-pilihan">
                            <input type="radio" name="opsi" value="{{ $opsi }}" required>
                            <span class="ic-pilihan__isi">
                                <x-inner-circle.ikon :nama="$loop->first ? 'camera' : 'love-letter'" kelas="ic-ikon--event" />
                                {{ $opsi }}
                            </span>
                        </label>
                    @endforeach
                </fieldset>
                <div class="ic-aksi-form">
                    <button type="button" class="ic-tombol ic-tombol--terang" data-ic-modal-tutup>Batal</button>
                    <button type="submit" class="ic-tombol">Ajukan hadiah <i class="bi bi-arrow-right"
                            aria-hidden="true"></i></button>
                </div>
            </form>
        </x-inner-circle.modal>
    @endforeach
@endpush
