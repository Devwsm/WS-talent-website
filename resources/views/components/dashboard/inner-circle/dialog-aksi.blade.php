{{--
    Dialog form kecil di halaman detail anggota (tambah poin, kurangi poin, nonaktifkan akun).
    Props: id, judul, aksi (URL), method (POST|PATCH), tombol (teks), bahaya (tombol merah penuh peringatan)
    Isian form ada di slot. Dibuka dengan: document.getElementById(id).showModal()
--}}
@props(['id', 'judul', 'aksi', 'method' => 'POST', 'tombol' => 'Simpan', 'bahaya' => false])
<dialog id="{{ $id }}" aria-labelledby="{{ $id }}-judul"
    class="fixed inset-0 m-0 w-screen h-dvh max-w-none max-h-none bg-black/70 backdrop:bg-transparent p-0 overflow-y-auto">
    <div class="min-h-dvh w-full flex items-center justify-center p-4">
        <div
            class="w-full max-w-md max-h-[calc(100dvh-2rem)] flex flex-col min-w-0 rounded-3xl border border-white/10 bg-black shadow-xl overflow-hidden">
            <div class="flex items-center justify-between gap-2 px-6 py-4 border-b border-white/10 bg-white/3 shrink-0">
                <h3 id="{{ $id }}-judul" class="font-bold uppercase tracking-wide text-sm text-white">
                    {{ $judul }}</h3>
                <button type="button" onclick="document.getElementById('{{ $id }}').close()" aria-label="Tutup"
                    class="text-white/40 hover:text-white transition">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <form method="POST" action="{{ $aksi }}" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                @csrf
                @if ($method !== 'POST')
                    @method($method)
                @endif
                <div class="flex-1 min-h-0 p-6 flex flex-col gap-4 overflow-y-auto">
                    {{ $slot }}
                </div>
                <div class="flex justify-end gap-2 px-6 py-4 border-t border-white/10 bg-white/3 shrink-0">
                    <button type="button" onclick="document.getElementById('{{ $id }}').close()"
                        class="h-11 px-5 rounded-lg border border-white/15 text-sm font-semibold text-white/70 hover:bg-white/10 hover:text-white transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                        class="h-11 px-5 text-white font-bold uppercase tracking-widest text-sm {{ $bahaya ? 'bg-red-700 hover:bg-red-600' : 'bg-red-950 hover:bg-red-900' }} active:scale-95 transition rounded-lg">
                        {{ $tombol }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</dialog>
