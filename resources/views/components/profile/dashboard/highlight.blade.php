{{-- FORM TAMBAH HIGHLIGHT --}}
<form action="{{ route('highlight.tambah') }}" method="POST" enctype="multipart/form-data"
    class="flex flex-col gap-4 bg-white/5 border border-white/10 p-4 md:p-6 rounded-lg">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="flex flex-col gap-2">
            <label for="place" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                place
            </label>
            <input type="text" name="place" id="place" placeholder="Masukan place..."
                class="bg-white/10 border border-white/20 text-white placeholder-gray-500 p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition" />
        </div>
        <div class="flex flex-col gap-2">
            <label for="description" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                description
            </label>
            <input type="text" name="description" id="description" placeholder="Masukan description..."
                class="bg-white/10 border border-white/20 text-white placeholder-gray-500 p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition" />
        </div>
        <div class="flex flex-col gap-2">
            <label for="year" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                year
            </label>
            <input type="text" name="year" id="year" placeholder="Masukan year..."
                class="bg-white/10 border border-white/20 text-white placeholder-gray-500 p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition" />
        </div>
    </div>
    <button type="submit"
        class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-[#5E0006] hover:bg-[#5E0006]/70 active:scale-95 transition rounded-lg">
        Tambah highlight
    </button>
</form>

{{-- LIST HIGHLIGHT --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
    @forelse ($highlight as $item)
        <div class="flex flex-col gap-4 p-4 border-2 border-[#5E0006] rounded-lg hover:bg-white/5 transition">
            <div class="grid grid-cols-3 gap-4">
                <div class="text-center">
                    <h3 class="text-xs uppercase text-white/60">place</h3>
                    <p class="text-xs md:text-lg font-semibold">{{ $item->place }}</p>
                </div>
                <div class="text-center">
                    <h3 class="text-xs uppercase text-white/60">description</h3>
                    <p class="text-xs md:text-lg font-semibold">{{ $item->description }}</p>
                </div>
                <div class="text-center">
                    <h3 class="text-xs uppercase text-white/60">year</h3>
                    <p class="text-xs md:text-lg font-semibold">{{ $item->year }}</p>
                </div>
            </div>
            <div class="flex flex-col gap-2 text-center border-t border-white/10 pt-4">
                <div class="flex gap-2 justify-center items-center">
                    @include('components.dashboard.profile.btn-hapus-highlight')
                    @include('components.dashboard.profile.modal-edit-highlight')
                </div>
            </div>
        </div>
    @empty
        <p class="col-span-full text-center text-white/50 py-6">Belum ada data highlight.</p>
    @endforelse
</div>
