@extends('template/dashboardLayout')
@section('content')
    <div class="w-full p-8 gap-4 flex flex-col justify-center items-center">
        @include('components/dashboard/navbar')
        <div class="bg-black/80 text-white p-6 md:p-8 w-full md:w-176 rounded-lg">
            <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center mb-6">banner</h1>
            @include('components/errors')
            @include('components/success')
            <form action="{{ route('banner.tambah') }}" method="POST" enctype="multipart/form-data"
                class="flex flex-col gap-2">
                @csrf
                <label htmlFor="banner_name" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                    banner name
                </label>
                <input type="text" name="banner_name" placeholder="Masukan nama banner..."
                    class="bg-white/10 border border-white/20 text-white placeholder-gray-500 p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition" />
                <label htmlFor="link_banner" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                    link banner
                </label>
                <input type="text" name="link_banner" placeholder="https://..."
                    class="bg-white/10 border border-white/20 text-white placeholder-gray-500 p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition" />

                {{-- banner Cover --}}
                <div class="flex flex-col gap-1">
                    <label for="banner_cover" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                        banner Cover
                    </label>
                    <div class="relative">
                        <input type="file" id="banner_cover" name="banner_cover" accept="image/*"
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
                    Upload banner
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
            <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center mb-6">banner list</h1>
            <div class="grid grid-cols-1 gap-4 w-full">
                @foreach ($banner as $item)
                    <div class="grid grid-cols-1 gap-4 p-4 border-2 border-[#5E0006] items-center rounded-lg">
                        <div class="grid grid-cols-1 gap-4">
                            <div class="grid grid-cols-1 gap-4">
                                <!-- banner_name -->
                                <div class="text-center md:text-left">
                                    <h1 class="text-xs uppercase text-white/60">banner name</h1>
                                    <h1 class="text-xs md:text-lg font-semibold">{{ $item->banner_name }}</h1>
                                </div>
                                <!-- link_banner -->
                                <div class="text-center md:text-left">
                                    <h1 class="text-xs uppercase text-white/60">link banner</h1>
                                    <h1 class="text-xs md:text-lg font-semibold break-all">{{ $item->link_banner }}</h1>
                                </div>
                            </div>
                            <!-- banner cover -->
                            <div class="flex flex-col gap-2 text-center justify-center items-center">
                                <h1 class="text-xs uppercase text-white/60">banner cover</h1>
                                <img src="{{ Storage::url('banner/' . $item->banner_cover) }}"
                                    alt="{{ $item->banner_name }}" loading="lazy" decoding="async"
                                    class="object-cover w-full h-full transition duration-300 rounded-lg">
                            </div>
                        </div>
                        {{-- action button --}}
                        <div class="flex flex-col w-full gap-4 mt-4 text-center">
                            <h1 class="text-xs uppercase text-white/60">action button</h1>
                            <div class="flex gap-2 justify-center items-center">
                                @include('components.dashboard.btn-hapus-banner')
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
