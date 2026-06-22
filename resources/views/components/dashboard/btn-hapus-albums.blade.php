<form action="{{ route('albums.hapus', $item->id_albums) }}" method="POST" class="w-full"
    onsubmit="return confirm('Apakah Anda yakin ingin menghapus albums ini?');">
    @csrf
    @method('DELETE')
    <button type="submit" class="w-full text-white font-bold uppercase tracking-wide p-2 bg-[#5E0006] hover:bg-[#5E0006]/70 transition rounded-lg">
        {{-- Hapus --}}
        <i class="bi bi-trash-fill"></i>
    </button>
</form>
