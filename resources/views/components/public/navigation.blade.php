@props(['items' => []])

<nav id="primary-navigation" class="border-t border-neutral-200 bg-white lg:block" aria-label="প্রধান নেভিগেশন" x-bind:class="menuOpen ? 'block' : 'hidden lg:block'">
    <div class="public-container">
        <ul class="flex max-h-[70vh] flex-col gap-0 overflow-y-auto lg:max-h-none lg:flex-row lg:flex-nowrap lg:items-center lg:overflow-x-auto lg:overflow-y-visible">
            @foreach ($items as $item)
                <li class="shrink-0">
                    <a href="{{ $item['url'] }}" class="block min-h-11 border-b border-neutral-100 px-1 py-3 text-base font-semibold leading-6 text-brand-dark hover:text-brand-green lg:border-b-0 lg:px-4">
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</nav>
