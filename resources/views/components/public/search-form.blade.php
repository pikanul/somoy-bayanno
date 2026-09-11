<form action="{{ route('search') }}" method="get" role="search" class="flex overflow-hidden border border-neutral-300 bg-white">
    <label for="site-search-{{ $attributes->get('id', 'default') }}" class="sr-only">খুঁজুন</label>
    <input id="site-search-{{ $attributes->get('id', 'default') }}" name="q" type="search" value="{{ request('q') }}" maxlength="120" placeholder="সংবাদ খুঁজুন" class="min-h-11 min-w-0 flex-1 px-3 py-2 text-sm outline-none" autocomplete="off">
    <button type="submit" class="min-h-11 shrink-0 bg-brand-green px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">
        খুঁজুন
    </button>
</form>
