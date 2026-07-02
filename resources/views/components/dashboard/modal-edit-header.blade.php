<button command="show-modal" commandfor="updateHeaderDialog{{ $item->id_header }}"
    class="w-full text-white font-bold uppercase tracking-wide p-2 bg-blue-950 hover:bg-blue-950/70 transition rounded-lg">
    <i class="bi bi-pencil-fill"></i>
</button>
<el-dialog>
    <dialog id="updateHeaderDialog{{ $item->id_header }}"
        class="fixed inset-0 w-full h-full bg-black/50 backdrop:bg-transparent p-0 overflow-y-auto">
        <!-- Centering -->
        <div class="flex min-h-full items-center justify-center p-4">
            <!-- Panel -->
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden transition-all">
                <!-- Header -->
                <div class="bg-blue-950 text-white px-6 py-4">
                    <h3 class="font-bold text-lg">UPDATE HEADER</h3>
                </div>
                <!-- Form -->
                <form action="{{ route('headers.update', $item->id_header) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <!-- Body -->
                    <div class="p-6 flex flex-col gap-4 max-h-[70vh] overflow-y-auto">

                        <div class="flex flex-col gap-2">
                            <label for="header_color-{{ $item->id_highlight }}" class="text-sm uppercase font-semibold">
                                header color
                            </label>

                            <input id="header_color-{{ $item->id_highlight }}" type="color" name="header_color"
                                value="{{ $item->header_color }}"
                                class="h-12 w-full cursor-pointer rounded-lg border border-white/20 bg-white/10 p-1" />

                            <div class="flex items-center gap-3">
                                <div id="header_color_preview-{{ $item->id_highlight }}"
                                    class="h-8 w-8 rounded-md border border-white/20"
                                    style="background-color: {{ $item->header_color }};"></div>
                                <span class="text-sm text-gray-300" id="header_color_value-{{ $item->id_highlight }}">
                                    {{ $item->header_color }}
                                </span>
                            </div>
                        </div>

                        <script>
                            document.getElementById('header_color-{{ $item->id_highlight }}').addEventListener('input', function() {
                                document.getElementById('header_color_preview-{{ $item->id_highlight }}').style.backgroundColor = this
                                    .value;
                                document.getElementById('header_color_value-{{ $item->id_highlight }}').textContent = this.value;
                            });
                        </script>
                        
                        <div class="flex flex-col gap-2">
                            <label class="text-sm uppercase font-semibold">header title</label>
                            <input type="text" name="header_title" value="{{ $item->header_title }}"
                                class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-800 outline-none">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-sm uppercase font-semibold">header name</label>
                            <input type="text" name="header_name" value="{{ $item->header_name }}"
                                class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-800 outline-none">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-sm uppercase font-semibold">header description</label>
                            <textarea name="header_description" rows="3"
                                class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-800 outline-none">{{ $item->header_description }}</textarea>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-sm uppercase font-semibold">link header</label>
                            <input type="text" name="link_header" value="{{ $item->link_header }}"
                                class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-800 outline-none">
                        </div>

                        {{-- header_img --}}
                        <div class="flex flex-col gap-2" data-media-input>
                            <label class="text-sm uppercase font-semibold">header image</label>

                            @if ($item->header_img)
                                <img src="{{ Storage::url('header/img/' . $item->header_img) }}"
                                    class="max-h-32 rounded-lg border object-cover mb-1">
                            @endif

                            <input type="file" name="header_img" accept="image/jpeg,image/jpg,image/png"
                                data-preview-target="edit_header_img_{{ $item->id_header }}"
                                class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-800 outline-none"
                                onchange="new MediaPreview(this).handle(event)" />
                            <p class="text-xs text-gray-500">Kosongkan jika tidak ingin mengganti gambar.</p>

                            <div id="preview-edit_header_img_{{ $item->id_header }}" class="hidden mt-1">
                                <p class="text-xs text-gray-500 mb-1">Preview baru:</p>
                                <img class="preview-el max-h-32 rounded-lg border object-cover" />
                                <video class="preview-el max-h-32 rounded-lg border object-cover w-full hidden" controls
                                    muted></video>
                            </div>
                            <p class="error-el hidden text-xs text-red-500"></p>
                        </div>

                        {{-- header_background (gambar atau video) --}}
                        <div class="flex flex-col gap-2" data-media-input>
                            <label class="text-sm uppercase font-semibold">header background</label>

                            @php
                                $bgExtension = $item->header_background
                                    ? strtolower(pathinfo($item->header_background, PATHINFO_EXTENSION))
                                    : null;
                                $bgIsVideo = in_array($bgExtension, ['mp4', 'webm', 'mov']);
                            @endphp

                            @if ($item->header_background)
                                @if ($bgIsVideo)
                                    <video src="{{ Storage::url('header/background/' . $item->header_background) }}"
                                        class="max-h-32 rounded-lg border object-cover mb-1 w-full" controls
                                        muted></video>
                                @else
                                    <img src="{{ Storage::url('header/background/' . $item->header_background) }}"
                                        class="max-h-32 rounded-lg border object-cover mb-1">
                                @endif
                            @endif

                            <input type="file" name="header_background"
                                accept="image/jpeg,image/jpg,image/png,video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm"
                                data-preview-target="edit_header_background_{{ $item->id_header }}"
                                class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-800 outline-none"
                                onchange="new MediaPreview(this).handle(event)" />
                            <p class="text-xs text-gray-500">
                                Gambar maks 1MB &middot; Video maks 25MB. Kosongkan jika tidak ingin mengganti.
                            </p>

                            <div id="preview-edit_header_background_{{ $item->id_header }}" class="hidden mt-1">
                                <p class="text-xs text-gray-500 mb-1">Preview baru:</p>
                                <img class="preview-el max-h-32 rounded-lg border object-cover" />
                                <video class="preview-el max-h-32 rounded-lg border object-cover w-full hidden" controls
                                    muted></video>
                            </div>
                            <p class="error-el hidden text-xs text-red-500"></p>
                        </div>

                    </div>
                    <!-- Footer -->
                    <div class="flex justify-end gap-2 px-6 py-4 bg-gray-100">
                        <button type="button" command="close" commandfor="updateHeaderDialog{{ $item->id_header }}"
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
