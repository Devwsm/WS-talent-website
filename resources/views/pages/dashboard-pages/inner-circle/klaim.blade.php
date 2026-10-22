@extends('template/dashboardLayout')
@section('content')
    @php
        $statusKlaim = config('inner_circle.status_klaim');
        $alurKlaim = config('inner_circle.alur_klaim');

        // Warna lencana status untuk tema gelap dashboard (kunci = `warna` di config status_klaim).
        $kelasLencana = [
            'kuning' => 'border-amber-300/30 bg-amber-300/10 text-amber-200',
            'biru' => 'border-sky-300/30 bg-sky-300/10 text-sky-200',
            'hijau' => 'border-emerald-300/30 bg-emerald-300/10 text-emerald-200',
            'merah' => 'border-red-400/30 bg-red-400/10 text-red-300',
        ];
        $statusMenunggu = ['diajukan', 'ditinjau', 'disetujui'];
        $jumlahMenunggu = count(array_filter($klaim, fn($k) => in_array($k->status, $statusMenunggu, true)));
    @endphp

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        {{-- Dekorasi abstrak, senada sama halaman lain --}}
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6 px-5 md:px-10 pt-8 pb-28 text-white">

            @include('components.dashboard.inner-circle.tabs', [
                'aktif' => 'klaim',
                'judul' => 'Klaim hadiah',
                'deskripsi' => 'Tinjau pengajuan hadiah anggota dan gerakkan statusnya sampai selesai.',
            ])

            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-inbox-fill text-white/50" aria-hidden="true"></i>
                    Antrean pengajuan ({{ count($klaim) }})
                </h2>
                <p class="text-xs text-white/50" role="status" aria-live="polite">
                    Menunggu tindakan: <b id="hitung-menunggu" class="text-white">{{ $jumlahMenunggu }}</b>
                </p>
            </div>

            <p class="text-xs text-white/40 -mt-3">
                Alur status: Diajukan → Ditinjau → Disetujui → Selesai. Pengajuan juga bisa ditolak selama belum selesai.
            </p>

            @if (empty($klaim))
                @include('components/dashboard/card/empty-state', [
                    'message' => 'Belum ada pengajuan hadiah.',
                ])
            @else
                <ul class="flex flex-col gap-3" id="antrean-klaim">
                    @foreach ($klaim as $k)
                        @php
                            $status = $statusKlaim[$k->status];
                            $aksi = $alurKlaim[$k->status] ?? null;
                        @endphp
                        <li class="rounded-3xl border border-white/10 bg-white/3 p-4 md:p-5 transition-colors hover:border-white/20"
                            data-klaim data-status="{{ $k->status }}"
                            data-judul="{{ $k->nama_member }} · {{ $k->judul }}">
                            <div class="flex flex-col md:flex-row md:items-center gap-4">
                                <div class="flex items-start gap-3 min-w-0 flex-1">
                                    {{-- Bulatan ikon hadiah: gaya yang sama dengan daftar klaim anggota --}}
                                    <span class="ic-app ic-klaim__bulat ic-warna-{{ $k->warna }} shrink-0">
                                        <x-inner-circle.ikon :nama="$k->ikon" kelas="ic-ikon--kecil" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold">
                                            {{ $k->nama_member }}
                                            <span class="text-white/40 font-normal">· {{ $k->kode_member }}</span>
                                        </p>
                                        <p class="text-sm text-white/70">{{ $k->target_stempel }} stempel ·
                                            {{ $k->judul }}
                                            @if ($k->opsi_dipilih)
                                                <span class="text-white/40">· pilihan: {{ $k->opsi_dipilih }}</span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-white/40 mt-0.5">Diajukan
                                            {{ $k->diajukan_pada->locale('id')->translatedFormat('j M Y') }}</p>
                                        <p class="text-xs text-white/60 mt-2 {{ $k->catatan_admin ? '' : 'hidden' }}"
                                            data-catatan>
                                            <i class="bi bi-chat-left-text" aria-hidden="true"></i>
                                            <span data-catatan-isi>{{ $k->catatan_admin }}</span>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 md:justify-end shrink-0">
                                    <span data-lencana
                                        class="rounded-full border px-3 py-1.5 text-xs font-semibold {{ $kelasLencana[$status['warna']] }}">{{ $status['label'] }}</span>

                                    <button type="button" data-aksi="lanjut"
                                        class="h-11 px-5 rounded-full bg-white text-black text-xs font-semibold hover:bg-white/80 transition-colors {{ $aksi ? '' : 'hidden' }}">
                                        {{ $aksi['aksi'] ?? '' }}
                                    </button>
                                    <button type="button" data-aksi="tolak"
                                        class="h-11 px-4 rounded-full border border-red-400/30 text-red-300 text-xs font-semibold hover:bg-red-400/10 transition-colors {{ $aksi ? '' : 'hidden' }}">
                                        Tolak
                                    </button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            const STATUS = @json($statusKlaim);
            const ALUR = @json($alurKlaim);
            const KELAS_LENCANA = @json($kelasLencana);
            const MENUNGGU = @json($statusMenunggu);

            // Perbarui satu baris sesuai status barunya: lencana, tombol alur, dan penghitung.
            function tampilkanStatus(baris, status) {
                baris.dataset.status = status;

                const lencana = baris.querySelector('[data-lencana]');
                const info = STATUS[status];
                lencana.textContent = info.label;
                lencana.className = 'rounded-full border px-3 py-1.5 text-xs font-semibold ' + KELAS_LENCANA[info.warna];

                const lanjut = baris.querySelector('[data-aksi="lanjut"]');
                const tolak = baris.querySelector('[data-aksi="tolak"]');
                const alur = ALUR[status];
                lanjut.classList.toggle('hidden', !alur);
                tolak.classList.toggle('hidden', !alur);
                if (alur) lanjut.textContent = alur.aksi;

                document.getElementById('hitung-menunggu').textContent = [...document.querySelectorAll('[data-klaim]')].filter((
                    b) => MENUNGGU.includes(b.dataset.status)).length;
            }

            document.addEventListener('click', async (e) => {
                const tombol = e.target.closest('[data-aksi]');
                if (!tombol) return;
                const baris = tombol.closest('[data-klaim]');
                const alur = ALUR[baris.dataset.status];
                if (!alur) return;

                if (tombol.dataset.aksi === 'lanjut') {
                    tampilkanStatus(baris, alur.berikutnya);
                    window.icSegera(`Status diubah ke ${STATUS[alur.berikutnya].label} (contoh, belum tersimpan).`);
                    return;
                }

                const hasil = await window.Swal.fire({
                    title: 'Tolak pengajuan ini?',
                    text: baris.dataset.judul,
                    input: 'textarea',
                    inputLabel: 'Alasan (tampil di profil anggota)',
                    inputAttributes: {
                        maxlength: 160
                    },
                    inputValidator: (nilai) => (nilai.trim() ? undefined : 'Alasan wajib diisi.'),
                    showCancelButton: true,
                    confirmButtonText: 'Ya, tolak',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                });
                if (!hasil.isConfirmed) return;

                baris.querySelector('[data-catatan-isi]').textContent = hasil.value.trim();
                baris.querySelector('[data-catatan]').classList.remove('hidden');
                tampilkanStatus(baris, 'ditolak');
                window.icSegera('Pengajuan ditolak (contoh, belum tersimpan).');
            });
        </script>
    @endpush
@endsection
