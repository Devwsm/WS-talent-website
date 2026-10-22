<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;

class masukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:150'],
            'sandi' => ['required', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'Email',
            'sandi' => 'Kata sandi',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'email' => 'Format email belum benar.',
            'max' => ':attribute terlalu panjang (maksimal :max karakter).',
        ];
    }
}
