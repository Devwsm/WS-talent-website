<button type="button" onclick="document.getElementById('updateHighlightDialog{{ $item->id_highlight }}').showModal()"
    class="text-white/40 hover:text-white transition text-xs leading-none">
    <i class="bi bi-pencil-fill"></i>
</button>
<dialog id="updateHighlightDialog{{ $item->id_highlight }}"
    class="fixed inset-0 m-0 w-screen h-screen max-w-none max-h-none bg-black/70 backdrop:bg-transparent p-0 overflow-y-auto">
    <!-- Centering -->
    <div class="min-h-screen w-full flex items-center justify-center p-4">
        <!-- Panel -->
        <div
            class="w-full max-w-lg max-h-[calc(100vh-2rem)] flex flex-col min-w-0 rounded-3xl border border-white/10 bg-black shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between gap-2 px-6 py-4 border-b border-white/10 bg-white/3 shrink-0">
                <h3 class="font-bold uppercase tracking-wide text-sm text-white">Update Highlight</h3>
                <button type="button"
                    onclick="document.getElementById('updateHighlightDialog{{ $item->id_highlight }}').close()"
                    class="text-white/40 hover:text-white transition">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <!-- Form -->
            <form action="{{ route('highlight.update', $item->id_highlight) }}" method="POST"
                class="flex flex-col flex-1 min-h-0 overflow-hidden">
                @csrf
                @method('PUT')
                <!-- Body -->
                <div class="flex-1 min-h-0 p-6 flex flex-col gap-4 overflow-y-auto">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold uppercase tracking-widest text-white/60">
                            Place (nama event) <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="place" value="{{ $item->place }}"
                            class="w-full min-w-0 bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold uppercase tracking-widest text-white/60">
                            Description (lokasi) <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="description" value="{{ $item->description }}"
                            class="w-full min-w-0 bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold uppercase tracking-widest text-white/60">
                            Year <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="year" value="{{ $item->year }}"
                            class="w-full min-w-0 bg-white/5 border border-white/15 text-white placeholder-white/30 p-3 rounded-lg focus:outline-none focus:border-red-900 focus:ring-1 focus:ring-red-900 transition">
                    </div>
                </div>
                <!-- Footer -->
                <div
                    class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 px-6 py-4 border-t border-white/10 bg-white/5 shrink-0">
                    <button type="button"
                        onclick="document.getElementById('updateHighlightDialog{{ $item->id_highlight }}').close()"
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
