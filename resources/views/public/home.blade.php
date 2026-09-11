<x-public.layout
    :title="$seo['title']"
    :description="$seo['description']"
    :canonical="$seo['canonical']"
    :json-ld="$jsonLd"
>
    @php
        $managedArticle = function (string $key) use ($managedSections) {
            return $managedSections->get($key)?->items?->first()?->article;
        };
        $managedArticles = function (string $key) use ($managedSections) {
            return $managedSections->get($key)?->items?->pluck('article')->filter()->values() ?? collect();
        };
        $homeLead = $managedArticle('main_lead') ?? $leadArticle;
        $homeSecondaryStories = collect([
            $managedArticle('secondary_lead_1'),
            $managedArticle('secondary_lead_2'),
        ])->filter()->values();

        if ($homeSecondaryStories->isEmpty()) {
            $homeSecondaryStories = $secondaryStories;
        }

        $topStories = $managedArticles('top_stories');
        $editorsChoice = $managedArticles('editors_choice');
        $specialReport = $managedArticles('special_report');
        $latestHighlight = $managedArticles('latest_highlight');
    @endphp

    <h1 class="sr-only">দৈনিক সময় বায়ান্ন - সর্বশেষ সংবাদ</h1>

    <section class="grid gap-5 lg:grid-cols-[minmax(0,1.55fr)_minmax(260px,0.9fr)_minmax(260px,0.75fr)]" aria-labelledby="hero-heading">
        <h2 id="hero-heading" class="sr-only">প্রধান সংবাদ</h2>

        <div class="min-w-0 bg-white">
            @if ($homeLead)
                @php($leadImage = $homeLead->featuredMedia?->url() ?? $homeLead->featured_image_url)
                <article>
                    <a href="{{ route('articles.show', $homeLead->slug) }}" class="block">
                        @if ($leadImage)
                            <img src="{{ $leadImage }}" alt="" loading="eager" width="760" height="428" class="aspect-[16/9] w-full object-cover">
                        @else
                            <div class="aspect-[16/9] w-full bg-neutral-200" aria-hidden="true"></div>
                        @endif
                    </a>
                    <div class="space-y-3 p-5">
                        @if ($homeLead->primaryCategory)
                            <p class="text-sm font-bold text-brand-red">{{ $homeLead->primaryCategory->name_bn }}</p>
                        @endif
                        <h3 class="text-2xl font-bold leading-tight text-brand-dark sm:text-4xl">
                            <a class="hover:text-brand-green" href="{{ route('articles.show', $homeLead->slug) }}">{{ $homeLead->headline_bn }}</a>
                        </h3>
                        @if ($homeLead->summary_bn)
                            <p class="leading-7 text-neutral-700">{{ $homeLead->summary_bn }}</p>
                        @endif
                        <time class="block text-sm text-neutral-500" datetime="{{ $homeLead->published_at?->toIso8601String() }}">{{ $homeLead->published_at?->diffForHumans() }}</time>
                    </div>
                </article>
            @else
                <div class="p-6">
                    <h2 class="text-3xl font-bold text-brand-dark">দৈনিক সময় বায়ান্ন</h2>
                    <p class="mt-3 leading-7 text-neutral-700">প্রকাশিত সংবাদ যুক্ত হলে এখানে প্রধান সংবাদ দেখা যাবে।</p>
                </div>
            @endif
        </div>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-1">
            @foreach ($homeSecondaryStories as $article)
                <x-public.article-card
                    :title="$article->headline_bn"
                    :summary="$article->summary_bn"
                    :url="route('articles.show', $article->slug)"
                    :image="$article->featuredMedia?->url() ?? $article->featured_image_url"
                    :category="$article->primaryCategory?->name_bn"
                    :published-at="$article->published_at"
                />
            @endforeach
        </div>

        <aside class="space-y-5" aria-label="সর্বাধিক পঠিত">
            <section class="bg-white p-4">
                <x-public.section-header title="সর্বাধিক পঠিত" />
                @php($mostReadStories = $mostReadArticles->isNotEmpty() ? $mostReadArticles : ($topStories->isNotEmpty() ? $topStories : $latestArticles))
                @if ($mostReadStories->isNotEmpty())
                    <ol class="space-y-4">
                        @foreach ($mostReadStories->take(5) as $article)
                            <li class="flex gap-3 border-b border-neutral-100 pb-3 last:border-b-0 last:pb-0">
                                <span class="text-lg font-bold text-brand-red">{{ $loop->iteration }}</span>
                                <a class="text-sm font-semibold leading-6 hover:text-brand-green" href="{{ route('articles.show', $article->slug) }}">{{ $article->headline_bn }}</a>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="text-sm leading-6 text-neutral-600">পাঠকপ্রিয় সংবাদ এখানে দেখানো হবে।</p>
                @endif
            </section>

            <x-public.ad-slot class="min-h-48" label="Advertisement" />
        </aside>
    </section>

    <section class="mt-8" aria-label="বিজ্ঞাপন">
        <x-public.ad-slot class="min-h-28" label="Advertisement" />
    </section>

    <section class="mt-8" aria-labelledby="category-highlights-heading">
        <x-public.section-header title="বিভাগীয় হাইলাইটস" />
        <h2 id="category-highlights-heading" class="sr-only">বিভাগীয় হাইলাইটস</h2>
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @if ($editorsChoice->isNotEmpty())
                <section class="bg-white p-4" aria-labelledby="highlight-editors-choice">
                    <h3 id="highlight-editors-choice" class="mb-3 text-xl font-bold text-brand-dark">সম্পাদকের পছন্দ</h3>
                    <ul class="space-y-3">
                        @foreach ($editorsChoice->take(3) as $article)
                            <li><a class="font-semibold leading-7 hover:text-brand-green" href="{{ route('articles.show', $article->slug) }}">{{ $article->headline_bn }}</a></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($specialReport->isNotEmpty())
                <section class="bg-white p-4" aria-labelledby="highlight-special-report">
                    <h3 id="highlight-special-report" class="mb-3 text-xl font-bold text-brand-dark">বিশেষ প্রতিবেদন</h3>
                    <ul class="space-y-3">
                        @foreach ($specialReport->take(3) as $article)
                            <li><a class="font-semibold leading-7 hover:text-brand-green" href="{{ route('articles.show', $article->slug) }}">{{ $article->headline_bn }}</a></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @foreach ($categorySections->take(6) as $section)
                <section class="bg-white p-4" aria-labelledby="highlight-{{ $section['key'] }}">
                    <h3 id="highlight-{{ $section['key'] }}" class="mb-3 text-xl font-bold text-brand-dark">{{ $section['title'] }}</h3>
                    @if ($section['articles']->isNotEmpty())
                        <ul class="space-y-3">
                            @foreach ($section['articles']->take(3) as $article)
                                <li>
                                    <a class="font-semibold leading-7 hover:text-brand-green" href="{{ route('articles.show', $article->slug) }}">{{ $article->headline_bn }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm leading-6 text-neutral-600">এই বিভাগে প্রকাশিত সংবাদ যুক্ত হলে এখানে দেখা যাবে।</p>
                    @endif
                </section>
            @endforeach
        </div>
    </section>

    <section class="mt-8" aria-labelledby="latest-news-heading">
        <x-public.section-header title="সর্বশেষ সংবাদ" />
        <h2 id="latest-news-heading" class="sr-only">সর্বশেষ সংবাদ</h2>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @forelse (($latestHighlight->isNotEmpty() ? $latestHighlight->merge($latestArticles)->unique('id')->values() : $latestArticles)->take(8) as $article)
                <x-public.article-card
                    :title="$article->headline_bn"
                    :summary="$article->summary_bn"
                    :url="route('articles.show', $article->slug)"
                    :image="$article->featuredMedia?->url() ?? $article->featured_image_url"
                    :category="$article->primaryCategory?->name_bn"
                    :published-at="$article->published_at"
                />
            @empty
                <div class="bg-white p-6 sm:col-span-2 lg:col-span-4">
                    <p class="leading-7 text-neutral-700">প্রকাশিত সংবাদ যুক্ত হলে এখানে সর্বশেষ কনটেন্ট দেখা যাবে।</p>
                </div>
            @endforelse
        </div>
    </section>

    @foreach ($categorySections as $section)
        <section class="mt-8" aria-labelledby="section-{{ $section['key'] }}">
            <x-public.section-header :title="$section['title']" />
            <h2 id="section-{{ $section['key'] }}" class="sr-only">{{ $section['title'] }}</h2>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @forelse ($section['articles'] as $article)
                    <x-public.article-card
                        :title="$article->headline_bn"
                        :summary="$article->summary_bn"
                        :url="route('articles.show', $article->slug)"
                        :image="$article->featuredMedia?->url() ?? $article->featured_image_url"
                        :category="$article->primaryCategory?->name_bn"
                        :published-at="$article->published_at"
                    />
                @empty
                    <div class="bg-white p-5 sm:col-span-2 lg:col-span-4">
                        <p class="text-sm leading-6 text-neutral-600">এই বিভাগে প্রকাশিত সংবাদ যুক্ত হলে এখানে দেখা যাবে।</p>
                    </div>
                @endforelse
            </div>
        </section>
    @endforeach

    <section class="mt-8 grid gap-6 lg:grid-cols-2">
        <div aria-labelledby="video-heading">
            <x-public.section-header title="ভিডিও" />
            <h2 id="video-heading" class="sr-only">ভিডিও</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                @forelse ($videos as $video)
                    <article class="bg-white p-4">
                        <div class="aspect-video bg-neutral-200" aria-hidden="true"></div>
                        <h3 class="mt-3 font-bold leading-7">{{ $video->title_bn }}</h3>
                    </article>
                @empty
                    <p class="bg-white p-5 text-sm leading-6 text-neutral-600 sm:col-span-2">প্রকাশিত ভিডিও এখানে দেখা যাবে।</p>
                @endforelse
            </div>
        </div>

        <div aria-labelledby="gallery-heading">
            <x-public.section-header title="ছবিঘর" />
            <h2 id="gallery-heading" class="sr-only">ছবিঘর</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                @forelse ($galleries as $gallery)
                    @php($coverImage = $gallery->coverImage?->url())
                    <article class="bg-white">
                        @if ($coverImage)
                            <img src="{{ $coverImage }}" alt="" loading="lazy" width="360" height="203" class="aspect-video w-full object-cover">
                        @else
                            <div class="aspect-video bg-neutral-200" aria-hidden="true"></div>
                        @endif
                        <h3 class="p-4 font-bold leading-7">{{ $gallery->title }}</h3>
                    </article>
                @empty
                    <p class="bg-white p-5 text-sm leading-6 text-neutral-600 sm:col-span-2">প্রকাশিত ছবিঘর এখানে দেখা যাবে।</p>
                @endforelse
            </div>
        </div>
    </section>
</x-public.layout>
