<form action="{{ route('statistik.hapus', $item->id_statistik) }}" method="POST" class="inline-flex"
    onsubmit="return confirm('Apakah Anda yakin ingin menghapus statistik ini?');">
    @csrf
    @method('DELETE')
    <button type="submit" class="text-white/40 hover:text-red-400 transition text-xs leading-none">
        <i class="bi bi-trash-fill"></i>
    </button>
</form>
