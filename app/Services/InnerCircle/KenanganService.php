<?php

namespace App\Services\InnerCircle;

use App\Models\ic_memory;
use App\Models\member;
use App\Support\GambarGagal;
use App\Support\ImageProcessor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Galeri kenangan WS Inner Circle: terbitkan, hapus, suka, dan sembunyikan (moderasi admin).
 * Foto selalu lewat ImageProcessor (WebP, EXIF diluruskan dan dibuang).
 */
class KenanganService
{
    public function __construct(private readonly ImageProcessor $gambar) {}

    /**
     * Terbitkan kenangan baru; langsung tayang (moderasi sesudah terbit: admin bisa menyembunyikan).
     *
     * @param  array{caption: string, tempat?: ?string, kategori: string}  $data
     *
     * @throws GambarGagal bila foto tidak bisa diolah (tidak ada yang tersimpan)
     */
    public function terbit(member $anggota, array $data, ?UploadedFile $foto = null): ic_memory
    {
        $path = $foto
            ? $this->gambar->simpanWebp($foto, 'inner-circle/kenangan', (int) config('inner_circle.foto_kenangan_sisi_maks'), 80)
            : null;

        try {
            return ic_memory::query()->create([
                'member_id' => $anggota->getKey(),
                'caption' => $data['caption'],
                'tempat' => ($data['tempat'] ?? null) ?: null,
                'kategori' => $data['kategori'],
                'foto' => $path,
                'status' => ic_memory::TAYANG,
            ]);
        } catch (\Throwable $e) {
            // Jangan tinggalkan berkas yatim bila baris gagal disimpan.
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }
    }

    /** Hapus kenangan beserta fotonya. Tanda suka ikut terhapus (kunci asing cascade). */
    public function hapus(ic_memory $kenangan): void
    {
        $foto = $kenangan->foto;
        $kenangan->delete();

        if ($foto) {
            Storage::disk('public')->delete($foto);
        }
    }

    /**
     * Suka atau batal suka. Hapus dulu; kalau tidak ada yang terhapus berarti belum suka, jadi sisipkan.
     * insertOrIgnore + kunci utama (anggota, kenangan) membuat klik ganda tidak pernah menggandakan.
     *
     * @return array{disukai: bool, jumlah: int}
     */
    public function toggleSuka(member $anggota, ic_memory $kenangan): array
    {
        $disukai = DB::transaction(function () use ($anggota, $kenangan) {
            $dihapus = DB::table('ic_memory_likes')
                ->where('member_id', $anggota->getKey())
                ->where('memory_id', $kenangan->getKey())
                ->delete();

            if ($dihapus > 0) {
                return false;
            }

            DB::table('ic_memory_likes')->insertOrIgnore([
                'member_id' => $anggota->getKey(),
                'memory_id' => $kenangan->getKey(),
                'created_at' => now(),
            ]);

            return true;
        });

        return [
            'disukai' => $disukai,
            'jumlah' => (int) DB::table('ic_memory_likes')->where('memory_id', $kenangan->getKey())->count(),
        ];
    }

    /** Sembunyikan dari galeri atau tayangkan lagi (admin). Tidak menghapus apa pun. */
    public function atur(ic_memory $kenangan, bool $sembunyi): ic_memory
    {
        $kenangan->status = $sembunyi ? ic_memory::DISEMBUNYIKAN : ic_memory::TAYANG;
        $kenangan->save();

        return $kenangan;
    }

    /** Galeri anggota: hanya yang tayang, terbaru dulu, dengan jumlah suka dan status suka anggota ini. */
    public function galeri(member $anggota, ?string $kategori): LengthAwarePaginator
    {
        $halaman = ic_memory::query()
            ->tayang()
            ->with('member')
            ->withCount('penyuka as jumlah_like')
            ->withExists(['penyuka as disukai_saya' => fn($q) => $q->where('ic_memory_likes.member_id', $anggota->getKey())])
            ->when($kategori, fn($q) => $q->where('kategori', $kategori))
            ->orderByDesc('created_at')
            ->orderByDesc('id_memory')
            ->paginate((int) config('inner_circle.kenangan_per_halaman', 12))
            ->withQueryString();

        foreach ($halaman as $k) {
            $k->setAttribute('milik_saya', (int) $k->member_id === (int) $anggota->getKey());
        }

        return $halaman;
    }
}