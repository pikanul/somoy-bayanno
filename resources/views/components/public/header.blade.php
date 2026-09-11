@php
    $utilityLinks = [
        ['label' => 'আজকের পত্রিকা', 'url' => '#'],
        ['label' => 'ই-পেপার', 'url' => '#'],
        ['label' => 'যোগাযোগ', 'url' => '#'],
    ];

    $navItems = app(\App\Services\PublicContentCache::class)->navigationItems();
@endphp

<header class="border-b border-neutral-200 bg-white" x-data="{ menuOpen: false, searchOpen: false }">
    <div class="bg-brand-dark text-white">
        <div class="public-container flex min-h-10 flex-wrap items-center justify-between gap-3 py-2 text-sm">
            <p>{{ now()->translatedFormat('l, d F Y') }}</p>
            <nav aria-label="সহায়ক লিংক">
                <ul class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    @foreach ($utilityLinks as $link)
                        <li><a class="hover:text-white/80" href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </div>

    <div class="public-container py-4">
        <div class="flex items-center justify-between gap-3 sm:gap-4">
            <a href="{{ url('/') }}" class="min-w-0" aria-label="দৈনিক সময় বায়ান্ন হোম">
                <span class="block text-2xl font-bold leading-tight text-brand-green min-[390px]:text-3xl sm:text-4xl">দৈনিক সময় বায়ান্ন</span>
                <span class="block text-xs font-semibold uppercase tracking-normal text-brand-red sm:text-sm">The Daily Somoy Bayanno</span>
            </a>

            <div class="hidden min-w-[260px] max-w-sm flex-1 md:block">
                <x-public.search-form id="desktop" />
            </div>

            <div class="flex items-center gap-2">
                <button type="button" class="inline-flex h-11 w-11 shrink-0 items-center justify-center border border-neutral-300 text-brand-dark md:hidden" x-on:click="searchOpen = ! searchOpen" :aria-expanded="searchOpen.toString()" aria-controls="mobile-search">
                    <span class="sr-only">সার্চ খুলুন</span>
                    <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m21 21-4.3-4.3" />
                        <circle cx="11" cy="11" r="7" />
                    </svg>
                </button>
                <button type="button" class="inline-flex h-11 w-11 shrink-0 items-center justify-center border border-neutral-300 text-brand-dark lg:hidden" x-on:click="menuOpen = ! menuOpen" :aria-expanded="menuOpen.toString()" aria-controls="primary-navigation">
                    <span class="sr-only">মেনু খুলুন</span>
                    <svg aria-hidden="true" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path x-show="!menuOpen" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="menuOpen" d="M6 6l12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>
        </div>

        <div id="mobile-search" class="mt-4 md:hidden" x-show="searchOpen" x-transition>
            <x-public.search-form id="mobile" />
        </div>
    </div>

    <x-public.navigation :items="$navItems" />
</header>
