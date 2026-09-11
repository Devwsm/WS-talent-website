<form action="{{ route('media_coverage.hapus', $item->id_media_coverage) }}" method="POST" class="inline-flex"
    onsubmit="return confirm('Apakah Anda yakin ingin menghapus media coverage ini?');">
    @csrf
    @method('DELETE')
    <button type="submit" class="text-white/40 hover:text-red-400 transition text-xs leading-none">
        <i class="bi bi-trash-fill"></i>
    </button>
</form>
