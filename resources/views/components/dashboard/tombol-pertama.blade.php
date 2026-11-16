{{-- "Jadikan pertama": sematkan data di paling depan (mis. rilisan terbaru). Disembunyikan bila sudah pertama. --}}
@props(['modul', 'id', 'nama', 'posisi'])
@if ($posisi > 1)
    <form method="POST" action="{{ route($modul . '.urutan', $id) }}" class="inline-flex">
        @csrf @method('PATCH')
        <input type="hidden" name="arah" value="awal">
        <button type="submit" class="text-white/40 hover:text-white transition text-xs leading-none"
            title="Jadikan pertama" aria-label="Jadikan pertama: {{ $nama }}"><i class="bi bi-pin-angle-fill"
                aria-hidden="true"></i></button>
    </form>
@endif
