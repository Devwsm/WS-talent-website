<?php

namespace App\Http\Controllers;

use App\Models\highlight;
use App\Models\statistik;
use Illuminate\Http\Request;

class profileController extends Controller
{
    //
    public function profile()
    {
        $statistik = statistik::all();
        $highlight = highlight::all();
        return view('pages.dashboard-pages.profile', compact('statistik', 'highlight'));
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
}