<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Simpan subjek dan paragraf pembuka satu jenis email (tab Email). Aturannya sama dengan teks konten lain
 * (simpanKontenRequest::aturanTeks): teks biasa, batas panjang, tanpa < >, dan hanya placeholder milik jenis itu.
 */
class simpanEmailTeksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    private function definisi(): array
    {
        $jenis = (string) $this->route('jenis');
        abort_unless(is_array(config("ic_konten.email.$jenis")), 404);

        return config("ic_konten.email.$jenis");
    }

    protected function prepareForValidation(): void
    {
        $this->errorBag = 'emailteks';
    }

    protected function getRedirectUrl(): string
    {
        return route('ic.admin.email', ['jenis' => (string) $this->route('jenis')]) . '#teks-email';
    }

    public function rules(): array
    {
        $def = $this->definisi();
        $diizinkan = array_merge(['program'], $def['placeholder'] ?? []);

        return [
            'subjek' => simpanKontenRequest::aturanTeks((int) $def['subjek']['maks'], $diizinkan),
            'pembuka' => simpanKontenRequest::aturanTeks((int) $def['pembuka']['maks'], $diizinkan),
        ];
    }

    public function attributes(): array
    {
        return ['subjek' => '"Subjek"', 'pembuka' => '"Paragraf pembuka"'];
    }

    public function messages(): array
    {
        return [
            'max' => ':attribute terlalu panjang (maksimal :max karakter).',
            'regex' => 'Tanda < dan > tidak boleh dipakai di :attribute (teks biasa saja).',
            'string' => ':attribute harus berupa teks.',
        ];
    }

    /** @return array{subjek: string, pembuka: string} */
    public function nilai(): array
    {
        return [
            'subjek' => (string) ($this->validated('subjek') ?? ''),
            'pembuka' => (string) ($this->validated('pembuka') ?? ''),
        ];
    }
}