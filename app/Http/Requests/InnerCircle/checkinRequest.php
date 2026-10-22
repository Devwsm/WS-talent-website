<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;

class checkinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Bisa berisi kode ketikan atau URL lengkap hasil pindai QR; service yang mengambil tokennya.
            'kode' => ['required', 'string', 'max:300'],
            'acara' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode.required' => 'Masukkan kode check-in dari panitia atau pindai QR di lokasi.',
            'kode.max' => 'Kode check-in terlalu panjang.',
        ];
    }
}
