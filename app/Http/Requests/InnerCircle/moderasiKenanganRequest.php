<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Moderasi satu kenangan di dashboard: sembunyikan dari galeri atau tayangkan kembali. */
class moderasiKenanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'aksi' => ['required', Rule::in(['sembunyikan', 'tayangkan'])],
        ];
    }

    public function messages(): array
    {
        return [
            'aksi.required' => 'Pilih tindakan untuk kenangan ini.',
            'aksi.in' => 'Tindakan tidak dikenal.',
        ];
    }
}