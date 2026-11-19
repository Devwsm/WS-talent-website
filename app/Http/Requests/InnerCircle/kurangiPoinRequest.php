<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;

/** Koreksi (kurangi) poin anggota. Batas atas sebenarnya (total poin anggota) dicek di PoinService. */
class kurangiPoinRequest extends FormRequest
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
            'jumlah' => ['required', 'integer', 'min:1', 'max:' . (int) config('inner_circle.poin.koreksi_maks', 100000)],
            'catatan' => ['required', 'string', 'max:160'],
        ];
    }

    public function attributes(): array
    {
        return [
            'jumlah' => 'Jumlah poin',
            'catatan' => 'Alasan koreksi',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'integer' => ':attribute harus berupa angka.',
            'jumlah.min' => 'Jumlah poin minimal :min.',
            'jumlah.max' => 'Jumlah poin maksimal :max per koreksi.',
            'catatan.max' => ':attribute terlalu panjang (maksimal :max karakter).',
        ];
    }
}