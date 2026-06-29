<div
    class="news p-6 bg-white 
            flex gap-4 overflow-x-auto lg:grid lg:grid-cols-4 lg:overflow-visible hide-scrollbar w-full">

    @foreach ($news as $item)
        <a href="{{ $item->news_link }}" target="_blank" class="min-w-[80%] sm:min-w-[60%] lg:min-w-0">

            <div class="card flex flex-col bg-gray-100 hover:bg-gray-200 gap-2 rounded-lg overflow-hidden">
                <div class="w-full aspect-square overflow-hidden">
                    <img src="{{ Storage::url('news/' . $item->news_cover) }}" alt="{{ $item->news_title }}" loading="lazy"
                        decoding="async" class="w-full h-full object-cover object-center">
                </div>

                <div class="flex flex-col p-4 gap-2">
                    <div class="header flex items-center gap-2">
                        <h1 class="font-bold text-lg line-clamp-2">
                            {{ $item->news_source }}
                        </h1>
                        <h1 class="font-semibold text-sm text-[#5E0006]">
                            {{ $item->news_date }}
                        </h1>
                    </div>
                    <div class="body flex flex-col">
                        <h1 class="font-bold text-3xl line-clamp-2">
                            {{ $item->news_title }}
                        </h1>
                        <h1 class="font-light text-sm text-gray-600 line-clamp-3">
                            {!! $item->news_description !!}
                        </h1>
                        <button
                            class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-[#5E0006] hover:bg-[#5E0006]/70 active:scale-95 transition rounded-lg">
                            Read more
                        </button>
                    </div>
                </div>
            </div>

        </a>
    @endforeach

</div>
