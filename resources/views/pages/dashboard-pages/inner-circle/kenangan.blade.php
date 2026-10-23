@extends('template/dashboardLayout')
@section('content')
    @php
        $kategoriKenangan = config('inner_circle.kategori_kenangan');
        $chipAktif = 'bg-white text-black border-white';
        $chipBiasa = 'border-white/10 text-white/60 hover:bg-white/10 hover:text-white';
    @endphp

    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        {{-- Dekorasi abstrak, senada sama halaman lain --}}
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-6 px-5 md:px-10 pt-8 pb-28 text-white">

            @include('components.dashboard.inner-circle.tabs', [
                'aktif' => 'kenangan',
                'judul' => 'Kenangan',
                'deskripsi' =>
                    'Moderasi galeri kenangan anggota. Kenangan yang disembunyikan tidak tampil di galeri.',
            ])

            @include('components/errors')
            @include('components/success')

            {{-- Filter: tautan GET, jalan tanpa JavaScript --}}
            <div class="flex flex-col gap-3 rounded-3xl border border-white/10 bg-white/3 p-4 md:p-5">
                <nav aria-label="Filter kategori kenangan" class="flex flex-wrap gap-2">
                    <a href="{{ route('ic.admin.kenangan', array_filter(['status' => $status !== 'all' ? $status : null])) }}"
                        @if ($kategori === 'all') aria-current="true" @endif
                        class="inline-flex items-center gap-2 h-11 px-4 rounded-full border text-sm font-semibold transition-colors {{ $kategori === 'all' ? $chipAktif : $chipBiasa }}">
                        Semua kategori
                    </a>
                    @foreach ($kategoriKenangan as $kunci => $k)
                        <a href="{{ route('ic.admin.kenangan', array_filter(['kategori' => $kunci, 'status' => $status !== 'all' ? $status : null])) }}"
                            @if ($kategori === $kunci) aria-current="true" @endif
                            class="inline-flex items-center gap-2 h-11 px-4 rounded-full border text-sm font-semibold transition-colors {{ $kategori === $kunci ? $chipAktif : $chipBiasa }}">
                            {{ $k['label'] }}
                        </a>
                    @endforeach
                </nav>

                <nav aria-label="Filter status kenangan" class="flex flex-wrap gap-2">
                    @foreach (['all' => 'Semua status', 'tayang' => 'Tayang', 'disembunyikan' => 'Disembunyikan'] as $kunci => $label)
                        <a href="{{ route('ic.admin.kenangan', array_filter(['kategori' => $kategori !== 'all' ? $kategori : null, 'status' => $kunci !== 'all' ? $kunci : null])) }}"
                            @if ($status === $kunci) aria-current="true" @endif
                            class="inline-flex items-center h-11 px-4 rounded-full border text-sm font-semibold transition-colors {{ $status === $kunci ? $chipAktif : $chipBiasa }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-images text-white/50" aria-hidden="true"></i>
                    Kenangan ({{ $kenangan->total() }} dari {{ $jumlahSemua }})
                </h2>
                <p class="text-xs text-white/50">
                    Disembunyikan: <b class="text-white">{{ $jumlahDisembunyikan }}</b>
                </p>
            </div>

            @if ($kenangan->isEmpty())
                @include('components/dashboard/card/empty-state', [
                    'message' => 'Tidak ada kenangan yang cocok dengan filter ini.',
                ])
            @else
                <ul class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($kenangan as $k)
                        <li class="flex flex-col rounded-3xl border border-white/10 bg-white/3 overflow-hidden min-w-0">

                            {{-- Kartu yang sama dengan galeri anggota; `inert` supaya tombol suka tidak bisa dipakai di sini --}}
                            <div class="ic-app p-3 transition-opacity {{ $k->disembunyikan ? 'opacity-40' : '' }}" inert>
                                <x-inner-circle.kartu-kenangan :kenangan="$k" />
                            </div>

                            <div class="flex flex-wrap items-center gap-2 px-4 py-3 border-t border-white/10">
                                <span
                                    class="rounded-full border px-3 py-1.5 text-xs font-semibold {{ $k->disembunyikan ? 'border-red-400/30 bg-red-400/10 text-red-300' : 'border-emerald-300/30 bg-emerald-300/10 text-emerald-200' }}">{{ $k->disembunyikan ? 'Disembunyikan' : 'Tayang' }}</span>
                                {{-- Admin melihat nama lengkap; anggota lain hanya melihat singkatannya di kartu. --}}
                                <span class="min-w-0 truncate text-xs text-white/50">{{ $k->member->nama }} ·
                                    {{ $k->member->kode_member }}</span>
                                <form method="POST" action="{{ route('ic.admin.kenangan.moderasi', $k->id_memory) }}"
                                    class="ml-auto"
                                    @if (!$k->disembunyikan) data-konfirmasi-sembunyi
                                        data-ringkas="{{ $k->member->nama }} · {{ $k->tempat ?: 'tanpa tempat' }}" @endif>
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="aksi"
                                        value="{{ $k->disembunyikan ? 'tayangkan' : 'sembunyikan' }}">
                                    <button type="submit"
                                        class="h-11 px-4 rounded-full border border-white/15 text-xs font-semibold text-white/80 hover:bg-white/10 hover:text-white transition-colors">
                                        {{ $k->disembunyikan ? 'Tayangkan' : 'Sembunyikan' }}
                                    </button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>

                @include('components.dashboard.pagination', ['paginator' => $kenangan])
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            // Menyembunyikan butuh konfirmasi; menayangkan lagi langsung terkirim. Tanpa JS form tetap terkirim.
            document.addEventListener('submit', async (e) => {
                const form = e.target.closest('form[data-konfirmasi-sembunyi]');
                if (!form || form.dataset.sudahKonfirmasi === '1') return;
                e.preventDefault();

                const hasil = await window.Swal.fire({
                    title: 'Sembunyikan kenangan ini?',
                    text: `${form.dataset.ringkas}. Kenangan tidak akan tampil di galeri anggota.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, sembunyikan',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    focusCancel: true,
                });
                if (!hasil.isConfirmed) return;

                form.dataset.sudahKonfirmasi = '1';
                form.requestSubmit();
            });
        </script>
    @endpush
@endsection
