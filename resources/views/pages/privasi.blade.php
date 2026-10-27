@extends('template.layout')

@section('title', 'Kebijakan Privasi — Whisnu Santika')
@section('meta_description', 'Kebijakan privasi situs whisnusantika.com dan komunitas WS Inner Circle: data apa yang
    dikumpulkan, untuk apa, dan cara menghapus akunmu.')

@section('content')
    @include('components/navbar')

    @php
        $kontak = config('site.kontak_privasi');
        $diperbarui = \Illuminate\Support\Carbon::parse(config('site.privasi_diperbarui'))
            ->locale('id')
            ->translatedFormat('j F Y');
        $h2 = 'text-xl md:text-2xl font-bold text-white mt-12 mb-3';
        $p = 'text-white/75 leading-relaxed mb-3';
        $ul = 'list-disc pl-5 space-y-2 text-white/75 leading-relaxed mb-3 marker:text-white/40';
    @endphp

    <article class="bg-black text-white px-6 md:px-16 lg:px-52 pt-32 pb-20">
        <div class="max-w-3xl mx-auto">
            <p class="text-xs uppercase tracking-[0.2em] text-white/60">Diperbarui {{ $diperbarui }}</p>
            <h1 class="text-3xl md:text-5xl font-bold tracking-tight mt-3 mb-6">Kebijakan Privasi</h1>
            <p class="{{ $p }}">
                Halaman ini menjelaskan data apa yang kami kumpulkan di situs <b>whisnusantika.com</b> dan komunitas
                <b>WS Inner Circle</b>, untuk apa data itu dipakai, dan bagaimana kamu bisa menghapusnya. “Kami” adalah
                tim yang mengelola situs dan komunitas ini.
            </p>

            <h2 class="{{ $h2 }}">1. Saat kamu hanya melihat situs</h2>
            <p class="{{ $p }}">
                Kamu tidak perlu akun untuk membuka halaman utama dan profil. Seperti server web pada umumnya, server kami
                mencatat data teknis (alamat IP, jenis peramban, dan halaman yang dibuka) pada log untuk keamanan dan
                pemeliharaan. Log ini disimpan sementara.
            </p>

            <h2 class="{{ $h2 }}">2. Data yang kami simpan untuk akun Inner Circle</h2>
            <ul class="{{ $ul }}">
                <li><b>Data akun:</b> nama, alamat email, dan kata sandi bila kamu mendaftar dengan email. Kata sandi
                    disimpan dalam bentuk hash, jadi tidak bisa dibaca siapa pun, termasuk kami.</li>
                <li><b>Aktivitas komunitas:</b> riwayat check-in acara dan stempel, serta pengajuan hadiah beserta
                    catatan penanganannya.</li>
                <li><b>Konten yang kamu bagikan:</b> foto profil, kenangan (teks, tempat, kategori, foto), dan tanda
                    suka. Kenangan yang tayang bisa dilihat anggota lain dengan nama yang disingkat.</li>
                <li><b>Data sesi:</b> cookie sesi supaya kamu tetap masuk, serta alamat IP dan jenis peramban yang
                    tercatat pada sesi tersebut.</li>
            </ul>

            <h2 class="{{ $h2 }}">3. Masuk dengan Google</h2>
            <p class="{{ $p }}">Bila kamu memilih masuk atau mendaftar dengan Google, kami menerima dari Google:
            </p>
            <ul class="{{ $ul }}">
                <li>nama dan alamat email akun Google-mu, serta ID akun Google (untuk mengenali akunmu saat masuk lagi).
                </li>
            </ul>
            <p class="{{ $p }}">
                Kami <b>tidak</b> menerima kata sandi Google-mu, tidak menyimpan token akses Google, tidak memakai foto
                profil Google-mu, dan tidak mengakses Gmail, kontak, atau berkas Google Drive-mu. Penggunaan informasi
                yang diterima dari Google mematuhi
                <a class="underline hover:text-white"
                    href="https://developers.google.com/terms/api-services-user-data-policy" target="_blank"
                    rel="noopener noreferrer">Google API Services User Data Policy</a>, termasuk
                persyaratan Limited Use.
            </p>

            <h2 class="{{ $h2 }}">4. Untuk apa data dipakai</h2>
            <ul class="{{ $ul }}">
                <li>menjalankan akunmu: masuk, check-in acara, stempel, hadiah, dan galeri kenangan;</li>
                <li>memproses dan menghubungimu soal pengajuan hadiah;</li>
                <li>menjaga keamanan, mencegah penyalahgunaan, dan memoderasi konten.</li>
            </ul>
            <p class="{{ $p }}">Kami tidak menjual datamu dan tidak memakainya untuk iklan.</p>

            <h2 class="{{ $h2 }}">5. Siapa yang bisa melihat datamu</h2>
            <ul class="{{ $ul }}">
                <li><b>Tim pengelola:</b> admin dapat melihat data anggota seperlunya untuk mencatat check-in manual,
                    memproses hadiah, dan memoderasi kenangan.</li>
                <li><b>Penyedia hosting:</b> data disimpan di server penyedia hosting kami.</li>
                <li><b>Layanan pihak ketiga di situs publik:</b> jadwal tur (Bandsintown), pemutar musik dan video
                    (Spotify, YouTube), dan huruf (Google Fonts) dimuat dari penyedia masing-masing. Penyedia itu dapat
                    menerima alamat IP-mu dan memasang cookie sesuai kebijakan mereka sendiri.</li>
                <li><b>Google</b>, bila kamu memilih masuk dengan Google.</li>
            </ul>

            <h2 class="{{ $h2 }}">6. Menyimpan dan menghapus data</h2>
            <p class="{{ $p }}">
                Datamu disimpan selama akunmu aktif. Kamu bisa menghapus akun sendiri kapan saja lewat halaman
                <b>Profil &rarr; Akun dan privasi &rarr; Hapus akunku</b> (perlu masuk lebih dulu). Penghapusan
                langsung dan permanen, dan mencakup:
            </p>
            <ul class="{{ $ul }}">
                <li>data akun, foto profil, dan ID Google yang tertaut;</li>
                <li>riwayat check-in dan stempel;</li>
                <li>pengajuan hadiah beserta lognya;</li>
                <li>kenangan beserta fotonya, dan tanda suka.</li>
            </ul>
            <p class="{{ $p }}">
                Akun belum bisa dihapus selama masih ada pengajuan hadiah yang belum selesai, supaya hadiahmu tidak
                hilang di tengah proses; setelah selesai atau ditolak, akun bisa dihapus. Setelah akun dihapus, masuk
                dengan Google lagi akan membuat akun baru dari nol.
            </p>

            <h2 class="{{ $h2 }}">7. Keamanan</h2>
            <p class="{{ $p }}">
                Situs memakai koneksi HTTPS, kata sandi disimpan sebagai hash, percobaan masuk dibatasi, dan akses admin
                dilindungi login. Tidak ada sistem yang sepenuhnya bebas risiko, jadi pakai kata sandi yang unik dan
                jangan membagikannya.
            </p>

            <h2 class="{{ $h2 }}">8. Hak dan pertanyaan</h2>
            <p class="{{ $p }}">
                Kamu bisa memperbarui foto profilmu langsung di halaman Profil dan menghapus akunmu seperti di atas.
                Untuk pertanyaan atau permintaan lain terkait datamu,
                @if ($kontak)
                    hubungi kami di <a class="underline hover:text-white"
                        href="mailto:{{ $kontak }}">{{ $kontak }}</a>.
                @else
                    hubungi kami lewat kontak resmi yang tercantum di halaman
                    <a class="underline hover:text-white" href="{{ route('profile') }}">Profil</a>.
                @endif
            </p>

            <h2 class="{{ $h2 }}">9. Perubahan kebijakan</h2>
            <p class="{{ $p }}">
                Bila kebijakan ini berubah, tanggal “Diperbarui” di atas ikut berubah. Perubahan yang berarti akan kami
                umumkan di komunitas.
            </p>
        </div>
    </article>

    @include('components/footer')
@endsection
