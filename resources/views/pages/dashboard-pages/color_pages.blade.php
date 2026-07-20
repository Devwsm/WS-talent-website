@extends('template/dashboardLayout')
@section('content')
    <div class="w-full p-8 gap-4 flex flex-col justify-center items-center">
        @include('components/dashboard/navbar')
        <div class="bg-black/80 text-white p-6 md:p-8 w-full md:w-176 rounded-lg">
            <h1 class="text-2xl lg:text-3xl font-bold uppercase text-center mb-6">Warna Universal</h1>
            @include('components/errors')
            @include('components/success')

            <form action="{{ route('color_pages.update') }}" method="POST" class="flex flex-col gap-2">
                @csrf
                <label for="colorHex" class="text-sm font-semibold uppercase tracking-widest text-gray-300">
                    Warna
                </label>
                <div class="flex items-center gap-3">
                    <input type="color" id="colorPicker" value="{{ $color_pages->color ?? '#5E0006' }}"
                        class="h-12 w-16 shrink-0 bg-white/10 border border-white/20 rounded-lg cursor-pointer" />
                    <input type="text" name="color" id="colorHex" maxlength="7" placeholder="#5E0006"
                        value="{{ $color_pages->color ?? '#5E0006' }}"
                        class="flex-1 bg-white/10 border border-white/20 text-white p-3 rounded-lg focus:outline-none focus:border-[#5E0006] focus:ring-1 focus:ring-[#5E0006] transition" />
                </div>

                <button type="submit"
                    class="w-full text-white font-bold uppercase tracking-widest p-3 mt-2 bg-[#5E0006] hover:bg-[#5E0006]/70 active:scale-95 transition rounded-lg">
                    Simpan Warna
                </button>
            </form>
        </div>
    </div>

    <script>
        const colorPicker = document.getElementById('colorPicker');
        const colorHex = document.getElementById('colorHex');
        const hexPattern = /^#([0-9A-F]{3}){1,2}$/i;

        colorPicker.addEventListener('input', () => colorHex.value = colorPicker.value);
        colorHex.addEventListener('input', () => {
            if (hexPattern.test(colorHex.value)) colorPicker.value = colorHex.value;
        });
    </script>
@endsection
