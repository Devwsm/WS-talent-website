<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;

/** Menonaktifkan akun anggota. Alasan hanya catatan untuk admin (tidak ditampilkan ke anggota). */
class blokirAnggotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['alasan' => trim((string) $this->input('alasan'))]);
    }

    public function rules(): array
    {
        return ['alasan' => ['required', 'string', 'max:160']];
    }

    public function attributes(): array
    {
        return ['alasan' => 'Alasan'];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute terlalu panjang (maksimal :max karakter).',
        ];
    }
}