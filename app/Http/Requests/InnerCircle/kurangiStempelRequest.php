<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;

/** Koreksi (kurangi) stempel anggota. Batas atas sebenarnya (total stempel anggota) dicek di StempelService. */
class kurangiStempelRequest extends FormRequest
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
            'jumlah' => ['required', 'integer', 'min:1', 'max:100'],
            'catatan' => ['required', 'string', 'max:160'],
        ];
    }

    public function attributes(): array
    {
        return [
            'jumlah' => 'Jumlah stempel',
            'catatan' => 'Alasan koreksi',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'integer' => ':attribute harus berupa angka.',
            'jumlah.min' => 'Jumlah stempel minimal :min.',
            'jumlah.max' => 'Jumlah stempel maksimal :max per koreksi.',
            'catatan.max' => ':attribute terlalu panjang (maksimal :max karakter).',
        ];
    }
}