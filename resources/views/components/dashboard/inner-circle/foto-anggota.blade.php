@props(['member', 'ukuran' => 'w-12 h-12 text-2xl'])
{{-- Foto profil anggota untuk CMS (emoji bawaan bila belum mengunggah foto). --}}
<span aria-hidden="true"
    class="{{ $ukuran }} shrink-0 overflow-hidden rounded-full bg-white/10 border border-white/10 flex items-center justify-center">
    @if (!empty($member->avatar))
        <img src="{{ \Illuminate\Support\Facades\Storage::url($member->avatar) }}" alt="" loading="lazy"
            class="w-full h-full object-cover">
    @else
        {{ $member->avatar_emoji ?? '😎' }}
    @endif
</span>
