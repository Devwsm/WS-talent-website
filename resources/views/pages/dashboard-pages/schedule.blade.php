@extends('template/dashboardLayout')
@section('content')
    <div class="w-full p-8 gap-4 flex flex-col justify-center items-center">
        @include('components/dashboard/navbar')
        <div class="bg-black/80 text-white p-6 md:p-8 w-full md:w-176 rounded-lg">
            <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center mb-6">schedule</h1>
            @include('components/errors')
            <form action="{{ route('schedule.tambah') }}" method="POST" class="flex flex-col gap-2">
                @csrf
                <label htmlFor="tanggal" class="text-xl lg:text-2xl capitalize font-semibold">tanggal</label>
                <input type="date" placeholder="tanggal" name="tanggal" class="border p-2 mb-2 rounded-lg"/>
                <label htmlFor="nama_tempat" class="text-xl lg:text-2xl capitalize font-semibold">nama tempat</label>
                <input type="text" placeholder="nama_tempat" name="nama_tempat" class="border p-2 mb-2 rounded-lg"/>
                <label htmlFor="daerah" class="text-xl lg:text-2xl capitalize font-semibold">daerah</label>
                <input type="text" placeholder="daerah" name="daerah" class="border p-2 mb-2 rounded-lg"/>
                <button type="submit" class="w-full text-white font-bold uppercase tracking-wide p-2 mt-4 bg-[#5E0006] hover:bg-[#5E0006]/70 transition rounded-lg">submit</button>
            </form>
        </div>
        <div class="bg-black/80 text-white p-6 md:p-8 mb-24 w-full rounded-lg">
            <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center mb-6">schedule list</h1>
            <div class="flex flex-col gap-4 w-full">
                @foreach ($schedule as $item)
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 py-4 border-y-2 border-y-[#5E0006] items-center">
                    <!-- Tanggal -->
                    <div class="text-center md:text-left">
                        <h1 class="text-xs uppercase text-white/60">Date</h1>
                        <h1 class="text-lg font-semibold">{{ $item->tanggal }}</h1>
                    </div>
                    <!-- Tempat -->
                    <div class="text-center">
                        <h1 class="text-xs uppercase text-white/60">Venue</h1>
                        <h1 class="text-lg font-semibold">{{ $item->nama_tempat }}</h1>
                    </div>
                    <!-- Daerah -->
                    <div class="text-center md:text-right">
                        <h1 class="text-xs uppercase text-white/60">Location</h1>
                        <h1 class="text-lg font-semibold">{{ $item->daerah }}</h1>
                    </div>
                    <div class="flex w-full justify-center items-center">
                        @include('components.dashboard.btn-hapus-schedule')
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection