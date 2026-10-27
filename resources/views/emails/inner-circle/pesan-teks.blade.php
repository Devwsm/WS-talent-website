{{-- Versi teks polos dari pesan.blade.php (dikirim bersama versi HTML untuk aplikasi email yang tidak menampilkan HTML). --}}
@if ($tes)
    [Ini email percobaan dari dashboard Inner Circle]
@endif
{!! $judul !!}

{!! $sapaan !!}

@foreach ($paragraf as $baris)
    {!! $baris !!}
@endforeach
@if ($tombol)
    {!! $tombol['teks'] !!}: {!! $tombol['url'] !!}
@endif
@foreach ($catatan as $baris)
    {!! $baris !!}
@endforeach

--
WS Inner Circle - Whisnu Santika
Kebijakan Privasi: {!! route('privasi') !!}
