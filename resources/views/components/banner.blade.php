@if ($banner->isNotEmpty())
    {{-- pt-16 = ruang buat navbar fixed. Banner > 1 jadi carousel: geser/seret saja,
        tanpa panah & pagination (lihat initSwiper(".bannerSwiper") di app.js). --}}
    <div id="banner-slider" class="flex flex-col pt-16 w-full z-30">
        <div class="swiper bannerSwiper w-full">
            <div class="swiper-wrapper">
                @foreach ($banner as $item)
                    <div class="swiper-slide">
                        <a href="{{ $item->link_banner }}" target="_blank" rel="noopener noreferrer" class="block">
                            <img src="{{ Storage::url('banner/' . $item->banner_cover) }}" alt="{{ $item->banner_name }}"
                                loading="{{ $loop->first ? 'eager' : 'lazy' }}" decoding="async"
                                @if ($loop->first) fetchpriority="high" @endif
                                class="object-cover w-full rounded-lg">
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
