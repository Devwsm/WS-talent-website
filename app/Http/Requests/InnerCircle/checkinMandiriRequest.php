<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;

/** Check-in mandiri (tombol): kode acara dan posisi HP. Server yang menilai jarak dan akurasinya. */
class checkinMandiriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'acara' => ['required', 'string', 'max:20'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'akurasi' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'acara.required' => 'Pilih acara yang mau dipakai check-in.',
            'lat.*' => 'Posisi HP tidak terbaca dengan benar. Izinkan akses lokasi lalu coba lagi.',
            'lng.*' => 'Posisi HP tidak terbaca dengan benar. Izinkan akses lokasi lalu coba lagi.',
            'akurasi.*' => 'Posisi HP tidak terbaca dengan benar. Izinkan akses lokasi lalu coba lagi.',
        ];
    }
}