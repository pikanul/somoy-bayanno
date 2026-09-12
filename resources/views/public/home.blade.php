<x-public.layout
    :title="$seo['title']"
    :description="$seo['description']"
    :canonical="$seo['canonical']"
    :json-ld="$jsonLd"
>
    @php
        $asset = fn (string $name): string => asset("demo-home/{$name}.png");
        $articleUrl = fn (\App\Models\Article $article): string => route('articles.show', $article->slug);
        $articleImage = fn (\App\Models\Article $article, string $fallback): string => $article->featuredMedia?->url() ?? $article->featured_image_url ?? $asset($fallback);
        $fallbackNewsCards = [
            ['image' => 'martyrs', 'category' => 'জাতীয়', 'title' => 'রাষ্ট্র সংস্কারে সবাইকে ঐক্যবদ্ধ থাকার আহ্বান', 'slug' => 'national'],
            ['image' => 'parliament', 'category' => 'রাজনীতি', 'title' => 'নতুন রাজনৈতিক সমঝোতার সম্ভাবনা', 'slug' => 'politics'],
            ['image' => 'port', 'category' => 'অর্থনীতি', 'title' => 'রপ্তানি আয়ে নতুন রেকর্ড, বাড়ছে সম্ভাবনা', 'slug' => 'economy'],
            ['image' => 'football', 'category' => 'খেলা', 'title' => 'বিশ্বকাপ প্রস্তুতি: বাংলাদেশ প্রস্তুত', 'slug' => 'sports'],
            ['image' => 'ai', 'category' => 'প্রযুক্তি', 'title' => 'কৃত্রিম বুদ্ধিমত্তা বদলে দিচ্ছে আমাদের জীবন', 'slug' => 'technology'],
            ['image' => 'yoga', 'category' => 'জীবনযাপন', 'title' => 'মানসিক সুস্থতায় নিয়মিত ব্যায়ামের গুরুত্ব', 'slug' => 'lifestyle'],
        ];
        $fallbackSideStories = [
            ['image' => 'leader', 'category' => 'রাজনীতি', 'title' => 'সহযোগিতায় সরকারের অগ্রগতিতে প্রধান উপদেষ্টার বার্তা', 'slug' => 'politics'],
            ['image' => 'biden', 'category' => 'আন্তর্জাতিক', 'title' => 'ইউক্রেনকে আরও অস্ত্র সহায়তা দেবে যুক্তরাষ্ট্র', 'slug' => 'international'],
            ['image' => 'flood', 'category' => 'দেশ', 'title' => 'চট্টগ্রামে ভারী বৃষ্টিতে জলাবদ্ধতা, দুর্ভোগে মানুষ', 'slug' => 'national'],
            ['image' => 'cricket', 'category' => 'খেলা', 'title' => 'বিশ্বকাপকে সামনে রেখে বাংলাদেশের স্কোয়াড ঘোষণা', 'slug' => 'sports'],
        ];
        $fallbackVideos = [
            ['image' => 'padma', 'title' => 'পদ্মা সেতুর পরিবর্তন: বাংলাদেশের উন্নয়নের নতুন অধ্যায়', 'time' => '02:34', 'slug' => 'videos'],
            ['image' => 'rally', 'title' => 'বিশ্বকাপকে ঘিরে ক্রিকেট উন্মাদনা', 'time' => '03:15', 'slug' => 'videos'],
            ['image' => 'yunus-video', 'title' => 'সময় বায়ান্ন বিশেষ সাক্ষাৎকার: ড. মুহাম্মদ ইউনূস', 'time' => '04:22', 'slug' => 'videos'],
        ];
        $fallbackGalleries = [
            ['image' => 'cox', 'title' => 'কক্সবাজারের অপূর্ব সন্ধ্যা', 'slug' => 'photos'],
            ['image' => 'lilies', 'title' => 'শাপলার দেশে বাংলাদেশ', 'slug' => 'photos'],
            ['image' => 'dhaka', 'title' => 'ঢাকার পুরোনো শহরের রঙ', 'slug' => 'photos'],
        ];
        $mostRead = [
            ['title' => 'সংস্কৃতিতে নতুন সমীকরণ, আলোচনায় ঐক্যফ্রন্ট', 'readers' => '৪৫.৫K', 'slug' => 'opinion'],
            ['title' => 'ঢাকা আংশিকল থেকে নতুন ২টি মেট্রো স্টেশন চালু', 'readers' => '৩২.৯K', 'slug' => 'national'],
            ['title' => 'জলবায়ু সংকটে বৈশ্বিক সহযোগিতা জরুরি', 'readers' => '২৮.৭K', 'slug' => 'international'],
            ['title' => 'বিশ্বকাপে বাংলাদেশের স্কোয়াড ঘোষণা', 'readers' => '২৪.৪K', 'slug' => 'sports'],
            ['title' => 'স্মার্ট বাংলাদেশ গড়তে প্রযুক্তির ব্যবহার বাড়াতে হবে', 'readers' => '১৮.৫K', 'slug' => 'technology'],
        ];

        if ($leadArticle) {
            $leadImage = $articleImage($leadArticle, 'hero-metro');
            $leadUrl = $articleUrl($leadArticle);
            $leadCategory = $leadArticle->primaryCategory?->name_bn ?? 'জাতীয়';
            $leadTitle = $leadArticle->headline_bn;
            $leadSummary = $leadArticle->summary_bn;
            $leadDate = $leadArticle->published_at?->translatedFormat('d F Y, h:i A') ?? now('Asia/Dhaka')->translatedFormat('d F Y, h:i A');
        } else {
            $leadImage = $asset('hero-metro');
            $leadUrl = route('static.show', 'national');
            $leadCategory = 'জাতীয়';
            $leadTitle = 'রাজধানীতে মেট্রোরেলের যাত্রা আরও সহজ হচ্ছে, যুক্ত হচ্ছে নতুন ২ স্টেশন';
            $leadSummary = 'উত্তরা-মতিঝিল রুটে মেট্রোরেলের দুটি নতুন স্টেশন চালু হলে যাত্রীদের জন্য দৈনন্দিন যাত্রা আরও সহজ হবে বলে জানিয়েছে কর্তৃপক্ষ।';
            $leadDate = now('Asia/Dhaka')->translatedFormat('d F Y, h:i A');
        }

        $sideStories = $secondaryStories->take(4)->values();
        $newsArticles = $latestArticles->take(6)->values();
        $videoItems = $videos->take(3)->values();
        $galleryItems = $galleries->take(3)->values();
    @endphp

    <h1 class="sr-only">দৈনিক সময় বায়ান্ন - হোম পেজ</h1>

    <section class="grid gap-3 lg:grid-cols-[minmax(0,1.05fr)_250px_236px]" aria-label="প্রধান সংবাদ">
        <a href="{{ $leadUrl }}" class="group block overflow-hidden rounded border border-neutral-200 bg-white text-brand-dark">
            <div class="relative bg-white">
                <img src="{{ $leadImage }}" alt="" loading="eager" class="aspect-[16/10] w-full bg-white object-contain">
                <span class="absolute left-3 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white text-brand-dark ring-1 ring-neutral-200" aria-hidden="true">
                    ‹
                </span>
                <span class="absolute right-3 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white text-brand-dark ring-1 ring-neutral-200" aria-hidden="true">
                    ›
                </span>
            </div>
            <div class="p-4 sm:p-5">
                <span class="inline-flex rounded bg-brand-red px-3 py-1 text-sm font-bold text-white">{{ $leadCategory }}</span>
                <h2 class="mt-3 max-w-2xl text-3xl font-black leading-tight sm:text-[34px]">{{ $leadTitle }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-neutral-700">{{ $leadSummary }}</p>
                <div class="mt-3 flex items-center gap-6 text-xs text-neutral-500">
                    <span>◷ {{ $leadDate }}</span>
                    <span>◉ ৩.২K</span>
                </div>
            </div>
        </a>

        <div class="grid gap-3">
            @forelse ($sideStories as $story)
                <a href="{{ $articleUrl($story) }}" class="grid grid-cols-[96px_1fr] gap-3 rounded border border-neutral-200 bg-white p-2 shadow-sm hover:border-brand-red">
                    <img src="{{ $articleImage($story, $fallbackSideStories[$loop->index]['image'] ?? 'martyrs') }}" alt="" loading="lazy" class="h-24 w-24 rounded bg-neutral-100 object-contain">
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-brand-red">{{ $story->primaryCategory?->name_bn }}</p>
                        <h3 class="mt-1 text-[15px] font-bold leading-6 text-brand-dark">{{ $story->headline_bn }}</h3>
                        <p class="mt-1 text-xs text-neutral-500">◷ {{ $story->published_at?->format('d M Y') }}</p>
                    </div>
                </a>
            @empty
                @foreach ($fallbackSideStories as $story)
                    <a href="{{ route('static.show', $story['slug']) }}" class="grid grid-cols-[96px_1fr] gap-3 rounded border border-neutral-200 bg-white p-2 shadow-sm hover:border-brand-red">
                        <img src="{{ $asset($story['image']) }}" alt="" loading="lazy" class="h-24 w-24 rounded bg-neutral-100 object-contain">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-brand-red">{{ $story['category'] }}</p>
                            <h3 class="mt-1 text-[15px] font-bold leading-6 text-brand-dark">{{ $story['title'] }}</h3>
                            <p class="mt-1 text-xs text-neutral-500">◷ ১১ সেপ্টেম্বর ২০২৬</p>
                        </div>
                    </a>
                @endforeach
            @endforelse
        </div>

        <aside class="rounded border border-neutral-200 bg-white p-3 shadow-sm" aria-label="সর্বাধিক পঠিত">
            <div class="grid grid-cols-2 border-b border-neutral-200 text-center text-sm font-bold">
                <span class="border-b-2 border-brand-red py-3 text-brand-red">সর্বাধিক পঠিত</span>
                <span class="py-3 text-neutral-700">সর্বশেষ</span>
            </div>
            <ol class="divide-y divide-neutral-100">
                @foreach ($mostRead as $story)
                    <li class="grid grid-cols-[38px_1fr] gap-2 py-4">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-brand-red text-lg font-black text-white">{{ $loop->iteration }}</span>
                        <div>
                            <a href="{{ $story['url'] ?? route('static.show', $story['slug']) }}" class="text-sm font-bold leading-6 hover:text-brand-red">{{ $story['title'] }}</a>
                            <p class="mt-1 text-xs text-neutral-500">◷ {{ $story['readers'] }} পাঠক</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </aside>
    </section>

    <section class="mt-4 overflow-hidden rounded border border-neutral-200 bg-white">
        <img src="{{ $asset('ad-strip') }}" alt="একটি সবুজ, নিরাপদ ও সমৃদ্ধ বাংলাদেশ" class="h-[66px] w-full object-cover">
    </section>

    <section class="mt-7" aria-label="সংবাদ বিভাগ">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="border-l-4 border-brand-red pl-3 text-2xl font-black">সংবাদ বিভাগ</h2>
            <a href="{{ route('static.show', 'latest') }}" class="text-sm font-semibold hover:text-brand-red">সব দেখুন →</a>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                @forelse ($newsArticles as $card)
                <a href="{{ $articleUrl($card) }}" class="overflow-hidden rounded border border-neutral-200 bg-white shadow-sm hover:border-brand-red">
                    <img src="{{ $articleImage($card, $fallbackNewsCards[$loop->index]['image'] ?? 'martyrs') }}" alt="" loading="lazy" class="aspect-[16/10] w-full bg-neutral-100 object-contain">
                    <div class="p-3">
                        <p class="text-xs font-bold text-brand-red">{{ $card->primaryCategory?->name_bn }}</p>
                        <h3 class="mt-1 text-[15px] font-bold leading-6">{{ $card->headline_bn }}</h3>
                        <p class="mt-2 text-xs text-neutral-500">◷ {{ $card->published_at?->format('d M Y') }}</p>
                    </div>
                </a>
            @empty
                @foreach ($fallbackNewsCards as $card)
                    <a href="{{ route('static.show', $card['slug']) }}" class="overflow-hidden rounded border border-neutral-200 bg-white shadow-sm hover:border-brand-red">
                        <img src="{{ $asset($card['image']) }}" alt="" loading="lazy" class="aspect-[16/10] w-full bg-neutral-100 object-contain">
                        <div class="p-3">
                            <p class="text-xs font-bold text-brand-red">{{ $card['category'] }}</p>
                            <h3 class="mt-1 text-[15px] font-bold leading-6">{{ $card['title'] }}</h3>
                            <p class="mt-2 text-xs text-neutral-500">◷ ১১ সেপ্টেম্বর ২০২৬</p>
                        </div>
                    </a>
                @endforeach
            @endforelse
        </div>
    </section>

    <section class="mt-7 grid gap-6 lg:grid-cols-2">
        <div>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="border-l-4 border-brand-red pl-3 text-2xl font-black">ভিডিও</h2>
                <a href="{{ route('static.show', 'videos') }}" class="text-sm font-semibold hover:text-brand-red">সব দেখুন →</a>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                @forelse ($videoItems as $video)
                    <a href="{{ route('static.show', 'videos') }}" class="block">
                        <div class="relative overflow-hidden rounded">
                            <img src="{{ $video->thumbnail ?: $asset($fallbackVideos[$loop->index]['image'] ?? 'padma') }}" alt="" loading="lazy" class="aspect-video w-full bg-neutral-100 object-contain">
                            <span class="absolute left-1/2 top-1/2 grid h-10 w-10 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-brand-red">▶</span>
                            <span class="absolute bottom-2 right-2 rounded bg-black/75 px-2 py-1 text-xs font-bold text-white">{{ gmdate('i:s', (int) $video->duration) }}</span>
                        </div>
                        <h3 class="mt-2 text-sm font-bold leading-6">{{ $video->title_bn }}</h3>
                        <p class="mt-1 text-xs text-neutral-500">◷ ১.২K দেখেছে</p>
                    </a>
                @empty
                    @foreach ($fallbackVideos as $video)
                        <a href="{{ route('static.show', $video['slug']) }}" class="block">
                            <div class="relative overflow-hidden rounded">
                                <img src="{{ $asset($video['image']) }}" alt="" loading="lazy" class="aspect-video w-full bg-neutral-100 object-contain">
                                <span class="absolute left-1/2 top-1/2 grid h-10 w-10 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-brand-red">▶</span>
                                <span class="absolute bottom-2 right-2 rounded bg-black/75 px-2 py-1 text-xs font-bold text-white">{{ $video['time'] }}</span>
                            </div>
                            <h3 class="mt-2 text-sm font-bold leading-6">{{ $video['title'] }}</h3>
                            <p class="mt-1 text-xs text-neutral-500">◷ ১.২K দেখেছে</p>
                        </a>
                    @endforeach
                @endforelse
            </div>
        </div>

        <div>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="border-l-4 border-brand-red pl-3 text-2xl font-black">ছবিঘর</h2>
                <a href="{{ route('static.show', 'photos') }}" class="text-sm font-semibold hover:text-brand-red">সব দেখুন →</a>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                @forelse ($galleryItems as $gallery)
                    <a href="{{ route('static.show', 'photos') }}" class="block">
                        <img src="{{ $gallery->coverImage?->url() ?? $asset($fallbackGalleries[$loop->index]['image'] ?? 'cox') }}" alt="" loading="lazy" class="aspect-video w-full rounded bg-neutral-100 object-contain">
                        <h3 class="mt-2 text-sm font-bold leading-6">{{ $gallery->title }}</h3>
                    </a>
                @empty
                    @foreach ($fallbackGalleries as $gallery)
                        <a href="{{ route('static.show', $gallery['slug']) }}" class="block">
                            <img src="{{ $asset($gallery['image']) }}" alt="" loading="lazy" class="aspect-video w-full rounded bg-neutral-100 object-contain">
                            <h3 class="mt-2 text-sm font-bold leading-6">{{ $gallery['title'] }}</h3>
                        </a>
                    @endforeach
                @endforelse
            </div>
        </div>
    </section>
</x-public.layout>
