@extends('template.layout')

@section('title', 'Kebijakan Privasi — Whisnu Santika')
@section('meta_description',
    'Kebijakan privasi situs whisnusantika.com dan komunitas WS Inner Circle: data apa yang
    dikumpulkan, untuk apa, dan cara menghapus akunmu.')

@section('content')
    @include('components/navbar')

    @php
        // Isi dari CMS (Konten > Kebijakan privasi) bila ada; kalau tidak, teks bawaan di privasi-bawaan.blade.php.
        $privasi = app(\App\Services\InnerCircle\IcPrivasi::class);
        $isiKustom = $privasi->isi();
        $kontak = config('site.kontak_privasi');
        $diperbarui = \Illuminate\Support\Carbon::parse($privasi->diperbarui())
            ->locale('id')
            ->translatedFormat('j F Y');
    @endphp

    <article class="bg-black text-white px-6 md:px-16 lg:px-52 pt-32 pb-20">
        <div class="max-w-3xl mx-auto">
            <p class="text-xs uppercase tracking-[0.2em] text-white/60">Diperbarui {{ $diperbarui }}</p>
            <h1 class="text-3xl md:text-5xl font-bold tracking-tight mt-3 mb-6">Kebijakan Privasi</h1>
            @if ($isiKustom)
                {{-- Isi dari editor (sudah disaring HtmlSanitizer): hanya tag aman, jadi aman ditulis mentah. --}}
                <div
                    class="text-white/75 leading-relaxed wrap-break-word [&_p]:mb-3 [&_h1]:text-3xl [&_h1]:font-bold [&_h1]:text-white [&_h1]:mt-12 [&_h1]:mb-3 [&_h2]:text-xl md:[&_h2]:text-2xl [&_h2]:font-bold [&_h2]:text-white [&_h2]:mt-12 [&_h2]:mb-3 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:text-white [&_h3]:mt-8 [&_h3]:mb-2 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-2 [&_ul]:mb-3 [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:space-y-2 [&_ol]:mb-3 [&_a]:underline hover:[&_a]:text-white [&_strong]:text-white [&_blockquote]:border-l-2 [&_blockquote]:border-white/20 [&_blockquote]:pl-4 [&_blockquote]:mb-3">
                    {!! $isiKustom !!}
                </div>
            @else
                @include('pages.privasi-bawaan', ['kontak' => $kontak])
            @endif
        </div>
    </article>

    @include('components/footer')
@endsection
