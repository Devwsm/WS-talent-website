<?php

namespace App\Http\Requests\InnerCircle;

use App\Services\InnerCircle\EmailService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Kirim email contoh dari tab Email di dashboard. */
class kirimEmailTesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['tujuan' => trim((string) $this->input('tujuan'))]);
    }

    public function rules(): array
    {
        return [
            'jenis' => ['required', Rule::in(array_keys(EmailService::JENIS))],
            'tujuan' => ['required', 'email', 'max:150'],
        ];
    }

    public function attributes(): array
    {
        return ['jenis' => 'Jenis email', 'tujuan' => 'Email tujuan'];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'email' => 'Format email tujuan belum benar.',
            'in' => ':attribute tidak dikenal.',
            'max' => ':attribute terlalu panjang (maksimal :max karakter).',
        ];
    }
}