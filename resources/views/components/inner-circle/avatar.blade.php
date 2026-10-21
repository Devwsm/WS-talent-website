@props(['member', 'kelas' => ''])
<span class="ic-avatar {{ $kelas }}" aria-hidden="true">
    @if (!empty($member->avatar))
        <img src="{{ \Illuminate\Support\Facades\Storage::url($member->avatar) }}" alt="">
    @else
        {{ $member->avatar_emoji ?? '😎' }}
    @endif
</span>
