<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Ganti nama anggota (nama yang tampil di kartu member, kenangan, dan komentar). Boleh memakai spasi:
 * huruf, angka, spasi, dan tanda baca biasa. Spasi ganda dirapikan.
 */
class ubahNamaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nama = is_string($this->input('nama')) ? trim((string) preg_replace('/\s+/u', ' ', $this->input('nama'))) : '';

        $this->merge(['nama' => $nama]);
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'min:2', 'max:100', "regex:/^[\p{L}\p{N} .,'’_-]+$/u"],
        ];
    }

    public function attributes(): array
    {
        return ['nama' => 'Nama'];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama wajib diisi.',
            'nama.min' => 'Nama minimal :min karakter.',
            'nama.max' => 'Nama terlalu panjang (maksimal :max karakter).',
            'nama.regex' => 'Nama hanya boleh huruf, angka, spasi, dan tanda baca biasa (. , \' - _).',
        ];
    }
}