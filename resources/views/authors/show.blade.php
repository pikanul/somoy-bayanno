<x-public.layout
    :title="$seo['title'].' - '.config('app.name')"
    :description="$seo['description'] ?? $author['name_bn']"
    :canonical="$seo['canonical']"
    og-type="profile"
    :og-image="$seo['image']"
    :json-ld="$jsonLd"
>
    <article class="mx-auto max-w-3xl bg-white p-6 sm:p-8">
        @if ($author['photo'])
            <img src="{{ Storage::url($author['photo']) }}" alt="{{ $author['name_bn'] }}" loading="lazy" class="mb-6 aspect-[4/3] w-full object-cover">
        @endif

        <h1 class="text-3xl font-bold text-brand-dark">{{ $author['name_bn'] }}</h1>

        @if ($author['name_en'])
            <p class="mt-2 text-lg font-semibold text-brand-green">{{ $author['name_en'] }}</p>
        @endif

        @if ($author['designation'])
            <p class="mt-2 text-sm font-medium text-brand-red">{{ $author['designation'] }}</p>
        @endif

        @if ($author['bio_bn'])
            <div class="mt-6 leading-8 text-neutral-800">{{ $author['bio_bn'] }}</div>
        @endif

        @if ($author['bio_en'])
            <div class="mt-6 leading-7 text-neutral-700">{{ $author['bio_en'] }}</div>
        @endif
    </article>
</x-public.layout>
