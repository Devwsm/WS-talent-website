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

        $pilihanFilter = [
            'menunggu' => 'Menunggu',
            'semua' => 'Semua',
            'selesai' => 'Selesai',
            'ditolak' => 'Ditolak',
        ];
        $pesanKosong = [
            'menunggu' => 'Tidak ada pengajuan yang menunggu tindakan.',
            'semua' => 'Belum ada pengajuan hadiah.',
            'selesai' => 'Belum ada pengajuan yang selesai.',
            'ditolak' => 'Belum ada pengajuan yang ditolak.',
        ];
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

            @include('components/errors')
            @include('components/success')

            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-inbox-fill text-white/50" aria-hidden="true"></i>
                    Antrean pengajuan
                </h2>

                {{-- Filter status lewat ?status= (bisa dibagikan, jalan tanpa JavaScript) --}}
                <nav aria-label="Saring pengajuan" class="-mx-5 md:mx-0 overflow-x-auto md:overflow-visible">
                    <ul class="flex gap-2 px-5 md:px-0 w-max md:w-auto">
                        @foreach ($pilihanFilter as $kunci => $label)
                            @php $dipilih = $kunci === $filterStatus; @endphp
                            <li>
                                <a href="{{ route('ic.admin.klaim', ['status' => $kunci]) }}"
                                    @if ($dipilih) aria-current="page" @endif
                                    class="inline-flex items-center gap-2 h-11 px-4 rounded-full border text-sm font-semibold whitespace-nowrap transition-colors
                                    {{ $dipilih ? 'bg-white text-black border-white' : 'border-white/10 text-white/60 hover:bg-white/10 hover:text-white' }}">
                                    {{ $label }}
                                    <span
                                        class="text-xs {{ $dipilih ? 'text-black/60' : 'text-white/40' }}">{{ $hitung[$kunci] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </div>

            <p class="text-xs text-white/40 -mt-3">
                Alur status: Diajukan → Ditinjau → Disetujui → Selesai. Pengajuan bisa ditolak selama belum selesai
                (alasan wajib dan tampil di profil anggota). Catatan, mis. kode voucher, juga tampil di profil anggota.
            </p>

            @if ($klaim->isEmpty())
                @include('components/dashboard/card/empty-state', [
                    'message' => $pesanKosong[$filterStatus],
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
                            data-judul="{{ $k->nama_member }} · {{ $k->judul }}"
                            data-catatan-awal="{{ $k->catatan_admin }}">
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
                                        @if ($k->catatan_admin)
                                            <p class="text-xs text-white/60 mt-2">
                                                <i class="bi bi-chat-left-text" aria-hidden="true"></i>
                                                {{ $k->catatan_admin }}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 md:justify-end shrink-0">
                                    <span
                                        class="rounded-full border px-3 py-1.5 text-xs font-semibold {{ $kelasLencana[$status['warna']] }}">{{ $status['label'] }}</span>

                                    @if ($aksi)
                                        <button type="button" data-aksi="lanjut" data-label="{{ $aksi['aksi'] }}"
                                            class="h-11 px-5 rounded-full bg-white text-black text-xs font-semibold hover:bg-white/80 transition-colors">
                                            {{ $aksi['aksi'] }}
                                        </button>
                                        <button type="button" data-aksi="tolak"
                                            class="h-11 px-4 rounded-full border border-red-400/30 text-red-300 text-xs font-semibold hover:bg-red-400/10 transition-colors">
                                            Tolak
                                        </button>
                                    @endif
                                    <button type="button" data-aksi="catatan"
                                        class="h-11 px-4 rounded-full border border-white/15 text-white/70 text-xs font-semibold hover:bg-white/10 hover:text-white transition-colors">
                                        Catatan
                                    </button>
                                </div>
                            </div>

                            {{-- Riwayat perubahan status (siapa, kapan, catatan) --}}
                            <details class="mt-3 text-xs text-white/50">
                                <summary class="cursor-pointer select-none py-2 hover:text-white/80">
                                    Riwayat ({{ $k->logs->count() }})
                                </summary>
                                <ol class="flex flex-col gap-1.5 pb-1 pl-1">
                                    @foreach ($k->logs as $log)
                                        <li>
                                            <b class="text-white/70">{{ $statusKlaim[$log->status]['label'] }}</b>
                                            · {{ $log->created_at->locale('id')->translatedFormat('j M Y, H.i') }}
                                            · {{ $log->oleh ?? ($log->status === 'diajukan' ? 'anggota' : 'admin') }}
                                            @if ($log->catatan)
                                                <span class="text-white/40">— {{ $log->catatan }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            </details>

                            {{-- Dikirim lewat JavaScript setelah dialog dikonfirmasi. `dari` = status yang dilihat admin;
                                 server menolak bila statusnya sudah berubah (klik ganda atau admin lain). --}}
                            <form method="POST" action="{{ route('ic.admin.klaim.proses', $k->id_claim) }}"
                                data-form-klaim hidden>
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="aksi" value="">
                                <input type="hidden" name="dari" value="{{ $k->status }}">
                                <input type="hidden" name="catatan" value="">
                            </form>
                        </li>
                    @endforeach
                </ul>

                @include('components.dashboard.pagination', ['paginator' => $klaim])
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('click', async (e) => {
                const tombol = e.target.closest('[data-aksi]');
                if (!tombol) return;
                const baris = tombol.closest('[data-klaim]');
                const form = baris.querySelector('form[data-form-klaim]');
                const aksi = tombol.dataset.aksi;
                const catatanAwal = baris.dataset.catatanAwal || '';

                const dialog = {
                    lanjut: {
                        title: `${tombol.dataset.label} pengajuan ini?`,
                        inputLabel: 'Catatan untuk anggota (opsional, mis. kode voucher)',
                        inputValue: catatanAwal,
                        confirmButtonText: tombol.dataset.label,
                    },
                    tolak: {
                        title: 'Tolak pengajuan ini?',
                        inputLabel: 'Alasan (tampil di profil anggota)',
                        inputValidator: (nilai) => (nilai.trim() ? undefined : 'Alasan wajib diisi.'),
                        confirmButtonText: 'Ya, tolak',
                    },
                    catatan: {
                        title: 'Catatan pengajuan',
                        inputLabel: 'Tampil di profil anggota. Kosongkan untuk menghapus catatan.',
                        inputValue: catatanAwal,
                        confirmButtonText: 'Simpan catatan',
                    },
                } [aksi];
                if (!dialog) return;

                const hasil = await window.Swal.fire({
                    ...dialog,
                    text: baris.dataset.judul,
                    input: 'textarea',
                    inputAttributes: {
                        maxlength: 160
                    },
                    showCancelButton: true,
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                });
                if (!hasil.isConfirmed) return;

                form.elements.aksi.value = aksi;
                form.elements.catatan.value = (hasil.value || '').trim();
                // Kunci semua tombol baris ini supaya klik ganda tidak mengirim dua permintaan.
                baris.querySelectorAll('[data-aksi]').forEach((b) => (b.disabled = true));
                form.submit();
            });
        </script>
    @endpush
@endsection
