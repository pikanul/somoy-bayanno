@props([
    'title',
    'summary' => null,
    'url' => '#',
    'image' => null,
    'category' => null,
    'publishedAt' => null,
])

<article class="group h-full bg-white">
    <a href="{{ $url }}" class="block">
        @if ($image)
            <img src="{{ $image }}" alt="" loading="lazy" class="aspect-[16/9] w-full object-cover">
        @else
            <div class="aspect-[16/9] w-full bg-neutral-200" aria-hidden="true"></div>
        @endif
    </a>
    <div class="space-y-2 p-4">
        @if ($category)
            <p class="text-xs font-bold uppercase tracking-normal text-brand-red">{{ $category }}</p>
        @endif
        <h3 class="text-lg font-bold leading-snug text-brand-dark group-hover:text-brand-green">
            <a href="{{ $url }}">{{ $title }}</a>
        </h3>
        @if ($summary)
            <p class="line-clamp-3 text-sm leading-6 text-neutral-700">{{ $summary }}</p>
        @endif
        @if ($publishedAt)
            <time class="block text-xs text-neutral-500" datetime="{{ $publishedAt->toIso8601String() }}">{{ $publishedAt->diffForHumans() }}</time>
        @endif
    </div>
</article>
