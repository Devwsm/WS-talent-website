@foreach ($banner as $item)
    <div
        class="flex flex-col pt-16 w-full justify-center items-center z-30 transition-transform duration-300">
        <img src="{{ Storage::url('banner/' . $item->banner_cover) }}" alt="{{ $item->banner_name }}"
            loading="lazy" decoding="async" alt="whisnu-santika" class="object-cover w-full rounded-lg">
    </div>
@endforeach
