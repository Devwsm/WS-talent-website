<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Unggah kenangan oleh anggota: cerita, tempat, kategori, dan foto opsional. Teks apa adanya (di-escape saat tampil). */
class simpanKenanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $teks = fn(string $kunci) => is_string($this->input($kunci)) ? trim($this->input($kunci)) : '';

        $this->merge([
            'caption' => $teks('caption'),
            'tempat' => $teks('tempat'),
        ]);
    }

    public function rules(): array
    {
        return [
            'caption' => ['required', 'string', 'max:240'],
            'tempat' => ['nullable', 'string', 'max:65'],
            'kategori' => ['required', Rule::in(array_keys(config('inner_circle.kategori_kenangan')))],
            'foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:' . (int) config('inner_circle.foto_kenangan_maks_kb')],
        ];
    }

    public function messages(): array
    {
        $mb = rtrim(rtrim(number_format(config('inner_circle.foto_kenangan_maks_kb') / 1024, 1, ',', ''), '0'), ',');

        return [
            'caption.required' => 'Tulis dulu cerita singkat tentang momenmu.',
            'caption.max' => 'Ceritamu terlalu panjang (maksimal :max karakter).',
            'tempat.max' => 'Nama acara atau tempat terlalu panjang (maksimal :max karakter).',
            'kategori.required' => 'Pilih kategori kenangan.',
            'kategori.in' => 'Kategori kenangan tidak dikenal.',
            'foto.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'foto.max' => "Ukuran foto maksimal {$mb} MB.",
            'foto.uploaded' => "Foto gagal diunggah. Pastikan ukurannya tidak lebih dari {$mb} MB, atau coba foto lain.",
            'foto.file' => 'Foto gagal diunggah. Coba pilih ulang.',
        ];
    }
}