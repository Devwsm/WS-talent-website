@props(['acara', 'tampil' => false])
{{-- Tanda info satu acara di daftar CMS: check-in yang sedang dibuka, undian GL gratis, dan (bila $tampil) status
     tampil/sembunyi di daftar anggota. Warna latar dan tulisan ditulis eksplisit (inline) karena tema CMS Inner Circle
     mendefinisikan ulang warna "white" menjadi biru tua, sehingga text-white tidak terbaca di atas latar berwarna. --}}
@php
    $dibuka = $acara->checkin_dibuka;
    $gl = $acara->punya_gl;
    $diundi = $gl && $acara->status_undian === \App\Models\ic_event::UNDIAN_SELESAI;
    $sembunyi = $tampil && $acara->status_tampil !== 'tampil';
    $kelas =
        'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide leading-none';
@endphp
@if ($dibuka || $gl || $sembunyi)
    <span class="mt-1.5 flex flex-wrap items-center gap-1.5">
        @if ($dibuka)
            <span class="{{ $kelas }}" style="background:#0b6b3f;color:#ffffff"><i class="bi bi-broadcast"
                    aria-hidden="true"></i> Check-in dibuka</span>
        @endif
        @if ($gl)
            <span class="{{ $kelas }}" style="background:#17629a;color:#ffffff"><i
                    class="bi bi-ticket-perforated-fill" aria-hidden="true"></i>
                {{ $diundi ? 'GL sudah diundi' : 'Undian GL gratis' }}</span>
        @endif
        @if ($sembunyi)
            <span class="{{ $kelas }}" style="background:#475569;color:#ffffff"><i class="bi bi-eye-slash-fill"
                    aria-hidden="true"></i>
                {{ $acara->status_tampil === 'sembunyi_manual' ? 'Disembunyikan' : 'Tersembunyi otomatis' }}</span>
        @endif
    </span>
@endif
