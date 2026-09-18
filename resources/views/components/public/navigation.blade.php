@props(['items' => []])

@php
    $items = collect($items);

    $fallbackUrls = [
        'সর্বশেষ' => route('home'),
        'Live' => route('static.show', 'live'),
        'জাতীয়' => route('static.show', 'national'),
        'রাজনীতি' => route('static.show', 'politics'),
        'আন্তর্জাতিক' => route('static.show', 'international'),
        'অর্থনীতি' => route('static.show', 'economy'),
        'খেলা' => route('static.show', 'sports'),
        'বিনোদন' => route('static.show', 'entertainment'),
        'প্রযুক্তি' => route('static.show', 'technology'),
        'জীবনযাপন' => route('static.show', 'lifestyle'),
        'মতামত' => route('static.show', 'opinion'),
        'ভিডিও' => route('static.show', 'videos'),
        'ছবিঘর' => route('static.show', 'photos'),
        'লেখক' => route('static.show', 'writers'),
        'আরও' => route('static.show', 'more'),
    ];

    if ($items->isEmpty()) {
        $items = collect($fallbackUrls)->map(fn (string $url, string $label): array => [
            'label' => $label,
            'url' => $url,
            'children' => [],
        ])->values();
    }

    $items = $items->map(function (array $item) use ($fallbackUrls): array {
        $item['url'] = ($item['url'] ?? '#') === '#'
            ? ($fallbackUrls[$item['label']] ?? route('home'))
            : $item['url'];
        $item['children'] = $item['children'] ?? [];

        return $item;
    });
@endphp

<nav id="primary-navigation" class="header-nav live-theme-nav sticky top-0 z-[70] text-white shadow-lg shadow-emerald-950/20" aria-label="প্রধান নেভিগেশন" x-data="{ openDropdown: null }" x-on:click.outside="openDropdown = null">
    <div class="header-nav-track flex min-h-[54px] w-full items-stretch px-2 sm:px-4 lg:px-6">
        <a href="{{ route('home') }}" @class([
            'header-home-button grid w-[58px] shrink-0 place-items-center bg-brand-red text-white transition hover:bg-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-4px] focus-visible:outline-white',
            'ring-2 ring-white/30' => request()->routeIs('home'),
        ]) aria-label="হোম">
            <svg aria-hidden="true" class="h-6 w-6 drop-shadow" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 3 3 10.8V21h6v-6h6v6h6V10.8L12 3Z" />
            </svg>
        </a>

        <div class="header-nav-scroll min-w-0 flex-1 overflow-x-auto">
            <ul class="flex min-w-max items-stretch">
                @foreach ($items as $index => $item)
                    @php
                        $children = collect($item['children']);
                        $hasChildren = $children->isNotEmpty();
                        $isActive = request()->fullUrlIs($item['url']) || request()->url() === $item['url'];
                    @endphp
                    <li class="group relative">
                        <a href="{{ $item['url'] }}" @if ($hasChildren) x-on:mouseenter="openDropdown = {{ $index }}" x-on:focus="openDropdown = {{ $index }}" @endif @class([
                            'header-nav-link flex min-h-[54px] items-center gap-1.5 whitespace-nowrap px-3 text-sm font-black text-white transition lg:text-[0.95rem] xl:px-4',
                            'is-active' => $isActive,
                        ])>
                            {{ $item['label'] }}
                            @if ($hasChildren)
                                <span class="text-lg transition group-hover:rotate-180" aria-hidden="true">⌄</span>
                            @endif
                        </a>

                        @if ($hasChildren)
                            <div x-cloak x-show="openDropdown === {{ $index }}" x-transition.opacity.duration.150ms x-on:mouseleave="openDropdown = null" class="absolute left-0 top-full z-50 min-w-56 rounded-b-xl border border-emerald-900/10 bg-white py-2 text-[#202020] shadow-xl">
                                @foreach ($children as $child)
                                    <a href="{{ $child['url'] }}" class="block px-4 py-2.5 text-sm font-bold transition hover:bg-emerald-50 hover:text-[#007a3d] focus-visible:bg-emerald-50 focus-visible:text-[#007a3d] focus-visible:outline-none">
                                        {{ $child['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <a href="{{ route('static.show', 'live') }}" class="header-live-button hidden min-h-[54px] shrink-0 -skew-x-12 items-center gap-2.5 bg-brand-red px-5 text-base font-black uppercase text-white transition hover:bg-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-4px] focus-visible:outline-white lg:inline-flex xl:px-6">
            <span class="skew-x-12" aria-hidden="true">((•))</span>
            <span class="skew-x-12">LIVE</span>
        </a>
    </div>
</nav>
