<?php

namespace App\Http\Requests\InnerCircle;

use App\Services\InnerCircle\IcBagian;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tambah atau ubah satu butir daftar konten Inner Circle (langkah, FAQ, poin teaser).
 *
 * Aturannya dibangun dari config/ic_konten.php (`daftar.<nama>`): batas panjang, apakah ada isi dan ikon, dan tautan
 * yang boleh disisipkan. Galat disimpan di kantong bernama per butir (`item_<id>` atau `item_baru_<daftar>`), karena
 * satu halaman memuat banyak form sekaligus dan galat satu form tidak boleh muncul di form lain.
 */
class simpanItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Nama form ini di halaman (juga dipakai sebagai kantong galat dan penanda old()). */
    public function kodeForm(): string
    {
        $id = $this->route('id');

        return $id !== null ? 'item_' . $id : 'item_baru_' . $this->route('daftar');
    }

    protected function prepareForValidation(): void
    {
        $this->errorBag = $this->kodeForm();
        $this->def(); // daftar tidak dikenal = 404 sebelum validasi
    }

    /** @return array<string, mixed> */
    private function def(): array
    {
        $def = config('ic_konten.daftar.' . $this->route('daftar'));
        abort_unless(is_array($def), 404);

        return $def;
    }

    /** Alamat tujuan saat gagal: kembali ke halaman Konten, tepat di bagian daftar yang sedang diisi. */
    protected function getRedirectUrl(): string
    {
        return IcBagian::url(IcBagian::untukDaftar((string) $this->route('daftar')), 'daftar-' . $this->route('daftar'));
    }

    public function rules(): array
    {
        $def = $this->def();
        $diizinkan = array_merge(['program'], $def['tautan'] ?? []);

        $rules = [
            'judul' => simpanKontenRequest::aturanTeks((int) $def['judul']['maks'], ['program'], wajib: true),
        ];

        if (! empty($def['isi'])) {
            $rules['isi'] = simpanKontenRequest::aturanTeks((int) $def['isi']['maks'], $diizinkan, wajib: true);
        }

        if (! empty($def['ikon'])) {
            $rules['ikon'] = ['required', 'string', Rule::in(array_keys(config('ic_konten.ikon_daftar')))];
        }

        return $rules;
    }

    public function attributes(): array
    {
        $def = $this->def();

        return [
            'judul' => '"' . $def['judul']['label'] . '"',
            'isi' => '"' . ($def['isi']['label'] ?? 'Isi') . '"',
            'ikon' => '"Ikon"',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute terlalu panjang (maksimal :max karakter).',
            'regex' => 'Tanda < dan > tidak boleh dipakai di :attribute (teks biasa saja).',
            'string' => ':attribute harus berupa teks.',
            'ikon.in' => 'Ikon yang dipilih tidak tersedia.',
        ];
    }

    /** @return array{judul: string, isi: ?string, ikon: ?string} */
    public function butir(): array
    {
        return [
            'judul' => (string) $this->validated('judul'),
            'isi' => $this->validated('isi'),
            'ikon' => $this->validated('ikon'),
        ];
    }
}