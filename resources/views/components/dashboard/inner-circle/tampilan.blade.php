{{-- Tampilan dan merek di tab Konten: nama program, tiga warna, dan logo, dengan pratinjau langsung di sebelahnya.
     Variabel: $tampilanInfo (config), $tampilanWarna (efektif), $tampilanWarnaSimpan (tersimpan, null = bawaan),
     $tampilanProgram (tersimpan), $tampilanProgramBawaan, $tampilanLogo (alamat logo kustom atau null). --}}
@php
    $galat = $errors->getBag('tampilan');
    $pakaiOld = $galat->any();
    $kelasInput =
        'w-full min-w-0 bg-white/5 border border-white/15 text-white p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition';
    $kelasLabel = 'text-sm font-semibold uppercase tracking-widest text-white/60';
    $kelasBantu = 'text-xs text-white/40';
    $nilaiProgram = $pakaiOld ? old('k.tampilan__program', '') : $tampilanProgram ?? '';
    $infoPratinjau = ['label' => $tampilanInfo['label'], 'pratinjau' => $tampilanInfo['pratinjau']];
@endphp
<section id="tampilan" aria-labelledby="judul-tampilan" class="scroll-mt-8 flex flex-col gap-4">
    <div class="min-w-0">
        <h2 id="judul-tampilan" class="font-bold uppercase tracking-wide text-sm flex items-center gap-2">
            <i class="bi bi-palette text-white/50" aria-hidden="true"></i> {{ $tampilanInfo['label'] }}
        </h2>
        <p class="text-xs text-white/40 mt-1">Nama program, warna area anggota, dan logo. Kosongkan isian untuk memakai
            bawaan. Warna dicek kontrasnya supaya teks tetap terbaca.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        <form method="POST" id="form-tampilan" action="{{ route('ic.admin.konten.tampilan.simpan') }}"
            enctype="multipart/form-data"
            class="rounded-3xl border border-white/10 bg-white/3 p-6 md:p-8 flex flex-col gap-6 min-w-0">
            @csrf
            @if ($galat->any())
                <div role="alert"
                    class="rounded-xl border border-red-300/40 bg-red-950/20 p-3 text-xs text-red-300 flex flex-col gap-1">
                    @foreach ($galat->all() as $pesan)
                        <p>{{ $pesan }}</p>
                    @endforeach
                </div>
            @endif

            <div class="flex flex-col gap-1.5 min-w-0">
                <label for="f-tampilan-program" class="{{ $kelasLabel }}">Nama program</label>
                <input id="f-tampilan-program" name="k[tampilan__program]"
                    maxlength="{{ $tampilanInfo['program_maks'] }}" autocomplete="off" value="{{ $nilaiProgram }}"
                    placeholder="{{ $tampilanProgramBawaan }}" class="{{ $kelasInput }}">
                <p class="{{ $kelasBantu }}">Dipakai di judul tab, teks yang memuat <code
                        class="rounded bg-white/10 px-1.5 py-0.5 text-white/70">&#123;program&#125;</code>, dan teks
                    alternatif logo. Bawaan: {{ $tampilanProgramBawaan }}. Teks yang sudah kamu ubah sendiri di atas
                    tidak ikut berganti. Email belum ikut (menyusul di Fase 7).</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                @foreach ($tampilanInfo['warna'] as $kode => $def)
                    @php
                        $nilaiWarna = $pakaiOld ? old('k.tampilan__' . $kode, '') : $tampilanWarnaSimpan[$kode] ?? '';
                        $pilihanAwal = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $nilaiWarna)
                            ? $nilaiWarna
                            : $def['bawaan'];
                    @endphp
                    <div class="flex flex-col gap-1.5 min-w-0" data-warna data-warna-bawaan="{{ $def['bawaan'] }}">
                        <label for="f-tampilan-{{ $kode }}"
                            class="{{ $kelasLabel }}">{{ $def['label'] }}</label>
                        <div class="flex items-center gap-2">
                            <input type="color" value="{{ $pilihanAwal }}" data-warna-pilih
                                aria-label="Pilih {{ strtolower($def['label']) }}"
                                class="h-11 w-14 shrink-0 cursor-pointer rounded-lg border border-white/15 bg-transparent p-1">
                            <input id="f-tampilan-{{ $kode }}" name="k[tampilan__{{ $kode }}]"
                                maxlength="7" autocomplete="off" inputmode="text" spellcheck="false"
                                value="{{ $nilaiWarna }}" placeholder="{{ $def['bawaan'] }}" data-warna-teks
                                class="{{ $kelasInput }}">
                        </div>
                        <p class="{{ $kelasBantu }}">{{ $def['petunjuk'] }} Bawaan: {{ $def['bawaan'] }}.</p>
                        <div>
                            <button type="button" data-warna-kosongkan
                                class="text-xs text-white/50 underline underline-offset-4 hover:text-white min-h-11">Pakai
                                bawaan</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex flex-col gap-2 min-w-0">
                <label for="f-tampilan-logo" class="{{ $kelasLabel }}">Logo</label>
                @if ($tampilanLogo)
                    <div class="flex flex-wrap items-center gap-4 rounded-xl border border-white/10 bg-white p-4">
                        <img src="{{ $tampilanLogo }}" alt="Logo kustom saat ini"
                            class="max-h-16 max-w-full object-contain">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-white/70 min-h-11">
                        <input type="checkbox" name="hapus_logo" value="1" class="size-5 accent-red-900"
                            @checked($pakaiOld && old('hapus_logo'))>
                        Hapus logo kustom (kembali ke logo bawaan)
                    </label>
                @else
                    <p class="rounded-xl border border-white/10 bg-white/2 px-4 py-3 text-sm text-white/70">Sekarang
                        memakai logo bawaan.</p>
                @endif
                <input type="file" id="f-tampilan-logo" name="logo" accept="image/png,image/jpeg,image/webp"
                    class="w-full min-w-0 text-sm text-white/70 file:mr-3 file:min-h-11 file:cursor-pointer file:rounded-lg file:border-0 file:bg-white/10 file:px-4 file:text-xs file:font-semibold file:text-white hover:file:bg-white/15">
                <p class="{{ $kelasBantu }}">JPG, PNG, atau WebP, maksimal
                    {{ rtrim(rtrim(number_format($tampilanInfo['logo_maks_kb'] / 1024, 1, ',', ''), '0'), ',') }} MB.
                    Otomatis diubah ke WebP dan dikecilkan (sisi terpanjang {{ $tampilanInfo['logo_sisi_maks'] }} px).
                    PNG transparan dianjurkan. Logo tampil di header, halaman Masuk, dan Daftar. Pratinjau di samping
                    baru menampilkan logo setelah disimpan. Gambar OG (kartu tautan di media sosial) tidak ikut
                    berganti.</p>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <button type="submit"
                    class="h-11 px-5 inline-flex items-center gap-2 text-white font-bold uppercase tracking-widest text-xs bg-red-950 hover:bg-red-900 active:scale-95 transition rounded-lg">
                    <i class="bi bi-check2-circle" aria-hidden="true"></i> Simpan tampilan</button>
                <button type="submit" formaction="{{ route('ic.admin.konten.tampilan.bawaan') }}" formnovalidate
                    class="min-h-11 text-xs text-white/50 underline underline-offset-4 hover:text-white">Kembalikan
                    semua ke bawaan</button>
            </div>
        </form>

        @include('components.dashboard.inner-circle.pratinjau', [
            'kodeGrup' => 'tampilan',
            'info' => $infoPratinjau,
        ])
    </div>
</section>
