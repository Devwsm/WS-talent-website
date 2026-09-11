<button command="show-modal" commandfor="updateBookingDialog{{ $item->id_booking }}"
    class="text-white/40 hover:text-white transition text-xs leading-none">
    <i class="bi bi-pencil-fill"></i>
</button>
<el-dialog>
    <dialog id="updateBookingDialog{{ $item->id_booking }}"
        class="fixed inset-0 w-full h-full bg-black/50 backdrop:bg-transparent p-0 overflow-y-auto">
        <!-- Centering -->
        <div class="flex min-h-full items-center justify-center p-4">
            <!-- Panel -->
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden transition-all">
                <!-- Header -->
                <div class="bg-blue-950 text-white px-6 py-4">
                    <h3 class="font-bold text-lg">UPDATE BOOKING & KONTAK</h3>
                </div>
                <!-- Form -->
                <form action="{{ route('booking.update', $item->id_booking) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <!-- Body -->
                    <div class="p-6 flex flex-col gap-4">
                        <div class="flex flex-col gap-2">
                            <label class="text-sm uppercase font-semibold">label / kategori</label>
                            <input type="text" name="label" value="{{ $item->label }}"
                                class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-800 outline-none">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-sm uppercase font-semibold">email</label>
                            <input type="email" name="email" value="{{ $item->email }}"
                                class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-800 outline-none">
                        </div>
                    </div>
                    <!-- Footer -->
                    <div class="flex justify-end gap-2 px-6 py-4 bg-gray-100">
                        <button type="button" command="close" commandfor="updateBookingDialog{{ $item->id_booking }}"
                            class="px-4 py-2 bg-gray-400 text-white rounded-lg">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-800 hover:bg-blue-700 text-white rounded-lg font-semibold">
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </dialog>
</el-dialog>
