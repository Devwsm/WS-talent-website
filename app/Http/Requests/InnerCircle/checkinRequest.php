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
            // Posisi HP dari browser (hanya dipakai bila acara punya titik lokasi). Server yang menilai, bukan form.
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'akurasi' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode.required' => 'Masukkan kode check-in dari panitia atau pindai QR di lokasi.',
            'kode.max' => 'Kode check-in terlalu panjang.',
            'lat.*' => 'Posisi HP tidak terbaca dengan benar. Izinkan akses lokasi lalu coba lagi.',
            'lng.*' => 'Posisi HP tidak terbaca dengan benar. Izinkan akses lokasi lalu coba lagi.',
            'akurasi.*' => 'Posisi HP tidak terbaca dengan benar. Izinkan akses lokasi lalu coba lagi.',
        ];
    }
}