@props(['label' => 'Advertisement'])

<aside {{ $attributes->merge(['class' => 'ad-slot flex min-h-24 items-center justify-center overflow-hidden rounded border border-emerald-100 bg-white text-center text-xs font-semibold uppercase tracking-normal text-neutral-500']) }} aria-label="{{ $label }}">
    <div>
        <span class="block text-lg font-bold text-brand-green">একটি সবুজ, নিরাপদ ও সমৃদ্ধ বাংলাদেশ</span>
        <span class="mt-1 block text-xs text-neutral-500">{{ $label }}</span>
    </div>
</aside>
