@if ($errors->any())
<div class="flex flex-col mb-4 justify-center items-center">
    <div class="bg-red-600 text-white w-full p-4 text-xs md:text-lg font-bold sora rounded-lg">
        <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif
