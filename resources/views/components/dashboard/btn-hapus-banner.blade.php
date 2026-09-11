<form action="{{ route('banner.hapus', $item->id_banner) }}" method="POST" class="inline-flex" data-swal-confirm
    data-confirm-message="Apakah Anda yakin ingin menghapus banner ini?">
    @csrf
    @method('DELETE')
    <button type="submit" class="text-white/40 hover:text-red-400 transition text-xs leading-none" title="Hapus banner">
        <i class="bi bi-trash-fill"></i>
    </button>
</form>
