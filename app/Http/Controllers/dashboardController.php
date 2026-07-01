<?php

namespace App\Http\Controllers;

use App\Models\albums;
use App\Models\banner;
use App\Models\header;
use App\Models\heroSection;
use App\Models\schedule;
use App\Models\merchandise;
use App\Models\news;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $schedule = schedule::all();
        $news = news::all();
        $merchandise = merchandise::all();
        return view(
            'pages.dashboard',
            compact(
                'banner',
                'albums',
                'header',
                'schedule',
                'news',
                'merchandise',
            )
        );
    }
    // dashboard end

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
            'link_banner' => 'required',
            'banner_cover' => 'required|image|mimes:jpg,jpeg,png|max:1024',
        ], [
            'banner_name.required' => 'Judul Banner harus diisi.',
            'link_banner.required' => 'Link Banner harus diisi.',
            'banner_cover.required' => 'Gambar Banner harus diisi.',
            'banner_cover.image' => 'File harus berupa gambar.',
            'banner_cover.max' => 'Gambar Banner tidak boleh lebih dari 1MB.',
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
            'header_title' => 'required',
            'header_img' => 'required|image|mimes:jpg,jpeg,png|max:1024',
            'header_name' => 'required',
            'header_description' => 'required',
            'link_header' => 'required',
            'header_background'  => 'required|file|mimes:jpg,jpeg,png,mp4,mov,avi,mkv,webm',
        ], [
            'header_title.required' => 'Judul Header harus diisi.',
            'header_img.required' => 'Gambar Header harus diisi.',
            'header_img.image' => 'File harus berupa gambar.',
            'header_img.max' => 'Cover Header tidak boleh lebih dari 1MB.',

            'header_name.required' => 'Nama Header harus diisi.',
            'header_description.required' => 'Deskripsi Header harus diisi.',
            'link_header.required' => 'Link Header harus diisi.',

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

        $file     = $request->file('header_img');
        $filename = now()->timestamp . '_' . Str::uuid() . '.webp';
        // Konversi ke WebP → simpan ke storage/app/public/albums/
        $webpData = $this->convertToWebP($file->getRealPath(), 82);
        Storage::disk('public')->put('header/img/' . $filename, $webpData);

        // === header_background (gambar ATAU video) ===
        if ($isVideo) {
            $bgFilename = now()->timestamp . '_' . Str::uuid() . '.mp4';
            $bgType     = 'video';
            $this->convertVideoToMp4(
                $bgFile->getRealPath(),
                Storage::disk('public')->path('header/background/' . $bgFilename)
            );
        } else {
            $bgFilename = now()->timestamp . '_' . Str::uuid() . '.webp';
            $bgType     = 'image';
            $webpData   = $this->convertToWebP($bgFile->getRealPath(), 82);
            Storage::disk('public')->put('header/background/' . $bgFilename, $webpData);
        }

        // simpan data ( simple )
        $data = new header();
        $data->header_title = $request->header_title;
        $data->header_img = $filename; // hanya nama file
        $data->header_name = $request->header_name;
        $data->header_description = $request->header_description;
        $data->link_header = $request->link_header;
        $data->header_background = $bgFilename; // hanya nama file
        $data->save();

        return redirect()->route('headers')->with('success', 'inputan berhasil ditambahkan');
    }


    public function updateHeaders(Request $request, $id)
    {
        $request->validate([
            'header_title' => 'required',
            'header_img' => 'required|image|mimes:jpg,jpeg,png|max:1024',
            'header_name' => 'required',
            'header_description' => 'required',
            'link_header' => 'required',
            'header_cover' => 'required|image|mimes:jpg,jpeg,png|max:1024',
        ], [
            'header_title.required' => 'Judul Header harus diisi.',
            'header_img.required' => 'Gambar Header harus diisi.',
            'header_img.image' => 'File harus berupa gambar.',
            'header_img.max' => 'Cover Header tidak boleh lebih dari 1MB.',

            'header_name.required' => 'Nama Header harus diisi.',
            'header_description.required' => 'Deskripsi Header harus diisi.',
            'link_header.required' => 'Link Header harus diisi.',

            'header_cover.required' => 'Cover Header harus diisi.',
            'header_cover.image' => 'File harus berupa gambar.',
            'header_cover.max' => 'Cover Header tidak boleh lebih dari 1MB.',
        ]);

        $data = header::findOrFail($id);
        if ($request->hasFile('header_cover')) {
            // Hapus file lama dari storage
            if ($data->header_cover && Storage::disk('public')->exists('header/' . $data->header_cover)) {
                Storage::disk('public')->delete('header/' . $data->header_cover);
            }

            // Konversi dan simpan file baru
            $file     = $request->file('header_cover');
            $filename = now()->timestamp . '_' . Str::uuid() . '.webp';
            $webpData = $this->convertToWebP($file->getRealPath(), 82);
            Storage::disk('public')->put('header/' . $filename, $webpData);

            $data->header_cover = $filename;
        }

        $data->header_name = $request->header_name;
        $data->header_description = $request->header_description;
        $data->link_header = $request->link_header;
        $data->save();

        return redirect()->back()->with('success', 'Data berhasil diupdate');
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
        $albums = albums::all();
        return view('pages.dashboard-pages.albums', compact('albums'));
    }

    public function tambahAlbums(Request $request)
    {
        $request->validate([
            'albums_name' => 'required',
            'link_spotify' => 'required',
            'albums_cover' => 'required|image|mimes:jpg,jpeg,png|max:1024',
        ], [
            'albums_name.required' => 'Nama Album harus diisi.',
            'link_spotify.required' => 'Link Spotify harus diisi.',
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
            'link_spotify' => 'required',
            'albums_cover' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
        ], [
            'albums_name.required' => 'Nama Album harus diisi.',
            'link_spotify.required' => 'Link Spotify harus diisi.',
            'albums_cover.image' => 'File harus berupa gambar.',
            'albums_cover.max' => 'Cover Album tidak boleh lebih dari 1MB.',
        ]);

        $data = albums::findOrFail($id);
        if ($request->hasFile('albums_cover')) {
            // Hapus file lama dari storage
            if ($data->albums_cover && Storage::disk('public')->exists('albums/' . $data->albums_cover)) {
                Storage::disk('public')->delete('albums/' . $data->albums_cover);
            }

            // Konversi dan simpan file baru
            $file     = $request->file('albums_cover');
            $filename = now()->timestamp . '_' . Str::uuid() . '.webp';
            $webpData = $this->convertToWebP($file->getRealPath(), 82);
            Storage::disk('public')->put('albums/' . $filename, $webpData);

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
        $news = news::all();
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
            'news_link' => 'required',
        ], [
            'news_title.required' => 'Judul Berita harus diisi.',
            'news_description.required' => 'Deskripsi Berita harus diisi.',
            'news_source.required' => 'Sumber Berita harus diisi.',
            'news_date.required' => 'Tanggal Berita harus diisi.',
            'news_cover.required' => 'Cover Berita harus diisi.',
            'news_cover.image' => 'File harus berupa gambar.',
            'news_cover.max' => 'Cover Berita tidak boleh lebih dari 1MB.',
            'news_link.required' => 'Link Berita harus diisi.',
        ]);

        $file     = $request->file('news_cover');
        $filename = now()->timestamp . '_' . Str::uuid() . '.webp';

        // Konversi ke WebP → simpan ke storage/app/public/news/
        $webpData = $this->convertToWebP($file->getRealPath(), 82);
        Storage::disk('public')->put('news/' . $filename, $webpData);

        // simpan data ( simple )
        $data = new news();
        $data->news_title = $request->news_title;
        $data->news_description = $request->news_description;
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
            'news_link' => 'required',
        ], [
            'news_title.required' => 'Judul Berita harus diisi.',
            'news_description.required' => 'Deskripsi Berita harus diisi.',
            'news_source.required' => 'Sumber Berita harus diisi.',
            'news_date.required' => 'Tanggal Berita harus diisi.',
            'news_cover.image' => 'File harus berupa gambar.',
            'news_cover.max' => 'Cover Berita tidak boleh lebih dari 1MB.',
            'news_link.required' => 'Link Berita harus diisi.',
        ]);

        $data = news::findOrFail($id);
        if ($request->hasFile('news_cover')) {
            // Hapus file lama dari storage
            if ($data->news_cover && Storage::disk('public')->exists('news/' . $data->news_cover)) {
                Storage::disk('public')->delete('news/' . $data->news_cover);
            }

            // Konversi dan simpan file baru
            $file     = $request->file('news_cover');
            $filename = now()->timestamp . '_' . Str::uuid() . '.webp';
            $webpData = $this->convertToWebP($file->getRealPath(), 82);
            Storage::disk('public')->put('news/' . $filename, $webpData);

            $data->news_cover = $filename;
        }

        $data->news_title = $request->news_title;
        $data->news_description = $request->news_description;
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


    // schedule
    public function schedule()
    {
        $schedule = schedule::all();
        return view('pages.dashboard-pages.schedule', compact('schedule'));
    }

    public function tambahSchedule(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date|before:9999-12-31',
            'nama_tempat' => 'required',
            'daerah' => 'required',
        ], [
            'tanggal.required' => 'Tanggal harus diisi.',
            'tanggal.date' => 'Format tanggal tidak valid.',
            'tanggal.before' => 'Tanggal terlalu besar / tidak masuk akal.',
            'nama_tempat.required' => 'Nama Tempat harus diisi.',
            'daerah.required' => 'Daerah harus diisi.',
        ]);

        // simpan data ( simple )
        $data = new schedule();
        $data->tanggal = $request->tanggal;
        $data->nama_tempat = $request->nama_tempat;
        $data->daerah = $request->daerah;
        $data->save();

        return redirect()->route('schedule')->with('success', 'inputan berhasil ditambahkan');
    }

    public function hapusSchedule($id)
    {
        try {
            schedule::where('id_schedule', $id)->delete();
            return to_route('schedule')->with('success', 'jadwal berhasil dihapus');
        } catch (\Exception $e) {
            return to_route('schedule')->withErrors('gagal hapus');
        }
    }
    // schedule end


    // merchandise
    public function merchandise()
    {
        $merchandise = merchandise::all();
        return view('pages.dashboard-pages.merchandise', compact('merchandise'));
    }

    public function tambahmerchandise(Request $request)
    {
        $request->validate([
            'merchandise_name' => 'required',
            'link_merchandise' => 'required',
            'merchandise_cover' => 'required|image|mimes:jpg,jpeg,png|max:1024',
        ], [
            'merchandise_name.required' => 'Nama Merchandise harus diisi.',
            'link_merchandise.required' => 'Link Merchandise harus diisi.',
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
            'link_merchandise' => 'required',
            'merchandise_cover' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
        ], [
            'merchandise_name.required' => 'Nama Merchandise harus diisi.',
            'link_merchandise.required' => 'Link Merchandise harus diisi.',
            'merchandise_cover.image' => 'File harus berupa gambar.',
            'merchandise_cover.max' => 'Cover Merchandise tidak boleh lebih dari 1MB.',
        ]);

        $data = merchandise::findOrFail($id);
        if ($request->hasFile('merchandise_cover')) {
            // Hapus file lama dari storage
            if ($data->merchandise_cover && Storage::disk('public')->exists('merchandise/' . $data->merchandise_cover)) {
                Storage::disk('public')->delete('merchandise/' . $data->merchandise_cover);
            }

            // Konversi dan simpan file baru
            $file     = $request->file('merchandise_cover');
            $filename = now()->timestamp . '_' . Str::uuid() . '.webp';
            $webpData = $this->convertToWebP($file->getRealPath(), 82);
            Storage::disk('public')->put('merchandise/' . $filename, $webpData);

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
     * Konversi gambar (JPG/PNG) ke WebP menggunakan PHP GD.
     * Tidak butuh library tambahan — GD sudah aktif by default di cPanel.
     *
     * @param  string  $sourcePath  Path file asli
     * @param  string  $destPath    Path tujuan file .webp
     * @param  int     $quality     Kualitas WebP: 0–100 (82 = sweet spot ukuran vs kualitas)
     */
    private function convertToWebP(string $sourcePath, int $quality = 82): string
    {
        $mime = mime_content_type($sourcePath);

        $image = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png'  => imagecreatefrompng($sourcePath),
            default      => throw new \RuntimeException("Format tidak didukung: {$mime}"),
        };

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
            '-y',                       // overwrite jika ada
            '-i',
            $inputPath,
            '-an',                      // hapus audio (background video biasanya muted)
            '-vcodec',
            'libx264',
            '-preset',
            'veryfast',
            '-crf',
            '28',               // makin besar = makin ringan (kualitas turun sedikit)
            '-vf',
            'scale=1280:-2',     // resize max width 1280px, height auto (kelipatan 2)
            '-movflags',
            '+faststart',  // biar bisa langsung play sebelum full download
            $outputPath,
        ]);

        $process->setTimeout(300); // 5 menit, sesuaikan kalau video besar
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}