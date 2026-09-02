@foreach ($banner as $item)
    <div class="flex flex-col pt-16 w-full justify-center items-center z-30 transition-transform duration-300">
        <a href="{{ $item->link_banner }}" target="_blank" rel="noopener noreferrer">
            <img src="{{ Storage::url('banner/' . $item->banner_cover) }}" alt="{{ $item->banner_name }}" loading="lazy"
                decoding="async" class="object-cover w-full rounded-lg">
        </a>
    </div>
@endforeach
