@extends('template/dashboardLayout')
@section('content')
    @php
        $kategoriLabel = config('log_admin.kategori');
        $kelasKategori = [
            'akses' => 'border-sky-300/30 bg-sky-300/10 text-sky-200',
            'konten' => 'border-violet-300/30 bg-violet-300/10 text-violet-200',
            'inner-circle' => 'border-pink-300/30 bg-pink-300/10 text-pink-200',
        ];
        $filterError = ['terbuka' => 'Terbuka', 'selesai' => 'Selesai', 'semua' => 'Semua'];
    @endphp

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6 px-5 md:px-10 pt-8 pb-28 text-white">

            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-white/40 mb-1">Dashboard</p>
                <h1 class="text-2xl lg:text-3xl font-bold uppercase">Log</h1>
                <p class="text-white/50 mt-1">Error yang terjadi di website, dan jejak aktivitas admin di dashboard.</p>
            </div>

            {{-- Tab --}}
            <nav aria-label="Bagian Log">
                <ul class="flex flex-wrap gap-2">
                    @foreach (['error' => ['Error', 'bi-bug-fill'], 'aktivitas' => ['Aktivitas admin', 'bi-clock-history']] as $kunci => [$label, $ikon])
                        @php $dipilih = $kunci === $tab; @endphp
                        <li>
                            <a href="{{ route('log.index', ['tab' => $kunci]) }}"
                                @if ($dipilih) aria-current="page" @endif
                                class="inline-flex items-center gap-2 h-11 px-4 rounded-full border text-sm font-semibold whitespace-nowrap transition-colors
                                {{ $dipilih ? 'bg-white text-black border-white' : 'border-white/10 text-white/60 hover:bg-white/10 hover:text-white' }}">
                                <i class="bi {{ $ikon }}" aria-hidden="true"></i>
                                {{ $label }}
                                @if ($kunci === 'error' && $jumlahError['terbuka'] > 0)
                                    <span
                                        class="min-w-5 h-5 px-1.5 inline-flex items-center justify-center rounded-full text-[11px] font-bold {{ $dipilih ? 'bg-black text-white' : 'bg-red-950 text-white' }}">{{ $jumlahError['terbuka'] }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            @include('components/errors')
            @include('components/success')

            @if ($tab === 'error')
                {{-- Status email peringatan --}}
                @if ($emailPeringatan)
                    <p class="text-xs text-white/40 flex items-start gap-2">
                        <i class="bi bi-envelope-check mt-0.5" aria-hidden="true"></i>
                        <span>Error baru dikabari lewat email ke <b class="text-white/60">{{ $emailPeringatan }}</b>
                            (satu email per jenis error per jam).</span>
                    </p>
                @else
                    <div
                        class="flex items-start gap-3 rounded-2xl border border-amber-300/20 bg-amber-300/5 px-4 py-3 text-sm text-amber-100/80">
                        <i class="bi bi-info-circle mt-0.5 shrink-0" aria-hidden="true"></i>
                        <p>Email peringatan belum aktif. Isi <code>LOG_ALERT_EMAIL</code> di <code>.env</code> server agar
                            error baru dikabari lewat email.</p>
                    </div>
                @endif

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <nav aria-label="Saring error">
                        <ul class="flex flex-wrap gap-2">
                            @foreach ($filterError as $kunci => $label)
                                @php $dipilih = $kunci === $filterStatus; @endphp
                                <li>
                                    <a href="{{ route('log.index', ['tab' => 'error', 'status' => $kunci]) }}"
                                        @if ($dipilih) aria-current="page" @endif
                                        class="inline-flex items-center gap-2 h-11 px-4 rounded-full border text-sm font-semibold whitespace-nowrap transition-colors
                                        {{ $dipilih ? 'bg-white text-black border-white' : 'border-white/10 text-white/60 hover:bg-white/10 hover:text-white' }}">
                                        {{ $label }}
                                        <span
                                            class="text-xs {{ $dipilih ? 'text-black/60' : 'text-white/40' }}">{{ $jumlahError[$kunci] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>

                    @if ($jumlahError['selesai'] > 0)
                        <form method="POST" action="{{ route('log.error.bersihkan') }}" data-swal-confirm
                            data-confirm-message="{{ $jumlahError['selesai'] }} catatan error yang sudah selesai akan dihapus permanen.">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="h-11 px-4 rounded-full border border-white/15 text-white/60 text-sm font-semibold hover:bg-white/10 hover:text-white transition-colors">
                                <i class="bi bi-trash3" aria-hidden="true"></i> Hapus yang selesai
                            </button>
                        </form>
                    @endif
                </div>

                @if ($daftar->isEmpty())
                    @include('components/dashboard/card/empty-state', [
                        'message' =>
                            $filterStatus === 'terbuka'
                                ? 'Tidak ada error yang terbuka. 🎉'
                                : 'Belum ada catatan error.',
                    ])
                @else
                    <ul class="flex flex-col gap-3">
                        @foreach ($daftar as $e)
                            <li
                                class="rounded-3xl border p-4 md:p-5 {{ $e->selesai ? 'border-white/10 bg-white/2' : 'border-red-400/30 bg-red-400/5' }}">
                                <div class="flex flex-col md:flex-row md:items-start gap-4">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <p class="font-semibold">{{ class_basename($e->kelas) }}</p>
                                            <span
                                                class="rounded-full border border-white/15 px-2 py-0.5 text-[11px] font-semibold text-white/70">{{ number_format($e->jumlah, 0, ',', '.') }}x</span>
                                            @if ($e->selesai)
                                                <span
                                                    class="rounded-full border border-emerald-300/30 bg-emerald-300/10 px-2 py-0.5 text-[11px] font-semibold text-emerald-200">Selesai</span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-white/70 wrap-break-word">
                                            {{ \Illuminate\Support\Str::limit($e->pesan, 300) }}</p>
                                        <p class="text-xs text-white/40 mt-1 break-all">{{ $e->lokasi }}</p>
                                        <p class="text-xs text-white/40 mt-0.5">Terakhir
                                            {{ $e->terakhir_pada->locale('id')->translatedFormat('j M Y, H.i') }}
                                            · pertama
                                            {{ $e->pertama_pada?->locale('id')->translatedFormat('j M Y') ?? '—' }}</p>
                                    </div>

                                    <form method="POST" action="{{ route('log.error.ubah', $e->id_log_error) }}"
                                        class="shrink-0">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="aksi"
                                            value="{{ $e->selesai ? 'buka' : 'selesai' }}">
                                        <button type="submit"
                                            class="h-11 px-5 rounded-full text-xs font-semibold transition-colors {{ $e->selesai ? 'border border-white/15 text-white/70 hover:bg-white/10' : 'bg-white text-black hover:bg-white/80' }}">
                                            {{ $e->selesai ? 'Buka lagi' : 'Tandai selesai' }}
                                        </button>
                                    </form>
                                </div>

                                <details class="mt-3 text-xs text-white/50">
                                    <summary class="cursor-pointer select-none py-2 hover:text-white/80">Detail</summary>
                                    <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 pb-2">
                                        <dt class="text-white/40">Jenis</dt>
                                        <dd class="break-all">{{ $e->kelas }}</dd>
                                        <dt class="text-white/40">Alamat</dt>
                                        <dd class="break-all">{{ $e->metode }} {{ $e->url ?? '—' }}</dd>
                                        <dt class="text-white/40">Pengguna</dt>
                                        <dd>{{ $e->pengguna ?? 'tamu' }}</dd>
                                        <dt class="text-white/40">IP</dt>
                                        <dd>{{ $e->ip ?? '—' }}</dd>
                                    </dl>
                                    @if ($e->jejak)
                                        <pre class="overflow-x-auto rounded-xl bg-black/40 p-3 text-[11px] leading-relaxed text-white/60">{{ $e->jejak }}</pre>
                                    @endif
                                    <p class="mt-2 text-white/30">Pesan lengkap dan jejak penuh ada di storage/logs di
                                        server.</p>
                                </details>
                            </li>
                        @endforeach
                    </ul>

                    @include('components.dashboard.pagination', ['paginator' => $daftar])
                @endif
            @else
                <div class="flex flex-wrap items-center gap-2">
                    @foreach (['semua' => 'Semua'] + $kategoriLabel as $kunci => $label)
                        @php $dipilih = $kunci === $filterKategori; @endphp
                        <a href="{{ route('log.index', array_filter(['tab' => 'aktivitas', 'kategori' => $kunci === 'semua' ? null : $kunci, 'status' => $hanyaGagal ? 'gagal' : null])) }}"
                            @if ($dipilih) aria-current="page" @endif
                            class="inline-flex items-center h-11 px-4 rounded-full border text-sm font-semibold whitespace-nowrap transition-colors
                            {{ $dipilih ? 'bg-white text-black border-white' : 'border-white/10 text-white/60 hover:bg-white/10 hover:text-white' }}">{{ $label }}</a>
                    @endforeach
                    <a href="{{ route('log.index', array_filter(['tab' => 'aktivitas', 'kategori' => $filterKategori === 'semua' ? null : $filterKategori, 'status' => $hanyaGagal ? null : 'gagal'])) }}"
                        @if ($hanyaGagal) aria-current="true" @endif
                        class="inline-flex items-center gap-2 h-11 px-4 rounded-full border text-sm font-semibold whitespace-nowrap transition-colors
                        {{ $hanyaGagal ? 'bg-red-400 text-black border-red-400' : 'border-red-400/30 text-red-300 hover:bg-red-400/10' }}">
                        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Hanya yang gagal
                    </a>
                </div>

                <p class="text-xs text-white/40 -mt-3">Yang dicatat: siapa, kapan, dan aksi apa. Isi form, kata sandi, dan
                    file tidak disimpan. Catatan lebih dari {{ config('log_admin.simpan_aktivitas_hari') }} hari dihapus
                    otomatis.</p>

                @if ($daftar->isEmpty())
                    @include('components/dashboard/card/empty-state', [
                        'message' => 'Belum ada aktivitas yang cocok.',
                    ])
                @else
                    <ul class="flex flex-col gap-2">
                        @foreach ($daftar as $a)
                            <li
                                class="rounded-2xl border border-white/10 bg-white/3 px-4 py-3 flex flex-col md:flex-row md:items-center gap-1 md:gap-4">
                                <p class="text-xs text-white/40 md:w-40 shrink-0">
                                    {{ $a->created_at->locale('id')->translatedFormat('j M Y, H.i') }}</p>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold">{{ $a->aksi }}
                                        @if ($a->status === 'gagal')
                                            <span
                                                class="ml-1 rounded-full border border-red-400/30 bg-red-400/10 px-2 py-0.5 text-[11px] font-semibold text-red-300">Gagal</span>
                                        @endif
                                    </p>
                                    @if ($a->keterangan)
                                        <p class="text-xs text-white/50 wrap-break-word">{{ $a->keterangan }}</p>
                                    @endif
                                </div>
                                <p class="text-xs text-white/60 md:w-28 shrink-0">{{ $a->pelaku ?? 'tidak dikenal' }}</p>
                                <p class="text-xs text-white/30 md:w-32 shrink-0">{{ $a->ip }}</p>
                                <span
                                    class="shrink-0 w-fit rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $kelasKategori[$a->kategori] ?? 'border-white/15 text-white/60' }}">{{ $kategoriLabel[$a->kategori] ?? $a->kategori }}</span>
                            </li>
                        @endforeach
                    </ul>

                    @include('components.dashboard.pagination', ['paginator' => $daftar])
                @endif
            @endif
        </div>
    </div>
@endsection
