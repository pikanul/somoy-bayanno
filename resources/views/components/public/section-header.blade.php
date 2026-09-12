@props(['title', 'url' => null])

<div class="mb-4 flex items-center gap-4">
    <h2 class="section-title shrink-0 text-xl font-bold text-brand-dark sm:text-2xl">{{ $title }}</h2>
    <span class="h-px flex-1 bg-neutral-200" aria-hidden="true"></span>
    @if ($url)
        <a href="{{ $url }}" class="shrink-0 text-sm font-semibold text-brand-dark hover:text-brand-red">সব দেখুন →</a>
    @endif
</div>
