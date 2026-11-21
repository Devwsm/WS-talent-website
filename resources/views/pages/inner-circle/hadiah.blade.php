@extends('template.innerCircleLayout')

@section('title', 'Rewards')

@php
    $semua = collect($rewards);
    $statusKlaim = config('inner_circle.status_klaim');
    // Hadiah hanya bisa ditukar setelah email terverifikasi (ClaimService menolaknya di server).
    $terverifikasi = $member->email_verified_at !== null;
@endphp

@section('content')
    <h1 class="ic-judul">Rewards<span>.</span></h1>
    <p class="ic-deskripsi">{{ ic_html('hadiah.deskripsi') }}</p>

    @unless ($terverifikasi)
        <section class="ic-panel ic-jarak-atas" aria-labelledby="verifikasi-judul">
            <h2 class="ic-panel__judul" id="verifikasi-judul">✉ Verifikasi emailmu dulu</h2>
            <p class="ic-bantuan">Hadiah baru bisa ditukar setelah emailmu terverifikasi. Kami mengirim tautan ke
                <b>{{ $member->email }}</b>; buka tautannya saat kamu sudah masuk. Tidak ada email? Cek folder spam, atau
                kirim ulang.
            </p>
            <form method="POST" action="{{ route('ic.verifikasi.kirim') }}" data-ic-sekali>
                @csrf
                <button type="submit" class="ic-tombol ic-tombol--kecil">Kirim ulang email verifikasi</button>
            </form>
        </section>
    @endunless

    {{-- Dompet poin: saldo yang bisa dipakai, poin yang sedang ditahan penukaran, dan poin yang sudah dipakai. --}}
    <div class="ic-metrik-grid ic-jarak-atas" id="dompet" aria-label="Dompet poin">
        <div class="ic-metrik">
            <strong>{{ ic_angka($dompet->saldo) }}</strong>
            <small>Saldo poin</small>
        </div>
        <div class="ic-metrik">
            <strong>{{ ic_angka($dompet->tertahan) }}</strong>
            <small>Poin ditahan</small>
        </div>
        <div class="ic-metrik">
            <strong>{{ ic_angka($dompet->dipakai) }}</strong>
            <small>Poin terpakai</small>
        </div>
    </div>

    <div class="ic-grid ic-grid--2 ic-jarak-atas">
        @foreach ($rewards as $reward)
            <x-inner-circle.ubin-hadiah :reward="$reward" :lebar="$loop->last && $loop->count % 2 === 1" href="#katalog" />
        @endforeach
    </div>

    <div class="ic-bagian-kepala" id="katalog">
        <h2 class="ic-judul-bagian">{{ ic_teks('hadiah.klaim.judul') }}</h2>
        <span class="ic-pil">{{ ic_angka($member->poin_total) }} poin terkumpul</span>
    </div>
    <div class="ic-panel ic-klaim-daftar">
        @foreach ($rewards as $reward)
            <div class="ic-klaim">
                <span class="ic-klaim__bulat ic-warna-{{ $reward->warna }}">
                    <x-inner-circle.ikon :nama="$reward->ikon" kelas="ic-ikon--kecil" />
                </span>
                <div>
                    <h3>{{ ic_angka($reward->harga_poin) }} poin · {{ $reward->judul }}</h3>
                    <p class="ic-bantuan">{{ $reward->deskripsi }}</p>

                    @if ($reward->stok !== null && $reward->stok > 0)
                        <p class="ic-bantuan">Sisa stok: <b>{{ ic_angka($reward->stok) }}</b></p>
                    @endif

                    @if ($reward->status_member === 'terkunci')
                        {{-- Progres saldo menuju harga hadiah ("Poin belum cukup, lihat progres"). --}}
                        <div class="ic-progres" role="progressbar" aria-label="Progres poin untuk {{ $reward->judul }}"
                            aria-valuemin="0" aria-valuemax="{{ $reward->harga_poin }}"
                            aria-valuenow="{{ min($member->poin_total, $reward->harga_poin) }}">
                            <span style="width: {{ $reward->persen }}%"></span>
                        </div>
                        <p class="ic-bantuan">Poin belum cukup: kurang <b>{{ ic_angka($reward->sisa_poin) }}</b> poin lagi.
                        </p>
                    @elseif ($reward->di_tunggu && $reward->status_member === 'terbuka')
                        <p class="ic-bantuan"><b>Stok tersedia lagi.</b> Kamu bisa menukarnya sekarang.</p>
                    @endif
                </div>

                @if ($reward->status_member === 'habis')
                    <div>
                        <span class="ic-pil ic-pil--redup">Stok habis</span>
                        @if ($reward->di_tunggu)
                            <form method="POST" action="{{ route('ic.hadiah.tunggu.keluar', $reward->id_reward) }}"
                                data-ic-sekali>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ic-tombol ic-tombol--kecil ic-tombol--terang">Keluar daftar
                                    tunggu</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('ic.hadiah.tunggu', $reward->id_reward) }}"
                                data-ic-sekali>
                                @csrf
                                <button type="submit" class="ic-tombol ic-tombol--kecil">Masuk daftar tunggu</button>
                            </form>
                        @endif
                    </div>
                @elseif ($reward->status_member === 'terbuka' && !$terverifikasi)
                    <span class="ic-pil ic-pil--redup"><i class="bi bi-envelope-fill" aria-hidden="true"></i> Verifikasi
                        email dulu</span>
                @elseif ($reward->status_member === 'terbuka')
                    @if ($reward->opsi)
                        {{-- Hadiah dengan pilihan A/B: buka modal pilihan (bukan prompt() seperti di prototype). --}}
                        <button type="button" class="ic-tombol ic-tombol--kecil"
                            data-ic-modal-buka="modal-opsi-{{ $reward->id_reward }}">Tukar <i class="bi bi-arrow-right"
                                aria-hidden="true"></i></button>
                    @else
                        {{-- Konfirmasi lewat JS (data-ic-konfirmasi); tanpa JS form tetap terkirim dan dicek di server. --}}
                        <form method="POST" action="{{ route('ic.hadiah.tukar', $reward->id_reward) }}" data-ic-konfirmasi
                            data-ic-sekali data-ic-konfirmasi-judul="Tukar poin dengan hadiah ini?"
                            data-ic-konfirmasi-tombol="Ya, tukar"
                            data-judul="{{ ic_angka($reward->harga_poin) }} poin · {{ $reward->judul }}">
                            @csrf
                            <button type="submit" class="ic-tombol ic-tombol--kecil">Tukar <i class="bi bi-arrow-right"
                                    aria-hidden="true"></i></button>
                        </form>
                    @endif
                @else
                    <span class="ic-pil ic-pil--redup"><i class="bi bi-lock-fill" aria-hidden="true"></i> Terkunci</span>
                @endif
            </div>
        @endforeach
    </div>

    <h2 class="ic-judul-bagian" id="klaim">Penukaran saya</h2>
    <div class="ic-panel ic-klaim-daftar">
        @forelse ($penukaran as $k)
            @php $status = $statusKlaim[$k->status]; @endphp
            <div class="ic-klaim-baris">
                <div>
                    <h3>{{ ic_angka($k->harga_poin) }} poin · {{ $k->judul }}</h3>
                    <p class="ic-bantuan">
                        {{ $k->opsi_dipilih ? $k->opsi_dipilih . ' · ' : '' }}{{ $k->diajukan_pada->locale('id')->translatedFormat('j M Y') }}
                    </p>
                    @if ($k->memakaiPoin())
                        <p class="ic-bantuan">
                            @if (in_array($k->status, ['ditolak', 'dibatalkan'], true))
                                {{ ic_angka($k->poin_ditahan) }} poin sudah kembali ke saldomu.
                            @elseif ($k->status === 'selesai')
                                {{ ic_angka($k->poin_ditahan) }} poin terpakai. Hadiah diterima.
                            @else
                                {{ ic_angka($k->poin_ditahan) }} poin ditahan sampai pemeriksaan selesai.
                            @endif
                        </p>
                    @endif
                    @if ($k->catatan_admin)
                        <p class="ic-bantuan"><b>Catatan WSM:</b> {{ $k->catatan_admin }}</p>
                    @endif
                </div>
                <div>
                    <span class="ic-pil ic-pil--{{ $status['warna'] }}">{{ $status['label'] }}</span>
                    @if ($k->status === 'diajukan')
                        <form method="POST" action="{{ route('ic.penukaran.batal', $k->id_claim) }}" data-ic-konfirmasi
                            data-ic-sekali data-ic-konfirmasi-judul="Batalkan penukaran?"
                            data-ic-konfirmasi-tombol="Ya, batalkan" data-judul="Poinmu akan kembali ke saldo.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="ic-tombol ic-tombol--kecil ic-tombol--terang">Batalkan</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="ic-bantuan">Belum ada penukaran. Pilih hadiah di katalog saat poinmu cukup.</p>
        @endforelse
    </div>

    <section class="ic-panel ic-jarak-atas" aria-labelledby="cara-kerja">
        <h2 class="ic-panel__judul" id="cara-kerja">{{ ic_teks('hadiah.cara.judul') }}</h2>
        <p class="ic-bantuan">{{ ic_html('hadiah.cara.deskripsi') }}</p>
    </section>
@endsection

@push('modal')
    @foreach ($semua->whereNotNull('opsi') as $reward)
        <x-inner-circle.modal :id="'modal-opsi-' . $reward->id_reward" :judul="$reward->judul" :eyebrow="ic_angka($reward->harga_poin) . ' poin'">
            <form method="POST" action="{{ route('ic.hadiah.tukar', $reward->id_reward) }}" data-ic-sekali>
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
                    <button type="submit" class="ic-tombol">Tukar hadiah <i class="bi bi-arrow-right"
                            aria-hidden="true"></i></button>
                </div>
            </form>
        </x-inner-circle.modal>
    @endforeach
@endpush
