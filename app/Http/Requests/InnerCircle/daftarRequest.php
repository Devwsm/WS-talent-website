<?php

namespace App\Http\Requests\InnerCircle;

use App\Rules\SandiAnggota;
use App\Services\InnerCircle\BatasEmailKembar;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class daftarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama' => trim((string) $this->input('nama')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * Saat IP ini sedang ditahan (terlalu banyak percobaan email kembar), aturan `unique` tidak dijalankan sama
     * sekali, supaya jawabannya tidak lagi membocorkan email mana yang terdaftar. Pesan penahanannya dipasang di
     * after() di bawah.
     */
    private function aturanEmail(): array
    {
        $aturan = ['required', 'email', 'max:150'];

        if (! app(BatasEmailKembar::class)->ditahan($this)) {
            $aturan[] = 'unique:members,email';
        }

        return $aturan;
    }

    /** Selama ditahan, SEMUA galat email diganti satu pesan yang sama (email terdaftar atau tidak, benar atau salah format). */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (app(BatasEmailKembar::class)->ditahan($this)) {
                    $validator->errors()->forget('email');
                    $validator->errors()->add('email', BatasEmailKembar::PESAN_DITAHAN);
                }
            },
        ];
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            'email' => $this->aturanEmail(),
            // Maks 72: bcrypt hanya membaca 72 byte pertama, sisanya diam-diam diabaikan.
            // SandiAnggota: wajib huruf besar + kecil + angka, simbol boleh, emoji tidak.
            'sandi' => ['required', 'string', 'min:8', 'max:72', new SandiAnggota(), 'confirmed'],
        ];
    }

    /** Setiap penolakan karena email kembar dihitung ke pembatas khusus (lihat BatasEmailKembar). */
    protected function failedValidation(Validator $validator): void
    {
        if (isset($validator->failed()['email']['Unique'])) {
            app(BatasEmailKembar::class)->catat($this);
        }

        parent::failedValidation($validator);
    }

    public function attributes(): array
    {
        return [
            'nama' => 'Nama lengkap',
            'email' => 'Email',
            'sandi' => 'Kata sandi',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'email' => 'Format email belum benar.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk.',
            'max' => ':attribute terlalu panjang (maksimal :max karakter).',
            'sandi.min' => 'Kata sandi minimal :min karakter.',
            'sandi.confirmed' => 'Ulangi kata sandi belum sama.',
        ];
    }
}