@extends('template/dashboardLayout')
@section('content')
    <div class="min-h-screen w-full grid grid-cols-1 lg:grid-cols-2 bg-white">

        {{-- Panel kiri: identitas brand (jadi hero band di mobile) --}}
        <div class="relative bg-black text-white flex flex-col justify-between overflow-hidden px-8 py-10 lg:px-16 lg:py-16">
            {{-- Tekstur dot grid halus biar gak polos tapi tetap netral hitam-putih --}}
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-[0.08]"
                style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;">
            </div>
            <div aria-hidden="true"
                class="pointer-events-none absolute -right-24 -top-24 w-72 h-72 rounded-full border border-white/10">
            </div>

            <a href="{{ route('home') }}"
                class="relative z-10 inline-flex items-center gap-2 text-sm text-white/60 hover:text-white transition-colors w-fit">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke beranda
            </a>

            <div class="relative z-10">
                <h1 class="text-4xl lg:text-6xl font-bold uppercase leading-[0.95] tracking-tight">
                    Whisnu<br>Santika
                </h1>
                <p class="mt-4 text-white/50 text-sm lg:text-base max-w-xs">
                    Panel admin untuk mengelola konten, rilisan, dan profil artis.
                </p>
            </div>

            <p class="relative z-10 text-xs text-white/30">&copy; {{ date('Y') }} Whisnu Santika. All rights
                reserved.</p>
        </div>

        {{-- Panel kanan: form login --}}
        <div class="flex flex-col justify-center items-center px-6 py-12 lg:px-16 bg-white">
            <div class="w-full max-w-sm">
                <h2 class="text-2xl lg:text-3xl font-bold text-black">Masuk</h2>
                <p class="text-sm text-black/50 mt-1 mb-8">Masukkan kredensial admin untuk melanjutkan.</p>

                @include('components/errors')

                <form action="{{ route('login.proses') }}" method="POST" class="flex flex-col gap-5">
                    @csrf

                    <div class="flex flex-col gap-1.5">
                        <label for="username" class="text-sm font-semibold text-black">Username</label>
                        <input type="text" id="username" name="username" placeholder="Masukkan username"
                            autocomplete="username" required value="{{ old('username') }}"
                            class="border border-black/20 focus:border-black outline-none p-3 rounded-lg text-black placeholder:text-black/30 transition-colors">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="password" class="text-sm font-semibold text-black">Password</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" placeholder="Masukkan password"
                                autocomplete="current-password" required
                                class="w-full border border-black/20 focus:border-black outline-none p-3 pr-11 rounded-lg text-black placeholder:text-black/30 transition-colors">
                            <button type="button" id="togglePassword" aria-label="Tampilkan password" aria-pressed="false"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-black/40 hover:text-black transition-colors">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full text-white font-bold uppercase tracking-wide p-3 mt-2 bg-black hover:bg-black/80 active:scale-[0.98] transition rounded-lg">
                        Login
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const pwInput = document.getElementById("password");
        const toggleBtn = document.getElementById("togglePassword");

        toggleBtn?.addEventListener("click", () => {
            const isHidden = pwInput.type === "password";
            pwInput.type = isHidden ? "text" : "password";
            toggleBtn.querySelector("i").className = isHidden ? "bi bi-eye-slash" : "bi bi-eye";
            toggleBtn.setAttribute("aria-label", isHidden ? "Sembunyikan password" : "Tampilkan password");
            toggleBtn.setAttribute("aria-pressed", isHidden ? "true" : "false");
        });
    </script>
@endsection
