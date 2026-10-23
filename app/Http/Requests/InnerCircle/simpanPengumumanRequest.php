<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tambah atau ubah pengumuman di dashboard (route ubah membawa {id}). Panjang teks mengikuti kolom tabel
 * dan batas di form. `unggulan` dikirim sebagai kotak centang (ada atau tidak ada).
 */
class simpanPengumumanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $teks = fn(string $kunci) => is_string($this->input($kunci)) ? trim($this->input($kunci)) : '';

        $this->merge([
            'tag' => $teks('tag'),
            'judul' => $teks('judul'),
            'isi' => $teks('isi'),
            'cta_label' => $teks('cta_label'),
            'unggulan' => $this->boolean('unggulan'),
        ]);
    }

    /** Gagal validasi: kembali ke form yang sama (mode ubah tetap mode ubah), bukan ke form kosong. */
    protected function getRedirectUrl(): string
    {
        $id = $this->route('id');

        return route('ic.admin.pengumuman', $id ? ['ubah' => $id] : []) . '#form-pengumuman-bagian';
    }

    public function rules(): array
    {
        return [
            'tag' => ['nullable', 'string', 'max:40'],
            'terbit_pada' => ['required', 'date_format:Y-m-d'],
            'judul' => ['required', 'string', 'max:80'],
            'isi' => ['required', 'string', 'max:220'],
            'ikon' => ['required', Rule::in(array_keys(config('inner_circle.ikon_pilihan')))],
            'warna' => ['required', Rule::in(array_keys(config('inner_circle.warna_kartu')))],
            'cta_label' => ['required', 'string', 'max:30'],
            'cta_tujuan' => ['required', Rule::in(array_keys(config('inner_circle.cta_pengumuman')))],
            'unggulan' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tag' => 'Kategori',
            'terbit_pada' => 'Tanggal terbit',
            'judul' => 'Judul',
            'isi' => 'Isi',
            'ikon' => 'Ikon',
            'warna' => 'Warna',
            'cta_label' => 'Teks tombol',
            'cta_tujuan' => 'Tujuan tombol',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'in' => ':attribute tidak dikenal.',
            'max' => ':attribute terlalu panjang (maksimal :max karakter).',
            'date_format' => ':attribute tidak valid.',
        ];
    }
}