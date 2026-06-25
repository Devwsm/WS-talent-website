@extends('template/dashboardLayout')
@section('content')
    <div class="w-full p-8 gap-4 flex flex-col justify-center items-center">
        @include('components/dashboard/navbar')
        <div class="bg-black/80 text-white p-6 md:p-8 w-full md:w-176 rounded-lg">
            <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center mb-6">news</h1>
            @include('components/errors')
            <form action="{{ route('news.tambah') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                @csrf
                <div class="flex flex-col gap-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <label htmlFor="news_title" class="text-xl lg:text-2xl capitalize font-semibold">news title</label>
                            <input type="text" placeholder="news title" name="news_title" class="border p-2 mb-2 rounded-lg"/>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label htmlFor="news_description" class="text-xl lg:text-2xl capitalize font-semibold">news description</label>
                            <input type="text" placeholder="news description" name="news_description" class="border p-2 mb-2 rounded-lg"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <label htmlFor="news_source" class="text-xl lg:text-2xl capitalize font-semibold">news source</label>
                            <input type="text" placeholder="news source" name="news_source" class="border p-2 mb-2 rounded-lg"/>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label htmlFor="news_date" class="text-xl lg:text-2xl capitalize font-semibold">news date</label>
                            <input type="text" placeholder="news date" name="news_date" class="border p-2 mb-2 rounded-lg"/>
                        </div>
                    </div>
                </div>
                <label htmlFor="news_cover" class="text-xl lg:text-2xl capitalize font-semibold">news cover</label>
                <input type="file" placeholder="news cover" name="news_cover" class="border p-2 mb-2 rounded-lg"/>
                <label htmlFor="news_link" class="text-xl lg:text-2xl capitalize font-semibold">link berita</label>
                <input type="text" placeholder="link berita" name="news_link" class="border p-2 mb-2 rounded-lg"/>
                <button type="submit" class="w-full text-white font-bold uppercase tracking-wide p-2 mt-4 bg-[#5E0006] hover:bg-[#5E0006]/70 transition rounded-lg">submit</button>
            </form>
        </div>
        <div class="bg-black/80 text-white p-6 md:p-8 mb-24 w-full rounded-lg">
            <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center mb-6">news list</h1>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                @foreach ($news as $item)
                <div class="grid grid-cols-1 gap-4 p-4 border-2 border-[#5E0006] items-center rounded-lg">
                    <div class="grid grid-cols-1 gap-4">
                        <div class="flex flex-col w-full gap-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- news_title -->
                                <div class="text-center">
                                    <h1 class="text-xs uppercase text-white/60">news title</h1>
                                    <h1 class="text-xs md:text-lg font-semibold">{{ $item->news_title }}</h1>
                                </div>
                                <!-- news_description -->
                                <div class="text-center">
                                    <h1 class="text-xs uppercase text-white/60">news description</h1>
                                    <h1 class="text-xs md:text-lg font-semibold">{{ $item->news_description }}</h1>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- news_source -->
                                <div class="text-center">
                                    <h1 class="text-xs uppercase text-white/60">news source</h1>
                                    <h1 class="text-xs md:text-lg font-semibold">{{ $item->news_source }}</h1>
                                </div>
                                <!-- news_date -->
                                <div class="text-center">
                                    <h1 class="text-xs uppercase text-white/60">news date</h1>
                                    <h1 class="text-xs md:text-lg font-semibold">{{ $item->news_date }}</h1>
                                </div>
                            </div>
                            <!-- news_link -->
                            <div class="text-center">
                                <h1 class="text-xs uppercase text-white/60">link berita</h1>
                                <h1 class="text-xs md:text-lg font-semibold break-all">{{ $item->news_link }}</h1>
                            </div>
                        </div>
                        <!-- news cover -->
                        <div class="flex flex-col gap-2 text-center justify-center items-center">
                            <h1 class="text-xs uppercase text-white/60">news cover</h1>
                            <img src="{{ Storage::url('news/' . $item->news_cover) }}" alt="{{ $item->news_title }}"
                                loading="lazy" decoding="async"
                            class="object-cover aspect-square w-full max-w-xs transition duration-300 rounded-lg">
                        </div>
                    </div>
                    {{-- action button --}}
                    <div class="flex flex-col w-full gap-4 mt-4 text-center">
                        <h1 class="text-xs uppercase text-white/60">action button</h1>
                        <div class="flex gap-2 justify-center items-center">
                            @include('components.dashboard.btn-hapus-news')
                            @include('components.dashboard.modal-edit-news')
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection