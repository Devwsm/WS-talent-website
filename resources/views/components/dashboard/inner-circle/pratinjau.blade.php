@props(['kodeGrup', 'info'])
@php $halamanPratinjau = $info['pratinjau'] ?? []; @endphp
@if ($halamanPratinjau)
    <aside class="xl:sticky xl:top-6 flex flex-col gap-3 min-w-0" data-pratinjau
        data-url="{{ route('ic.admin.konten.pratinjau') }}" data-grup="{{ $kodeGrup }}"
        data-form="form-{{ $kodeGrup }}" aria-label="Pratinjau {{ $info['label'] }}">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-xs font-semibold uppercase tracking-widest text-white/60">Pratinjau langsung</p>
            <div class="flex items-center gap-3">
                <span class="text-xs text-white/40" role="status" data-pratinjau-status>Menunggu…</span>
                <button type="button" data-pratinjau-muat
                    class="min-h-11 px-2 text-xs text-white/60 underline underline-offset-4 hover:text-white">Muat
                    ulang</button>
            </div>
        </div>

        @if (count($halamanPratinjau) > 1)
            <label class="sr-only" for="pilih-pratinjau-{{ $kodeGrup }}">Halaman yang dipratinjau</label>
            <select id="pilih-pratinjau-{{ $kodeGrup }}" data-pratinjau-halaman
                class="w-full min-w-0 bg-white/5 border border-white/15 text-white p-3 rounded-lg text-sm focus:outline-none focus:border-red-900">
                @foreach ($halamanPratinjau as $kode => $label)
                    <option value="{{ $kode }}" class="text-black">{{ $label }}</option>
                @endforeach
            </select>
        @endif

        {{-- sandbox tanpa allow-forms/allow-popups/allow-top-navigation: isinya hanya bisa dilihat, tidak bisa mengirim atau berpindah halaman. --}}
        <div class="rounded-3xl border border-white/10 bg-white overflow-hidden">
            <iframe data-pratinjau-frame sandbox="allow-scripts allow-same-origin"
                title="Pratinjau {{ $info['label'] }}" class="block w-full h-[70vh] min-h-112 bg-white"></iframe>
        </div>
        <p class="text-xs text-white/40">Isian yang belum disimpan ikut tampil di sini. Tampilan memakai anggota
            contoh; tautan dan tombol kirim di pratinjau tidak aktif.</p>
    </aside>
@endif
