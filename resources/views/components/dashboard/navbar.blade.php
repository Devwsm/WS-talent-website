{{-- desktop --}}
<div class="nav z-50 fixed bottom-5 left-1/2 -translate-x-1/2 hidden md:flex gap-2">
    <div class="nav-links flex justify-center items-center p-6 rounded-lg bg-[#1A1A1B] shrink-0">
        <a href="{{ route('dashboard') }}" class="text-[#F5F1E6] hover:text-[#d5ccb3] text-[2rem]">
            <i class="bi bi-house-door-fill"></i>
        </a>
    </div>
    <div class="nav-links flex justify-center items-center gap-4 p-6 rounded-lg bg-[#1A1A1B] shrink-0">
        <a href="{{ route('banner') }}" class="text-[#F5F1E6] hover:text-[#d5ccb3] text-[2rem]"><i
                class="bi bi-images"></i></a>
        <a href="{{ route('headers') }}" class="text-[#F5F1E6] hover:text-[#d5ccb3] text-[2rem]"><i
                class="bi bi-card-image"></i></a>
        <a href="{{ route('dashboard.profile') }}" class="text-[#F5F1E6] hover:text-[#d5ccb3] text-[2rem]"><i
                class="bi bi-person-fill"></i></a>
        <a href="{{ route('albums') }}" class="text-[#B71C1C] hover:text-[#891212] text-[2rem]"><i
                class="bi bi-disc-fill"></i></a>
        <a href="{{ route('news') }}" class="text-[#B71C1C] hover:text-[#891212] text-[2rem]"><i
                class="bi bi-newspaper"></i></a>
        <a href="{{ route('merchandise') }}" class="text-[#B71C1C] hover:text-[#891212] text-[2rem]"><i
                class="bi bi-basket-fill"></i></a>
    </div>
    <div class="nav-links flex justify-center items-center p-6 rounded-lg bg-[#1A1A1B] shrink-0">
        <a href="{{ route('logout') }}" class="text-[#B71C1C] hover:text-[#891212] text-[2rem]"><i
                class="bi bi-box-arrow-right"></i></a>
    </div>
</div>
{{-- ↑ DESKTOP DITUTUP DI SINI, sebelum blok mobile dimulai --}}


{{-- Mobile trigger --}}
<button id="dashOpenBtn"
    class="fixed z-50 bottom-5 right-5 md:hidden flex justify-center items-center h-14 w-14 rounded-full bg-[#1A1A1B] text-[#F5F1E6] text-2xl shadow-lg">
    <i class="bi bi-list"></i>
</button>

{{-- Mobile Fullscreen --}}
<div id="dashMobileMenu"
    class="fixed inset-0 bg-[#5E0006] text-white z-60
    flex flex-col items-center justify-center gap-8
    translate-x-full transition-transform duration-300 md:hidden">

    <a href="{{ route('dashboard') }}" class="menu-link flex items-center gap-3">
        <i class="bi bi-house-door-fill text-3xl"></i>
        <h1 class="text-2xl font-bold uppercase">Dashboard</h1>
    </a>
    <a href="{{ route('banner') }}" class="menu-link flex items-center gap-3">
        <i class="bi bi-images text-3xl"></i>
        <h1 class="text-2xl font-bold uppercase">Banner</h1>
    </a>
    <a href="{{ route('headers') }}" class="menu-link flex items-center gap-3">
        <i class="bi bi-card-image text-3xl"></i>
        <h1 class="text-2xl font-bold uppercase">Headers</h1>
    </a>
    <a href="{{ route('dashboard.profile') }}" class="menu-link flex items-center gap-3">
        <i class="bi bi-person-fill text-3xl"></i>
        <h1 class="text-2xl font-bold uppercase">Profile</h1>
    </a>
    <a href="{{ route('albums') }}" class="menu-link flex items-center gap-3">
        <i class="bi bi-disc-fill text-3xl"></i>
        <h1 class="text-2xl font-bold uppercase">Albums</h1>
    </a>
    <a href="{{ route('news') }}" class="menu-link flex items-center gap-3">
        <i class="bi bi-newspaper text-3xl"></i>
        <h1 class="text-2xl font-bold uppercase">News</h1>
    </a>
    <a href="{{ route('merchandise') }}" class="menu-link flex items-center gap-3">
        <i class="bi bi-basket-fill text-3xl"></i>
        <h1 class="text-2xl font-bold uppercase">Merchandise</h1>
    </a>
    <a href="{{ route('logout') }}" class="menu-link flex items-center gap-3 mt-4 text-[#F5B5B5]">
        <i class="bi bi-box-arrow-right text-3xl"></i>
        <h1 class="text-2xl font-bold uppercase">Logout</h1>
    </a>

    <button id="dashCloseBtn"
        class="fixed bottom-5 right-5 flex justify-center items-center h-14 w-14 rounded-full bg-white/10 border border-white/20 text-white text-3xl">
        <i class="bi bi-x"></i>
    </button>
</div>

<script>
    const dashOpenBtn = document.getElementById('dashOpenBtn');
    const dashCloseBtn = document.getElementById('dashCloseBtn');
    const dashMobileMenu = document.getElementById('dashMobileMenu');

    dashOpenBtn.addEventListener('click', () => {
        dashMobileMenu.classList.remove('translate-x-full');
    });

    dashCloseBtn.addEventListener('click', () => {
        dashMobileMenu.classList.add('translate-x-full');
    });
</script>
