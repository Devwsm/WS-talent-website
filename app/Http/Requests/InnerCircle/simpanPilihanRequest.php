<?php

namespace App\Http\Requests\InnerCircle;

use App\Services\InnerCircle\IcBagian;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ubah tipe acara atau tambah/ubah kategori kenangan (tab Konten, bagian Pilihan).
 *
 * Satu halaman memuat banyak form sekaligus, jadi galat disimpan di kantong bernama per form (`pilihan_<jenis>_<kunci>`
 * atau `pilihan_kategori_baru`), dan kembali ke bagian yang sedang diisi.
 */
class simpanPilihanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Nama form ini (kantong galat dan penanda old()). */
    public function kodeForm(): string
    {
        $kunci = $this->route('kunci');

        return 'pilihan_' . $this->route('jenis') . '_' . ($kunci ?? 'baru');
    }

    protected function prepareForValidation(): void
    {
        $this->errorBag = $this->kodeForm();
        abort_unless(in_array($this->route('jenis'), ['tipe', 'kategori'], true), 404);
    }

    protected function getRedirectUrl(): string
    {
        return IcBagian::url(IcBagian::untukPilihan((string) $this->route('jenis')));
    }

    public function rules(): array
    {
        $ikon = array_keys(config('inner_circle.ikon_pilihan'));
        if ($this->route('jenis') === 'tipe') {
            $ikon = array_merge($ikon, array_keys(config('inner_circle.ikon_tipe_tambahan')));
        }

        $rules = [
            'label' => simpanKontenRequest::aturanTeks(40, [], wajib: true),
            'ikon' => ['required', 'string', Rule::in($ikon)],
            'warna' => ['required', 'string', Rule::in(array_keys(config('inner_circle.warna_kartu')))],
        ];

        if ($this->route('jenis') === 'kategori') {
            $rules['sampul'] = ['required', 'string', Rule::in(array_keys(config('inner_circle.ikon_pilihan')))];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return ['label' => '"Nama"', 'ikon' => '"Ikon"', 'sampul' => '"Ikon sampul"', 'warna' => '"Warna"'];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute terlalu panjang (maksimal :max karakter).',
            'regex' => 'Tanda < dan > tidak boleh dipakai di :attribute (teks biasa saja).',
            'in' => ':attribute yang dipilih tidak tersedia.',
        ];
    }

    /** @return array{label: string, ikon: string, warna: string, sampul?: string} */
    public function isian(): array
    {
        return $this->safe()->only(['label', 'ikon', 'warna', 'sampul']);
    }
}