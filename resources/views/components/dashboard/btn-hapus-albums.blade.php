<form action="{{ route('albums.hapus', $item->id_albums) }}" method="POST" class="inline-flex"
    onsubmit="return confirm('Apakah Anda yakin ingin menghapus album ini?');">
    @csrf
    @method('DELETE')
    <button type="submit" aria-label="Hapus" title="Hapus"
        class="text-white/40 hover:text-red-400 transition text-xs leading-none">
        <i class="bi bi-trash-fill"></i>
    </button>
</form>
