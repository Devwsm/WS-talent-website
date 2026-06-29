@extends('template/dashboardLayout')
@section('content')
    <div class="w-full p-8 gap-4 flex flex-col justify-center items-center">
        @include('components/dashboard/navbar')
        <div class="bg-black/80 text-white p-6 md:p-8 w-full md:w-176 rounded-lg">
            <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center mb-6">merchandise</h1>
            @include('components/errors')
            @include('components/success')
            <form action="{{ route('merchandise.tambah') }}" method="POST" enctype="multipart/form-data"
                class="flex flex-col gap-2">
                @csrf
                <label htmlFor="merchandise_name" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                    merchandise name
                </label>
                <input type="text" placeholder="https://..." name="merchandise_name"
                    class="bg-white/10 border border-white/20 text-white placeholder-gray-500 p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition" />
                <label htmlFor="link_merchandise" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                    link merch
                </label>
                <input type="text" placeholder="Nama" name="link_merchandise"
                    class="bg-white/10 border border-white/20 text-white placeholder-gray-500 p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition" />

                {{-- merchandise Cover --}}
                <div class="flex flex-col gap-1">
                    <label for="merchandise_cover" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                        merchandise Cover
                    </label>
                    <div class="relative">
                        <input type="file" id="merchandise_cover" name="merchandise_cover" accept="image/*"
                            class="w-full bg-white/10 border border-white/20 border-dashed text-gray-400 p-3 rounded-lg cursor-pointer
                            file:mr-4 file:py-1 file:px-3 file:rounded file:border-0
                            file:text-sm file:font-semibold file:bg-[#5E0006] file:text-white
                            hover:file:bg-[#5E0006]/80 transition"
                            onchange="previewImage(event)" />
                    </div>
                    {{-- Preview gambar --}}
                    <div id="image-preview-wrapper" class="hidden mt-2">
                        <p class="text-xs text-gray-400 mb-1">Preview:</p>
                        <img id="image-preview" src="#" alt="Preview"
                            class="max-h-48 rounded-lg border border-white/20 object-cover" />
                    </div>
                </div>

                {{-- Submit Button --}}
                <button type="submit"
                    class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-[#5E0006] hover:bg-[#5E0006]/70 active:scale-95 transition rounded-lg">
                    Upload merchandise
                </button>
            </form>
        </div>

        {{-- Preview gambar cover sebelum upload --}}
        <script>
            // Preview gambar cover sebelum upload
            function previewImage(event) {
                const file = event.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('image-preview').src = e.target.result;
                    document.getElementById('image-preview-wrapper').classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        </script>

        <div class="bg-black/80 text-white p-6 md:p-8 mb-24 w-full rounded-lg">
            <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center mb-6">merchandise list</h1>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                @foreach ($merchandise as $item)
                    <div class="grid grid-cols-1 gap-4 p-4 border-2 border-[#5E0006] items-center rounded-lg">
                        <div class="grid grid-cols-1 gap-4">
                            <div class="grid grid-cols-2 gap-4">
                                <!-- merchandise_name -->
                                <div class="text-center">
                                    <h1 class="text-xs uppercase text-white/60">merchandise name</h1>
                                    <h1 class="text-xs md:text-lg font-semibold">{{ $item->merchandise_name }}</h1>
                                </div>
                                <!-- link_merchandise -->
                                <div class="text-center">
                                    <h1 class="text-xs uppercase text-white/60">link merchandise</h1>
                                    <h1 class="text-xs md:text-lg font-semibold break-all">{{ $item->link_merchandise }}
                                    </h1>
                                </div>
                            </div>
                            <!-- merchandise cover -->
                            <div class="flex flex-col gap-2 text-center justify-center items-center">
                                <h1 class="text-xs uppercase text-white/60">merchandise cover</h1>
                                <img src="{{ Storage::url('merchandise/' . $item->merchandise_cover) }}"
                                    alt="{{ $item->merchandise_name }}" loading="lazy" decoding="async"
                                    class="object-cover aspect-square w-full max-w-xs transition duration-300 rounded-lg">
                            </div>
                        </div>
                        {{-- action button --}}
                        <div class="flex flex-col w-full gap-4 mt-4 text-center">
                            <h1 class="text-xs uppercase text-white/60">action button</h1>
                            <div class="flex gap-2 justify-center items-center">
                                @include('components.dashboard.btn-hapus-merchandise')
                                @include('components.dashboard.modal-edit-merchandise')
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
