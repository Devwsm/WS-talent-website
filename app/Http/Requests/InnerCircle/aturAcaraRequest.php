<?php

namespace App\Http\Requests\InnerCircle;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Pengaturan lengkap satu acara (halaman Atur acara di CMS): data dasar, GL, jendela dan poin check-in, poster.
 * Titik lokasi punya form dan request sendiri (simpanLokasiRequest).
 */
class aturAcaraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Isian kosong disimpan sebagai NULL; kosong di kolom bawaan = memakai bawaan. */
    protected function prepareForValidation(): void
    {
        $bersih = fn(string $kunci) => ($v = trim((string) $this->input($kunci))) === '' ? null : $v;

        $this->merge([
            'kota' => $bersih('kota'),
            'venue' => $bersih('venue'),
            'deskripsi' => $bersih('deskripsi'),
            'petunjuk_masuk' => $bersih('petunjuk_masuk'),
            'username_acara' => ($u = $bersih('username_acara')) === null ? null : preg_replace('/ {2,}/', ' ', $u),
            'sandi_acara' => $bersih('sandi_acara'),
            'kuota_gl' => $bersih('kuota_gl'),
            'undian_pada' => $bersih('undian_pada'),
            'poin_gl' => $bersih('poin_gl'),
            'poin_mandiri' => $bersih('poin_mandiri'),
            'jendela_mulai' => $bersih('jendela_mulai'),
            'jendela_selesai' => $bersih('jendela_selesai'),
            'hapus_poster' => $this->boolean('hapus_poster'),
            'hapus_sandi' => $this->boolean('hapus_sandi'),
        ]);
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'tipe' => ['required', Rule::in(array_keys(ic_tipe_event()))],
            'venue' => ['nullable', 'string', 'max:80'],
            'kota' => ['nullable', 'string', 'max:60'],
            'deskripsi' => ['nullable', 'string', 'max:500'],
            'petunjuk_masuk' => ['nullable', 'string', 'max:300'],
            'poster' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'hapus_poster' => ['boolean'],
            'kuota_gl' => ['nullable', 'integer', 'min:0', 'max:' . (int) config('inner_circle.gl.kuota_maks')],
            'undian_pada' => [
                'nullable',
                'date',
                function (string $atribut, mixed $nilai, \Closure $gagal) {
                    $tanggal = (string) $this->input('tanggal');
                    if (
                        $nilai && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)
                        && Carbon::parse($nilai)->greaterThan(Carbon::parse($tanggal)->endOfDay())
                    ) {
                        $gagal('Waktu undian harus paling lambat di tanggal acara.');
                    }
                },
            ],
            // Username boleh memuat spasi (mis. "Whisnu Santika VIP"); ujung depan dan belakang sudah dipangkas di prepareForValidation.
            'username_acara' => ['nullable', 'string', 'max:60', 'regex:/^[\x20-\x7E]+$/'],
            'sandi_acara' => ['nullable', 'string', 'min:4', 'max:60', 'regex:/^[\x21-\x7E]+$/'],
            'hapus_sandi' => ['boolean'],
            'poin_gl' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'poin_mandiri' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'jendela_mulai' => ['nullable', 'date', 'required_with:jendela_selesai'],
            'jendela_selesai' => ['nullable', 'date', 'required_with:jendela_mulai', 'after:jendela_mulai'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tanggal' => 'Tanggal show',
            'tipe' => 'Tipe acara',
            'venue' => 'Venue',
            'kota' => 'Kota',
            'deskripsi' => 'Deskripsi',
            'petunjuk_masuk' => 'Petunjuk masuk',
            'poster' => 'Poster',
            'kuota_gl' => 'Kuota GL',
            'undian_pada' => 'Waktu undian',
            'username_acara' => 'Username acara',
            'sandi_acara' => 'Sandi acara',
            'poin_gl' => 'Poin GL',
            'poin_mandiri' => 'Poin check-in mandiri',
            'jendela_mulai' => 'Check-in dibuka',
            'jendela_selesai' => 'Check-in ditutup',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'date_format' => ':attribute harus berformat tanggal yang benar.',
            'date' => ':attribute harus berformat tanggal dan jam yang benar.',
            'in' => ':attribute tidak dikenal.',
            'max' => ':attribute terlalu panjang atau terlalu besar (maksimal :max).',
            'min' => ':attribute terlalu kecil (minimal :min).',
            'integer' => ':attribute harus berupa angka bulat.',
            'image' => 'Poster harus berupa gambar (JPG, PNG, atau WebP).',
            'mimes' => 'Poster harus berupa gambar JPG, PNG, atau WebP.',
            'poster.max' => 'Poster terlalu besar (maksimal 1 MB).',
            'regex' => ':attribute tidak boleh memuat spasi atau karakter di luar huruf, angka, dan simbol biasa.',
            'username_acara.regex' => 'Username acara hanya boleh huruf, angka, spasi, dan simbol biasa.',
            'required_with' => 'Isi jendela check-in secara lengkap (dibuka dan ditutup), atau kosongkan keduanya.',
            'jendela_selesai.after' => 'Check-in ditutup harus setelah check-in dibuka.',
        ];
    }
}