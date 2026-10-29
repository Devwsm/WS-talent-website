<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Proses klaim di dashboard: lanjut, tolak, ubah catatan, atau buka kembali yang ditolak. Aturan alurnya dicek di ClaimService. */
class ubahKlaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $catatan = $this->input('catatan');
        $this->merge(['catatan' => is_string($catatan) ? trim($catatan) : null]);
    }

    public function rules(): array
    {
        return [
            'aksi' => ['required', Rule::in(['lanjut', 'tolak', 'catatan', 'buka_kembali'])],
            'dari' => ['required', Rule::in(array_keys(config('inner_circle.status_klaim')))],
            'catatan' => ['nullable', 'string', 'max:160'],
        ];
    }

    public function attributes(): array
    {
        return [
            'aksi' => 'Aksi',
            'dari' => 'Status',
            'catatan' => 'Catatan',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'in' => ':attribute tidak dikenal.',
            'catatan.max' => 'Catatan terlalu panjang (maksimal :max karakter).',
        ];
    }
}