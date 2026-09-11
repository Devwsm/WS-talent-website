<form action="{{ route('merchandise.hapus', $item->id_merchandise) }}" method="POST" class="inline-flex" data-swal-confirm
    data-confirm-message="Apakah Anda yakin ingin menghapus merchandise ini?">
    @csrf
    @method('DELETE')
    <button type="submit" class="text-white/40 hover:text-red-400 transition text-xs leading-none"
        title="Hapus merchandise">
        <i class="bi bi-trash-fill"></i>
    </button>
</form>
