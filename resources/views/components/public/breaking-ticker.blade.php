@php
    $items = app(\App\Services\PublicContentCache::class)->breakingNews();
@endphp

<section class="border-b border-red-100 bg-white" aria-label="ব্রেকিং নিউজ">
    <div class="public-container flex flex-col gap-2 py-3 sm:flex-row sm:items-center">
        <h2 class="shrink-0 bg-brand-red px-3 py-2 text-sm font-bold text-white">ব্রেকিং</h2>
        <div class="min-w-0 flex-1 overflow-hidden">
            @if ($items->isNotEmpty())
                <ul class="flex gap-6 overflow-x-auto whitespace-nowrap pb-1 text-sm font-medium text-brand-dark" aria-live="polite">
                    @foreach ($items as $item)
                        <li class="shrink-0">
                            @if ($item->target_type === \App\Enums\BreakingNewsTargetType::External && $item->external_url)
                                <a class="hover:text-brand-red" href="{{ $item->external_url }}" rel="noopener noreferrer">{{ $item->headline_bn }}</a>
                            @elseif ($item->target_type === \App\Enums\BreakingNewsTargetType::Article && $item->article)
                                <a class="hover:text-brand-red" href="{{ route('articles.show', $item->article->slug) }}">{{ $item->headline_bn }}</a>
                            @else
                                <span>{{ $item->headline_bn }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-neutral-600">এই মুহূর্তে কোনো ব্রেকিং নিউজ নেই।</p>
            @endif
        </div>
    </div>
</section>
