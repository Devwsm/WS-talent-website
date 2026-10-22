<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;

class stempelManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['catatan' => trim((string) $this->input('catatan'))]);
    }

    public function rules(): array
    {
        return [
            'jumlah' => ['required', 'integer', 'min:1', 'max:10'],
            'catatan' => ['required', 'string', 'max:160'],
        ];
    }

    public function attributes(): array
    {
        return [
            'jumlah' => 'Jumlah stempel',
            'catatan' => 'Alasan',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'integer' => ':attribute harus berupa angka.',
            'jumlah.min' => 'Jumlah stempel minimal :min.',
            'jumlah.max' => 'Jumlah stempel maksimal :max per penambahan.',
            'catatan.max' => 'Alasan terlalu panjang (maksimal :max karakter).',
        ];
    }
}