<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;

class imporEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Pemeriksaan MIME CSV sering gagal untuk berkas dari Excel, jadi cukup ekstensi + ukuran.
            'csv' => ['required', 'file', 'extensions:csv,txt', 'max:1024'],
        ];
    }

    public function messages(): array
    {
        return [
            'csv.required' => 'Pilih berkas CSV jadwal dulu.',
            'csv.file' => 'Berkas CSV tidak bisa dibaca.',
            'csv.extensions' => 'Berkas harus berformat .csv.',
            'csv.max' => 'Ukuran berkas maksimal 1 MB.',
        ];
    }
}