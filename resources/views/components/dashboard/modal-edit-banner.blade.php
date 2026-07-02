<button command="show-modal" commandfor="updateDialog{{ $item->id_banner }}"
    class="w-full text-white font-bold uppercase tracking-wide p-2 bg-blue-950 hover:bg-blue-950/70 transition rounded-lg">
    <i class="bi bi-pencil-fill"></i>
</button>
<el-dialog>
    <dialog id="updateDialog{{ $item->id_banner }}"
        class="fixed inset-0 w-full h-full bg-black/50 backdrop:bg-transparent p-0 overflow-y-auto">
        <!-- Centering -->
        <div class="flex min-h-full items-center justify-center p-4">
            <!-- Panel -->
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden transition-all">
                <!-- banner -->
                <div class="bg-blue-950 text-white px-6 py-4">
                    <h3 class="font-bold text-lg">UPDATE DATA</h3>
                </div>
                <!-- Form -->
                <form action="{{ route('banner.update', $item->id_banner) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <!-- Body -->
                    <div class="p-6 flex flex-col gap-4">
                        <div class="grid grid-cols-1 gap-4">
                            <div class="banner_name flex flex-col gap-2">
                                <label class="text-sm uppercase font-semibold">banner name</label>
                                <input type="text" name="banner_name" value="{{ $item->banner_name }}"
                                    class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-800 outline-none">
                            </div>
                            <div class="link_banner flex flex-col gap-2">
                                <label class="text-sm uppercase font-semibold">banner link</label>
                                <input type="text" name="link_banner" value="{{ $item->link_banner }}"
                                    class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-800 outline-none">
                            </div>
                            {{-- banner_cover --}}
                            <div class="flex flex-col gap-2" data-media-input>
                                <label class="text-sm uppercase font-semibold">banner image</label>

                                @if ($item->banner_cover)
                                    <img src="{{ Storage::url('banner/' . $item->banner_cover) }}"
                                        class="max-h-32 rounded-lg border object-cover mb-1">
                                @endif

                                <input type="file" name="banner_cover" accept="image/jpeg,image/jpg,image/png"
                                    data-preview-target="edit_banner_cover_{{ $item->id_banner }}"
                                    class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-800 outline-none"
                                    onchange="new MediaPreview(this).handle(event)" />
                                <p class="text-xs text-gray-500">Kosongkan jika tidak ingin mengganti gambar.</p>

                                <div id="preview-edit_banner_cover_{{ $item->id_banner }}" class="hidden mt-1">
                                    <p class="text-xs text-gray-500 mb-1">Preview baru:</p>
                                    <img class="preview-el max-h-32 rounded-lg border object-cover" />
                                    <video class="preview-el max-h-32 rounded-lg border object-cover w-full hidden"
                                        controls muted></video>
                                </div>
                                <p class="error-el hidden text-xs text-red-500"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-2 px-6 py-4 bg-gray-100">
                        <button type="button" command="close" commandfor="updateDialog{{ $item->id_banner }}"
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
