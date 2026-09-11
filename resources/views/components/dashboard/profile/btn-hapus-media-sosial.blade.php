<form action="{{ route('media_sosial.hapus', $item->id_media_sosial) }}" method="POST" class="inline-flex"
    data-swal-confirm data-confirm-message="Apakah Anda yakin ingin menghapus media sosial ini?">
    @csrf
    @method('DELETE')
    <button type="submit" class="text-white/40 hover:text-red-400 transition text-xs leading-none"
        title="Hapus media sosial">
        <i class="bi bi-trash-fill"></i>
    </button>
</form>
