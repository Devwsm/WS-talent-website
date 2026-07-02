{{-- FORM TAMBAH STATISTIK --}}
<form action="{{ route('statistik.tambah') }}" method="POST" enctype="multipart/form-data"
    class="flex flex-col gap-4 bg-white/5 border border-white/10 p-4 md:p-6 rounded-lg">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="flex flex-col gap-2">
            <label for="total" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                total
            </label>
            <input type="text" name="total" id="total" placeholder="Masukan total..."
                class="bg-white/10 border border-white/20 text-white placeholder-gray-500 p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition" />
        </div>
        <div class="flex flex-col gap-2">
            <label for="platform" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                platform
            </label>
            <input type="text" name="platform" id="platform" placeholder="Masukan nama platform..."
                class="bg-white/10 border border-white/20 text-white placeholder-gray-500 p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition" />
        </div>
    </div>
    <button type="submit"
        class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-[#5E0006] hover:bg-[#5E0006]/70 active:scale-95 transition rounded-lg">
        Tambah statistik
    </button>
</form>

{{-- LIST STATISTIK --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
    @forelse ($statistik as $item)
        <div class="flex flex-col gap-4 p-4 border-2 border-[#5E0006] rounded-lg hover:bg-white/5 transition">
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center">
                    <h3 class="text-xs uppercase text-white/60">total</h3>
                    <p class="text-xs md:text-lg font-semibold">{{ $item->total }}</p>
                </div>
                <div class="text-center">
                    <h3 class="text-xs uppercase text-white/60">platform</h3>
                    <p class="text-xs md:text-lg font-semibold">{{ $item->platform }}</p>
                </div>
            </div>

            <div class="flex flex-col gap-2 text-center border-t border-white/10 pt-4">
                <div class="flex gap-2 justify-center items-center">
                    @include('components.dashboard.profile.btn-hapus-statistik')
                    @include('components.dashboard.profile.modal-edit-statistik')
                </div>
            </div>
        </div>
    @empty
        <p class="col-span-full text-center text-white/50 py-6">Belum ada data statistik.</p>
    @endforelse
</div>
