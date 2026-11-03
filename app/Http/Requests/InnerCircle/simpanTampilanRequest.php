<?php

namespace App\Http\Requests\InnerCircle;

use App\Services\InnerCircle\IcTampilan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Simpan tampilan dan merek Inner Circle (tab Konten, bagian Tampilan): nama program, tiga warna, dan logo.
 *
 * Isian teks dikirim sebagai k[tampilan__program], k[tampilan__aksen], dst. (titik diganti "__", sama seperti isian
 * konten lain) supaya form yang sama bisa dipakai pratinjau langsung. Logo dikirim sebagai `logo`, centang hapus
 * sebagai `hapus_logo`. Warna harus #RRGGBB (kosong = bawaan) dan lolos pemeriksaan kontras.
 */
class simpanTampilanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->errorBag = 'tampilan';
    }

    protected function getRedirectUrl(): string
    {
        return route('ic.admin.konten') . '#tampilan';
    }

    public function rules(): array
    {
        $cfg = config('ic_konten.tampilan');
        $rules = [
            'k' => ['required', 'array'],
            'k.tampilan__program' => [
                'nullable',
                'string',
                'max:' . (int) $cfg['program_maks'],
                // Teks biasa tanpa tanda kurung kurawal (bukan placeholder) dan tanpa < >.
                'regex:/^[^<>{}]*$/u',
            ],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:' . (int) $cfg['logo_maks_kb']],
            'hapus_logo' => ['nullable', 'boolean'],
        ];

        foreach ($cfg['warna'] as $nama => $def) {
            $rules['k.tampilan__' . $nama] = ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->any()) {
                return;
            }

            $galat = app(IcTampilan::class)->periksaKontras($this->warnaBaru());
            foreach ($galat as $pesan) {
                $v->errors()->add('warna', $pesan);
            }
        });
    }

    public function attributes(): array
    {
        $nama = ['k.tampilan__program' => '"Nama program"', 'logo' => '"Logo"'];
        foreach (config('ic_konten.tampilan.warna') as $kode => $def) {
            $nama['k.tampilan__' . $kode] = '"' . $def['label'] . '"';
        }

        return $nama;
    }

    public function messages(): array
    {
        $mb = rtrim(rtrim(number_format(config('ic_konten.tampilan.logo_maks_kb') / 1024, 1, ',', ''), '0'), ',');

        return [
            'k.required' => 'Tidak ada isian yang dikirim.',
            'k.tampilan__program.max' => ':attribute terlalu panjang (maksimal :max karakter).',
            'k.tampilan__program.regex' => ':attribute tidak boleh memuat < > { }.',
            'k.*.regex' => ':attribute harus berupa kode warna #RRGGBB, mis. #8d73ff (kosongkan untuk bawaan).',
            'logo.mimes' => 'Logo harus berformat JPG, PNG, atau WebP.',
            'logo.max' => "Ukuran logo maksimal {$mb} MB.",
            'logo.uploaded' => "Logo gagal diunggah. Pastikan ukurannya tidak lebih dari {$mb} MB, atau coba berkas lain.",
            'logo.file' => 'Logo gagal diunggah. Coba pilih ulang.',
        ];
    }

    /** Warna yang akan berlaku bila disimpan: isian kosong berarti bawaan. @return array{aksen: string, latar: string, kartu: string} */
    private function warnaBaru(): array
    {
        $hasil = app(IcTampilan::class)->bawaan();
        foreach (array_keys($hasil) as $nama) {
            $isi = trim((string) $this->input('k.tampilan__' . $nama, ''));
            if ($isi !== '') {
                $hasil[$nama] = IcTampilan::normalisasi($isi);
            }
        }

        return $hasil;
    }

    /** @return array{program: string, aksen: string, latar: string, kartu: string} */
    public function nilai(): array
    {
        $hasil = ['program' => (string) ($this->validated('k.tampilan__program') ?? '')];
        foreach (array_keys(config('ic_konten.tampilan.warna')) as $nama) {
            $hasil[$nama] = (string) ($this->validated('k.tampilan__' . $nama) ?? '');
        }

        return $hasil;
    }
}