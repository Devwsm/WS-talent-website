<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;

/** Komentar anggota pada kenangan. Teks apa adanya (di-escape saat tampil); spasi ganda dan baris kosong dirapikan. */
class simpanKomentarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $isi = is_string($this->input('isi')) ? trim((string) preg_replace('/\s+/u', ' ', $this->input('isi'))) : '';

        $this->merge(['isi' => $isi]);
    }

    public function rules(): array
    {
        return [
            'isi' => ['required', 'string', 'max:' . (int) config('inner_circle.komentar_maks', 300)],
        ];
    }

    public function messages(): array
    {
        return [
            'isi.required' => 'Tulis dulu komentarmu.',
            'isi.max' => 'Komentarmu terlalu panjang (maksimal :max karakter).',
        ];
    }
}