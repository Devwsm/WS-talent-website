@extends('template/dashboardLayout')
@section('content')
    <div class="w-full flex flex-col justify-center items-center">
        @include('components/dashboard/navbar')
        <div class=" text-white p-6 md:p-8 w-full mb-24 rounded-2xl">
            {{-- <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center mb-6">product list</h1> --}}
            <div class="flex flex-col gap-4 justify-center">

                <div class="flex w-full gap-4 justify-center">
                    <div class="flex flex-col w-full p-4 bg-black/80 gap-4 rounded-xl border-2 border-[#5E0006]">                    
                        <h1 class="text-xl lg:text-2xl font-bold uppercase text-center my-3">schedule list</h1>
                        @foreach ($schedule as $item)
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 border-y-2 border-[#5E0006] items-center">
                            <!-- Tanggal -->
                            <div class="text-center md:text-left">
                                <h1 class="text-xs uppercase text-white/60">Date</h1>
                                <h1 class="text-xs md:text-md lg:text-lg font-semibold">{{ $item->tanggal }}</h1>
                            </div>
                            <!-- Tempat -->
                            <div class="text-center">
                                <h1 class="text-xs uppercase text-white/60">Venue</h1>
                                <h1 class="text-xs md:text-md lg:text-lg font-semibold">{{ $item->nama_tempat }}</h1>
                            </div>
                            <!-- Daerah -->
                            <div class="text-center md:text-right">
                                <h1 class="text-xs uppercase text-white/60">Location</h1>
                                <h1 class="text-xs md:text-md lg:text-lg font-semibold">{{ $item->daerah }}</h1>
                            </div>
                            <div class="text-center">
                                <a href="{{ route('schedule') }}" class="w-full flex items-center justify-center text-white font-bold uppercase tracking-wide p-2 bg-[#5E0006] hover:bg-[#5E0006]/70 transition rounded-lg">
                                    <i class="bi bi-calendar-check me-2"></i>
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 justify-center">
                    <div class="flex flex-col h-fit gap-4 p-4 bg-black/80 rounded-xl border-2 border-[#5E0006]">                    
                        <h1 class="text-xl lg:text-2xl font-bold uppercase text-center my-3">albums list</h1>
                        @foreach ($albums as $item)
                        <div class="grid grid-cols-1 gap-4 p-4 border-2 border-[#5E0006] items-center">
                            <div class="grid grid-cols-1 gap-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- albums_name -->
                                    <div class="text-center">
                                        <h1 class="text-xs uppercase text-white/60">albums name</h1>
                                        <h1 class="text-xs md:text-md lg:text-lg font-semibold">{{ $item->albums_name }}</h1>
                                    </div>
                                    <!-- link_spotify -->
                                    <div class="text-center">
                                        <h1 class="text-xs uppercase text-white/60">link spotify</h1>
                                        <h1 class="text-xs md:text-md lg:text-lg font-semibold break-all">{{ $item->link_spotify }}</h1>
                                    </div>
                                </div>
                                <!-- album cover -->
                                <div class="flex flex-col gap-2 text-center justify-center items-center">
                                    <h1 class="text-xs uppercase text-white/60">album cover</h1>
                                    <img src="{{ asset('aset/albums/' . $item->albums_cover) }}" 
                                    alt="albums_cover" 
                                    class="object-cover aspect-square w-full max-w-xs transition duration-300 rounded-lg">
                                </div>
                            </div>
                            {{-- action button --}}
                            <div class="flex flex-col w-full gap-4 mt-4 text-center">
                                <h1 class="text-xs uppercase text-white/60">action button</h1>
                                <div class="flex gap-2 justify-center items-center">
                                    <a href="{{ route('albums') }}" class="w-full text-white font-bold uppercase tracking-wide p-2 bg-[#5E0006] hover:bg-[#5E0006]/70 transition rounded-lg">
                                        <i class="bi bi-disc-fill me-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="flex flex-col h-fit gap-4 p-4 bg-black/80 rounded-xl border-2 border-[#5E0006]">                    
                        <h1 class="text-xl lg:text-2xl font-bold uppercase text-center my-3">merchandise list</h1>
                        @foreach ($merchandise as $item)
                        <div class="grid grid-cols-1 gap-4 p-4 border-2 border-[#5E0006] items-center">
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
                                        <h1 class="text-xs md:text-lg font-semibold break-all">{{ $item->link_merchandise }}</h1>
                                    </div>
                                </div>
                                <!-- merchandise cover -->
                                <div class="flex flex-col gap-2 text-center justify-center items-center">
                                    <h1 class="text-xs uppercase text-white/60">merchandise cover</h1>
                                    <img src="{{ asset('aset/merchandise/' . $item->merchandise_cover) }}" 
                                    alt="merchandise_cover" 
                                    class="object-cover aspect-square w-full max-w-xs transition duration-300 rounded-lg">
                                </div>
                            </div>
                            {{-- action button --}}
                            <div class="flex flex-col w-full gap-4 mt-4 text-center">
                                <h1 class="text-xs uppercase text-white/60">action button</h1>
                                <div class="flex gap-2 justify-center items-center">
                                    <a href="{{ route('merchandise') }}" class="w-full text-white font-bold uppercase tracking-wide p-2 bg-[#5E0006] hover:bg-[#5E0006]/70 transition rounded-lg">
                                        <i class="bi bi-basket-fill"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection