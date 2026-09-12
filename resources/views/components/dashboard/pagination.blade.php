{{-- Pagination sederhana buat list dashboard, ngikutin tema gelap
     (rounded-2xl border-white/10 bg-white/3) yang dipake di card list lain. --}}
@if ($paginator->hasPages())
    <nav aria-label="Navigasi halaman" class="flex items-center justify-between gap-3 mt-4 flex-wrap">
        <p class="text-xs text-white/40">
            Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}
            ({{ $paginator->total() }} data)
        </p>

        <div class="flex items-center gap-2">
            @if ($paginator->onFirstPage())
                <span class="px-3 py-1.5 text-xs rounded-xl border border-white/10 text-white/30 cursor-not-allowed">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i> Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                    class="px-3 py-1.5 text-xs rounded-xl border border-white/10 text-white hover:border-white/30 transition-colors">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i> Sebelumnya
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                    class="px-3 py-1.5 text-xs rounded-xl border border-white/10 text-white hover:border-white/30 transition-colors">
                    Selanjutnya <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </a>
            @else
                <span class="px-3 py-1.5 text-xs rounded-xl border border-white/10 text-white/30 cursor-not-allowed">
                    Selanjutnya <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </span>
            @endif
        </div>
    </nav>
@endif
