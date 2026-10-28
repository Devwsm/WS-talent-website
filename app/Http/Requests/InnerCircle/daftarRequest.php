<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;

class daftarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama' => trim((string) $this->input('nama')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:members,email'],
            // Maks 72: bcrypt hanya membaca 72 byte pertama, sisanya diam-diam diabaikan.
            'sandi' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama' => 'Nama lengkap',
            'email' => 'Email',
            'sandi' => 'Kata sandi',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'email' => 'Format email belum benar.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk.',
            'max' => ':attribute terlalu panjang (maksimal :max karakter).',
            'sandi.min' => 'Kata sandi minimal :min karakter.',
            'sandi.confirmed' => 'Ulangi kata sandi belum sama.',
        ];
    }
}