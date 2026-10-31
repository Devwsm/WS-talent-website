<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Titik lokasi check-in satu acara (dashboard CMS, Acara & QR). */
class simpanLokasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Petugas sering menempel koordinat dari Google Maps ("-6.2088, 106.8456") ke satu kolom, atau mengetik desimal
     * dengan koma ("-6,2088"). Keduanya dirapikan di sini; hasil akhirnya tetap divalidasi sebagai angka biasa.
     */
    protected function prepareForValidation(): void
    {
        $lat = trim((string) $this->input('lat'));
        $lng = trim((string) $this->input('lng'));

        if ($lng === '' && preg_match('/^(-?\d+(?:\.\d+)?)\s*[,;\s]\s*(-?\d+(?:\.\d+)?)$/', $lat, $cocok)) {
            [$lat, $lng] = [$cocok[1], $cocok[2]];
        }

        $nama = trim((string) $this->input('petugas'));

        $this->merge([
            'lat' => str_replace(',', '.', $lat),
            'lng' => str_replace(',', '.', $lng),
            'petugas' => $nama === '' ? null : $nama,
            'konfirmasi_geser' => $this->boolean('konfirmasi_geser'),
        ]);
    }

    public function rules(): array
    {
        $cfg = config('inner_circle.lokasi');

        return [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'integer', 'between:' . $cfg['radius_min_m'] . ',' . $cfg['radius_maks_m']],
            'sumber' => ['required', Rule::in(['petugas', 'alamat', 'manual'])],
            'petugas' => ['nullable', 'string', 'max:40'],
            'konfirmasi_geser' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'lat' => 'Garis lintang (latitude)',
            'lng' => 'Garis bujur (longitude)',
            'radius' => 'Radius',
            'sumber' => 'Sumber titik',
            'petugas' => 'Nama petugas',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'numeric' => ':attribute harus berupa angka, mis. -6.2088.',
            'between' => ':attribute di luar rentang yang masuk akal.',
            'radius.between' => 'Radius harus antara :min dan :max meter.',
            'radius.integer' => 'Radius harus bilangan bulat (meter).',
            'in' => ':attribute tidak dikenal.',
            'max' => ':attribute terlalu panjang (maksimal :max karakter).',
        ];
    }
}