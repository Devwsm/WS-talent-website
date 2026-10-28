<?php

namespace App\Http\Controllers;

use App\Models\albums;
use App\Models\banner;
use App\Models\color_pages;
use App\Models\header;
use App\Models\highlight;
use App\Models\merchandise;
use App\Models\news;
use App\Models\statistik;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class dashboardController extends Controller
{
    // dashboard
    public function dashboard()
    {
        $banner = banner::all();
        $albums = albums::all();
        $header = header::all();
        $news = news::all();
        $merchandise = merchandise::all();
        $color_pages = color_pages::first();
        $highlightCount = highlight::count();
        $statistikCount = statistik::count();

        return view(
            'pages.dashboard',
            compact(
                'banner',
                'albums',
                'header',
                'news',
                'merchandise',
                'color_pages',
                'highlightCount',
                'statistikCount',
            )
        );
    }
    // dashboard end

    // color_pages
    public function ColorPages()
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
        $banner = banner::all();
        return view('pages.dashboard-pages.banner', compact('banner'));
    }

    public function tambahBanner(Request $request)
    {
        $request->validate([
            'banner_name' => 'required',
            'link_banner' => 'required|url:http,https|max:2048',
            'banner_cover' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'banner_name.required' => 'Judul Banner harus diisi.',
            'link_banner.required' => 'Link Banner harus diisi.',
            'link_banner.url' => 'Link Banner harus berupa alamat lengkap yang diawali http:// atau https://.',
            'link_banner.max' => 'Link Banner terlalu panjang (maksimal 2048 karakter).',
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
        $data->save();

        return redirect()->route('banner')->with('success', 'inputan berhasil ditambahkan');
    }

    public function updateBanner(Request $request, $id)
    {
        $request->validate([
            'banner_name' => 'required',
            'link_banner' => 'required|url:http,https|max:2048',
            'banner_cover' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'banner_name.required' => 'Judul Banner harus diisi.',
            'link_banner.required' => 'Link Banner harus diisi.',
            'link_banner.url' => 'Link Banner harus berupa alamat lengkap yang diawali http:// atau https://.',
            'link_banner.max' => 'Link Banner terlalu panjang (maksimal 2048 karakter).',
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
        $header = header::all();
        return view('pages.dashboard-pages.header', compact('header'));
    }

    public function tambahHeaders(Request $request)
    {
        $request->validate([
            'header_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'header_title' => 'required',
            'header_img' => 'required|image|mimes:jpg,jpeg,png|max:1024',
            'header_name' => 'required',
            'header_description' => 'required',
            'link_header' => 'required|url:http,https|max:2048',
            'header_background'  => 'required|file|mimes:jpg,jpeg,png,mp4,mov,avi,mkv,webm',
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
            'link_header.max' => 'Link Header terlalu panjang (maksimal 2048 karakter).',

            'header_background.required' => 'Cover Header harus diisi.',
            'header_background.mimes'    => 'File harus berupa gambar (jpg, jpeg, png) atau video (mp4, mov, avi, mkv, webm).',
        ]);
        // Validasi ukuran manual (beda batas untuk gambar vs video)
        $bgFile   = $request->file('header_background');
        $bgMime   = $bgFile->getMimeType();
        $isVideo  = str_starts_with($bgMime, 'video/');

        $maxImageSize = 1 * 1024 * 1024;   // 1MB
        $maxVideoSize = 25 * 1024 * 1024;  // 25MB (raw, sebelum dikompres)

        if (!$isVideo && $bgFile->getSize() > $maxImageSize) {
            return back()->withErrors(['header_background' => 'Gambar Cover Header tidak boleh lebih dari 1MB.'])->withInput();
        }
        if ($isVideo && $bgFile->getSize() > $maxVideoSize) {
            return back()->withErrors(['header_background' => 'Video Cover Header tidak boleh lebih dari 25MB.'])->withInput();
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
            'header_title'       => 'required',
            'header_img'         => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
            'header_name'        => 'required',
            'header_description' => 'required',
            'link_header'        => 'required|url:http,https|max:2048',
            'header_background'  => 'nullable|file|mimes:jpg,jpeg,png,mp4,mov,avi,mkv,webm',
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
            'link_header.max' => 'Link Header terlalu panjang (maksimal 2048 karakter).',

            'header_background.mimes' => 'File harus berupa gambar (jpg, jpeg, png) atau video (mp4, mov, avi, mkv, webm).',
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
            $maxVideoSize = 25 * 1024 * 1024;  // 25MB

            if (!$isVideo && $bgBaru->getSize() > $maxImageSize) {
                return back()->withErrors(['header_background' => 'Gambar Cover Header tidak boleh lebih dari 1MB.'])->withInput();
            }
            if ($isVideo && $bgBaru->getSize() > $maxVideoSize) {
                return back()->withErrors(['header_background' => 'Video Cover Header tidak boleh lebih dari 25MB.'])->withInput();
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
    public function albums()
    {
        $albums = albums::latest()->paginate(9)->withQueryString();
        return view('pages.dashboard-pages.albums', compact('albums'));
    }

    public function tambahAlbums(Request $request)
    {
        $request->validate([
            'albums_name' => 'required',
            'link_spotify' => 'required|url:http,https|max:2048',
            'albums_cover' => 'required|image|mimes:jpg,jpeg,png|max:1024',
        ], [
            'albums_name.required' => 'Nama Album harus diisi.',
            'link_spotify.required' => 'Link Spotify harus diisi.',
            'link_spotify.url' => 'Link Spotify harus berupa alamat lengkap yang diawali http:// atau https://.',
            'link_spotify.max' => 'Link Spotify terlalu panjang (maksimal 2048 karakter).',
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
        $data->save();

        return redirect()->route('albums')->with('success', 'inputan berhasil ditambahkan');
    }


    public function updateAlbums(Request $request, $id)
    {
        $request->validate([
            'albums_name' => 'required',
            'link_spotify' => 'required|url:http,https|max:2048',
            'albums_cover' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
        ], [
            'albums_name.required' => 'Nama Album harus diisi.',
            'link_spotify.required' => 'Link Spotify harus diisi.',
            'link_spotify.url' => 'Link Spotify harus berupa alamat lengkap yang diawali http:// atau https://.',
            'link_spotify.max' => 'Link Spotify terlalu panjang (maksimal 2048 karakter).',
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
    public function news()
    {
        $news = news::latest()->paginate(9)->withQueryString();
        return view('pages.dashboard-pages.news', compact('news'));
    }

    public function tambahnews(Request $request)
    {
        $request->validate([
            'news_title' => 'required',
            'news_description' => 'required',
            'news_source' => 'required',
            'news_date' => 'required',
            'news_cover' => 'required|image|mimes:jpg,jpeg,png|max:1024',
            'news_link' => 'required|url:http,https|max:2048',
        ], [
            'news_title.required' => 'Judul Berita harus diisi.',
            'news_description.required' => 'Deskripsi Berita harus diisi.',
            'news_source.required' => 'Sumber Berita harus diisi.',
            'news_date.required' => 'Tanggal Berita harus diisi.',
            'news_cover.required' => 'Cover Berita harus diisi.',
            'news_cover.image' => 'File harus berupa gambar.',
            'news_cover.max' => 'Cover Berita tidak boleh lebih dari 1MB.',
            'news_link.required' => 'Link Berita harus diisi.',
            'news_link.url' => 'Link Berita harus berupa alamat lengkap yang diawali http:// atau https://.',
            'news_link.max' => 'Link Berita terlalu panjang (maksimal 2048 karakter).',
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
        $data->save();

        return redirect()->route('news')->with('success', 'inputan berhasil ditambahkan');
    }

    public function updatenews(Request $request, $id)
    {
        $request->validate([
            'news_title' => 'required',
            'news_description' => 'required',
            'news_source' => 'required',
            'news_date' => 'required',
            'news_cover' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
            'news_link' => 'required|url:http,https|max:2048',
        ], [
            'news_title.required' => 'Judul Berita harus diisi.',
            'news_description.required' => 'Deskripsi Berita harus diisi.',
            'news_source.required' => 'Sumber Berita harus diisi.',
            'news_date.required' => 'Tanggal Berita harus diisi.',
            'news_cover.image' => 'File harus berupa gambar.',
            'news_cover.max' => 'Cover Berita tidak boleh lebih dari 1MB.',
            'news_link.required' => 'Link Berita harus diisi.',
            'news_link.url' => 'Link Berita harus berupa alamat lengkap yang diawali http:// atau https://.',
            'news_link.max' => 'Link Berita terlalu panjang (maksimal 2048 karakter).',
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
    public function merchandise()
    {
        $merchandise = merchandise::latest()->paginate(9)->withQueryString();
        return view('pages.dashboard-pages.merchandise', compact('merchandise'));
    }

    public function tambahmerchandise(Request $request)
    {
        $request->validate([
            'merchandise_name' => 'required',
            'link_merchandise' => 'required|url:http,https|max:2048',
            'merchandise_cover' => 'required|image|mimes:jpg,jpeg,png|max:1024',
        ], [
            'merchandise_name.required' => 'Nama Merchandise harus diisi.',
            'link_merchandise.required' => 'Link Merchandise harus diisi.',
            'link_merchandise.url' => 'Link Merchandise harus berupa alamat lengkap yang diawali http:// atau https://.',
            'link_merchandise.max' => 'Link Merchandise terlalu panjang (maksimal 2048 karakter).',
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
        $data->save();

        return redirect()->route('merchandise')->with('success', 'inputan berhasil ditambahkan');
    }


    public function updatemerchandise(Request $request, $id)
    {
        $request->validate([
            'merchandise_name' => 'required',
            'link_merchandise' => 'required|url:http,https|max:2048',
            'merchandise_cover' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
        ], [
            'merchandise_name.required' => 'Nama Merchandise harus diisi.',
            'link_merchandise.required' => 'Link Merchandise harus diisi.',
            'link_merchandise.url' => 'Link Merchandise harus berupa alamat lengkap yang diawali http:// atau https://.',
            'link_merchandise.max' => 'Link Merchandise terlalu panjang (maksimal 2048 karakter).',
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


    /**
     * Batas jumlah piksel (lebar x tinggi) gambar yang mau didecode. Berkas kecil di disk bisa mengembang jadi
     * ratusan MB di memori (bom dekompresi) dan menjatuhkan request dengan error 500 / kehabisan memori.
     * 16 MP ~ 4000x4000, decode-nya sekitar 64 MB sehingga masih aman di hosting dengan memory_limit 128 MB.
     */
    private const MAKS_PIKSEL = 16_000_000;

    /**
     * Konversi gambar (JPG/PNG) ke WebP menggunakan PHP GD.
     * Tidak butuh library tambahan — GD sudah aktif by default di cPanel.
     *
     * @param  string  $sourcePath  Path file asli
     * @param  string  $destPath    Path tujuan file .webp
     * @param  int     $quality     Kualitas WebP: 0–100 (82 = sweet spot ukuran vs kualitas)
     */
    private function convertToWebP(string $sourcePath, int $quality = 82): string
    {
        // Periksa ukuran SEBELUM didecode. Pelanggaran jadi pesan validasi (bukan error 500).
        $info = @getimagesize($sourcePath);
        if ($info === false || $info[0] < 1 || $info[1] < 1) {
            throw ValidationException::withMessages(['gambar' => 'Berkas ini bukan gambar yang valid. Pilih gambar JPG atau PNG.']);
        }
        if ($info[0] * $info[1] > self::MAKS_PIKSEL) {
            throw ValidationException::withMessages(['gambar' => 'Resolusi gambar terlalu besar (maksimal 16 megapiksel, misalnya 4000x4000 piksel). Kecilkan gambar lalu unggah ulang.']);
        }

        $mime = mime_content_type($sourcePath);

        $image = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png'  => imagecreatefrompng($sourcePath),
            default      => throw new \RuntimeException("Format tidak didukung: {$mime}"),
        };

        if ($image === false) {
            throw ValidationException::withMessages(['gambar' => 'Gambar tidak bisa dibaca. Coba simpan ulang gambarnya lalu unggah lagi.']);
        }

        if ($mime === 'image/png') {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }

        ob_start();
        imagewebp($image, null, $quality);
        $webpData = ob_get_clean();

        imagedestroy($image);

        // Intelephense perlu return eksplisit di akhir — ini yang bikin warning hilang
        return $webpData !== false ? $webpData : '';
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