<button type="button" onclick="document.getElementById('updateHeaderDialog{{ $item->id_header }}').showModal()"
    class="w-full text-white font-bold uppercase tracking-wide p-2 bg-[#5E0006] hover:bg-[#5E0006]/70 transition rounded-lg">
    <i class="bi bi-pencil-fill"></i>
</button>
<dialog id="updateHeaderDialog{{ $item->id_header }}"
    class="fixed inset-0 m-0 w-screen h-screen max-w-none max-h-none bg-black/70 backdrop:bg-transparent p-0 overflow-y-auto">
    <!-- Centering -->
    <div class="min-h-screen w-full flex items-center justify-center p-4">
        <!-- Panel -->
        <div
            class="w-full max-w-lg max-h-[calc(100vh-2rem)] flex flex-col min-w-0 rounded-3xl border border-white/10 bg-black shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between gap-2 px-6 py-4 border-b border-white/10 bg-white/3 shrink-0">
                <h3 class="font-bold uppercase tracking-wide text-sm text-white">Update Header</h3>
                <button type="button"
                    onclick="document.getElementById('updateHeaderDialog{{ $item->id_header }}').close()"
                    class="text-white/40 hover:text-white transition">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <!-- Form -->
            <form action="{{ route('headers.update', $item->id_header) }}" method="POST" enctype="multipart/form-data"
                class="flex flex-col flex-1 min-h-0 overflow-hidden">
                @csrf
                @method('PUT')
                <!-- Body -->
                <div class="flex-1 min-h-0 p-6 flex flex-col gap-4 overflow-y-auto">

                    <div class="flex flex-col gap-1.5">
                        <label for="header_color-{{ $item->id_header }}"
                            class="text-sm font-semibold uppercase tracking-widest text-white/60">
                            Header Color
                        </label>

                        <input id="header_color-{{ $item->id_header }}" type="color" name="header_color"
                            value="{{ $item->header_color }}"
                            class="h-12 w-full cursor-pointer rounded-lg border border-white/15 bg-white/5 p-1" />

                        <div class="flex items-center gap-3">
                            <div id="header_color_preview-{{ $item->id_header }}"
                                class="h-8 w-8 rounded-md border border-white/15"
                                style="background-color: {{ $item->header_color }};"></div>
                            <span class="text-sm text-white/60" id="header_color_value-{{ $item->id_header }}">
                                {{ $item->header_color }}
                            </span>
                        </div>
                    </div>

                    <script>
                        document.getElementById('header_color-{{ $item->id_header }}').addEventListener('input', function() {
                            document.getElementById('header_color_preview-{{ $item->id_header }}').style.backgroundColor = this
                                .value;
                            document.getElementById('header_color_value-{{ $item->id_header }}').textContent = this.value;
                        });
                    </script>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold uppercase tracking-widest text-white/60">
                            Header Title <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="header_title" value="{{ $item->header_title }}"
                            class="w-full min-w-0 bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold uppercase tracking-widest text-white/60">
                            Header Name <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="header_name" value="{{ $item->header_name }}"
                            class="w-full min-w-0 bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold uppercase tracking-widest text-white/60">
                            Header Description <span class="text-red-400">*</span>
                        </label>
                        <textarea name="header_description" rows="3"
                            class="w-full min-w-0 bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition resize-none">{{ $item->header_description }}</textarea>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold uppercase tracking-widest text-white/60">
                            Link Header <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="link_header" value="{{ $item->link_header }}"
                            class="w-full min-w-0 bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition">
                    </div>

                    {{-- header_img --}}
                    <div class="flex flex-col gap-1.5" data-media-input>
                        <label class="text-sm font-semibold uppercase tracking-widest text-white/60">
                            Header Image
                        </label>

                        @if ($item->header_img)
                            <img src="{{ Storage::url('header/img/' . $item->header_img) }}"
                                class="w-full max-h-32 rounded-lg border border-white/10 object-cover mb-1">
                        @endif

                        <input type="file" name="header_img" accept="image/jpeg,image/jpg,image/png"
                            data-preview-target="edit_header_img_{{ $item->id_header }}"
                            class="w-full min-w-0 bg-white/5 border border-white/15 border-dashed text-white/50 p-3 rounded-lg cursor-pointer
                                file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                file:text-sm file:font-semibold file:bg-red-950 file:text-white
                                hover:file:bg-red-900 transition"
                            onchange="new MediaPreview(this).handle(event)" />
                        <p class="text-xs text-white/30">Kosongkan kalau tidak ingin mengganti gambar.</p>

                        <div id="preview-edit_header_img_{{ $item->id_header }}" class="hidden mt-1">
                            <p class="text-xs text-white/30 mb-1">Preview baru:</p>
                            <img class="preview-el max-h-32 rounded-lg border border-white/10 object-cover" />
                            <video
                                class="preview-el max-h-32 rounded-lg border border-white/10 object-cover w-full hidden"
                                controls muted></video>
                        </div>
                        <p class="error-el hidden text-xs text-red-400"></p>
                    </div>

                    {{-- header_background (gambar atau video) --}}
                    <div class="flex flex-col gap-1.5" data-media-input>
                        <label class="text-sm font-semibold uppercase tracking-widest text-white/60">
                            Header Background
                        </label>

                        @php
                            $bgExtension = $item->header_background
                                ? strtolower(pathinfo($item->header_background, PATHINFO_EXTENSION))
                                : null;
                            $bgIsVideo = in_array($bgExtension, ['mp4', 'webm', 'mov']);
                        @endphp

                        @if ($item->header_background)
                            @if ($bgIsVideo)
                                <video src="{{ Storage::url('header/background/' . $item->header_background) }}"
                                    class="w-full max-h-32 rounded-lg border border-white/10 object-cover mb-1"
                                    controls muted></video>
                            @else
                                <img src="{{ Storage::url('header/background/' . $item->header_background) }}"
                                    class="w-full max-h-32 rounded-lg border border-white/10 object-cover mb-1">
                            @endif
                        @endif

                        <input type="file" name="header_background"
                            accept="image/jpeg,image/jpg,image/png,video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm"
                            data-preview-target="edit_header_background_{{ $item->id_header }}"
                            class="w-full min-w-0 bg-white/5 border border-white/15 border-dashed text-white/50 p-3 rounded-lg cursor-pointer
                                file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                file:text-sm file:font-semibold file:bg-red-950 file:text-white
                                hover:file:bg-red-900 transition"
                            onchange="new MediaPreview(this).handle(event)" />
                        <p class="text-xs text-white/30">
                            Gambar maks 1MB &middot; Video maks 25MB. Kosongkan jika tidak ingin mengganti.
                        </p>

                        <div id="preview-edit_header_background_{{ $item->id_header }}" class="hidden mt-1">
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
                        onclick="document.getElementById('updateHeaderDialog{{ $item->id_header }}').close()"
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
