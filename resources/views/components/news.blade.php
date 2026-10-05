<div class="news p-6 bg-white 
            flex gap-4 overflow-x-auto snap-x snap-mandatory md:snap-none md:grid md:grid-cols-2 lg:grid-cols-4 md:overflow-visible hide-scrollbar w-full"
    data-motion-stagger="0.1" data-motion-children="fade-up">

    @foreach ($news as $item)
        <a href="{{ $item->news_link }}" target="_blank" rel="noopener noreferrer"
            aria-label="Baca berita: {{ $item->news_title }}"
            class="group min-w-[80%] sm:min-w-[60%] md:min-w-0 snap-start">

            <div data-tilt="5" class="card flex flex-col bg-gray-100 hover:bg-gray-200 gap-2 rounded-lg overflow-hidden">
                <div class="w-full aspect-square overflow-hidden">
                    <img src="{{ Storage::url('news/' . $item->news_cover) }}" alt="{{ $item->news_title }}"
                        loading="lazy" decoding="async"
                        class="w-full h-full object-cover object-center transition-transform duration-700 group-hover:scale-110">
                </div>

                <div class="flex flex-col p-4 gap-2">
                    <div class="header flex items-center gap-2">
                        <span class="font-bold text-lg line-clamp-2">
                            {{ $item->news_source }}
                        </span>
                        <span class="font-semibold text-sm text-(--theme-color)">
                            {{ $item->news_date }}
                        </span>
                    </div>
                    <div class="body flex flex-col">
                        <h3 class="font-bold text-2xl xl:text-3xl line-clamp-2">
                            {{ $item->news_title }}
                        </h3>
                        <p class="font-light text-sm text-gray-600 line-clamp-3">
                            {!! $item->news_description !!}
                        </p>
                        <span aria-hidden="true"
                            class="w-full text-center text-white font-bold uppercase tracking-widest p-3 mt-2 bg-(--theme-color) group-hover:bg-(--theme-color)/70 transition rounded-lg">
                            Read more
                        </span>
                    </div>
                </div>
            </div>

        </a>
    @endforeach

</div>
