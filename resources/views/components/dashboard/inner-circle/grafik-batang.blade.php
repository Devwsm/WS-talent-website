{{--
    Grafik batang sederhana tanpa pustaka JavaScript (CSS saja), aman untuk CSP dan terbaca pembaca layar.

    Props:
    - data   : daftar ['label' => teks sumbu, 'nilai' => angka, 'keterangan' => teks lengkap untuk tooltip/pembaca layar]
    - satuan : kata benda di tooltip (mis. "pendaftar")
    - kosong : pesan bila data tidak ada
--}}
@props(['data' => [], 'satuan' => '', 'kosong' => 'Belum ada data.'])
@php
    $maks = max(1, ...array_column($data ?: [['nilai' => 1]], 'nilai'));
    $total = array_sum(array_column($data, 'nilai'));
@endphp
@if (empty($data))
    @include('components/dashboard/card/empty-state', ['message' => $kosong])
@else
    <p class="text-xs text-white/40 mb-3">Total {{ number_format($total, 0, ',', '.') }} {{ $satuan }} pada rentang
        ini.</p>
    <ul class="flex items-end gap-1.5 sm:gap-2 h-44 overflow-x-auto" role="list">
        @foreach ($data as $d)
            @php $tinggi = $d['nilai'] > 0 ? max(4, (int) round($d['nilai'] / $maks * 100)) : 0; @endphp
            <li class="group relative flex flex-1 min-w-7 flex-col items-center justify-end h-full gap-1"
                title="{{ $d['keterangan'] }}: {{ $d['nilai'] }} {{ $satuan }}">
                <span
                    class="text-[11px] font-semibold tabular-nums {{ $d['nilai'] > 0 ? 'text-white/80' : 'text-white/25' }}">{{ $d['nilai'] }}</span>
                <span aria-hidden="true"
                    class="w-full rounded-t-md {{ $d['nilai'] > 0 ? 'bg-white/60 group-hover:bg-white' : 'bg-white/10' }} transition-colors"
                    style="height: {{ $d['nilai'] > 0 ? $tinggi . '%' : '2px' }}"></span>
                <span class="text-[10px] text-white/40 whitespace-nowrap">{{ $d['label'] }}</span>
                <span class="sr-only">{{ $d['keterangan'] }}: {{ $d['nilai'] }} {{ $satuan }}</span>
            </li>
        @endforeach
    </ul>
@endif
