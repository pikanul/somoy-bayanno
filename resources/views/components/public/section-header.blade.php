@props(['title', 'url' => null])

<div class="mb-4 flex items-center justify-between border-b-2 border-brand-green pb-2">
    <h2 class="text-xl font-bold text-brand-dark sm:text-2xl">{{ $title }}</h2>
    @if ($url)
        <a href="{{ $url }}" class="text-sm font-semibold text-brand-red hover:text-red-800">সব দেখুন</a>
    @endif
</div>
