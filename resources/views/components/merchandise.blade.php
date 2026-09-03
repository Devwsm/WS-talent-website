<div id="store" class="merchandise relative bg-white">
    <div class="relative w-full px-6 py-24 md:px-16 lg:px-52 lg:py-32 gap-6">
        <div class="swiper merchSwiper">
            <div class="swiper-wrapper">
                @foreach ($merchandise as $item)
                    <div class="swiper-slide">
                        <a href="{{ $item->link_merchandise }}" target="_blank" rel="noopener noreferrer"
                            class="block aspect-square overflow-hidden rounded-lg">
                            <img src="{{ Storage::url('merchandise/' . $item->merchandise_cover) }}"
                                alt="{{ $item->merchandise_name }}" loading="lazy" decoding="async"
                                class="w-full h-full object-cover">
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
