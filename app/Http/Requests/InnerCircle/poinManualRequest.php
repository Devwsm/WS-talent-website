<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;

class poinManualRequest extends FormRequest
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
            'jumlah' => ['required', 'integer', 'min:1', 'max:' . (int) config('inner_circle.poin.manual_maks', 10000)],
            'catatan' => ['required', 'string', 'max:160'],
        ];
    }

    public function attributes(): array
    {
        return [
            'jumlah' => 'Jumlah poin',
            'catatan' => 'Alasan',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'integer' => ':attribute harus berupa angka.',
            'jumlah.min' => 'Jumlah poin minimal :min.',
            'jumlah.max' => 'Jumlah poin maksimal :max per penambahan.',
            'catatan.max' => 'Alasan terlalu panjang (maksimal :max karakter).',
        ];
    }
}