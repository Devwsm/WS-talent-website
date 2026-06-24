<div class="videos-cover w-full h-full gap-4">
    <div class="head flex flex-col w-full p-12 gap-2 justify-center items-center">
        <img src="{{ asset('aset/logo/Whisnu-Santika_Logo-2025-White.png') }}" alt="whisnu-santika"
            class="object-cover w-170 rounded-lg">
        <a href="">
            <button class="bg-white text-black px-4 py-1 rounded-lg">Watch Video</button>
        </a>
    </div>
    <div class="clip relative flex flex-col h-120 md:h-screen">
        <video autoplay muted loop class="absolute w-full h-full object-cover z-0">
            <source src="{{ asset('aset/videos/Whisnu Santika Cartel DWP.mp4') }}" type="video/mp4">
        </video>
        
        <div class="content absolute inset-0 z-10 bg-black/50 
            flex flex-col justify-center items-center text-white text-center gap-4">
            <img src="{{ asset('aset/logo/Whisnu-Santika_Logo-2025-2-White.png') }}" alt="whisnu-santika"
                class="object-cover w-120 rounded-lg">
            <div class="flex flex-col gap-2">
                <h1 class="text-sm md:text-lg opacity-80">
                    Official Music Experience
                </h1>
                <div class="direct flex gap-4">
                    <a href="#new-music" class="px-6 py-2 bg-white text-black rounded-full font-semibold">
                        Listen Now
                    </a>
                    <a href="#tour" class="px-6 py-2 border border-white rounded-full">
                        Tour Dates
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
