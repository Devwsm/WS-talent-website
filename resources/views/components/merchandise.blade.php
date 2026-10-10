<div id="store" class="merchandise relative bg-white">
    <div class="relative w-full px-6 py-24 md:px-16 lg:px-52 lg:py-32 gap-6">
        <div class="swiper merchSwiper" data-motion="fade-up">
            <div class="swiper-wrapper">
                @foreach ($merchandise as $item)
                    <div class="swiper-slide">
                        <a href="{{ config('site.mavnus_url') }}" target="_blank" rel="noopener noreferrer"
                            class="block aspect-square rounded-lg" data-tilt="6">
                            <img src="{{ Storage::url('merchandise/' . $item->merchandise_cover) }}"
                                alt="{{ $item->merchandise_name }}" loading="lazy" decoding="async"
                                class="w-full h-full object-cover rounded-lg transition-transform duration-500 hover:scale-105">
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
