@extends('template/dashboardLayout')
@section('content')
    <div class="w-full p-8 gap-4 flex flex-col justify-center items-center">
        @include('components/dashboard/navbar')
        <div class="bg-black/80 text-white p-6 md:p-8 w-full md:w-176 rounded-lg">
            <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center mb-6">statistik</h1>
            @include('components/errors')
            @include('components/success')
            <form action="{{ route('statistik.tambah') }}" method="POST" enctype="multipart/form-data"
                class="flex flex-col gap-2">
                @csrf
                <label htmlFor="total" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                    total
                </label>
                <input type="text" name="total" placeholder="Masukan total..."
                    class="bg-white/10 border border-white/20 text-white placeholder-gray-500 p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition" />
                <label htmlFor="platform" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                    platform
                </label>
                <input type="text" name="platform" placeholder="Masukan nama platform..."
                    class="bg-white/10 border border-white/20 text-white placeholder-gray-500 p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition" />

                {{-- Submit Button --}}
                <button type="submit"
                    class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-[#5E0006] hover:bg-[#5E0006]/70 active:scale-95 transition rounded-lg">
                    Upload album
                </button>
            </form>
        </div>

        <div class="bg-black/80 text-white p-6 md:p-8 mb-24 w-full rounded-lg">
            <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center mb-6">statistik list</h1>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                @foreach ($statistik as $item)
                    <div class="grid grid-cols-1 gap-4 p-4 border-2 border-[#5E0006] items-center rounded-lg">
                        <div class="grid grid-cols-1 gap-4">
                            <div class="grid grid-cols-2 gap-4">
                                <!-- total -->
                                <div class="text-center">
                                    <h1 class="text-xs uppercase text-white/60">total</h1>
                                    <h1 class="text-xs md:text-lg font-semibold">{{ $item->total }}</h1>
                                </div>
                                <!-- platform -->
                                <div class="text-center">
                                    <h1 class="text-xs uppercase text-white/60">platform</h1>
                                    <h1 class="text-xs md:text-lg font-semibold">{{ $item->platform }}</h1>
                                </div>
                            </div>
                        </div>
                        {{-- action button --}}
                        <div class="flex flex-col w-full gap-4 mt-4 text-center">
                            <h1 class="text-xs uppercase text-white/60">action button</h1>
                            <div class="flex gap-2 justify-center items-center">
                                @include('components.dashboard.profile.btn-hapus-statistik')
                                @include('components.dashboard.profile.modal-edit-statistik')
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
