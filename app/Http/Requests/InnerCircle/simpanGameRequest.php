<?php

namespace App\Http\Requests\InnerCircle;

use App\Services\InnerCircle\IcBagian;
use App\Services\InnerCircle\IcGame;
use Illuminate\Foundation\Http\FormRequest;

/** Simpan aturan game (tab Konten, bagian Aturan game). Rentang isian diambil dari config/inner_circle.php (game.batas). */
class simpanGameRequest extends FormRequest
{
    private const NAMA = [
        'aktif' => '"Status game"',
        'ronde_harian' => '"Jatah ronde per hari"',
        'skor_per_poin' => '"Skor per 1 poin"',
        'poin_maks_ronde' => '"Batas poin per ronde"',
        'poin_maks_harian' => '"Batas poin per hari"',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->errorBag = 'game';
    }

    protected function getRedirectUrl(): string
    {
        return IcBagian::url('game');
    }

    public function rules(): array
    {
        $rules = ['aktif' => ['required', 'in:0,1']];
        foreach (app(IcGame::class)->batas() as $nama => [$min, $maks]) {
            $rules[$nama] = ['required', 'integer', "between:$min,$maks"];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return self::NAMA;
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'in' => ':attribute tidak dikenal.',
            'integer' => ':attribute harus berupa angka bulat.',
            'between' => ':attribute harus antara :min dan :max.',
        ];
    }

    /** @return array<string, string|int|bool> */
    public function nilai(): array
    {
        $v = $this->safe()->only(array_keys(self::NAMA));
        $v['aktif'] = (string) $v['aktif'] === '1';

        return $v;
    }
}