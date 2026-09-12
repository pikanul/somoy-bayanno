@php
    use App\Enums\BreakingNewsTargetType;

    $breakingItems = app(\App\Services\PublicContentCache::class)->breakingNews();

    $targetUrl = function ($item): ?string {
        return match ($item->target_type) {
            BreakingNewsTargetType::Article => $item->article ? route('articles.show', $item->article->slug) : null,
            BreakingNewsTargetType::External => $item->external_url,
            default => null,
        };
    };
@endphp

<section class="header-action-bar border-b border-red-100 bg-white/95 shadow-[0_8px_24px_rgba(0,0,0,0.06)]" aria-label="ব্রেকিং নিউজ">
    <div class="public-container flex flex-col gap-3 py-3 lg:flex-row lg:items-center">
        <div class="w-full max-w-md lg:w-[300px] xl:w-[330px]">
            <x-public.search-form id="header-action" />
        </div>

        <div class="flex min-w-0 flex-1 items-center gap-3 overflow-hidden">
            <h2 class="inline-flex shrink-0 -skew-x-12 items-center gap-2 rounded-sm bg-brand-red px-5 py-3 text-sm font-black text-white shadow-lg shadow-red-600/20">
                <span class="skew-x-12">ϟ</span>
                <span class="skew-x-12">ব্রেকিং নিউজ</span>
            </h2>

            @if ($breakingItems->isNotEmpty())
                <div class="min-w-0 flex-1 overflow-hidden">
                    <ul class="flex gap-6 overflow-x-auto whitespace-nowrap pb-1 text-sm font-bold text-brand-dark" aria-live="polite">
                        @foreach ($breakingItems as $item)
                            @php($url = $targetUrl($item))
                            <li class="shrink-0">
                                <span class="mr-3 text-brand-red">●</span>
                                @if ($url)
                                    <a class="hover:text-brand-red" href="{{ $url }}">{{ $item->headline_bn }}</a>
                                @else
                                    <span>{{ $item->headline_bn }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <p class="min-w-0 flex-1 truncate text-sm font-semibold text-neutral-500">এই মুহূর্তে কোনো ব্রেকিং নিউজ নেই।</p>
            @endif
        </div>

        <div class="flex shrink-0 items-center gap-3">
            <div class="hidden gap-1 lg:flex">
                <button type="button" class="grid h-9 w-9 place-items-center rounded-full bg-white text-brand-dark shadow-md transition hover:bg-brand-red hover:text-white" aria-label="আগের ব্রেকিং নিউজ">‹</button>
                <button type="button" class="grid h-9 w-9 place-items-center rounded-full bg-white text-brand-dark shadow-md transition hover:bg-brand-red hover:text-white" aria-label="পরের ব্রেকিং নিউজ">›</button>
            </div>
            <a href="/newsroom" class="group inline-flex min-h-11 items-center gap-2 rounded-full bg-white px-5 py-2 text-sm font-black text-brand-green shadow-lg transition hover:-translate-y-0.5 hover:bg-brand-green hover:text-white">
                <span class="transition group-hover:scale-110">♙</span>
                লগইন
            </a>
            <a href="{{ route('static.show', 'subscribe') }}" class="inline-flex min-h-11 items-center gap-2 rounded-full bg-brand-red px-6 py-2 text-sm font-black text-white shadow-lg shadow-red-600/25 transition hover:-translate-y-0.5 hover:bg-red-700">
                <span>↗</span>
                সাবস্ক্রাইব
            </a>
        </div>
    </div>
</section>
