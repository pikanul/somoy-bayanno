@props(['label' => 'Advertisement'])

<aside {{ $attributes->merge(['class' => 'flex min-h-24 items-center justify-center border border-dashed border-neutral-300 bg-white text-xs font-semibold uppercase tracking-normal text-neutral-500']) }} aria-label="{{ $label }}">
    {{ $label }}
</aside>
