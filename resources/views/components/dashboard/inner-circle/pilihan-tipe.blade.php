{{-- Tipe acara di tab Konten: ubah nama, ikon, dan warna. Kunci tetap (dipakai logika, mis. acara privat tidak bisa check-in). --}}
<section id="pilihan-tipe" aria-labelledby="judul-pilihan-tipe" class="scroll-mt-8 flex flex-col gap-4">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
        <div class="min-w-0">
            <h2 id="judul-pilihan-tipe" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                <i class="bi bi-calendar-event text-white/50" aria-hidden="true"></i> Tipe acara
            </h2>
            <p class="text-xs text-white/40 mt-1">Nama, ikon, dan warna tipe acara di jadwal, filter, dan kartu check-in.
                Ketiga tipe tidak bisa ditambah atau dihapus karena dipakai aturan (acara privat tidak bisa check-in).
            </p>
        </div>
        <a href="{{ route('ic.checkin') }}" target="_blank" rel="noopener"
            class="{{ $kelasTombol }} self-start sm:self-auto shrink-0"><i class="bi bi-box-arrow-up-right"
                aria-hidden="true"></i> Lihat halaman</a>
    </div>

    <ul class="flex flex-col gap-3 min-w-0">
        @foreach ($tipeBaris as $b)
            @php $kode = 'pilihan_tipe_' . $b->kunci; @endphp
            <li class="rounded-2xl border border-white/10 bg-white/3 min-w-0">
                <div class="p-4 flex items-center gap-3 min-w-0">
                    <span aria-hidden="true" class="shrink-0 w-10 h-10 rounded-xl grid place-items-center text-lg"
                        style="background: {{ $warnaHex[$b->warna] ?? '#7db3d6' }}">{{ config('inner_circle.ikon.' . $b->ikon, '✦') }}</span>
                    <div class="min-w-0">
                        <p class="font-semibold wrap-break-word">{{ $b->label }}</p>
                        <p class="text-xs text-white/40">Kunci: <code class="text-white/60">{{ $b->kunci }}</code>
                        </p>
                    </div>
                </div>
                <details class="border-t border-white/10" @if ($errors->getBag($kode)->any()) open @endif>
                    <summary
                        class="min-h-11 px-4 flex items-center gap-2 cursor-pointer text-xs font-semibold uppercase tracking-widest text-white/60 hover:text-white">
                        <i class="bi bi-pencil-square" aria-hidden="true"></i> Ubah
                    </summary>
                    <div class="px-4 pb-4 pt-2 flex flex-col gap-3">
                        @include('components.dashboard.inner-circle.form-pilihan', [
                            'kode' => $kode,
                            'aksi' => route('ic.admin.konten.pilihan.tipe.ubah', $b->kunci),
                            'metode' => 'PUT',
                            'baris' => $b,
                            'ikonList' => $ikonTipe,
                            'warnaList' => $warnaKartu,
                            'denganSampul' => false,
                            'labelTombol' => 'Simpan',
                        ])
                        <form method="POST" action="{{ route('ic.admin.konten.pilihan.tipe.bawaan', $b->kunci) }}">
                            @csrf
                            <button type="submit"
                                class="min-h-11 text-xs text-white/50 underline underline-offset-4 hover:text-white">Kembalikan
                                bawaan</button>
                        </form>
                    </div>
                </details>
            </li>
        @endforeach
    </ul>
</section>
