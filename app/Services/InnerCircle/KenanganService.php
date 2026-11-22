<?php

namespace App\Services\InnerCircle;

use App\Models\ic_memory;
use App\Models\ic_memory_comment;
use App\Models\member;
use App\Support\GambarGagal;
use App\Support\ImageProcessor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
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
     * @param  array{caption: string, tempat?: ?string, kategori: string, bingkai?: ?string}  $data
     *
     * @throws GambarGagal bila foto tidak bisa diolah (tidak ada yang tersimpan)
     */
    public function terbit(member $anggota, array $data, ?UploadedFile $foto = null): ic_memory
    {
        $path = $foto
            ? // Semua foto kenangan 4:5 (potret ala feed): dipotong pas dari tengah, atau utuh di atas latar buram.
            $this->gambar->simpanWebp($foto, 'inner-circle/kenangan', (int) config('inner_circle.foto_kenangan_sisi_maks'), 80, 4 / 5, ($data['bingkai'] ?? 'potong') !== 'utuh')
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

    /** Satu kenangan untuk halaman detail: jumlah suka dan komentar, status suka anggota ini. */
    public function detail(member $anggota, ic_memory $kenangan): ic_memory
    {
        $kenangan->loadCount(['penyuka as jumlah_like', 'komentar as jumlah_komentar'])
            ->loadExists(['penyuka as disukai_saya' => fn($q) => $q->where('ic_memory_likes.member_id', $anggota->getKey())])
            ->load('member');
        $kenangan->setAttribute('milik_saya', (int) $kenangan->member_id === (int) $anggota->getKey());
        $this->isiPenyuka([$kenangan]);

        return $kenangan;
    }

    /** Tulis komentar. Dipanggil sesudah Gate 'komentar' (hanya kenangan yang tayang). */
    public function komentar(member $anggota, ic_memory $kenangan, string $isi): ic_memory_comment
    {
        return ic_memory_comment::query()->create([
            'memory_id' => $kenangan->getKey(),
            'member_id' => $anggota->getKey(),
            'isi' => $isi,
        ]);
    }

    public function hapusKomentar(ic_memory_comment $komentar): void
    {
        $komentar->delete();
    }

    /** Galeri anggota: hanya yang tayang, terbaru dulu, dengan jumlah suka dan status suka anggota ini. */
    public function galeri(member $anggota, ?string $kategori): LengthAwarePaginator
    {
        $halaman = ic_memory::query()
            ->tayang()
            ->with('member')
            ->withCount(['penyuka as jumlah_like', 'komentar as jumlah_komentar'])
            ->withExists(['penyuka as disukai_saya' => fn($q) => $q->where('ic_memory_likes.member_id', $anggota->getKey())])
            ->when($kategori, fn($q) => $q->where('kategori', $kategori))
            ->orderByDesc('created_at')
            ->orderByDesc('id_memory')
            ->paginate((int) config('inner_circle.kenangan_per_halaman', 12))
            ->withQueryString();

        foreach ($halaman as $k) {
            $k->setAttribute('milik_saya', (int) $k->member_id === (int) $anggota->getKey());
        }
        $this->isiPenyuka($halaman);

        return $halaman;
    }

    /** Isi `penyuka_tampil` (penyuka terbaru, maksimal $batas orang) pada tiap kenangan: bahan tumpukan foto di kartu. */
    public function isiPenyuka(iterable $daftar, int $batas = 3): void
    {
        foreach ($daftar as $k) {
            $k->setRelation('penyuka_tampil', $this->penyukaTerbaru($k, $batas));
        }
    }

    /**
     * Penyuka terbaru dulu. Satu query kecil per kenangan (satu halaman galeri = 12 kenangan), tanpa fungsi
     * window supaya jalan di MySQL/MariaDB versi lama di hosting.
     *
     * @return Collection<int, member>
     */
    public function penyukaTerbaru(ic_memory $kenangan, int $batas): Collection
    {
        return member::query()
            ->join('ic_memory_likes as l', 'l.member_id', '=', 'members.id_member')
            ->where('l.memory_id', $kenangan->getKey())
            ->orderByDesc('l.created_at')
            ->orderByDesc('l.member_id')
            ->limit($batas)
            ->get(['members.*']);
    }

    /**
     * Daftar penyuka untuk popup dan penyegaran tumpukan foto (JSON): jumlah semua suka + penyuka terbaru
     * (maksimal `inner_circle.penyuka_daftar_maks`). Nama disingkat seperti di kenangan dan komentar.
     *
     * @return array{jumlah: int, penyuka: list<array{nama: string, foto: ?string, emoji: string}>}
     */
    public function daftarPenyuka(ic_memory $kenangan): array
    {
        $penyuka = $this->penyukaTerbaru($kenangan, (int) config('inner_circle.penyuka_daftar_maks', 50))
            ->map(fn(member $m) => [
                'nama' => $m->nama_singkat,
                'foto' => $m->avatar ? Storage::url($m->avatar) : null,
                'emoji' => $m->avatar_emoji,
            ])
            ->values()
            ->all();

        return [
            'jumlah' => (int) DB::table('ic_memory_likes')->where('memory_id', $kenangan->getKey())->count(),
            'penyuka' => $penyuka,
        ];
    }
}