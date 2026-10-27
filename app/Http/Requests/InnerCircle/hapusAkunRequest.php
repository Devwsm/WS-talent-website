<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

/**
 * Konfirmasi hapus akun: ketik HAPUS, dan (bila akunnya punya kata sandi) masukkan kata sandi saat ini.
 * Akun yang hanya masuk lewat Google tidak punya sandi, jadi cukup mengetik HAPUS.
 */
class hapusAkunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'konfirmasi' => ['required', 'string', 'max:20'],
            'sandi' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return ['konfirmasi.required' => 'Ketik HAPUS untuk melanjutkan.'];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $anggota = Auth::guard('member')->user();

            if (mb_strtoupper(trim((string) $this->input('konfirmasi'))) !== 'HAPUS') {
                $validator->errors()->add('konfirmasi', 'Ketik HAPUS (huruf besar) untuk melanjutkan.');
            }

            if ($anggota && $anggota->password !== null && ! Hash::check((string) $this->input('sandi'), $anggota->password)) {
                $validator->errors()->add('sandi', 'Kata sandi salah.');
            }
        }];
    }
}