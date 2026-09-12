<?php

namespace App\Http\Controllers;

use App\Models\bio;
use App\Models\booking;
use App\Models\collab;
use App\Models\genre;
use App\Models\highlight;
use App\Models\media_coverage;
use App\Models\media_sosial;
use App\Models\profile_hero;
use App\Models\statistik;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class profileController extends Controller
{
    //
    public function profile()
    {
        $statistik = statistik::all();
        $highlight = highlight::all();

        // Fase 6 — data Profile lainnya (backend dulu, UI menyusul)
        $hero          = profile_hero::first();
        $genre         = genre::all();
        $bio           = bio::first();
        $collab        = collab::all();
        $mediaCoverage = media_coverage::all();
        $booking       = booking::all();
        $mediaSosial   = media_sosial::all();

        return view('pages.dashboard-pages.profile', compact(
            'statistik',
            'highlight',
            'hero',
            'genre',
            'bio',
            'collab',
            'mediaCoverage',
            'booking',
            'mediaSosial'
        ));
    }

    // statistik
    public function tambahStatistik(Request $request)
    {
        $request->validate([
            'total' => 'required',
            'platform' => 'required',
        ], [
            'total.required' => 'Total harus diisi.',
            'platform.required' => 'Platform harus diisi.',
        ]);

        // simpan data ( simple )
        $data = new statistik();
        $data->total = $request->total;
        $data->platform = $request->platform;
        $data->save();

        return redirect()->route('dashboard.profile')->with('success', 'inputan berhasil ditambahkan');
    }

    public function updateStatistik(Request $request, $id)
    {
        $request->validate([
            'total' => 'required',
            'platform' => 'required',
        ], [
            'total.required' => 'Total harus diisi.',
            'platform.required' => 'Platform harus diisi.',
        ]);

        $data = statistik::findOrFail($id);
        $data->total = $request->total;
        $data->platform = $request->platform;
        $data->save();

        return redirect()->back()->with('success', 'Data berhasil diupdate');
    }

    public function hapusStatistik($id)
    {
        try {
            $data = statistik::findOrFail($id);
            $data->delete();

            return to_route('dashboard.profile')->with('success', 'Statistik berhasil dihapus.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return to_route('dashboard.profile')->withErrors('Statistik tidak ditemukan.');
        } catch (\Exception $e) {
            return to_route('dashboard.profile')->withErrors('Gagal menghapus statistik.');
        }
    }
    // statistik end


    // highlight
    public function tambahHighlight(Request $request)
    {
        $request->validate([
            'place' => 'required',
            'description' => 'required',
            'year' => 'required',
        ], [
            'place.required' => 'place harus diisi.',
            'description.required' => 'description harus diisi.',
            'year.required' => 'year harus diisi.',
        ]);

        // simpan data ( simple )
        $data = new highlight();
        $data->place = $request->place;
        $data->description = $request->description;
        $data->year = $request->year;
        $data->save();

        return redirect()->route('dashboard.profile')->with('success', 'inputan berhasil ditambahkan');
    }

    public function updateHighlight(Request $request, $id)
    {
        $request->validate([
            'place' => 'required',
            'description' => 'required',
            'year' => 'required',
        ], [
            'place.required' => 'place harus diisi.',
            'description.required' => 'description harus diisi.',
            'year.required' => 'year harus diisi.',
        ]);

        $data = highlight::findOrFail($id);
        $data->place = $request->place;
        $data->description = $request->description;
        $data->year = $request->year;
        $data->save();

        return redirect()->back()->with('success', 'Data berhasil diupdate');
    }

    public function hapusHighlight($id)
    {
        try {
            $data = highlight::findOrFail($id);
            $data->delete();

            return to_route('dashboard.profile')->with('success', 'highlight berhasil dihapus.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return to_route('dashboard.profile')->withErrors('highlight tidak ditemukan.');
        } catch (\Exception $e) {
            return to_route('dashboard.profile')->withErrors('Gagal menghapus highlight.');
        }
    }
    // highlight end


    // hero (singleton — cuma 1 baris data)
    public function simpanHero(Request $request)
    {
        $request->validate([
            'judul_singkat' => 'required',
            'nama' => 'required',
            'tagline' => 'required',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png',
        ], [
            'judul_singkat.required' => 'Judul singkat harus diisi.',
            'nama.required' => 'Nama harus diisi.',
            'tagline.required' => 'Tagline harus diisi.',
            'foto.image' => 'File harus berupa gambar.',
        ]);

        $data = profile_hero::first() ?? new profile_hero();

        if ($request->hasFile('foto')) {
            // Hapus foto lama dari storage kalau ada
            if ($data->foto && Storage::disk('public')->exists('profile-hero/' . $data->foto)) {
                Storage::disk('public')->delete('profile-hero/' . $data->foto);
            }

            $file     = $request->file('foto');
            $filename = now()->timestamp . '_' . Str::uuid() . '.webp';
            $webpData = $this->convertToWebP($file->getRealPath(), 82);
            Storage::disk('public')->put('profile-hero/' . $filename, $webpData);

            $data->foto = $filename; // hanya nama file
        }

        $data->judul_singkat = $request->judul_singkat;
        $data->nama = $request->nama;
        $data->tagline = $request->tagline;
        $data->save();

        return redirect()->route('dashboard.profile')->with('success', 'Hero berhasil disimpan');
    }
    // hero end


    // bio (singleton — cuma 1 baris data, rich text dari Quill)
    public function simpanBio(Request $request)
    {
        $request->validate([
            'konten' => 'required',
        ], [
            'konten.required' => 'Bio harus diisi.',
        ]);

        $data = bio::first() ?? new bio();
        // Konten dari Quill disanitasi dulu (allowlist tag/atribut) sebelum
        // disimpan — jaga-jaga kalau ke depannya ada lebih dari 1 admin.
        $data->konten = HtmlSanitizer::clean($request->konten);
        $data->save();

        return redirect()->route('dashboard.profile')->with('success', 'Bio berhasil disimpan');
    }
    // bio end


    // genre
    public function tambahGenre(Request $request)
    {
        $request->validate([
            'nama_genre' => 'required',
        ], [
            'nama_genre.required' => 'Nama genre harus diisi.',
        ]);

        $data = new genre();
        $data->nama_genre = $request->nama_genre;
        $data->save();

        return redirect()->route('dashboard.profile')->with('success', 'inputan berhasil ditambahkan');
    }

    public function updateGenre(Request $request, $id)
    {
        $request->validate([
            'nama_genre' => 'required',
        ], [
            'nama_genre.required' => 'Nama genre harus diisi.',
        ]);

        $data = genre::findOrFail($id);
        $data->nama_genre = $request->nama_genre;
        $data->save();

        return redirect()->back()->with('success', 'Data berhasil diupdate');
    }

    public function hapusGenre($id)
    {
        try {
            $data = genre::findOrFail($id);
            $data->delete();

            return to_route('dashboard.profile')->with('success', 'Genre berhasil dihapus.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return to_route('dashboard.profile')->withErrors('Genre tidak ditemukan.');
        } catch (\Exception $e) {
            return to_route('dashboard.profile')->withErrors('Gagal menghapus genre.');
        }
    }
    // genre end


    // collab
    public function tambahCollab(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'role' => 'required',
        ], [
            'nama.required' => 'Nama harus diisi.',
            'role.required' => 'Role harus diisi.',
        ]);

        $data = new collab();
        $data->nama = $request->nama;
        $data->role = $request->role;
        $data->save();

        return redirect()->route('dashboard.profile')->with('success', 'inputan berhasil ditambahkan');
    }

    public function updateCollab(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required',
            'role' => 'required',
        ], [
            'nama.required' => 'Nama harus diisi.',
            'role.required' => 'Role harus diisi.',
        ]);

        $data = collab::findOrFail($id);
        $data->nama = $request->nama;
        $data->role = $request->role;
        $data->save();

        return redirect()->back()->with('success', 'Data berhasil diupdate');
    }

    public function hapusCollab($id)
    {
        try {
            $data = collab::findOrFail($id);
            $data->delete();

            return to_route('dashboard.profile')->with('success', 'Collab berhasil dihapus.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return to_route('dashboard.profile')->withErrors('Collab tidak ditemukan.');
        } catch (\Exception $e) {
            return to_route('dashboard.profile')->withErrors('Gagal menghapus collab.');
        }
    }
    // collab end


    // media coverage
    public function tambahMediaCoverage(Request $request)
    {
        $request->validate([
            'nama_media' => 'required',
        ], [
            'nama_media.required' => 'Nama media harus diisi.',
        ]);

        $data = new media_coverage();
        $data->nama_media = $request->nama_media;
        $data->save();

        return redirect()->route('dashboard.profile')->with('success', 'inputan berhasil ditambahkan');
    }

    public function updateMediaCoverage(Request $request, $id)
    {
        $request->validate([
            'nama_media' => 'required',
        ], [
            'nama_media.required' => 'Nama media harus diisi.',
        ]);

        $data = media_coverage::findOrFail($id);
        $data->nama_media = $request->nama_media;
        $data->save();

        return redirect()->back()->with('success', 'Data berhasil diupdate');
    }

    public function hapusMediaCoverage($id)
    {
        try {
            $data = media_coverage::findOrFail($id);
            $data->delete();

            return to_route('dashboard.profile')->with('success', 'Media coverage berhasil dihapus.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return to_route('dashboard.profile')->withErrors('Media coverage tidak ditemukan.');
        } catch (\Exception $e) {
            return to_route('dashboard.profile')->withErrors('Gagal menghapus media coverage.');
        }
    }
    // media coverage end


    // booking & kontak
    public function tambahBooking(Request $request)
    {
        $request->validate([
            'label' => 'required',
            'email' => 'required|email',
        ], [
            'label.required' => 'Label harus diisi.',
            'email.required' => 'Email harus diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $data = new booking();
        $data->label = $request->label;
        $data->email = $request->email;
        $data->save();

        return redirect()->route('dashboard.profile')->with('success', 'inputan berhasil ditambahkan');
    }

    public function updateBooking(Request $request, $id)
    {
        $request->validate([
            'label' => 'required',
            'email' => 'required|email',
        ], [
            'label.required' => 'Label harus diisi.',
            'email.required' => 'Email harus diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $data = booking::findOrFail($id);
        $data->label = $request->label;
        $data->email = $request->email;
        $data->save();

        return redirect()->back()->with('success', 'Data berhasil diupdate');
    }

    public function hapusBooking($id)
    {
        try {
            $data = booking::findOrFail($id);
            $data->delete();

            return to_route('dashboard.profile')->with('success', 'Booking berhasil dihapus.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return to_route('dashboard.profile')->withErrors('Booking tidak ditemukan.');
        } catch (\Exception $e) {
            return to_route('dashboard.profile')->withErrors('Gagal menghapus booking.');
        }
    }
    // booking end


    // media sosial
    public function tambahMediaSosial(Request $request)
    {
        $request->validate([
            'platform' => 'required',
            'url' => 'required|url',
        ], [
            'platform.required' => 'Platform harus diisi.',
            'url.required' => 'URL harus diisi.',
            'url.url' => 'Format URL tidak valid.',
        ]);

        $data = new media_sosial();
        $data->platform = $request->platform;
        $data->url = $request->url;
        $data->save();

        return redirect()->route('dashboard.profile')->with('success', 'inputan berhasil ditambahkan');
    }

    public function updateMediaSosial(Request $request, $id)
    {
        $request->validate([
            'platform' => 'required',
            'url' => 'required|url',
        ], [
            'platform.required' => 'Platform harus diisi.',
            'url.required' => 'URL harus diisi.',
            'url.url' => 'Format URL tidak valid.',
        ]);

        $data = media_sosial::findOrFail($id);
        $data->platform = $request->platform;
        $data->url = $request->url;
        $data->save();

        return redirect()->back()->with('success', 'Data berhasil diupdate');
    }

    public function hapusMediaSosial($id)
    {
        try {
            $data = media_sosial::findOrFail($id);
            $data->delete();

            return to_route('dashboard.profile')->with('success', 'Media sosial berhasil dihapus.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return to_route('dashboard.profile')->withErrors('Media sosial tidak ditemukan.');
        } catch (\Exception $e) {
            return to_route('dashboard.profile')->withErrors('Gagal menghapus media sosial.');
        }
    }
    // media sosial end


    /**
     * Convert gambar ke WebP. Sama persis sama helper di dashboardController —
     * sengaja di-duplicate (bukan di-extract ke trait) biar konsisten sama
     * pola project ini (tiap controller berdiri sendiri, lihat statistik/highlight).
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

        return $webpData !== false ? $webpData : '';
    }
}