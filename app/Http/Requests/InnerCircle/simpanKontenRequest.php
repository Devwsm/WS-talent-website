<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

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

    /**
     * Aturan satu isian teks biasa: batas panjang, tanpa < dan >, dan hanya placeholder yang diizinkan. Dipakai juga
     * oleh simpanItemRequest (butir daftar), supaya dua tempat ini tidak pernah berbeda aturan.
     *
     * @param  list<string>  $diizinkan nama placeholder/tautan yang boleh dipakai (tanpa kurung kurawal)
     */
    public static function aturanTeks(int $maks, array $diizinkan, bool $wajib = false): array
    {
        return [
            $wajib ? 'required' : 'nullable',
            'string',
            'max:' . $maks,
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

    public function rules(): array
    {
        $rules = ['k' => ['required', 'array']];

        foreach ($this->kunciGrup() as $kolom => $def) {
            $diizinkan = array_merge(['program'], $def['placeholder'] ?? [], $def['tautan'] ?? []);

            $rules['k.' . $kolom] = self::aturanTeks((int) $def['maks'], $diizinkan);
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
     * Isian bersih dengan kunci aslinya (titik). Hanya isian yang DIKIRIM form yang ikut: form tiap modul hanya memuat
     * kunci miliknya, jadi menyimpan satu modul tidak boleh menyentuh (apalagi mengosongkan) isian modul lain di grup yang sama.
     *
     * @return array<string, string>
     */
    public function nilai(): array
    {
        $valid = $this->validated();
        $hasil = [];
        foreach ($this->kunciGrup() as $kolom => $def) {
            if (! Arr::has($valid, 'k.' . $kolom)) {
                continue;
            }

            $kunci = str_replace('__', '.', $kolom);
            $hasil[$kunci] = (string) (Arr::get($valid, 'k.' . $kolom) ?? '');
        }

        return $hasil;
    }
}