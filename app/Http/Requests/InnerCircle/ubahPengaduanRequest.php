<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Tindak lanjut pengaduan di dashboard: status tujuan, status yang dilihat admin, dan catatan untuk anggota. */
class ubahPengaduanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $catatan = $this->input('catatan');
        $this->merge(['catatan' => is_string($catatan) ? trim($catatan) : null]);
    }

    public function rules(): array
    {
        $status = array_keys(config('inner_circle.pengaduan.status'));

        return [
            'status' => ['required', Rule::in($status)],
            'dari' => ['required', Rule::in($status)],
            'catatan' => ['nullable', 'string', 'max:' . (int) config('inner_circle.pengaduan.catatan_maks', 300)],
        ];
    }

    public function attributes(): array
    {
        return ['status' => 'Status', 'dari' => 'Status', 'catatan' => 'Catatan'];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'in' => ':attribute tidak dikenal.',
            'catatan.max' => 'Catatan terlalu panjang (maksimal :max karakter).',
        ];
    }
}