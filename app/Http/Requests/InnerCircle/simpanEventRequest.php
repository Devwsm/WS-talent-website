<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Dipakai untuk tambah dan ubah acara di dashboard CMS. */
class simpanEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Isian kosong disimpan sebagai NULL (venue kosong = belum diumumkan). */
    protected function prepareForValidation(): void
    {
        $bersih = fn(string $kunci) => ($v = trim((string) $this->input($kunci))) === '' ? null : $v;

        $this->merge([
            'kota' => $bersih('kota'),
            'venue' => $bersih('venue'),
            'jendela_mulai' => $bersih('jendela_mulai'),
            'jendela_selesai' => $bersih('jendela_selesai'),
        ]);
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'tipe' => ['required', Rule::in(array_keys(config('inner_circle.tipe_event')))],
            'venue' => ['nullable', 'string', 'max:80'],
            'kota' => ['nullable', 'string', 'max:60'],
            'jendela_mulai' => ['nullable', 'date', 'required_with:jendela_selesai'],
            'jendela_selesai' => ['nullable', 'date', 'required_with:jendela_mulai', 'after:jendela_mulai'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tanggal' => 'Tanggal show',
            'tipe' => 'Tipe acara',
            'venue' => 'Venue',
            'kota' => 'Kota',
            'jendela_mulai' => 'Check-in dibuka',
            'jendela_selesai' => 'Check-in ditutup',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'date_format' => ':attribute harus berformat tanggal yang benar.',
            'date' => ':attribute harus berformat tanggal dan jam yang benar.',
            'in' => ':attribute tidak dikenal.',
            'max' => ':attribute terlalu panjang (maksimal :max karakter).',
            'required_with' => 'Isi jendela check-in secara lengkap (dibuka dan ditutup), atau kosongkan keduanya.',
            'jendela_selesai.after' => 'Check-in ditutup harus setelah check-in dibuka.',
        ];
    }
}