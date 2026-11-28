<?php

namespace App\Http\Requests\InnerCircle;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Menyelesaikan ronde game. Hanya bentuknya yang diperiksa di sini; keabsahan catatan input dan skornya dinilai
 * GameService dengan menjalankan ulang ronde di server.
 */
class gameSelesaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'regex:/^[a-f0-9]{32}$/'],
            'langkah' => ['required', 'integer', 'min:1', 'max:' . (int) config('inner_circle.game.langkah_maks')],
            // Catatan input "selisih,kode,selisih,kode,...". Bisa kosong (pemain tidak menggerakkan apa pun).
            'log' => ['nullable', 'string', 'max:' . (int) config('inner_circle.game.log_maks_karakter')],
            // Skor menurut browser: hanya jejak audit, tidak pernah dipakai untuk memberi poin.
            'skor' => ['nullable', 'integer', 'min:-100000', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode.*' => 'Ronde tidak dikenali. Mulai ronde baru ya.',
            'langkah.*' => 'Data ronde tidak lengkap. Mulai ronde baru ya.',
            'log.*' => 'Data ronde terlalu besar atau rusak. Mulai ronde baru ya.',
            'skor.*' => 'Data ronde tidak valid. Mulai ronde baru ya.',
        ];
    }
}