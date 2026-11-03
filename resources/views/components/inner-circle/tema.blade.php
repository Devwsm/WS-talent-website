{{-- Menimpa token warna area anggota dengan pengaturan CMS (Tampilan). Tanpa warna kustom tidak menulis apa pun.
    Isinya hanya variabel CSS dari config dan nilai #RRGGBB yang sudah divalidasi (IcTampilan::css), jadi aman ditulis mentah. --}}
@php $cssTema = ic_tampilan()->css(); @endphp
@if ($cssTema !== '')
    <style>
        {!! $cssTema !!}
    </style>
@endif
