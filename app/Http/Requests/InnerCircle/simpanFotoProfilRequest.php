<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;

/** Ganti foto profil anggota. Isi berkas diperiksa ulang oleh ImageProcessor, bukan hanya ekstensi dan tipe kiriman. */
class simpanFotoProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'foto' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:' . (int) config('inner_circle.foto_profil_maks_kb')],
        ];
    }

    public function messages(): array
    {
        $mb = rtrim(rtrim(number_format(config('inner_circle.foto_profil_maks_kb') / 1024, 1, ',', ''), '0'), ',');

        return [
            'foto.required' => 'Pilih foto lebih dulu.',
            'foto.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'foto.max' => "Ukuran foto maksimal {$mb} MB.",
            'foto.uploaded' => "Foto gagal diunggah. Pastikan ukurannya tidak lebih dari {$mb} MB, atau coba foto lain.",
            'foto.file' => 'Foto gagal diunggah. Coba pilih ulang.',
        ];
    }
}