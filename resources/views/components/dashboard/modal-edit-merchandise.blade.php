<button command="show-modal" commandfor="updateDialog{{ $item->id_merchandise }}"
    class="w-full text-white font-bold uppercase tracking-wide p-2 bg-blue-950 hover:bg-blue-950/70 transition rounded-lg">
    <i class="bi bi-pencil-fill"></i>
</button>
<el-dialog>
    <dialog id="updateDialog{{ $item->id_merchandise }}"
        class="fixed inset-0 w-full h-full bg-black/50 backdrop:bg-transparent p-0 overflow-y-auto">
        <!-- Centering -->
        <div class="flex min-h-full items-center justify-center p-4">
            <!-- Panel -->
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden transition-all">
                <!-- Header -->
                <div class="bg-blue-950 text-white px-6 py-4">
                    <h3 class="font-bold text-lg">UPDATE DATA</h3>
                </div>
                <!-- Form -->
                <form action="{{ route('merchandise.update', $item->id_merchandise) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <!-- Body -->
                    <div class="p-6 flex flex-col gap-4">
                        <div class="flex flex-col gap-2">
                            <label class="text-sm uppercase font-semibold">merchandise name</label>
                            <input type="text" name="merchandise_name"
                                value="{{ $item->merchandise_name }}"
                                class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-800 outline-none">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-sm uppercase font-semibold">link merchandise</label>
                            <input type="text" name="link_merchandise"
                                value="{{ $item->link_merchandise }}"
                                class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-800 outline-none">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-sm uppercase font-semibold">merchandise cover</label>
                            <input type="file" name="merchandise_cover"
                                value="{{ $item->merchandise_cover }}"
                                class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-800 outline-none">
                        </div>
                    </div>
                    <!-- Footer -->
                    <div class="flex justify-end gap-2 px-6 py-4 bg-gray-100">
                        <button type="button" command="close" commandfor="updateDialog{{ $item->id_merchandise }}"
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
