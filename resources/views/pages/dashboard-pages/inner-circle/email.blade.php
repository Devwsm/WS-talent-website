@extends('template/dashboardLayout')
@section('content')
    <div class="relative w-full flex flex-col justify-center items-center overflow-hidden">
        @include('components/dashboard/navbar')

        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-24 w-96 h-96 rounded-full blur-3xl z-0"
            style="background: radial-gradient(circle, #5e0006 0%, transparent 70%); opacity: 0.35;"></div>

        <div class="relative z-10 w-full max-w-7xl mx-auto flex flex-col gap-8 px-5 md:px-10 pt-8 pb-28 text-white">

            @include('components.dashboard.inner-circle.tabs', [
                'aktif' => 'email',
                'judul' => 'Email',
                'deskripsi' =>
                    'Lihat format email notifikasi untuk anggota dan kirim email tes untuk memastikan pengiriman berfungsi.',
                'pakaiCss' => false,
            ])

            @include('components/errors')
            @include('components/success')

            {{-- ============ 1. PENGATURAN PENGIRIMAN ============ --}}
            <section aria-labelledby="judul-kirim" class="flex flex-col gap-4">
                <h2 id="judul-kirim" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-send-check text-white/50" aria-hidden="true"></i> Pengaturan pengiriman
                </h2>

                <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8 flex flex-col gap-4">
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                        <div class="rounded-2xl border border-white/10 bg-white/2 p-4">
                            <dt class="text-xs uppercase tracking-widest text-white/40">Mailer</dt>
                            <dd class="mt-1 font-semibold font-mono">{{ $mailer }}</dd>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/2 p-4">
                            <dt class="text-xs uppercase tracking-widest text-white/40">Alamat pengirim</dt>
                            <dd class="mt-1 font-semibold wrap-anywhere">{{ $dariAlamat }}</dd>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/2 p-4">
                            <dt class="text-xs uppercase tracking-widest text-white/40">Nama pengirim</dt>
                            <dd class="mt-1 font-semibold wrap-anywhere">{{ $dariNama }}</dd>
                        </div>
                    </dl>

                    @unless ($emailNyata)
                        <div
                            class="flex items-start gap-3 rounded-2xl border border-amber-300/20 bg-amber-300/5 px-4 py-3 text-sm text-amber-100/80">
                            <i class="bi bi-exclamation-triangle mt-0.5 shrink-0" aria-hidden="true"></i>
                            <p>Mailer <b>{{ $mailer }}</b> tidak mengirim email ke kotak masuk: isinya hanya ditulis ke
                                <code>storage/logs</code>. Anggota tidak akan menerima tautan lupa kata sandi sampai
                                <code>MAIL_MAILER=smtp</code> dan akun email pengirim diisi di <code>.env</code> (lihat
                                README, bagian Email).
                            </p>
                        </div>
                    @endunless
                </div>
            </section>

            {{-- ============ 2. FORMAT & TES ============ --}}
            <section id="format" aria-labelledby="judul-format" class="flex flex-col gap-4 scroll-mt-8">
                <h2 id="judul-format" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
                    <i class="bi bi-envelope-paper text-white/50" aria-hidden="true"></i> Format email
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    {{-- Kiri: pilih jenis + kirim tes --}}
                    <div class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8 flex flex-col gap-5">
                        <nav aria-label="Jenis email">
                            <ul class="flex flex-col gap-2">
                                @foreach ($daftar as $kunci => $info)
                                    @php $dipilih = $kunci === $jenis; @endphp
                                    <li>
                                        <a href="{{ route('ic.admin.email', ['jenis' => $kunci]) }}#format"
                                            @if ($dipilih) aria-current="page" @endif
                                            class="flex items-center gap-3 rounded-2xl border px-4 py-3 text-sm font-semibold transition-colors
                                            {{ $dipilih ? 'bg-white text-black border-white' : 'border-white/10 text-white/70 hover:bg-white/10 hover:text-white' }}">
                                            <i class="bi {{ $info['ikon'] }}" aria-hidden="true"></i>
                                            {{ $info['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </nav>

                        <p class="text-sm text-white/60">{{ $daftar[$jenis]['kapan'] }}</p>

                        <form method="POST" action="{{ route('ic.admin.email.tes') }}"
                            class="flex flex-col gap-2 pt-5 border-t border-white/10">
                            @csrf
                            <input type="hidden" name="jenis" value="{{ $jenis }}">
                            <label for="email-tujuan"
                                class="text-sm font-semibold uppercase tracking-widest text-white/60">Kirim email tes
                                ke</label>
                            <input type="email" id="email-tujuan" name="tujuan" value="{{ old('tujuan') }}"
                                placeholder="emailmu@contoh.com" autocomplete="email" required
                                class="bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition placeholder:text-white/30" />
                            <p class="text-xs text-white/30">Isinya data contoh dan subjeknya diberi awalan [TES]. Tidak ada
                                anggota yang menerimanya.</p>
                            <button type="submit"
                                class="self-start h-11 px-5 inline-flex items-center gap-2 text-white font-bold uppercase tracking-widest text-xs bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                                <i class="bi bi-send" aria-hidden="true"></i> Kirim email tes
                            </button>
                        </form>
                    </div>

                    {{-- Kanan: pratinjau (iframe tersandbox: skrip dan tautan keluar dimatikan) --}}
                    <div class="lg:sticky lg:top-8 rounded-3xl border border-white/10 overflow-hidden bg-white">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-black/10 bg-black/3 text-black">
                            <i class="bi bi-eye text-black/40" aria-hidden="true"></i>
                            <span class="text-xs uppercase tracking-widest text-black/40">Pratinjau:
                                {{ $daftar[$jenis]['label'] }}</span>
                        </div>
                        <iframe title="Pratinjau email {{ $daftar[$jenis]['label'] }}" sandbox=""
                            src="{{ route('ic.admin.email.pratinjau', $jenis) }}" class="w-full h-160 bg-white"
                            loading="lazy"></iframe>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
