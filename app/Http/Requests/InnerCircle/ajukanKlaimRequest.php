<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;

/** Pengajuan hadiah oleh anggota. Tahapnya dari URL; `opsi` hanya dipakai tahap yang punya pilihan. */
class ajukanKlaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $opsi = $this->input('opsi');
        $this->merge(['opsi' => is_string($opsi) ? trim($opsi) : null]);
    }

    public function rules(): array
    {
        return [
            'opsi' => ['nullable', 'string', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'opsi.max' => 'Pilihan hadiah tidak valid.',
        ];
    }
}