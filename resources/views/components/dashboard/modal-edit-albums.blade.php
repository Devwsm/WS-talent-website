<button command="show-modal" commandfor="updateDialog{{ $item->id_albums }}"
    class="w-full text-white font-bold uppercase tracking-wide p-2 bg-[#5E0006] hover:bg-[#5E0006]/70 transition rounded-lg">
    <i class="bi bi-pencil-fill"></i>
</button>
<el-dialog>
    <dialog id="updateDialog{{ $item->id_albums }}"
        class="fixed inset-0 w-full h-full bg-black/70 backdrop:bg-transparent p-0 overflow-y-auto">
        <!-- Centering -->
        <div class="flex min-h-full items-center justify-center p-4">
            <!-- Panel -->
            <div
                class="w-full max-w-lg max-h-[90vh] flex flex-col rounded-3xl border border-white/10 bg-black shadow-xl overflow-hidden">
                <!-- Header -->
                <div
                    class="flex items-center justify-between gap-2 px-6 py-4 border-b border-white/10 bg-white/3 shrink-0">
                    <h3 class="font-bold uppercase tracking-wide text-sm text-white">Update Album</h3>
                    <button type="button" command="close" commandfor="updateDialog{{ $item->id_albums }}"
                        class="text-white/40 hover:text-white transition">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <!-- Form -->
                <form action="{{ route('albums.update', $item->id_albums) }}" method="POST"
                    enctype="multipart/form-data" class="flex flex-col flex-1 min-h-0">
                    @csrf
                    @method('PUT')
                    <!-- Body -->
                    <div class="p-6 flex flex-col gap-4 overflow-y-auto">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Albums Name <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="albums_name" value="{{ $item->albums_name }}"
                                class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Link Spotify <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="link_spotify" value="{{ $item->link_spotify }}"
                                class="bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-semibold uppercase tracking-widest text-white/60">
                                Albums Cover
                            </label>

                            @if ($item->albums_cover)
                                <img src="{{ Storage::url('albums/' . $item->albums_cover) }}"
                                    class="max-h-32 rounded-lg border border-white/10 object-cover mb-1">
                            @endif

                            <input type="file" name="albums_cover"
                                class="w-full bg-white/5 border border-white/15 border-dashed text-white/50 p-3 rounded-lg cursor-pointer
                                file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                file:text-sm file:font-semibold file:bg-red-950 file:text-white
                                hover:file:bg-red-900 transition">
                            <p class="text-xs text-white/30">Kosongkan kalau tidak ingin mengganti gambar.</p>
                        </div>
                    </div>
                    <!-- Footer -->
                    <div class="flex justify-end gap-2 px-6 py-4 border-t border-white/10 bg-white/3 shrink-0">
                        <button type="button" command="close" commandfor="updateDialog{{ $item->id_albums }}"
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
