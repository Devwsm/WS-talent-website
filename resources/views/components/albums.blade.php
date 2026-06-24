<div id="new-music" class="relative bg-white overflow-hidden">
    <div class="absolute top-32 bottom-1 inset-x-0 
        bg-black transform z-0">
    </div>
    <div class="albums relative mt-24 
        w-full py-24 px-6 lg:px-52 gap-6">
        <div class="swiper musicSwiper">
            <div class="swiper-wrapper">
                @foreach ($albums as $item)
                <div class="swiper-slide">
                    <a href="{{ $item->link_spotify }}" target="_blank">
                        <img src="{{ asset('aset/albums/' . $item->albums_cover) }}" 
                        alt="{{ $item->albums_name }}" loading="lazy" decoding="async"
                        class="object-cover aspect-square w-full hover:scale-105 transition duration-300 rounded-lg">
                    </a>
                </div>
                @endforeach
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next text-white"></div>
            <div class="swiper-button-prev text-white"></div>

        </div>
    </div>
</div>
