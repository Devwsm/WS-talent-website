<form action="{{ route('statistik.hapus', $item->id_statistik) }}" method="POST" class="w-full"
    onsubmit="return confirm('Apakah Anda yakin ingin menghapus statistik ini?');">
    @csrf
    @method('DELETE')
    <button type="submit" class="w-full text-white font-bold uppercase tracking-wide p-2 bg-[#5E0006] hover:bg-[#5E0006]/70 transition rounded-lg">
        {{-- Hapus --}}
        <i class="bi bi-trash-fill"></i>
    </button>
</form>
