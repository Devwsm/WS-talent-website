<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ubah satu tahapan hadiah di dashboard. Target stempel tetap (dari URL), jadi tidak ikut diubah.
 * `opsi` dikirim sebagai teks satu pilihan per baris; hasilnya daftar kosong atau tepat dua pilihan.
 */
class simpanHadiahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $opsi = $this->input('opsi');
        $baris = is_string($opsi) ? preg_split('/\R/u', $opsi) : (is_array($opsi) ? $opsi : []);

        $this->merge([
            'judul' => trim((string) $this->input('judul')),
            'deskripsi' => trim((string) $this->input('deskripsi')),
            'opsi' => array_values(array_filter(
                array_map(fn($b) => is_string($b) ? trim($b) : '', $baris),
                fn($b) => $b !== ''
            )),
        ]);
    }

    /** Kembali ke tahap yang sedang diedit (bukan tahap pertama), lengkap dengan isian yang tadi diketik. */
    protected function getRedirectUrl(): string
    {
        return route('ic.admin.hadiah', ['tahap' => $this->route('target')]);
    }

    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:40'],
            'deskripsi' => ['required', 'string', 'max:140'],
            'ikon' => ['required', Rule::in(array_keys(config('inner_circle.ikon_pilihan')))],
            'warna' => ['required', Rule::in(array_keys(config('inner_circle.warna_kartu')))],
            'opsi' => [
                'array',
                function (string $atribut, mixed $nilai, \Closure $gagal) {
                    if (! in_array(count($nilai), [0, 2], true)) {
                        $gagal('Isi tepat dua pilihan hadiah (satu per baris), atau kosongkan bila hadiah ini tidak punya pilihan.');
                    }
                },
            ],
            'opsi.*' => ['string', 'max:60', 'distinct'],
        ];
    }

    public function attributes(): array
    {
        return [
            'judul' => 'Judul',
            'deskripsi' => 'Deskripsi',
            'ikon' => 'Ikon',
            'warna' => 'Warna',
            'opsi' => 'Pilihan hadiah',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'in' => ':attribute tidak dikenal.',
            'max' => ':attribute terlalu panjang (maksimal :max karakter).',
            'opsi.*.max' => 'Tiap pilihan hadiah maksimal :max karakter.',
            'opsi.*.distinct' => 'Dua pilihan hadiah tidak boleh sama.',
        ];
    }
}