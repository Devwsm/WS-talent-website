<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tambah atau ubah satu hadiah di katalog (dashboard). Harga poin boleh diubah: penukaran yang sudah masuk menyimpan harganya
 * sendiri. `stok` kosong = tak terbatas. `opsi` dikirim sebagai teks satu pilihan per baris; hasilnya daftar kosong atau tepat dua pilihan.
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
            'stok' => ($this->input('stok') === null || trim((string) $this->input('stok')) === '') ? null : trim((string) $this->input('stok')),
            'judul' => trim((string) $this->input('judul')),
            'deskripsi' => trim((string) $this->input('deskripsi')),
            'opsi' => array_values(array_filter(
                array_map(fn($b) => is_string($b) ? trim($b) : '', $baris),
                fn($b) => $b !== ''
            )),
        ]);
    }

    /** Kembali ke hadiah yang sedang diedit (atau form tambah), lengkap dengan isian yang tadi diketik. */
    protected function getRedirectUrl(): string
    {
        $id = $this->route('id');

        return route('ic.admin.hadiah', $id ? ['hadiah' => $id] : ['baru' => 1]);
    }

    public function rules(): array
    {
        return [
            'harga_poin' => ['required', 'integer', 'min:1000', 'max:10000000'],
            'stok' => ['nullable', 'integer', 'min:0', 'max:100000'],
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
            'harga_poin' => 'Harga poin',
            'stok' => 'Stok',
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
            'integer' => ':attribute harus berupa angka bulat.',
            'min' => ':attribute minimal :min.',
            'harga_poin.max' => 'Harga poin terlalu besar.',
            'stok.max' => 'Stok terlalu besar.',
            'max' => ':attribute terlalu panjang (maksimal :max karakter).',
            'opsi.*.max' => 'Tiap pilihan hadiah maksimal :max karakter.',
            'opsi.*.distinct' => 'Dua pilihan hadiah tidak boleh sama.',
        ];
    }
}