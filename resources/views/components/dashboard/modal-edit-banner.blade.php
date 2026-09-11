<button type="button" onclick="document.getElementById('updateDialog{{ $item->id_banner }}').showModal()"
    class="w-full text-white font-bold uppercase tracking-wide p-2 bg-[#5E0006] hover:bg-[#5E0006]/70 transition rounded-lg">
    <i class="bi bi-pencil-fill"></i>
</button>
<dialog id="updateDialog{{ $item->id_banner }}"
    class="fixed inset-0 m-0 w-screen h-screen max-w-none max-h-none bg-black/70 backdrop:bg-transparent p-0 overflow-y-auto">
    <!-- Centering -->
    <div class="min-h-screen w-full flex items-center justify-center p-4">
        <!-- Panel -->
        <div
            class="w-full max-w-lg max-h-[calc(100vh-2rem)] flex flex-col min-w-0 rounded-3xl border border-white/10 bg-black shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between gap-2 px-6 py-4 border-b border-white/10 bg-white/3 shrink-0">
                <h3 class="font-bold uppercase tracking-wide text-sm text-white">Update Banner</h3>
                <button type="button" onclick="document.getElementById('updateDialog{{ $item->id_banner }}').close()"
                    class="text-white/40 hover:text-white transition">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <!-- Form -->
            <form action="{{ route('banner.update', $item->id_banner) }}" method="POST" enctype="multipart/form-data"
                class="flex flex-col flex-1 min-h-0 overflow-hidden">
                @csrf
                @method('PUT')
                <!-- Body -->
                <div class="flex-1 min-h-0 p-6 flex flex-col gap-4 overflow-y-auto">
                    <div class="banner_name flex flex-col gap-1.5">
                        <label class="text-sm font-semibold uppercase tracking-widest text-white/60">
                            Banner Name <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="banner_name" value="{{ $item->banner_name }}"
                            class="w-full min-w-0 bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition">
                    </div>
                    <div class="link_banner flex flex-col gap-1.5">
                        <label class="text-sm font-semibold uppercase tracking-widest text-white/60">
                            Banner Link <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="link_banner" value="{{ $item->link_banner }}"
                            class="w-full min-w-0 bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition">
                    </div>
                    {{-- banner_cover --}}
                    <div class="flex flex-col gap-1.5" data-media-input>
                        <label class="text-sm font-semibold uppercase tracking-widest text-white/60">
                            Banner Image
                        </label>

                        @if ($item->banner_cover)
                            <img src="{{ Storage::url('banner/' . $item->banner_cover) }}"
                                class="w-full max-h-32 rounded-lg border border-white/10 object-cover mb-1">
                        @endif

                        <input type="file" name="banner_cover" accept="image/jpeg,image/jpg,image/png"
                            data-preview-target="edit_banner_cover_{{ $item->id_banner }}"
                            class="w-full min-w-0 bg-white/5 border border-white/15 border-dashed text-white/50 p-3 rounded-lg cursor-pointer
                                file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                file:text-sm file:font-semibold file:bg-red-950 file:text-white
                                hover:file:bg-red-900 transition"
                            onchange="new MediaPreview(this).handle(event)" />
                        <p class="text-xs text-white/30">Kosongkan kalau tidak ingin mengganti gambar.</p>

                        <div id="preview-edit_banner_cover_{{ $item->id_banner }}" class="hidden mt-1">
                            <p class="text-xs text-white/30 mb-1">Preview baru:</p>
                            <img class="preview-el max-h-32 rounded-lg border border-white/10 object-cover" />
                            <video
                                class="preview-el max-h-32 rounded-lg border border-white/10 object-cover w-full hidden"
                                controls muted></video>
                        </div>
                        <p class="error-el hidden text-xs text-red-400"></p>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 px-6 py-4 border-t border-white/10 bg-white/5 shrink-0">
                    <button type="button"
                        onclick="document.getElementById('updateDialog{{ $item->id_banner }}').close()"
                        class="w-full sm:w-auto px-4 py-2 rounded-lg border border-white/15 text-white/70 hover:bg-white/5 transition font-semibold">
                        Batal
                    </button>
                    <button type="submit"
                        class="w-full sm:w-auto px-4 py-2 rounded-lg bg-red-950 hover:bg-red-900 text-white font-semibold transition">
                        Update
                    </button>
                </div>

            </form>
        </div>
    </div>
</dialog>
