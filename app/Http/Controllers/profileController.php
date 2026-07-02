<?php

namespace App\Http\Controllers;

use App\Models\statistik;
use Illuminate\Http\Request;

class profileController extends Controller
{
    //
    public function profile()
    {
        $statistik = statistik::all();
        return view('pages.dashboard-pages.profile', compact('statistik'));
    }
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
    }    // statistik end
}