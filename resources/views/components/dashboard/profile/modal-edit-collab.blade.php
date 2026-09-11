<button command="show-modal" commandfor="updateCollabDialog{{ $item->id_collab }}"
    class="text-white/40 hover:text-white transition text-xs leading-none">
    <i class="bi bi-pencil-fill"></i>
</button>
<el-dialog>
    <dialog id="updateCollabDialog{{ $item->id_collab }}"
        class="fixed inset-0 w-full h-full bg-black/70 backdrop:bg-transparent p-0 overflow-y-auto">
        <!-- Centering -->
        <div class="flex min-h-full items-center justify-center p-4">
            <!-- Panel -->
            <div
                class="w-full max-w-lg max-h-[90vh] flex flex-col rounded-3xl border border-white/10 bg-black shadow-xl overflow-hidden">
                <!-- Header -->
                <div
                    class="flex items-center justify-between gap-2 px-6 py-4 border-b border-white/10 bg-white/3 shrink-0">
                    <h3 class="font-bold uppercase tracking-wide text-sm text-white">Update Kolaborasi</h3>
                    <button type="button" command="close" commandfor="updateCollabDialog{{ $item->id_collab }}"
                        class="text-white/40 hover:text-white transition">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <!-- Form -->
                <form action="{{ route('collab.update', $item->id_collab) }}" method="POST"
                    class="flex flex-col flex-1 min-h-0">
                    @csrf
                    @method('PUT')
                    <!-- Body -->
                    <div class="p-6 flex flex-col gap-4 overflow-y-auto">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Nama <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="nama" value="{{ $item->nama }}"
                                class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Role / Deskripsi <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="role" value="{{ $item->role }}"
                                class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition">
                        </div>
                    </div>
                    <!-- Footer -->
                    <div class="flex justify-end gap-2 px-6 py-4 border-t border-white/10 bg-white/3 shrink-0">
                        <button type="button" command="close" commandfor="updateCollabDialog{{ $item->id_collab }}"
                            class="px-4 py-2 rounded-lg border border-white/15 text-white/70 hover:bg-white/5 transition font-semibold">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 rounded-lg bg-red-950 hover:bg-red-900 text-white font-semibold transition">
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </dialog>
</el-dialog>
