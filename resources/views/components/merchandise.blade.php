<div id="store" class="merchandise relative">
    <div class="relative w-full px-12 py-24 lg:px-52 lg:py-32 gap-6">
        <div class="swiper merchSwiper">
            <div class="swiper-wrapper">
                @foreach ($merchandise as $item)
                <div class="swiper-slide">
                    <a href="{{ $item->link_merchandise }}" target="_blank">
                        <img src="{{ asset('aset/merchandise/' . $item->merchandise_cover) }}" 
                        alt="{{ $item->merchandise_name }}" 
                        class="object-cover w-full rounded-lg">
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
