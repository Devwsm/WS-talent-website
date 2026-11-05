<?php

namespace App\Http\Requests\InnerCircle;

use App\Services\InnerCircle\IcBagian;
use App\Services\InnerCircle\IcPrivasi;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Simpan isi kebijakan privasi dari editor Quill (tab Konten). HTML disaring HtmlSanitizer di service; di sini hanya
 * dicek bahwa ada isi yang terlihat dan hasil saringannya muat di kolom (batas karakter dan byte di config).
 */
class simpanPrivasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->errorBag = 'privasi';
    }

    protected function getRedirectUrl(): string
    {
        return IcBagian::url('privasi');
    }

    public function rules(): array
    {
        $cfg = config('ic_konten.privasi');

        return [
            'isi' => [
                'required',
                'string',
                'max:' . ((int) $cfg['maks_karakter'] * 4),
                function (string $atribut, mixed $nilai, \Closure $gagal) use ($cfg) {
                    $bersih = HtmlSanitizer::clean((string) $nilai);
                    if (IcPrivasi::kosong($bersih)) {
                        $gagal('Isi kebijakan privasi tidak boleh kosong. Untuk memakai teks bawaan, tekan "Kembalikan teks bawaan".');

                        return;
                    }
                    if (mb_strlen(strip_tags($bersih)) > (int) $cfg['maks_karakter'] || strlen($bersih) > (int) $cfg['maks_byte']) {
                        $gagal('Isi terlalu panjang. Batasnya sekitar ' . number_format((int) $cfg['maks_karakter'], 0, ',', '.') . ' karakter; ringkas teksnya.');
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return ['isi' => '"Isi kebijakan privasi"'];
    }

    public function messages(): array
    {
        return ['isi.required' => 'Isi kebijakan privasi tidak boleh kosong. Untuk memakai teks bawaan, tekan "Kembalikan teks bawaan".'];
    }

    public function isi(): string
    {
        return (string) $this->validated('isi');
    }
}