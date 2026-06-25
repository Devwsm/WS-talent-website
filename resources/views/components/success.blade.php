@if (session('success'))
<div class="flex flex-col mb-4 justify-center items-center">
    <div class="bg-green-600 text-white w-full p-4 text-xs md:text-lg font-bold sora rounded-lg">
        {{ session('success') }}
    </div>
</div>
@endif