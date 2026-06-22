<div class="nav z-50 fixed w-fit flex gap-2 bottom-5">
    <div class="nav-links text-3xl flex justify-center items-center gap-4 p-6 rounded-lg bg-[#1A1A1B]">
        <a href="{{ route('dashboard') }}" class="text-[#F5F1E6] hover:text-[#d5ccb3] text-[1.5rem] md:text-[2rem] ">
            <i class="bi bi-house-door-fill me-2"></i>
        </a>
        <a href="{{ route('albums') }}" class="text-[#F5F1E6] hover:text-[#d5ccb3] text-[1.5rem] md:text-[2rem] ">
            <i class="bi bi-disc-fill me-2"></i>
        </a>
        <a href="{{ route('schedule') }}" class="text-[#B71C1C] hover:text-[#891212] text-[1.5rem] md:text-[2rem] ">
            <i class="bi bi-calendar-check me-2"></i>
        </a>
        <a href="{{ route('merchandise') }}" class="text-[#B71C1C] hover:text-[#891212] text-[1.5rem] md:text-[2rem] ">
            <i class="bi bi-basket-fill"></i>
    </div>
    <div class="nav-links text-3xl flex justify-center items-center p-6 rounded-lg bg-[#1A1A1B]">
        <a href="{{ route('logout') }}" class="text-[#B71C1C] hover:text-[#891212] text-[1.5rem] md:text-[2rem] ">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</div>
