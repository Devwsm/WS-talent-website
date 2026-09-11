<form action="{{ route('genre.hapus', $item->id_genre) }}" method="POST" class="inline-flex"
    onsubmit="return confirm('Apakah Anda yakin ingin menghapus genre ini?');">
    @csrf
    @method('DELETE')
    <button type="submit" class="text-white/40 hover:text-red-400 transition text-xs leading-none">
        <i class="bi bi-trash-fill"></i>
    </button>
</form>
