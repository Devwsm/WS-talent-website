<form action="{{ route('booking.hapus', $item->id_booking) }}" method="POST" class="inline-flex" data-swal-confirm
    data-confirm-message="Apakah Anda yakin ingin menghapus booking & kontak ini?">
    @csrf
    @method('DELETE')
    <button type="submit" class="text-white/40 hover:text-red-400 transition text-xs leading-none"
        title="Hapus booking & kontak">
        <i class="bi bi-trash-fill"></i>
    </button>
</form>
