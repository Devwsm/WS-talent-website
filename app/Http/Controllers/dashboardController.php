<?php

namespace App\Http\Controllers;

use App\Models\albums;
use App\Models\heroSection;
use App\Models\schedule;
use App\Models\merchandise;
use App\Models\news;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class dashboardController extends Controller
{
    // dashboard
    public function dashboard()
    {
        $hero = heroSection::all();
        $albums = albums::all();
        $schedule = schedule::all();
        $news = news::all();
        $merchandise = merchandise::all();
        return view(
            'pages.dashboard',
            compact(
                'hero',
                'albums',
                'schedule',
                'news',
                'merchandise',
            )
        );
    }
    // dashboard end

    public function tambahHero(Request $request)
    {
        $request->validate([
            'logo' => 'required|image',
            'background_video' => 'required|mimes:mp4'
        ]);

        // upload
        $logoName = time() . '_logo.' . $request->logo->extension();
        $request->logo->move(public_path('uploads/hero'), $logoName);

        $videoName = time() . '_video.' . $request->background_video->extension();
        $request->background_video->move(public_path('uploads/hero'), $videoName);

        // kalau cuma 1 data → hapus dulu yang lama
        $old = HeroSection::first();
        if ($old) {
            // hapus file lama
            File::delete(public_path('uploads/hero/' . $old->logo));
            File::delete(public_path('uploads/hero/' . $old->background_video));
            $old->delete();
        }

        HeroSection::create([
            'logo' => $logoName,
            'background_video' => $videoName
        ]);

        return back();
    }


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

        // ambil file
        $file = $request->file('albums_cover');
        // buat nama file unik
        $filename = time() . '.' . $file->getClientOriginalExtension();
        // pindahkan ke public/aset/albums
        $file->move(public_path('aset/albums'), $filename);

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
            $oldFile = public_path('aset/albums/' . $data->albums_cover);
            if ($data->albums_cover && file_exists($oldFile)) {
                unlink($oldFile);
            }
            $file = $request->file('albums_cover');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('aset/albums'), $filename);
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
            // ambil data dulu
            $data = albums::where('id_albums', $id)->first();
            // cek kalau data ada
            if ($data) {
                // path file
                $path = public_path('aset/albums/' . $data->albums_cover);
                // cek file ada, lalu hapus
                if (file_exists($path)) {
                    unlink($path);
                }
                // hapus data dari database
                $data->delete();
            }
            return to_route('albums')->with('success', 'Album berhasil dihapus');
        } catch (\Exception $e) {
            return to_route('albums')->withErrors('Gagal hapus data');
        }
    }
    // albums end


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

        // ambil file
        $file = $request->file('news_cover');
        // buat nama file unik
        $filename = time() . '.' . $file->getClientOriginalExtension();
        // pindahkan ke public/aset/news
        $file->move(public_path('aset/news'), $filename);

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
            $oldFile = public_path('aset/news/' . $data->news_cover);
            if ($data->news_cover && file_exists($oldFile)) {
                unlink($oldFile);
            }
            $file = $request->file('news_cover');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('aset/news'), $filename);
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
            // ambil data dulu
            $data = news::where('id_news', $id)->first();
            // cek kalau data ada
            if ($data) {
                // path file
                $path = public_path('aset/news/' . $data->news_cover);
                // cek file ada, lalu hapus
                if (file_exists($path)) {
                    unlink($path);
                }
                // hapus data dari database
                $data->delete();
            }
            return to_route('news')->with('success', 'Berita berhasil dihapus');
        } catch (\Exception $e) {
            return to_route('news')->withErrors('Gagal hapus data');
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

        // ambil file
        $file = $request->file('merchandise_cover');
        // buat nama file unik
        $filename = time() . '.' . $file->getClientOriginalExtension();
        // pindahkan ke public/aset/merchandise
        $file->move(public_path('aset/merchandise'), $filename);

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
            $oldFile = public_path('aset/merchandise/' . $data->merchandise_cover);
            if ($data->merchandise_cover && file_exists($oldFile)) {
                unlink($oldFile);
            }
            $file = $request->file('merchandise_cover');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('aset/merchandise'), $filename);
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
            // ambil data dulu
            $data = merchandise::where('id_merchandise', $id)->first();
            // cek kalau data ada
            if ($data) {
                // path file
                $path = public_path('aset/merchandise/' . $data->merchandise_cover);
                // cek file ada, lalu hapus
                if (file_exists($path)) {
                    unlink($path);
                }
                // hapus data dari database
                $data->delete();
            }
            return to_route('merchandise')->with('success', 'Merchandise berhasil dihapus');
        } catch (\Exception $e) {
            return to_route('merchandise')->withErrors('Gagal hapus data');
        }
    }
    // merchandise end
}