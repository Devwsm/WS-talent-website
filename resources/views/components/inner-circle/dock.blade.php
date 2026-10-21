@props(['member'])
@php
    $menu = [
        ['route' => 'ic.beranda', 'label' => 'Beranda', 'ikon' => 'bi-house-door', 'aktif' => 'bi-house-door-fill'],
        ['route' => 'ic.hadiah', 'label' => 'Hadiah', 'ikon' => 'bi-gift', 'aktif' => 'bi-gift-fill'],
        ['route' => 'ic.checkin', 'label' => 'Check-in', 'ikon' => 'bi-qr-code-scan', 'aktif' => 'bi-qr-code-scan'],
        ['route' => 'ic.komunitas', 'label' => 'Komunitas', 'ikon' => 'bi-heart', 'aktif' => 'bi-heart-fill'],
    ];
    $profilAktif = request()->routeIs('ic.profil');
@endphp
{{-- Dock bawah: satu-satunya navigasi utama (desktop dan HP). Sidebar kiri di prototype tidak dibawa. --}}
<nav class="ic-dock" aria-label="Navigasi Inner Circle">
    @foreach ($menu as $m)
        @php $aktif = request()->routeIs($m['route']); @endphp
        <a href="{{ route($m['route']) }}" class="ic-dock__item {{ $aktif ? 'is-aktif' : '' }}"
            @if ($aktif) aria-current="page" @endif>
            <i class="bi {{ $aktif ? $m['aktif'] : $m['ikon'] }}" aria-hidden="true"></i>
            <span>{{ $m['label'] }}</span>
        </a>
    @endforeach
    <a href="{{ route('ic.profil') }}" class="ic-dock__item {{ $profilAktif ? 'is-aktif' : '' }}"
        @if ($profilAktif) aria-current="page" @endif>
        <x-inner-circle.avatar :member="$member" kelas="ic-avatar--dock" />
        <span>Profil</span>
    </a>
</nav>
