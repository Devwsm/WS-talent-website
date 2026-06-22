@extends('template/dashboardLayout')
@section('content')
    <div class="w-full p-8 gap-4 flex flex-col justify-center items-center">
        @include('components/dashboard/navbar')
        <div class="bg-black/80 text-white p-6 md:p-8 w-full md:w-176 rounded-lg">
            <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center mb-6">merchandise</h1>
            @include('components/errors')
            <form action="{{ route('merchandise.tambah') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                @csrf
                <label htmlFor="merchandise_name" class="text-xl lg:text-2xl capitalize font-semibold">name</label>
                <input type="text" placeholder="name" name="merchandise_name" class="border p-2 mb-2 rounded-lg"/>
                <label htmlFor="link_merchandise" class="text-xl lg:text-2xl capitalize font-semibold">link</label>
                <input type="text" placeholder="link merchandise" name="link_merchandise" class="border p-2 mb-2 rounded-lg"/>
                <label htmlFor="merchandise_cover" class="text-xl lg:text-2xl capitalize font-semibold">merchandise cover</label>
                <input type="file" placeholder="merchandise cover" name="merchandise_cover" class="border p-2 mb-2 rounded-lg"/>
                <button type="submit" class="w-full text-white font-bold uppercase tracking-wide p-2 mt-4 bg-[#5E0006] hover:bg-[#5E0006]/70 transition rounded-lg">submit</button>
            </form>
        </div>
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