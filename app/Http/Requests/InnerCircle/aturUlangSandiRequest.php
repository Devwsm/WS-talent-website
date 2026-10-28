<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;

class aturUlangSandiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            // Aturan sama dengan pendaftaran (daftarRequest).
            'sandi' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return ['email' => 'Email', 'sandi' => 'Kata sandi'];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'email' => 'Format email belum benar.',
            'max' => ':attribute terlalu panjang (maksimal :max karakter).',
            'sandi.min' => 'Kata sandi minimal :min karakter.',
            'sandi.confirmed' => 'Ulangi kata sandi belum sama.',
        ];
    }
}