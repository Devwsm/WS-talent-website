<?php

namespace App\Http\Requests\InnerCircle;

use App\Services\InnerCircle\IcBagian;
use Illuminate\Foundation\Http\FormRequest;

/** Simpan aturan bawaan check-in (tab Konten, bagian Aturan check-in). Batas isian diambil dari config/inner_circle.php. */
class simpanAturanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->errorBag = 'aturan';
    }

    protected function getRedirectUrl(): string
    {
        return IcBagian::url('aturan-checkin');
    }

    public function rules(): array
    {
        $cfg = config('inner_circle.lokasi');
        $jam = ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'];

        return [
            'jendela_buka' => $jam,
            'jendela_tutup' => array_merge($jam, [function (string $a, mixed $v, \Closure $gagal) {
                // Bila ditutup di hari yang sama, jam tutup harus setelah jam buka.
                if ((string) $this->input('jendela_tutup_hari') === '0' && is_string($v) && $v <= (string) $this->input('jendela_buka')) {
                    $gagal('Bila check-in ditutup di hari yang sama, jam tutup harus setelah jam buka.');
                }
            }]),
            'jendela_tutup_hari' => ['required', 'in:0,1'],
            'radius_bawaan_m' => ['required', 'integer', 'between:' . $cfg['radius_min_m'] . ',' . $cfg['radius_maks_m']],
            'akurasi_maks_m' => ['required', 'integer', 'between:' . $cfg['akurasi_isian_min_m'] . ',' . $cfg['akurasi_isian_maks_m']],
        ];
    }

    public function attributes(): array
    {
        return [
            'jendela_buka' => '"Check-in dibuka (jam)"',
            'jendela_tutup' => '"Check-in ditutup (jam)"',
            'jendela_tutup_hari' => '"Hari penutupan"',
            'radius_bawaan_m' => '"Radius bawaan"',
            'akurasi_maks_m' => '"Akurasi GPS maksimal"',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'regex' => ':attribute harus berformat jam, mis. 12:00.',
            'in' => ':attribute tidak dikenal.',
            'integer' => ':attribute harus berupa angka bulat.',
            'between' => ':attribute harus antara :min dan :max meter.',
        ];
    }

    /** @return array<string, string> */
    public function nilai(): array
    {
        return $this->safe()->only(['jendela_buka', 'jendela_tutup', 'jendela_tutup_hari', 'radius_bawaan_m', 'akurasi_maks_m']);
    }
}