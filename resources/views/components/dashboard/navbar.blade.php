<div class="nav z-50 fixed w-fit flex gap-2 bottom-5">
    <div class="nav-links text-3xl flex justify-center items-center p-4 md:p-6 rounded-lg bg-[#1A1A1B]">
        <a href="{{ route('dashboard') }}" class="text-[#F5F1E6] hover:text-[#d5ccb3] text-[1.2rem] md:text-[2rem] ">
            <i class="bi bi-house-door-fill"></i>
        </a>
    </div>
    <div class="nav-links text-3xl flex justify-center items-center gap-4 p-4 md:p-6 rounded-lg bg-[#1A1A1B]">
        <a href="{{ route('banner') }}" class="text-[#F5F1E6] hover:text-[#d5ccb3] text-[1.2rem] md:text-[2rem] ">
            <i class="bi bi-images"></i>
        </a>
        <a href="{{ route('headers') }}" class="text-[#F5F1E6] hover:text-[#d5ccb3] text-[1.2rem] md:text-[2rem] ">
            <i class="bi bi-card-image"></i>
        </a>
        <a href="{{ route('dashboard.profile') }}" class="text-[#F5F1E6] hover:text-[#d5ccb3] text-[1.2rem] md:text-[2rem] ">
            <i class="bi bi-person-fill"></i>
        </a>
        <a href="{{ route('albums') }}" class="text-[#B71C1C] hover:text-[#891212] text-[1.2rem] md:text-[2rem] ">
            <i class="bi bi-disc-fill"></i>
        </a>
        <a href="{{ route('news') }}" class="text-[#B71C1C] hover:text-[#891212] text-[1.2rem] md:text-[2rem] ">
            <i class="bi bi-newspaper"></i>
        </a>
        <a href="{{ route('merchandise') }}" class="text-[#B71C1C] hover:text-[#891212] text-[1.2rem] md:text-[2rem] ">
            <i class="bi bi-basket-fill"></i>
        </a>
    </div>
    <div class="nav-links text-3xl flex justify-center items-center p-4 md:p-6 rounded-lg bg-[#1A1A1B]">
        <a href="{{ route('logout') }}" class="text-[#B71C1C] hover:text-[#891212] text-[1.2rem] md:text-[2rem] ">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</div>
