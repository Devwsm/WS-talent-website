<div class="news p-6 bg-white 
            flex gap-4 overflow-x-auto lg:grid lg:grid-cols-4 lg:overflow-visible hide-scrollbar w-full">

    @foreach ($news as $item)
        <a href="{{ $item->news_link }}" target="_blank" class="min-w-[80%] sm:min-w-[60%] lg:min-w-0">

            <div class="card flex flex-col bg-gray-100 hover:bg-gray-200 gap-2 rounded-lg overflow-hidden">
                <div class="w-full aspect-square overflow-hidden">
                    <img src="{{ asset('aset/news/' . $item->news_cover) }}"
                        class="w-full h-full object-cover object-center">
                </div>

                <div class="p-4 flex flex-col gap-2">
                    <h1 class="font-light text-sm text-gray-500">
                        {{ $item->news_date }}
                    </h1>
                    <h1 class="font-bold text-lg line-clamp-2">
                        {{ $item->news_title }}
                    </h1>
                </div>
            </div>

        </a>
    @endforeach

</div>
