<div class="cover bg-white flex flex-col w-full py-16 justify-center">
    <section class="w-full px-4" aria-labelledby="tour-heading">
        <h2 id="tour-heading" class="text-center text-2xl md:text-4xl font-bold uppercase tracking-tight mb-8">
            Tour
        </h2>

        <div class="max-w-3xl mx-auto flex flex-col items-center text-center">
            {{-- Widget Bandsintown. Script pakai `defer`: jalan setelah HTML selesai dibaca
                (tag <a> di bawah sudah ada) dan nggak menahan loading halaman.
                Logo dimatikan karena tombol "Lihat semua jadwal" di bawah sudah mengarah ke sana. --}}
            <a class="bit-widget-initializer" data-artist-name="id_6784724" data-app-id="2380980eaea24e40832c0c2abc88643d"
                data-display-limit="5" data-display-logo="false" data-font="Sora" data-text-color="#000000"
                data-background-color="#ffffff" data-separator-color="#e5e5e5"></a>
            <script charset="utf-8" src="https://widgetv3.bandsintown.com/main.min.js" defer></script>

            <a href="https://www.bandsintown.com/a/6784724" target="_blank" rel="noopener noreferrer"
                class="mt-8 inline-flex items-center px-5 py-1.5 rounded-sm border border-black bg-white text-black text-sm uppercase hover:bg-black hover:text-white transition-colors">
                Lihat semua jadwal di Bandsintown
            </a>
        </div>
    </section>
</div>
