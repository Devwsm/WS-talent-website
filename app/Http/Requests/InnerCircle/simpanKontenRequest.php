<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Simpan semua isian satu grup konten Inner Circle (tab Konten).
 *
 * Aturan tiap isian dibangun dari config/ic_konten.php (batas panjang, placeholder yang diizinkan). Isian dikirim
 * sebagai k[<kunci>] dengan titik pada kunci diganti "__" (titik di nama kolom bentrok dengan notasi array Laravel).
 */
class simpanKontenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Kunci milik grup pada alamat, dalam bentuk nama kolom (checkin.eyebrow => checkin__eyebrow). */
    private function kunciGrup(): array
    {
        $grup = (string) $this->route('grup');
        abort_unless(isset(config('ic_konten.grup')[$grup]), 404);

        $hasil = [];
        foreach (config('ic_konten.kunci') as $kunci => $def) {
            if ($def['grup'] === $grup) {
                $hasil[self::kolom($kunci)] = $def;
            }
        }

        return $hasil;
    }

    public static function kolom(string $kunci): string
    {
        return str_replace('.', '__', $kunci);
    }

    public function rules(): array
    {
        $rules = ['k' => ['required', 'array']];

        foreach ($this->kunciGrup() as $kolom => $def) {
            $diizinkan = array_merge(['program'], $def['placeholder'] ?? []);

            $rules['k.' . $kolom] = [
                'nullable',
                'string',
                'max:' . (int) $def['maks'],
                // Teks biasa saja: < dan > ditolak (bukan dibuang diam-diam) supaya admin tahu kenapa.
                'regex:/^[^<>]*$/u',
                function (string $atribut, mixed $nilai, \Closure $gagal) use ($diizinkan) {
                    preg_match_all('/\{([A-Za-z_]+)\}/', (string) $nilai, $cocok);
                    $asing = array_values(array_diff($cocok[1], $diizinkan));
                    if ($asing) {
                        $gagal('Placeholder {' . $asing[0] . '} tidak dikenal. Yang boleh dipakai: ' .
                            implode(', ', array_map(fn($p) => '{' . $p . '}', $diizinkan)) . '.');
                    }
                },
            ];
        }

        return $rules;
    }

    public function attributes(): array
    {
        $nama = [];
        foreach ($this->kunciGrup() as $kolom => $def) {
            $nama['k.' . $kolom] = '"' . $def['label'] . '"';
        }

        return $nama;
    }

    public function messages(): array
    {
        return [
            'k.required' => 'Tidak ada isian yang dikirim.',
            'k.*.max' => ':attribute terlalu panjang (maksimal :max karakter).',
            'k.*.regex' => 'Tanda < dan > tidak boleh dipakai di :attribute (teks biasa saja).',
            'k.*.string' => ':attribute harus berupa teks.',
        ];
    }

    /**
     * Isian bersih dengan kunci aslinya (titik).
     *
     * @return array<string, string>
     */
    public function nilai(): array
    {
        $hasil = [];
        foreach ($this->kunciGrup() as $kolom => $def) {
            $kunci = str_replace('__', '.', $kolom);
            $hasil[$kunci] = (string) ($this->validated('k.' . $kolom) ?? '');
        }

        return $hasil;
    }
}