<form action="{{ route('search') }}" method="get" role="search" class="flex overflow-hidden rounded border border-neutral-200 bg-white shadow-sm">
    <label for="site-search-{{ $attributes->get('id', 'default') }}" class="sr-only">খুঁজুন</label>
    <input id="site-search-{{ $attributes->get('id', 'default') }}" name="q" type="search" value="{{ request('q') }}" maxlength="120" placeholder="খবর খুঁজুন..." class="min-h-11 min-w-0 flex-1 px-4 py-2 text-sm outline-none" autocomplete="off">
    <button type="submit" class="min-h-11 w-12 shrink-0 text-brand-dark hover:bg-neutral-100">
        <span class="sr-only">খুঁজুন</span>
        <svg aria-hidden="true" class="mx-auto h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m21 21-4.3-4.3" />
            <circle cx="11" cy="11" r="7" />
        </svg>
    </button>
</form>
