<x-public.layout
    :title="$seo['title'].' - '.config('app.name')"
    :description="$seo['description'] ?? $article->headline_bn"
    :canonical="$seo['canonical']"
    og-type="article"
    :og-image="$seo['image']"
    :json-ld="$jsonLd"
>
    @php
        $featuredImage = $article->featuredMedia?->url() ?? $article->featured_image_url;
        $shareUrl = route('articles.show', $article->slug);
        $shareText = $article->headline_bn;
        $body = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $article->body_bn) ?? '';
        $body = strip_tags($body, '<p><br><strong><b><em><i><ul><ol><li><blockquote><h2><h3><h4><a>');
        $body = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $body) ?? '';
        $body = preg_replace('/href\s*=\s*([\'"])\s*(javascript|data|vbscript):.*?\1/i', 'href="#"', $body) ?? '';
        $body = preg_replace_callback('/<a\s+([^>]*href=(["\'])(.*?)\2[^>]*)>/i', function (array $matches) {
            $href = $matches[3] ?? '';
            $attrs = $matches[1] ?? '';

            if (\Illuminate\Support\Str::startsWith($href, ['http://', 'https://']) && ! \Illuminate\Support\Str::startsWith($href, url('/'))) {
                $attrs = preg_replace('/\s(target|rel)=("[^"]*"|\'[^\']*\')/i', '', $attrs) ?? $attrs;

                return '<a '.$attrs.' target="_blank" rel="noopener noreferrer">';
            }

            return '<a '.$attrs.'>';
        }, $body) ?? '';
    @endphp

    <div class="grid gap-8 lg:grid-cols-[minmax(0,760px)_minmax(280px,1fr)]">
        <article class="min-w-0 bg-white p-5 sm:p-8">
            <nav aria-label="ব্রেডক্রাম্ব" class="mb-5 text-sm text-neutral-600">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a class="hover:text-brand-green" href="{{ url('/') }}">হোম</a></li>
                    @if ($article->primaryCategory)
                        <li aria-hidden="true">/</li>
                        <li>{{ $article->primaryCategory->name_bn }}</li>
                    @endif
                </ol>
            </nav>

            @if ($article->primaryCategory)
                <p class="mb-3 text-sm font-bold text-brand-red">{{ $article->primaryCategory->name_bn }}</p>
            @endif

            <h1 class="text-3xl font-bold leading-tight text-brand-dark sm:text-5xl">{{ $article->headline_bn }}</h1>

            @if ($article->subheadline_bn)
                <p class="mt-4 text-lg leading-8 text-neutral-700">{{ $article->subheadline_bn }}</p>
            @endif

            <div class="mt-5 border-y border-neutral-200 py-4 text-sm text-neutral-600">
                <div class="flex flex-wrap gap-x-3 gap-y-1">
                    <span>
                        @if ($article->authors->isNotEmpty())
                            @foreach ($article->authors as $author)
                                <a class="font-semibold text-brand-green hover:text-green-800" href="{{ route('authors.show', $author->slug) }}">{{ $author->name_bn }}</a>@if (! $loop->last), @endif
                            @endforeach
                        @else
                            {{ $article->reporter_name ?: 'দৈনিক সময় বায়ান্ন' }}
                        @endif
                    </span>
                    @if ($article->location)
                        <span>{{ $article->location }}</span>
                    @endif
                </div>
                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
                    <time datetime="{{ $article->published_at?->toIso8601String() }}">প্রকাশ: {{ $article->published_at?->translatedFormat('d F Y, h:i A') }}</time>
                    @if ($article->updated_content_at && $article->updated_content_at->gt($article->published_at))
                        <time datetime="{{ $article->updated_content_at->toIso8601String() }}">আপডেট: {{ $article->updated_content_at->translatedFormat('d F Y, h:i A') }}</time>
                    @endif
                </div>
            </div>

            @if ($featuredImage)
                <figure class="mt-6">
                    <img src="{{ $featuredImage }}" alt="{{ $article->featuredMedia?->alt_text ?: $article->headline_bn }}" loading="eager" width="760" height="428" class="aspect-[16/9] w-full object-cover">
                    @if ($article->image_caption || $article->image_credit || $article->featuredMedia?->caption || $article->featuredMedia?->credit)
                        <figcaption class="mt-2 text-sm leading-6 text-neutral-600">
                            {{ $article->image_caption ?: $article->featuredMedia?->caption }}
                            @if ($article->image_credit || $article->featuredMedia?->credit)
                                <span class="font-semibold">ছবি: {{ $article->image_credit ?: $article->featuredMedia?->credit }}</span>
                            @endif
                        </figcaption>
                    @endif
                </figure>
            @endif

            <div class="mt-6 flex flex-wrap items-center gap-3 border-y border-neutral-200 py-4" aria-label="সোশ্যাল শেয়ার">
                <span class="text-sm font-bold">শেয়ার করুন</span>
                <a class="inline-flex min-h-11 items-center border border-neutral-300 px-3 py-2 text-sm font-semibold hover:border-brand-green hover:text-brand-green" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener noreferrer">Facebook</a>
                <a class="inline-flex min-h-11 items-center border border-neutral-300 px-3 py-2 text-sm font-semibold hover:border-brand-green hover:text-brand-green" href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($shareText) }}" target="_blank" rel="noopener noreferrer">X</a>
                <a class="inline-flex min-h-11 items-center border border-neutral-300 px-3 py-2 text-sm font-semibold hover:border-brand-green hover:text-brand-green" href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}" target="_blank" rel="noopener noreferrer">LinkedIn</a>
            </div>

            <div class="article-body mt-7 max-w-none text-[1.0625rem] leading-8 text-neutral-900 sm:text-lg sm:leading-9">
                {!! $body !!}
            </div>

            @if ($article->correction_note)
                <section class="mt-8 border-l-4 border-brand-red bg-red-50 p-4" aria-label="সংশোধনী">
                    <h2 class="font-bold text-brand-dark">সংশোধনী</h2>
                    <p class="mt-2 text-sm leading-6 text-neutral-700">{{ $article->correction_note }}</p>
                </section>
            @endif

            @if ($article->tags->isNotEmpty() || $article->topics->isNotEmpty())
                <section class="mt-8 space-y-4" aria-label="ট্যাগ ও টপিক">
                    @if ($article->tags->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($article->tags as $tag)
                                <span class="bg-brand-light px-3 py-1 text-sm font-semibold text-brand-dark">#{{ $tag->name_bn }}</span>
                            @endforeach
                        </div>
                    @endif
                    @if ($article->topics->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($article->topics as $topic)
                                <span class="border border-neutral-300 px-3 py-1 text-sm font-semibold text-neutral-700">{{ $topic->name_bn }}</span>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif

            @if ($article->authors->isNotEmpty())
                <section class="mt-8 border-t border-neutral-200 pt-6" aria-label="লেখক পরিচিতি">
                    <x-public.section-header title="লেখক পরিচিতি" />
                    <div class="space-y-5">
                        @foreach ($article->authors as $author)
                            <div class="flex gap-4">
                                @if ($author->photo)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($author->photo) }}" alt="{{ $author->name_bn }}" loading="lazy" width="64" height="64" class="h-16 w-16 shrink-0 object-cover">
                                @endif
                                <div>
                                    <h2 class="font-bold text-brand-dark"><a class="hover:text-brand-green" href="{{ route('authors.show', $author->slug) }}">{{ $author->name_bn }}</a></h2>
                                    @if ($author->designation)
                                        <p class="text-sm text-brand-red">{{ $author->designation }}</p>
                                    @endif
                                    @if ($author->bio_bn)
                                        <p class="mt-2 line-clamp-3 text-sm leading-6 text-neutral-700">{{ $author->bio_bn }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </article>

        <aside class="space-y-6" aria-label="আরও পড়ুন">
            <x-public.ad-slot class="min-h-64" label="Advertisement" />
            <section class="bg-white p-4">
                <x-public.section-header title="সম্পর্কিত সংবাদ" />
                <p class="text-sm leading-6 text-neutral-600">সম্পর্কিত সংবাদ এখানে দেখানো হবে।</p>
            </section>
            <x-public.ad-slot class="min-h-48" label="Advertisement" />
        </aside>
    </div>
</x-public.layout>
