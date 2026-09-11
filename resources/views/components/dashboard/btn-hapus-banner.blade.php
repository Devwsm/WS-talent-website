<form action="{{ route('banner.hapus', $item->id_banner) }}" method="POST" class="inline-flex"
    onsubmit="return confirm('Apakah Anda yakin ingin menghapus banner ini?');">
    @csrf
    @method('DELETE')
    <button type="submit" aria-label="Hapus" title="Hapus"
        class="text-white/40 hover:text-red-400 transition text-xs leading-none">
        <i class="bi bi-trash-fill"></i>
    </button>
</form>
