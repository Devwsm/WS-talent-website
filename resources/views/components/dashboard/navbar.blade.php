{{-- desktop --}}
<div class="nav z-50 fixed bottom-5 left-1/2 -translate-x-1/2 hidden md:flex gap-2">
    <div class="nav-links flex justify-center items-center p-6 rounded-lg bg-[#1A1A1B] shrink-0">
        <a href="{{ route('dashboard') }}" class="text-[#F5F1E6] hover:text-[#d5ccb3] text-[2rem]">
            <i class="bi bi-house-door-fill"></i>
        </a>
    </div>

    <div class="relative shrink-0">
        <button id="contentBtn"
            class="nav-links flex justify-center items-center p-6 rounded-lg bg-[#1A1A1B] text-[#F5F1E6] hover:text-[#d5ccb3] text-[2rem]">
            <i class="bi bi-grid-fill"></i>
        </button>
        <div id="contentMenu"
            class="hidden absolute bottom-full mb-3 left-1/2 -translate-x-1/2 bg-[#1A1A1B] rounded-lg p-3 flex-col gap-1 w-52 shadow-xl">
            <a href="{{ route('banner') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-md text-[#F5F1E6] hover:bg-white/10"><i
                    class="bi bi-images text-lg"></i><span>Banner</span></a>
            <a href="{{ route('headers') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-md text-[#F5F1E6] hover:bg-white/10"><i
                    class="bi bi-card-image text-lg"></i><span>Headers</span></a>
            <a href="{{ route('dashboard.profile') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-md text-[#F5F1E6] hover:bg-white/10"><i
                    class="bi bi-person-fill text-lg"></i><span>Profile</span></a>
            <a href="{{ route('color_pages.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-md text-[#F5F1E6] hover:bg-white/10"><i
                    class="bi bi-palette-fill text-lg"></i><span>Warna Web</span></a>
            <div class="h-px bg-white/10 my-1"></div>
            <a href="{{ route('albums') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-md text-[#B71C1C] hover:bg-white/10"><i
                    class="bi bi-disc-fill text-lg"></i><span>Albums</span></a>
            <a href="{{ route('news') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-md text-[#B71C1C] hover:bg-white/10"><i
                    class="bi bi-newspaper text-lg"></i><span>News</span></a>
            <a href="{{ route('merchandise') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-md text-[#B71C1C] hover:bg-white/10"><i
                    class="bi bi-basket-fill text-lg"></i><span>Merchandise</span></a>
        </div>
    </div>

    <div class="nav-links flex justify-center items-center p-6 rounded-lg bg-[#1A1A1B] shrink-0">
        <a href="{{ route('logout') }}" class="text-[#B71C1C] hover:text-[#891212] text-[2rem]">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</div>

{{-- Mobile trigger --}}
<button id="dashOpenBtn"
    class="fixed z-50 bottom-5 right-5 md:hidden flex justify-center items-center h-14 w-14 rounded-full bg-[#1A1A1B] text-[#F5F1E6] text-2xl shadow-lg">
    <i class="bi bi-list"></i>
</button>

{{-- Mobile Fullscreen --}}
<div id="dashMobileMenu"
    class="fixed inset-0 bg-[#5E0006] text-white z-60 flex flex-col items-center justify-center gap-5
    translate-x-full transition-transform duration-300 md:hidden overflow-y-auto py-10">

    <a href="{{ route('dashboard') }}" class="menu-link flex items-center gap-3"><i
            class="bi bi-house-door-fill text-3xl"></i>
        <h1 class="text-2xl font-bold uppercase">Dashboard</h1>
    </a>

    <span class="text-xs tracking-widest uppercase text-white/50 mt-2">Konten</span>
    <a href="{{ route('banner') }}" class="menu-link flex items-center gap-3"><i class="bi bi-images text-3xl"></i>
        <h1 class="text-2xl font-bold uppercase">Banner</h1>
    </a>
    <a href="{{ route('headers') }}" class="menu-link flex items-center gap-3"><i
            class="bi bi-card-image text-3xl"></i>
        <h1 class="text-2xl font-bold uppercase">Headers</h1>
    </a>
    <a href="{{ route('dashboard.profile') }}" class="menu-link flex items-center gap-3"><i
            class="bi bi-person-fill text-3xl"></i>
        <h1 class="text-2xl font-bold uppercase">Profile</h1>
    </a>
    <a href="{{ route('color_pages.index') }}" class="menu-link flex items-center gap-3"><i
            class="bi bi-palette-fill text-3xl"></i>
        <h1 class="text-2xl font-bold uppercase">Warna Web</h1>
    </a>
    <a href="{{ route('albums') }}" class="menu-link flex items-center gap-3"><i class="bi bi-disc-fill text-3xl"></i>
        <h1 class="text-2xl font-bold uppercase">Albums</h1>
    </a>
    <a href="{{ route('news') }}" class="menu-link flex items-center gap-3"><i class="bi bi-newspaper text-3xl"></i>
        <h1 class="text-2xl font-bold uppercase">News</h1>
    </a>
    <a href="{{ route('merchandise') }}" class="menu-link flex items-center gap-3"><i
            class="bi bi-basket-fill text-3xl"></i>
        <h1 class="text-2xl font-bold uppercase">Merchandise</h1>
    </a>

    <a href="{{ route('logout') }}" class="menu-link flex items-center gap-3 mt-4 text-[#F5B5B5]"><i
            class="bi bi-box-arrow-right text-3xl"></i>
        <h1 class="text-2xl font-bold uppercase">Logout</h1>
    </a>

    <button id="dashCloseBtn"
        class="fixed bottom-5 right-5 flex justify-center items-center h-14 w-14 rounded-full bg-white/10 border border-white/20 text-white text-3xl">
        <i class="bi bi-x"></i>
    </button>
</div>

<script>
    document.getElementById('dashOpenBtn').addEventListener('click', () =>
        document.getElementById('dashMobileMenu').classList.remove('translate-x-full'));

    document.getElementById('dashCloseBtn').addEventListener('click', () =>
        document.getElementById('dashMobileMenu').classList.add('translate-x-full'));

    const contentBtn = document.getElementById('contentBtn');
    const contentMenu = document.getElementById('contentMenu');

    contentBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        contentMenu.classList.toggle('hidden');
        contentMenu.classList.toggle('flex');
    });

    document.addEventListener('click', (e) => {
        if (!contentMenu.contains(e.target) && !contentBtn.contains(e.target)) {
            contentMenu.classList.add('hidden');
            contentMenu.classList.remove('flex');
        }
    });
</script>
