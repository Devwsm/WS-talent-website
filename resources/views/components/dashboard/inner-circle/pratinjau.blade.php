{{--
    Panel pratinjau di samping form tab Konten. Bentuknya sama dengan panel "Tampilan di halaman Profile" di CMS Artis:
    kartu bulat dengan bilah judul, lalu isi pratinjau (di sini iframe halaman asli yang dipotong, lihat PratinjauPotongan).
    Variabel: $kodeGrup, $info (label + pratinjau), $idForm (form yang dipantau), $fokus (kunci teks penentu potongan),
    $judulPanel (nama modul), $pilih (menu pilihan halaman, hanya untuk pratinjau seluruh grup).
--}}
@props(['kodeGrup', 'info', 'idForm' => null, 'fokus' => null, 'judulPanel' => null, 'pilih' => true])
@php $halamanPratinjau = $info['pratinjau'] ?? []; @endphp
@if ($halamanPratinjau)
    <aside class="lg:sticky lg:top-8 min-w-0 rounded-3xl border border-white/10 bg-black overflow-hidden" data-pratinjau
        data-url="{{ route('ic.admin.konten.pratinjau') }}" data-grup="{{ $kodeGrup }}"
        @if ($idForm !== null || $judulPanel === null) data-form="{{ $idForm ?? 'form-' . $kodeGrup }}" @endif
        @if ($fokus) data-fokus="{{ str_replace('.', '__', $fokus) }}" @endif
        aria-label="Pratinjau {{ $judulPanel ?? $info['label'] }}">
        <div
            class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-5 py-3 border-b border-white/10 bg-white/3">
            <p class="text-xs uppercase tracking-widest text-white/60 flex items-center gap-2">
                <i class="bi bi-eye text-white/50" aria-hidden="true"></i> Tampilan di halaman
            </p>
            <div class="flex items-center gap-3">
                <span class="text-xs text-white/50" role="status" data-pratinjau-status>Menunggu…</span>
                <button type="button" data-pratinjau-muat
                    class="min-h-11 px-1 text-xs text-white/60 underline underline-offset-4 hover:text-white">Muat
                    ulang</button>
            </div>
        </div>

        <div class="p-5 flex flex-col gap-3">
            @if ($pilih && count($halamanPratinjau) > 1)
                <label class="sr-only" for="pilih-pratinjau-{{ $kodeGrup }}">Halaman yang dipratinjau</label>
                <select id="pilih-pratinjau-{{ $kodeGrup }}" data-pratinjau-halaman
                    class="w-full min-w-0 bg-white/5 border border-white/15 text-white p-3 rounded-lg text-sm focus:outline-none focus:border-red-900">
                    <option value="" class="text-black">Otomatis (halaman yang memuat teks ini)</option>
                    @foreach ($halamanPratinjau as $kode => $label)
                        <option value="{{ $kode }}" class="text-black">{{ $label }}</option>
                    @endforeach
                </select>
            @endif

            {{-- sandbox tanpa allow-forms/allow-popups/allow-top-navigation: isinya hanya bisa dilihat, tidak bisa mengirim atau berpindah halaman. --}}
            <div class="rounded-2xl border border-white/10 overflow-hidden" style="background:#fff">
                <iframe data-pratinjau-frame sandbox="allow-scripts allow-same-origin"
                    title="Pratinjau {{ $judulPanel ?? $info['label'] }}"
                    class="block w-full h-48 transition-[height] duration-200" style="background:#fff"></iframe>
            </div>
            <p class="text-xs text-amber-200/80" data-pratinjau-catatan hidden></p>
            <p class="text-xs text-white/50">Garis putus-putus = teks yang kamu ubah. Isian kosong tampil sebagai teks
                bawaan; tautan dan tombol di pratinjau tidak aktif.</p>
        </div>
    </aside>
@endif
