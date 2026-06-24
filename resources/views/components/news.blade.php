<div class="news p-6 bg-white
    grid grid-cols-1 lg:grid-cols-4 gap-4 w-full justify-center items-center">

    @foreach ($news as $item)
    <a href="{{ $item->news_link }}" target="_blank">
        <div class="card flex flex-col justify-center items-center bg-gray-100 hover:bg-gray-200 gap-2 rounded-lg">
            <div class="w-full h-8/12 flex justify-center">
                    <img src="{{ asset('aset/news/' . $item->news_cover) }}" alt="akuharusperi"
                    class="w-full h-full object-cover">
            </div>
            <div class="text-cover w-full h-4/12 flex flex-col justify-center p-4 gap-2">
                <div class="header flex gap-8">
                    <h1 class="font-light text-lg">{{ $item->news_date }}</h1>
                </div>
                <div class="description flex flex-col justify-center gap-2">
                    <h1 class="font-bold text-xl">{{ $item->news_title }}</h1>
                </div>
            </div>
        </div>
    </a>
    @endforeach

</div>
