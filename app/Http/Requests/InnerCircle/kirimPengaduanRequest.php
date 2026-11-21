<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Pengaduan anggota: kategori, acara terkait (opsional), dan isi. Aturan lanjutan (batas harian) dicek PengaduanService. */
class kirimPengaduanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $isi = $this->input('isi');
        $acara = $this->input('event_id');

        $this->merge([
            'isi' => is_string($isi) ? trim($isi) : null,
            'event_id' => (is_scalar($acara) && trim((string) $acara) !== '') ? $acara : null,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return route('ic.pengaduan') . '#form-pengaduan';
    }

    public function rules(): array
    {
        return [
            'kategori' => ['required', Rule::in(array_keys(config('inner_circle.pengaduan.kategori')))],
            'event_id' => ['nullable', 'integer'],
            'isi' => ['required', 'string', 'min:' . (int) config('inner_circle.pengaduan.isi_min', 10), 'max:' . (int) config('inner_circle.pengaduan.isi_maks', 1000)],
        ];
    }

    public function attributes(): array
    {
        return [
            'kategori' => 'Kategori',
            'event_id' => 'Acara',
            'isi' => 'Isi pengaduan',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'in' => ':attribute tidak dikenal.',
            'integer' => ':attribute tidak valid.',
            'isi.min' => 'Ceritakan sedikit lebih lengkap (minimal :min karakter).',
            'isi.max' => 'Pengaduan terlalu panjang (maksimal :max karakter).',
        ];
    }
}