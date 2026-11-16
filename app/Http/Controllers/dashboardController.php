<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MengonversiGambarWebp;
use App\Models\albums;
use App\Models\banner;
use App\Models\bio;
use App\Models\booking;
use App\Models\collab;
use App\Models\color_pages;
use App\Models\genre;
use App\Models\header;
use App\Models\highlight;
use App\Models\log_aktivitas;
use App\Models\log_error;
use App\Models\media_coverage;
use App\Models\media_sosial;
use App\Models\merchandise;
use App\Models\news;
use App\Models\profile_hero;
use App\Models\statistik;
use App\Services\Cms\UrutanTampil;
use App\Support\AreaLog;
use App\Support\HtmlSanitizer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class dashboardController extends Controller
{
    use MengonversiGambarWebp;

    // dashboard
    public function dashboard()
    {
        // Ringkasan dashboard hanya butuh jumlah dan beberapa data teratas, jadi tidak memuat semua baris
        // (dulu seluruh isi berita, album, dll. ikut ditarik hanya untuk menampilkan angka). Kartu "Berita Terbaru"
        // memakai urutan yang sama dengan halaman Home (paling baru dulu); album, banner, header, dan merchandise
        // memakai urutan tampil yang diatur di CMS. Berita dan album yang masih terjadwal belum ikut karena belum tampil.
        $bannerTotal = banner::count();
        $bannerPertama = banner::urut()->first();
        $headerTotal = header::count();
        $headerPertama = header::urut()->first();
        $newsTotal = news::count();
        $newsTampil = news::tayang()->terbaruTayang()->take(3)->get();
        $albumsTotal = albums::count();
        $albumsTampil = albums::tayang()->urut()->take(8)->get();
        $merchandiseTotal = merchandise::count();
        $merchandiseTampil = merchandise::urut()->take(8)->get();
        $color_pages = color_pages::first();
        $highlightCount = highlight::count();
        $statistikCount = statistik::count();

        // Ringkasan Profil: hero, bio, dan jumlah tiap daftar (hanya angka, tidak memuat semua baris).
        $profil = (object) [
            'hero' => profile_hero::first(),
            'bio' => bio::first(),
            'jumlah' => [
                'Highlight' => $highlightCount,
                'Statistik' => $statistikCount,
                'Genre' => genre::count(),
                'Kolaborasi' => collab::count(),
                'Media coverage' => media_coverage::count(),
                'Media sosial' => media_sosial::count(),
                'Booking' => booking::count(),
            ],
        ];

        // Log CMS Artis: masalah yang masih terbuka, login gagal 24 jam terakhir, dan tiga perubahan terakhir.
        // Bila fitur Inner Circle mati, CMS Artis menampilkan semua error (sama seperti halaman Log).
        $log = (object) [
            'error_terbuka' => log_error::query()
                ->where('selesai', false)
                ->when(config('inner_circle.aktif'), fn($q) => $q->where('area', AreaLog::ARTIS))
                ->count(),
            'login_gagal' => (int) log_aktivitas::query()
                ->where('kategori', 'akses')
                ->where('created_at', '>=', now()->subDay())
                ->sum('jumlah'),
            'aktivitas' => log_aktivitas::query()
                ->where('kategori', 'konten')
                ->orderByDesc('created_at')
                ->orderByDesc('id_log_aktivitas')
                ->limit(3)
                ->get(),
        ];

        return view(
            'pages.dashboard',
            compact(
                'bannerTotal',
                'bannerPertama',
                'headerTotal',
                'headerPertama',
                'newsTotal',
                'newsTampil',
                'albumsTotal',
                'albumsTampil',
                'merchandiseTotal',
                'merchandiseTampil',
                'color_pages',
                'highlightCount',
                'statistikCount',
                'profil',
                'log',
            )
        );
    }
    // dashboard end

    // color_pages
    public function colorPages()
    {
        $color_pages = color_pages::first();
        return view('pages.dashboard-pages.color_pages', compact('color_pages'));
    }

    public function updateColorPages(Request $request)
    {
        $request->validate([
            'color' => 'required|regex:/^#([0-9A-F]{3}){1,2}$/i',
        ]);
        color_pages::updateOrCreate([], ['color' => $request->color]);
        return back()->with('success', 'Warna berhasil diperbarui.');
    }
    // color_pages end

    // banner
    public function banner()
    {
        $banner = banner::urut()->get();
        return view('pages.dashboard-pages.banner', compact('banner'));
    }

    public function tambahBanner(Request $request)
    {
        $request->validate([
            'banner_name' => 'required|string|max:255',
            'link_banner' => 'required|url:http,https|max:255',
            'banner_cover' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'banner_name.required' => 'Judul Banner harus diisi.',
            'link_banner.required' => 'Link Banner harus diisi.',
            'link_banner.url' => 'Link Banner harus berupa alamat lengkap yang diawali http:// atau https://.',
            'link_banner.max' => 'Link Banner terlalu panjang (maksimal 255 karakter).',
            'banner_cover.required' => 'Gambar Banner harus diisi.',
            'banner_cover.image' => 'File harus berupa gambar.',
            'banner_cover.max' => 'Gambar Banner tidak boleh lebih dari 2MB.',
        ]);

        $file     = $request->file('banner_cover');
        $filename = now()->timestamp . '_' . Str::uuid() . '.webp';

        // Konversi ke WebP → simpan ke storage/app/public/banner/
        $webpData = $this->convertToWebP($file->getRealPath(), 82);
        Storage::disk('public')->put('banner/' . $filename, $webpData);

        // simpan data ( simple )
        $data = new banner();
        $data->banner_name = $request->banner_name;
        $data->link_banner = $request->link_banner;
        $data->banner_cover = $filename; // hanya nama file
        $data->urutan = banner::urutanBerikut();
        $data->save();

        return redirect()->route('banner')->with('success', 'inputan berhasil ditambahkan');
    }

    public function updateBanner(Request $request, $id)
    {
        $request->validate([
            'banner_name' => 'required|string|max:255',
            'link_banner' => 'required|url:http,https|max:255',
            'banner_cover' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'banner_name.required' => 'Judul Banner harus diisi.',
            'link_banner.required' => 'Link Banner harus diisi.',
            'link_banner.url' => 'Link Banner harus berupa alamat lengkap yang diawali http:// atau https://.',
            'link_banner.max' => 'Link Banner terlalu panjang (maksimal 255 karakter).',
            'banner_cover.image' => 'File harus berupa gambar.',
            'banner_cover.max' => 'Gambar Banner tidak boleh lebih dari 2MB.',
        ]);

        $data = banner::findOrFail($id);
        if ($request->hasFile('banner_cover')) {
            // Konversi dan simpan file baru DULU: kalau gagal (mis. resolusi terlalu besar), file lama tetap utuh.
            $file     = $request->file('banner_cover');
            $filename = now()->timestamp . '_' . Str::uuid() . '.webp';
            $webpData = $this->convertToWebP($file->getRealPath(), 82);
            Storage::disk('public')->put('banner/' . $filename, $webpData);

            // Baru setelah itu file lama dihapus dari storage
            if ($data->banner_cover && Storage::disk('public')->exists('banner/' . $data->banner_cover)) {
                Storage::disk('public')->delete('banner/' . $data->banner_cover);
            }

            $data->banner_cover = $filename;
        }
        $data->banner_name = $request->banner_name;
        $data->link_banner = $request->link_banner;
        $data->save();

        return redirect()->route('banner')->with('success', 'inputan berhasil ditambahkan');
    }

    public function hapusBanner($id)
    {
        try {
            $data = banner::findOrFail($id);

            // Hapus file dari storage jika ada
            if ($data->banner_cover && Storage::disk('public')->exists('banner/' . $data->banner_cover)) {
                Storage::disk('public')->delete('banner/' . $data->banner_cover);
            }

            $data->delete();

            return to_route('banner')->with('success', 'Banner berhasil dihapus.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return to_route('banner')->withErrors('Banner tidak ditemukan.');
        } catch (\Exception $e) {
            return to_route('banner')->withErrors('Gagal menghapus banner.');
        }
    }    // banner end

    // header
    public function headers()
    {
        $header = header::urut()->get();
        return view('pages.dashboard-pages.header', compact('header'));
    }

    public function tambahHeaders(Request $request)
    {
        $request->validate([
            'header_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'header_title' => 'required|string|max:255',
            'header_img' => 'required|image|mimes:jpg,jpeg,png|max:1024',
            'header_name' => 'required|string|max:255',
            'header_description' => 'required|string|max:255',
            'link_header' => 'required|url:http,https|max:255',
            'header_background'  => 'required|file|mimes:jpg,jpeg,png,mp4,mov,avi,mkv,webm|max:51200',
        ], [
            'header_color.required' => 'Warna Header harus diisi.',
            'header_color.regex' => 'Warna Header tidak valid. Pilih warna lewat pemilih warna.',
            'header_title.required' => 'Judul Header harus diisi.',
            'header_img.required' => 'Gambar Header harus diisi.',
            'header_img.image' => 'File harus berupa gambar.',
            'header_img.max' => 'Cover Header tidak boleh lebih dari 1MB.',

            'header_name.required' => 'Nama Header harus diisi.',
            'header_description.required' => 'Deskripsi Header harus diisi.',
            'link_header.required' => 'Link Header harus diisi.',
            'link_header.url' => 'Link Header harus berupa alamat lengkap yang diawali http:// atau https://.',
            'link_header.max' => 'Link Header terlalu panjang (maksimal 255 karakter).',

            'header_background.required' => 'Cover Header harus diisi.',
            'header_background.mimes'    => 'File harus berupa gambar (jpg, jpeg, png) atau video (mp4, mov, avi, mkv, webm).',
            'header_background.max' => 'Cover Header tidak boleh lebih dari 50MB (gambar maksimal 1MB).',
            'header_background.uploaded' => 'Cover Header gagal diunggah. Ukuran file mungkin melebihi batas unggah server.',
        ]);
        // Validasi ukuran manual (beda batas untuk gambar vs video)
        $bgFile   = $request->file('header_background');
        $bgMime   = $bgFile->getMimeType();
        $isVideo  = str_starts_with($bgMime, 'video/');

        $maxImageSize = 1 * 1024 * 1024;   // 1MB
        $maxVideoSize = 50 * 1024 * 1024;  // 50MB (raw, sebelum dikompres)

        if (!$isVideo && $bgFile->getSize() > $maxImageSize) {
            return back()->withErrors(['header_background' => 'Gambar Cover Header tidak boleh lebih dari 1MB.'])->withInput();
        }
        if ($isVideo && $bgFile->getSize() > $maxVideoSize) {
            return back()->withErrors(['header_background' => 'Video Cover Header tidak boleh lebih dari 50MB.'])->withInput();
        }

        // Video butuh ffmpeg. Dicek di awal, sebelum ada berkas yang tersimpan, supaya gagalnya rapi.
        if ($isVideo && !$this->ffmpegTersedia()) {
            return back()->withErrors(['header_background' => self::PESAN_TANPA_FFMPEG])->withInput();
        }

        // Setiap berkas yang sudah ditulis di request ini dicatat; kalau ada langkah yang gagal semuanya
        // dibersihkan, jadi tidak ada file yatim di storage.
        $tersimpan = [];

        try {
            $file     = $request->file('header_img');
            $filename = now()->timestamp . '_' . Str::uuid() . '.webp';
            // Konversi ke WebP → simpan ke storage/app/public/header/img/
            $webpData = $this->convertToWebP($file->getRealPath(), 82);
            Storage::disk('public')->put('header/img/' . $filename, $webpData);
            $tersimpan[] = 'header/img/' . $filename;

            // === header_background (gambar ATAU video) ===
            if ($isVideo) {
                $bgFilename  = now()->timestamp . '_' . Str::uuid() . '.mp4';
                $tersimpan[] = 'header/background/' . $bgFilename; // dicatat sebelum konversi: ffmpeg bisa meninggalkan file separuh jadi
                $this->convertVideoToMp4(
                    $bgFile->getRealPath(),
                    Storage::disk('public')->path('header/background/' . $bgFilename)
                );
            } else {
                $bgFilename = now()->timestamp . '_' . Str::uuid() . '.webp';
                $webpData   = $this->convertToWebP($bgFile->getRealPath(), 82);
                Storage::disk('public')->put('header/background/' . $bgFilename, $webpData);
                $tersimpan[] = 'header/background/' . $bgFilename;
            }

            // simpan data ( simple )
            $data = new header();
            $data->header_color = $request->header_color;
            $data->header_title = $request->header_title;
            $data->header_img = $filename; // hanya nama file
            $data->header_name = $request->header_name;
            $data->header_description = $request->header_description;
            $data->link_header = $request->link_header;
            $data->header_background = $bgFilename; // hanya nama file
            $data->urutan = header::urutanBerikut();
            $data->save();
        } catch (\Throwable $e) {
            return $this->gagalProsesHeader($e, $tersimpan);
        }

        return redirect()->route('headers')->with('success', 'inputan berhasil ditambahkan');
    }

    public function updateHeaders(Request $request, $id_header)
    {
        $data = header::findOrFail($id_header);

        $request->validate([
            'header_color'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'header_title'       => 'required|string|max:255',
            'header_img'         => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
            'header_name'        => 'required|string|max:255',
            'header_description' => 'required|string|max:255',
            'link_header'        => 'required|url:http,https|max:255',
            'header_background'  => 'nullable|file|mimes:jpg,jpeg,png,mp4,mov,avi,mkv,webm|max:51200',
        ], [
            'header_color.required' => 'Warna Header harus diisi.',
            'header_color.regex' => 'Warna Header tidak valid. Pilih warna lewat pemilih warna.',
            'header_title.required' => 'Judul Header harus diisi.',
            'header_img.image'      => 'File harus berupa gambar.',
            'header_img.max'        => 'Cover Header tidak boleh lebih dari 1MB.',

            'header_name.required'        => 'Nama Header harus diisi.',
            'header_description.required' => 'Deskripsi Header harus diisi.',
            'link_header.required'        => 'Link Header harus diisi.',
            'link_header.url' => 'Link Header harus berupa alamat lengkap yang diawali http:// atau https://.',
            'link_header.max' => 'Link Header terlalu panjang (maksimal 255 karakter).',

            'header_background.mimes' => 'File harus berupa gambar (jpg, jpeg, png) atau video (mp4, mov, avi, mkv, webm).',
            'header_background.max' => 'Cover Header tidak boleh lebih dari 50MB (gambar maksimal 1MB).',
            'header_background.uploaded' => 'Cover Header gagal diunggah. Ukuran file mungkin melebihi batas unggah server.',
        ]);

        $imgBaru = $request->file('header_img');
        $bgBaru  = $request->file('header_background');
        $isVideo = false;

        // Semua pemeriksaan dilakukan DULU, sebelum ada berkas yang ditulis atau dihapus.
        if ($imgBaru && $imgBaru->getSize() > 1 * 1024 * 1024) {
            return back()->withErrors(['header_img' => 'Gambar Header tidak boleh lebih dari 1MB.'])->withInput();
        }

        if ($bgBaru) {
            $isVideo = str_starts_with($bgBaru->getMimeType(), 'video/');

            $maxImageSize = 1 * 1024 * 1024;   // 1MB
            $maxVideoSize = 50 * 1024 * 1024;  // 50MB

            if (!$isVideo && $bgBaru->getSize() > $maxImageSize) {
                return back()->withErrors(['header_background' => 'Gambar Cover Header tidak boleh lebih dari 1MB.'])->withInput();
            }
            if ($isVideo && $bgBaru->getSize() > $maxVideoSize) {
                return back()->withErrors(['header_background' => 'Video Cover Header tidak boleh lebih dari 50MB.'])->withInput();
            }
            if ($isVideo && !$this->ffmpegTersedia()) {
                return back()->withErrors(['header_background' => self::PESAN_TANPA_FFMPEG])->withInput();
            }
        }

        // $tersimpan = berkas baru yang sudah ditulis (dibersihkan kalau ada yang gagal),
        // $hapusLama = berkas lama (baru dihapus setelah data berhasil disimpan).
        $tersimpan = [];
        $hapusLama = [];

        try {
            // === update header_img (kalau ada file baru) ===
            if ($imgBaru) {
                $filename = now()->timestamp . '_' . Str::uuid() . '.webp';
                $webpData = $this->convertToWebP($imgBaru->getRealPath(), 82);
                Storage::disk('public')->put('header/img/' . $filename, $webpData);
                $tersimpan[] = 'header/img/' . $filename;

                if ($data->header_img) {
                    $hapusLama[] = 'header/img/' . $data->header_img;
                }
                $data->header_img = $filename;
            }

            // === update header_background (kalau ada file baru, gambar ATAU video) ===
            if ($bgBaru) {
                if ($isVideo) {
                    $bgFilename  = now()->timestamp . '_' . Str::uuid() . '.mp4';
                    $tersimpan[] = 'header/background/' . $bgFilename; // dicatat sebelum konversi: ffmpeg bisa meninggalkan file separuh jadi
                    $this->convertVideoToMp4(
                        $bgBaru->getRealPath(),
                        Storage::disk('public')->path('header/background/' . $bgFilename)
                    );
                } else {
                    $bgFilename = now()->timestamp . '_' . Str::uuid() . '.webp';
                    $webpData   = $this->convertToWebP($bgBaru->getRealPath(), 82);
                    Storage::disk('public')->put('header/background/' . $bgFilename, $webpData);
                    $tersimpan[] = 'header/background/' . $bgFilename;
                }

                if ($data->header_background) {
                    $hapusLama[] = 'header/background/' . $data->header_background;
                }
                $data->header_background = $bgFilename;
            }

            // update field text (selalu diupdate)
            $data->header_color       = $request->header_color;
            $data->header_title       = $request->header_title;
            $data->header_name        = $request->header_name;
            $data->header_description = $request->header_description;
            $data->link_header        = $request->link_header;
            $data->save();
        } catch (\Throwable $e) {
            return $this->gagalProsesHeader($e, $tersimpan);
        }

        // Data sudah tersimpan, sekarang aman membuang berkas lama.
        foreach ($hapusLama as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        return redirect()->route('headers')->with('success', 'Data berhasil diupdate');
    }

    public function hapusHeaders($id)
    {
        try {
            $data = header::findOrFail($id);

            // Hapus file dari storage jika ada
            if ($data->header_img && Storage::disk('public')->exists('header/img/' . $data->header_img)) {
                Storage::disk('public')->delete('header/img/' . $data->header_img);
            }
            // Hapus file dari storage jika ada
            if ($data->header_background && Storage::disk('public')->exists('header/background/' . $data->header_background)) {
                Storage::disk('public')->delete('header/background/' . $data->header_background);
            }

            $data->delete();

            return to_route('headers')->with('success', 'Header berhasil dihapus.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return to_route('headers')->withErrors('Header tidak ditemukan.');
        } catch (\Exception $e) {
            return to_route('headers')->withErrors('Gagal menghapus header.');
        }
    }
    // headers end


    // albums
    public function albums(Request $request)
    {
        $q = $this->kataCari($request);
        $albums = albums::query()
            ->when($q !== '', fn($query) => $this->cocok($query, ['albums_name', 'link_spotify'], $q))
            ->urut()
            ->paginate(UrutanTampil::PER_HALAMAN)
            ->withQueryString();
        $urutanBisa = $q === '';

        return view('pages.dashboard-pages.albums', compact('albums', 'q', 'urutanBisa'));
    }

    public function tambahAlbums(Request $request)
    {
        $request->validate([
            'albums_name' => 'required|string|max:255',
            'link_spotify' => 'required|url:http,https|max:255',
            'albums_cover' => 'required|image|mimes:jpg,jpeg,png|max:1024',
            'terbit_pada' => self::ATURAN_JADWAL,
        ], [
            'albums_name.required' => 'Nama Album harus diisi.',
            'link_spotify.required' => 'Link Spotify harus diisi.',
            'link_spotify.url' => 'Link Spotify harus berupa alamat lengkap yang diawali http:// atau https://.',
            'link_spotify.max' => 'Link Spotify terlalu panjang (maksimal 255 karakter).',
            'terbit_pada.date_format' => self::PESAN_JADWAL,
            'albums_cover.required' => 'Cover Album harus diisi.',
            'albums_cover.image' => 'File harus berupa gambar.',
            'albums_cover.max' => 'Cover Album tidak boleh lebih dari 1MB.',
        ]);

        $file     = $request->file('albums_cover');
        $filename = now()->timestamp . '_' . Str::uuid() . '.webp';

        // Konversi ke WebP → simpan ke storage/app/public/albums/
        $webpData = $this->convertToWebP($file->getRealPath(), 82);
        Storage::disk('public')->put('albums/' . $filename, $webpData);

        // simpan data ( simple )
        $data = new albums();
        $data->albums_name = $request->albums_name;
        $data->link_spotify = $request->link_spotify;
        $data->albums_cover = $filename; // hanya nama file
        $data->urutan = albums::urutanBerikut();
        $data->terbit_pada = $this->jadwal($request);
        $data->save();

        return redirect()->route('albums')->with('success', $this->pesanTambah('inputan berhasil ditambahkan', 'Album', $data->terbit_pada));
    }


    public function updateAlbums(Request $request, $id)
    {
        $request->validate([
            'albums_name' => 'required|string|max:255',
            'link_spotify' => 'required|url:http,https|max:255',
            'albums_cover' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
            'terbit_pada' => self::ATURAN_JADWAL,
        ], [
            'albums_name.required' => 'Nama Album harus diisi.',
            'link_spotify.required' => 'Link Spotify harus diisi.',
            'link_spotify.url' => 'Link Spotify harus berupa alamat lengkap yang diawali http:// atau https://.',
            'link_spotify.max' => 'Link Spotify terlalu panjang (maksimal 255 karakter).',
            'terbit_pada.date_format' => self::PESAN_JADWAL,
            'albums_cover.image' => 'File harus berupa gambar.',
            'albums_cover.max' => 'Cover Album tidak boleh lebih dari 1MB.',
        ]);

        $data = albums::findOrFail($id);
        if ($request->hasFile('albums_cover')) {
            // Konversi dan simpan file baru DULU: kalau gagal (mis. resolusi terlalu besar), file lama tetap utuh.
            $file     = $request->file('albums_cover');
            $filename = now()->timestamp . '_' . Str::uuid() . '.webp';
            $webpData = $this->convertToWebP($file->getRealPath(), 82);
            Storage::disk('public')->put('albums/' . $filename, $webpData);

            // Baru setelah itu file lama dihapus dari storage
            if ($data->albums_cover && Storage::disk('public')->exists('albums/' . $data->albums_cover)) {
                Storage::disk('public')->delete('albums/' . $data->albums_cover);
            }

            $data->albums_cover = $filename;
        }

        $data->albums_name = $request->albums_name;
        $data->link_spotify = $request->link_spotify;
        $data->terbit_pada = $this->jadwal($request);
        $data->save();

        return redirect()->back()->with('success', 'Data berhasil diupdate');
    }

    public function hapusAlbums($id)
    {
        try {
            $data = albums::findOrFail($id);

            // Hapus file dari storage jika ada
            if ($data->albums_cover && Storage::disk('public')->exists('albums/' . $data->albums_cover)) {
                Storage::disk('public')->delete('albums/' . $data->albums_cover);
            }

            $data->delete();

            return to_route('albums')->with('success', 'Album berhasil dihapus.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return to_route('albums')->withErrors('Album tidak ditemukan.');
        } catch (\Exception $e) {
            return to_route('albums')->withErrors('Gagal menghapus album.');
        }
    }    // albums end


    // news
    public function news(Request $request)
    {
        $q = $this->kataCari($request);
        $news = news::query()
            ->when($q !== '', fn($query) => $this->cocok($query, ['news_title', 'news_source'], $q))
            ->terbaruTayang()
            ->paginate(UrutanTampil::PER_HALAMAN)
            ->withQueryString();

        return view('pages.dashboard-pages.news', compact('news', 'q'));
    }

    public function tambahnews(Request $request)
    {
        $request->validate([
            'news_title' => 'required|string|max:255',
            'news_description' => 'required|string|max:20000',
            'news_source' => 'required|string|max:255',
            'news_date' => 'required|date_format:Y-m-d',
            'news_cover' => 'required|image|mimes:jpg,jpeg,png|max:1024',
            'news_link' => 'required|url:http,https|max:255',
            'terbit_pada' => self::ATURAN_JADWAL,
        ], [
            'news_title.required' => 'Judul Berita harus diisi.',
            'news_description.required' => 'Deskripsi Berita harus diisi.',
            'news_source.required' => 'Sumber Berita harus diisi.',
            'news_date.required' => 'Tanggal Berita harus diisi.',
            'news_date.date_format' => 'Tanggal Berita tidak valid. Pilih tanggal lewat pemilih tanggal.',
            'terbit_pada.date_format' => self::PESAN_JADWAL,
            'news_cover.required' => 'Cover Berita harus diisi.',
            'news_cover.image' => 'File harus berupa gambar.',
            'news_cover.max' => 'Cover Berita tidak boleh lebih dari 1MB.',
            'news_link.required' => 'Link Berita harus diisi.',
            'news_link.url' => 'Link Berita harus berupa alamat lengkap yang diawali http:// atau https://.',
            'news_link.max' => 'Link Berita terlalu panjang (maksimal 255 karakter).',
        ]);

        $file     = $request->file('news_cover');
        $filename = now()->timestamp . '_' . Str::uuid() . '.webp';

        // Konversi ke WebP → simpan ke storage/app/public/news/
        $webpData = $this->convertToWebP($file->getRealPath(), 82);
        Storage::disk('public')->put('news/' . $filename, $webpData);

        // simpan data ( simple )
        $data = new news();
        $data->news_title = $request->news_title;
        // Deskripsi dari Quill disanitasi dulu (allowlist tag/atribut) sebelum
        // disimpan — jaga-jaga kalau ke depannya ada lebih dari 1 admin.
        $data->news_description = HtmlSanitizer::clean($request->news_description);
        $data->news_source = $request->news_source;
        $data->news_date = $request->news_date;
        $data->news_cover = $filename; // hanya nama file
        $data->news_link = $request->news_link;
        $data->terbit_pada = $this->jadwal($request);
        $data->save();

        return redirect()->route('news')->with('success', $this->pesanTambah('inputan berhasil ditambahkan', 'Berita', $data->terbit_pada));
    }

    public function updatenews(Request $request, $id)
    {
        $request->validate([
            'news_title' => 'required|string|max:255',
            'news_description' => 'required|string|max:20000',
            'news_source' => 'required|string|max:255',
            'news_date' => 'required|date_format:Y-m-d',
            'news_cover' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
            'news_link' => 'required|url:http,https|max:255',
            'terbit_pada' => self::ATURAN_JADWAL,
        ], [
            'news_title.required' => 'Judul Berita harus diisi.',
            'news_description.required' => 'Deskripsi Berita harus diisi.',
            'news_source.required' => 'Sumber Berita harus diisi.',
            'news_date.required' => 'Tanggal Berita harus diisi.',
            'news_date.date_format' => 'Tanggal Berita tidak valid. Pilih tanggal lewat pemilih tanggal.',
            'terbit_pada.date_format' => self::PESAN_JADWAL,
            'news_cover.image' => 'File harus berupa gambar.',
            'news_cover.max' => 'Cover Berita tidak boleh lebih dari 1MB.',
            'news_link.required' => 'Link Berita harus diisi.',
            'news_link.url' => 'Link Berita harus berupa alamat lengkap yang diawali http:// atau https://.',
            'news_link.max' => 'Link Berita terlalu panjang (maksimal 255 karakter).',
        ]);

        $data = news::findOrFail($id);
        if ($request->hasFile('news_cover')) {
            // Konversi dan simpan file baru DULU: kalau gagal (mis. resolusi terlalu besar), file lama tetap utuh.
            $file     = $request->file('news_cover');
            $filename = now()->timestamp . '_' . Str::uuid() . '.webp';
            $webpData = $this->convertToWebP($file->getRealPath(), 82);
            Storage::disk('public')->put('news/' . $filename, $webpData);

            // Baru setelah itu file lama dihapus dari storage
            if ($data->news_cover && Storage::disk('public')->exists('news/' . $data->news_cover)) {
                Storage::disk('public')->delete('news/' . $data->news_cover);
            }

            $data->news_cover = $filename;
        }

        $data->news_title = $request->news_title;
        $data->news_description = HtmlSanitizer::clean($request->news_description);
        $data->news_source = $request->news_source;
        $data->news_date = $request->news_date;
        $data->news_link = $request->news_link;
        $data->terbit_pada = $this->jadwal($request);
        $data->save();

        return redirect()->back()->with('success', 'Data berhasil diupdate');
    }

    public function hapusnews($id)
    {
        try {
            $data = news::findOrFail($id);

            // Hapus file dari storage jika ada
            if ($data->news_cover && Storage::disk('public')->exists('news/' . $data->news_cover)) {
                Storage::disk('public')->delete('news/' . $data->news_cover);
            }

            $data->delete();

            return to_route('news')->with('success', 'news berhasil dihapus.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return to_route('news')->withErrors('news tidak ditemukan.');
        } catch (\Exception $e) {
            return to_route('news')->withErrors('Gagal menghapus news.');
        }
    }
    // news end


    // merchandise
    public function merchandise(Request $request)
    {
        $q = $this->kataCari($request);
        $merchandise = merchandise::query()
            ->when($q !== '', fn($query) => $this->cocok($query, ['merchandise_name', 'link_merchandise'], $q))
            ->urut()
            ->paginate(UrutanTampil::PER_HALAMAN)
            ->withQueryString();
        $urutanBisa = $q === '';

        return view('pages.dashboard-pages.merchandise', compact('merchandise', 'q', 'urutanBisa'));
    }

    public function tambahMerchandise(Request $request)
    {
        $request->validate([
            'merchandise_name' => 'required|string|max:255',
            'link_merchandise' => 'required|url:http,https|max:255',
            'merchandise_cover' => 'required|image|mimes:jpg,jpeg,png|max:1024',
        ], [
            'merchandise_name.required' => 'Nama Merchandise harus diisi.',
            'link_merchandise.required' => 'Link Merchandise harus diisi.',
            'link_merchandise.url' => 'Link Merchandise harus berupa alamat lengkap yang diawali http:// atau https://.',
            'link_merchandise.max' => 'Link Merchandise terlalu panjang (maksimal 255 karakter).',
            'merchandise_cover.required' => 'Cover Merchandise harus diisi.',
            'merchandise_cover.image' => 'File harus berupa gambar.',
            'merchandise_cover.max' => 'Cover Merchandise tidak boleh lebih dari 1MB.',
        ]);

        $file     = $request->file('merchandise_cover');
        $filename = now()->timestamp . '_' . Str::uuid() . '.webp';

        // Konversi ke WebP → simpan ke storage/app/public/merchandise/
        $webpData = $this->convertToWebP($file->getRealPath(), 82);
        Storage::disk('public')->put('merchandise/' . $filename, $webpData);

        // simpan data ( simple )
        $data = new merchandise();
        $data->merchandise_name = $request->merchandise_name;
        $data->link_merchandise = $request->link_merchandise;
        $data->merchandise_cover = $filename; // hanya nama file
        $data->urutan = merchandise::urutanBerikut();
        $data->save();

        return redirect()->route('merchandise')->with('success', 'inputan berhasil ditambahkan');
    }


    public function updatemerchandise(Request $request, $id)
    {
        $request->validate([
            'merchandise_name' => 'required|string|max:255',
            'link_merchandise' => 'required|url:http,https|max:255',
            'merchandise_cover' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
        ], [
            'merchandise_name.required' => 'Nama Merchandise harus diisi.',
            'link_merchandise.required' => 'Link Merchandise harus diisi.',
            'link_merchandise.url' => 'Link Merchandise harus berupa alamat lengkap yang diawali http:// atau https://.',
            'link_merchandise.max' => 'Link Merchandise terlalu panjang (maksimal 255 karakter).',
            'merchandise_cover.image' => 'File harus berupa gambar.',
            'merchandise_cover.max' => 'Cover Merchandise tidak boleh lebih dari 1MB.',
        ]);

        $data = merchandise::findOrFail($id);
        if ($request->hasFile('merchandise_cover')) {
            // Konversi dan simpan file baru DULU: kalau gagal (mis. resolusi terlalu besar), file lama tetap utuh.
            $file     = $request->file('merchandise_cover');
            $filename = now()->timestamp . '_' . Str::uuid() . '.webp';
            $webpData = $this->convertToWebP($file->getRealPath(), 82);
            Storage::disk('public')->put('merchandise/' . $filename, $webpData);

            // Baru setelah itu file lama dihapus dari storage
            if ($data->merchandise_cover && Storage::disk('public')->exists('merchandise/' . $data->merchandise_cover)) {
                Storage::disk('public')->delete('merchandise/' . $data->merchandise_cover);
            }

            $data->merchandise_cover = $filename;
        }

        $data->merchandise_name = $request->merchandise_name;
        $data->link_merchandise = $request->link_merchandise;
        $data->save();

        return redirect()->back()->with('success', 'Data berhasil diupdate');
    }

    public function hapusmerchandise($id)
    {
        try {
            $data = merchandise::findOrFail($id);

            // Hapus file dari storage jika ada
            if ($data->merchandise_cover && Storage::disk('public')->exists('merchandise/' . $data->merchandise_cover)) {
                Storage::disk('public')->delete('merchandise/' . $data->merchandise_cover);
            }

            $data->delete();

            return to_route('merchandise')->with('success', 'merchandise berhasil dihapus.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return to_route('merchandise')->withErrors('merchandise tidak ditemukan.');
        } catch (\Exception $e) {
            return to_route('merchandise')->withErrors('Gagal menghapus merchandise.');
        }
    }
    // merchandise end

    // ---------- bantuan: jadwal tayang dan pencarian ----------

    /** Isian `terbit_pada` dari <input type="datetime-local">: boleh kosong (= langsung tampil). */
    private const ATURAN_JADWAL = ['nullable', 'date_format:Y-m-d\TH:i'];

    private const PESAN_JADWAL = 'Jadwal tayang tidak valid. Pilih tanggal dan jam lewat pemilih yang tersedia, atau kosongkan agar langsung tampil.';

    /** Jadwal tayang dari form (zona waktu aplikasi), atau null bila dikosongkan. */
    private function jadwal(Request $request): ?Carbon
    {
        $nilai = trim((string) $request->input('terbit_pada'));

        return $nilai === '' ? null : Carbon::createFromFormat('Y-m-d\TH:i', $nilai, config('app.timezone'));
    }

    /** Pesan sukses setelah menambah: bila terjadwal, sebut kapan mulai tampil. */
    private function pesanTambah(string $biasa, string $objek, ?Carbon $jadwal): string
    {
        return $jadwal !== null && $jadwal->isFuture()
            ? $objek . ' dijadwalkan: mulai tampil di website ' . $jadwal->locale('id')->translatedFormat('j F Y, H.i') . ' WIB.'
            : $biasa;
    }

    /** Kata pencarian dari ?q= (dipangkas, maksimal 60 karakter). */
    private function kataCari(Request $request): string
    {
        return mb_substr(trim((string) $request->query('q')), 0, 60);
    }

    /**
     * Cari $kata di salah satu kolom. % dan _ yang diketik admin dibaca apa adanya (karakter pelolos `!` dipilih
     * karena artinya sama di MySQL dan SQLite). Nama kolom ditulis di kode, bukan dari isian pengguna.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string[]  $kolom
     */
    private function cocok($query, array $kolom, string $kata)
    {
        $pola = '%' . strtr($kata, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';

        return $query->where(function ($w) use ($kolom, $pola) {
            foreach ($kolom as $k) {
                $w->orWhereRaw("$k LIKE ? ESCAPE '!'", [$pola]);
            }
        });
    }

    private const PESAN_TANPA_FFMPEG = 'Video belum bisa diproses di server ini karena ffmpeg tidak tersedia. Gunakan gambar (JPG/PNG) sebagai latar header, atau hubungi tim WSM.';

    /** Apakah ffmpeg bisa dijalankan dari PHP di server ini (hosting bersama sering tidak menyediakannya). */
    private function ffmpegTersedia(): bool
    {
        static $tersedia = null;

        if ($tersedia === null) {
            try {
                $proses = new Process(['ffmpeg', '-version']);
                $proses->setTimeout(10);
                $proses->run();
                $tersedia = $proses->isSuccessful();
            } catch (\Throwable) {
                // proc_open dimatikan, binary tidak ada, dsb.
                $tersedia = false;
            }
        }

        return $tersedia;
    }

    /**
     * Pemrosesan berkas header gagal di tengah jalan: buang semua berkas baru yang sudah sempat ditulis
     * (tidak ada file yatim), lalu kembali ke form dengan pesan yang jelas, bukan error 500.
     * Pelanggaran validasi (mis. resolusi gambar) dilempar ulang supaya ditangani Laravel seperti biasa.
     *
     * @param  string[]  $tersimpan  path di disk 'public' milik berkas yang ditulis request ini
     */
    private function gagalProsesHeader(\Throwable $e, array $tersimpan): \Illuminate\Http\RedirectResponse
    {
        foreach ($tersimpan as $path) {
            Storage::disk('public')->delete($path);
        }

        if ($e instanceof ValidationException) {
            throw $e;
        }

        report($e);

        $pesan = $e instanceof ProcessFailedException
            ? 'Video gagal diproses. Pastikan file videonya tidak rusak, atau coba video lain.'
            : 'Gagal memproses file. Coba lagi, atau gunakan file lain.';

        return back()->withErrors(['header_background' => $pesan])->withInput();
    }

    /**
     * Convert & compress video jadi MP4 H.264 ringan, tanpa audio,
     * cocok untuk background video (biasanya di-autoplay + loop + muted).
     */
    private function convertVideoToMp4(string $inputPath, string $outputPath): void
    {
        // pastikan folder tujuan ada
        if (!is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        $process = new Process([
            'ffmpeg',
            '-y',
            '-i',
            $inputPath,
            '-t',
            '12',                    // potong ke 12 detik pertama
            '-an',                         // hapus audio
            '-vcodec',
            'libx264',
            '-preset',
            'slow',             // kompresi lebih efisien (proses convert sedikit lebih lama)
            '-crf',
            '30',                  // kompresi lebih agresif dari sebelumnya
            '-vf',
            'scale=1280:-2,fps=24', // resize + batasi frame rate
            '-movflags',
            '+faststart',
            $outputPath,
        ]);

        $process->setTimeout(300); // 5 menit, sesuaikan kalau video besar
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}