{{--
    Navbar atas menu Acara di CMS Inner Circle: tiga bagian, dipilih lewat chip mengalir (sama seperti navbar atas tab
    Konten, bukan baris yang digeser).
    - Pengaturan acara : tambah, impor, sinkron Bandsintown, ubah dan hapus jadwal.
    - Hari H           : QR check-in, titik lokasi, dan (Fase 4 dan seterusnya) yang hadir serta undian GL.
    - Pemeriksaan      : pengajuan anggota yang GPS-nya gagal dan penanda check-in yang mencurigakan (Fase 5B).
                         Angkanya ($jumlahPemeriksaanMenunggu) dikirim PembantuHalamanAdminIc::tampil().

    Variabel: $aktifTab = 'pengaturan' | 'harih' | 'pemeriksaan'
--}}
@php
    $bagianAcara = [
        'pengaturan' => ['label' => 'Pengaturan acara', 'ikon' => 'bi-sliders', 'route' => 'ic.admin.acara'],
        'harih' => ['label' => 'Hari H', 'ikon' => 'bi-qr-code-scan', 'route' => 'ic.admin.acara.harih'],
        'pemeriksaan' => [
            'label' => 'Pemeriksaan',
            'ikon' => 'bi-shield-check',
            'route' => 'ic.admin.acara.pemeriksaan',
        ],
    ];
    $angkaPemeriksaan = (int) ($jumlahPemeriksaanMenunggu ?? 0);
@endphp
<nav aria-label="Bagian menu Acara" class="flex flex-col gap-2 rounded-3xl border border-white/10 bg-white/3 p-4 sm:p-5">
    <div class="cms-chips">
        @foreach ($bagianAcara as $kunciBagian => $b)
            @php $aktifBagian = $kunciBagian === $aktifTab; @endphp
            <a href="{{ route($b['route']) }}" @if ($aktifBagian) aria-current="page" @endif
                class="cms-chip rounded-full border text-xs font-semibold transition-colors {{ $aktifBagian ? 'bg-white text-black border-white' : 'border-white/15 text-white/70 hover:bg-white/10 hover:text-white' }}">
                <i class="bi {{ $b['ikon'] }} {{ $aktifBagian ? 'text-black/60' : 'text-white/50' }}"
                    aria-hidden="true"></i>
                <span>{{ $b['label'] }}</span>
                @if ($kunciBagian === 'pemeriksaan' && $angkaPemeriksaan > 0)
                    <span class="rounded-full bg-red-500 px-1.5 text-[10px] font-bold leading-4 text-white"
                        aria-label="{{ $angkaPemeriksaan }} perlu ditinjau">{{ $angkaPemeriksaan }}</span>
                @endif
            </a>
        @endforeach
    </div>
</nav>
