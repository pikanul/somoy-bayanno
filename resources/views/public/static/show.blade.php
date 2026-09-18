<x-public.layout
    :title="$seo['title']"
    :description="$seo['description']"
    :canonical="$seo['canonical']"
    :json-ld="$jsonLd"
>
    @php
        $asset = fn (string $name): string => asset("demo-home/{$name}.png");
        $articleImage = fn ($article, string $fallback): string => $article?->featuredMedia?->url() ?? $article?->featured_image_url ?? $asset($fallback);
        $leadArticle = $articles->first();
        $listArticles = $articles->skip(1)->take(5)->values();
        $popularArticles = $articles->take(5)->values();
        $topicTags = ['সরকার', 'নীতি', 'প্রশাসন', 'অর্থনীতি', 'শিক্ষা', 'স্বাস্থ্য', 'পরিবহন', 'নিরাপত্তা', 'উন্নয়ন', 'ত্রাণ', 'দুর্নীতি', 'জনজীবন'];
    @endphp

    @unless ($slug === 'live')
        <nav class="mb-3 text-sm text-neutral-600" aria-label="ব্রেডক্রাম্ব">
            <a href="{{ route('home') }}" class="hover:text-brand-red">হোম</a>
            <span class="mx-1">›</span>
            <span class="font-semibold text-brand-dark">{{ $title }}</span>
        </nav>
    @endunless

    @if ($slug === 'live')
        @php
            $playbackUrl = $liveStream?->playbackUrl();
            $isDirectVideo = $liveStream && in_array($liveStream->provider, ['hls', 'mp4'], true);
            $iframeUrl = null;

            if ($playbackUrl && ! $isDirectVideo) {
                $separator = str_contains($playbackUrl, '?') ? '&' : '?';
                $iframeUrl = $playbackUrl.$separator.http_build_query([
                    'autoplay' => $liveStream->autoplay ? 1 : 0,
                    'mute' => $liveStream->muted ? 1 : 0,
                    'controls' => 0,
                    'disablekb' => 1,
                    'fs' => 0,
                    'iv_load_policy' => 3,
                    'modestbranding' => 1,
                    'playsinline' => 1,
                    'rel' => 0,
                ]);
            }
        @endphp

        <section class="-mx-3 -my-4 overflow-hidden bg-white text-neutral-900 sm:-mx-4 sm:-my-5">
            <div class="relative aspect-video min-h-[360px] bg-black text-white">
                @if ($liveStream && $playbackUrl)
                    @if ($isDirectVideo)
                        <video class="absolute inset-0 h-full w-full object-cover" src="{{ $playbackUrl }}" poster="{{ $liveStream->poster_url }}" autoplay playsinline controls @if ($liveStream->muted) muted @endif></video>
                    @else
                        <iframe
                            src="{{ $iframeUrl }}"
                            title="{{ $liveStream->title_bn }}"
                            class="absolute left-0 -top-16 h-[calc(100%+128px)] w-full"
                            allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
                            allowfullscreen
                        ></iframe>
                    @endif
                @else
                    <div class="absolute inset-0 grid place-items-center bg-gradient-to-br from-[#0f1714] via-black to-[#067a3b] p-6 text-center">
                        <div>
                            <img src="{{ $asset('logo') }}" alt="দৈনিক সময় বায়ান্ন" class="mx-auto w-64">
                            <h1 class="mt-6 text-4xl font-black">লাইভ স্ট্রিম যুক্ত করুন</h1>
                            <p class="mt-3 text-white/70">Admin panel → Live Streams থেকে live video link add/edit করুন।</p>
                        </div>
                    </div>
                @endif

                <div class="absolute left-1/2 top-3 z-10 flex -translate-x-1/2 items-center gap-2 rounded-full border border-white/25 bg-black/25 px-3 py-1.5 text-xs text-white backdrop-blur">
                    <span class="flex h-7 w-28 items-center justify-center rounded bg-white/90 px-1">
                        <img src="{{ $asset('logo') }}" alt="দৈনিক সময় বায়ান্ন" class="max-h-full max-w-full object-contain">
                    </span>
                    <span id="live-clock" class="font-bold">--:--:--</span>
                    <span class="text-white/45">|</span>
                    <span id="live-date" class="whitespace-nowrap text-white/90">{{ now()->format('d M Y') }}</span>
                </div>
            </div>

            <div class="border-b border-neutral-200 bg-white px-4 py-4 sm:px-6">
                <div class="mx-auto max-w-7xl">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-2 rounded-full bg-brand-red px-3 py-1 text-xs font-black uppercase tracking-wide text-white">
                            <span class="h-2 w-2 rounded-full bg-white animate-pulse"></span>
                            {{ $liveStream?->status_text ?: 'LIVE' }}
                        </span>
                    </div>
                    <h1 class="mt-3 text-[18px] font-black leading-7">{{ $liveStream?->title_bn ?: 'দৈনিক সময় বায়ান্ন লাইভ' }}</h1>
                    @if ($liveStream?->description_bn)
                        <p class="mt-3 max-w-2xl leading-7 text-neutral-600">{{ $liveStream->description_bn }}</p>
                    @endif
                </div>
            </div>
        </section>

        <script>
            (() => {
                const clock = document.getElementById('live-clock');
                const date = document.getElementById('live-date');
                const render = () => {
                    const now = new Date();
                    if (clock) {
                        clock.textContent = now.toLocaleTimeString('bn-BD', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    }
                    if (date) {
                        date.textContent = now.toLocaleDateString('bn-BD', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                    }
                };

                render();
                window.setInterval(render, 1000);
            })();
        </script>
    @elseif ($slug === 'epaper')
        @php
            $dateLabel = fn ($date): string => $date ? $date->locale('bn')->translatedFormat('d F Y') : '';
            $selectedIndex = $selectedEpaper ? $epapers->search(fn ($epaper): bool => $epaper->is($selectedEpaper)) : false;
            $selectedPageIndex = collect($issuePages ?? [])->search(fn (array $page): bool => (int) $page['page_number'] === (int) $selectedPageNumber);
            $previousPage = $selectedPageIndex !== false ? collect($issuePages)->get($selectedPageIndex - 1) : null;
            $nextPage = $selectedPageIndex !== false ? collect($issuePages)->get($selectedPageIndex + 1) : null;
        @endphp

        <section class="epaper-reader-shell">
            <div class="epaper-reader-toolbar">
                <div class="flex min-w-0 flex-wrap items-center gap-3">
                    <a href="{{ route('home') }}" class="flex h-10 w-36 shrink-0 items-center justify-center sm:w-48" aria-label="দৈনিক সময় বায়ান্ন হোম">
                        <img src="{{ $asset('logo') }}" alt="দৈনিক সময় বায়ান্ন" class="max-h-full max-w-full object-contain">
                    </a>
                    <span class="hidden h-8 w-px bg-neutral-200 sm:block"></span>
                    <label class="sr-only" for="epaper-date">তারিখ নির্বাচন</label>
                    <select id="epaper-date" class="h-10 rounded border border-neutral-300 bg-white px-3 text-sm font-bold text-neutral-800 shadow-sm outline-none focus:border-brand-green" onchange="if (this.value) window.location.href = this.value">
                        @forelse ($epapers as $epaper)
                            <option value="{{ route('epaper.show', $epaper->issue_date?->format('Y-m-d')) }}" @selected($selectedEpaper?->is($epaper))>
                                {{ $dateLabel($epaper->issue_date) }}
                            </option>
                        @empty
                            <option value="">তারিখ নেই</option>
                        @endforelse
                    </select>
                    <label class="sr-only" for="epaper-edition">সংস্করণ নির্বাচন</label>
                    <select id="epaper-edition" class="h-10 rounded border border-neutral-300 bg-white px-3 text-sm font-bold text-neutral-800 shadow-sm outline-none focus:border-brand-green">
                        @foreach ($epapers->pluck('edition')->unique()->values() as $edition)
                            <option @selected($selectedEpaper?->edition === $edition)>{{ $edition }} সংস্করণ</option>
                        @endforeach
                    </select>
                    @if ($selectedEpaper && count($issuePages) > 1)
                        <label class="sr-only" for="epaper-page">পৃষ্ঠা নির্বাচন</label>
                        <select id="epaper-page" class="h-10 rounded border border-neutral-300 bg-white px-3 text-sm font-bold text-neutral-800 shadow-sm outline-none focus:border-brand-green" onchange="if (this.value) window.location.href = this.value">
                            @foreach ($issuePages as $page)
                                <option value="{{ route('epaper.show', [$selectedEpaper->issue_date?->format('Y-m-d'), $page['page_number']]) }}" @selected((int) $selectedPageNumber === (int) $page['page_number'])>
                                    পৃষ্ঠা {{ $page['page_number'] }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    @if ($selectedPage['pdf_url'] ?? null)
                        <a href="{{ $selectedPage['pdf_url'] }}" target="_blank" rel="noopener noreferrer" class="hidden min-h-10 items-center rounded bg-brand-red px-4 py-2 text-sm font-black text-white hover:bg-red-700 sm:inline-flex">PDF</a>
                    @endif
                    @if ($selectedPage['external_preview_url'] ?? null)
                        <a href="{{ $selectedPage['external_preview_url'] }}" target="_blank" rel="noopener noreferrer" class="hidden min-h-10 items-center rounded bg-brand-green px-4 py-2 text-sm font-black text-white hover:bg-green-800 sm:inline-flex">লিংক</a>
                    @endif
                    <a href="{{ route('static.show', 'subscribe') }}" class="inline-flex min-h-10 items-center rounded bg-brand-red px-4 py-2 text-sm font-black text-white shadow-md shadow-red-700/20 hover:bg-red-700">Subscribe Now</a>
                </div>
            </div>

            <div class="epaper-reader-stage">
                @if ($selectedEpaper)
                        @if ($selectedPage['scan_url'] ?? null)
                            <img src="{{ $selectedPage['scan_url'] }}" alt="{{ $selectedEpaper->title_bn }} - পৃষ্ঠা {{ $selectedPageNumber }}" class="epaper-reader-image">
                        @elseif ($selectedPage['external_image_url'] ?? null)
                            <img src="{{ $selectedPage['external_image_url'] }}" alt="{{ $selectedEpaper->title_bn }} - পৃষ্ঠা {{ $selectedPageNumber }}" class="epaper-reader-image" onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
                            @if ($selectedPage['external_preview_url'] ?? null)
                                <iframe hidden src="{{ $selectedPage['external_preview_url'] }}" title="{{ $selectedEpaper->title_bn }} - পৃষ্ঠা {{ $selectedPageNumber }}" class="epaper-reader-frame" allow="autoplay"></iframe>
                            @endif
                        @elseif ($selectedPage['external_preview_url'] ?? null)
                            <iframe src="{{ $selectedPage['external_preview_url'] }}" title="{{ $selectedEpaper->title_bn }} - পৃষ্ঠা {{ $selectedPageNumber }}" class="epaper-reader-frame" allow="autoplay"></iframe>
                        @else
                            <div class="grid min-h-[70vh] place-items-center bg-white p-6 text-center">
                                <div>
                                    <p class="text-xl font-black">স্ক্যান করা পেজ এখনো আপলোড করা হয়নি</p>
                                    <p class="mt-2 text-neutral-600">Admin → E-Papers থেকে image/PDF/link যোগ করুন।</p>
                                </div>
                            </div>
                        @endif

                    @if ($previousPage)
                        <a href="{{ route('epaper.show', [$selectedEpaper->issue_date?->format('Y-m-d'), $previousPage['page_number']]) }}" class="epaper-page-turn epaper-page-turn-prev" aria-label="আগের পৃষ্ঠা">‹</a>
                    @else
                        <span class="epaper-page-turn epaper-page-turn-prev opacity-30" aria-hidden="true">‹</span>
                    @endif

                    @if ($nextPage)
                        <a href="{{ route('epaper.show', [$selectedEpaper->issue_date?->format('Y-m-d'), $nextPage['page_number']]) }}" class="epaper-page-turn epaper-page-turn-next" aria-label="পরের পৃষ্ঠা">›</a>
                    @else
                        <span class="epaper-page-turn epaper-page-turn-next opacity-30" aria-hidden="true">›</span>
                    @endif
                @else
                    <div class="mx-auto mt-8 max-w-xl rounded border border-dashed border-neutral-300 bg-white p-8 text-center text-neutral-600">
                        <p class="text-xl font-black text-brand-dark">কোনো ই-পেপার পাওয়া যায়নি</p>
                        <p class="mt-2">Admin → E-Papers থেকে তারিখ অনুযায়ী scan/PDF/link যোগ করলে এখানে দেখা যাবে।</p>
                    </div>
                @endif
            </div>
        </section>
    @elseif ($slug === 'correspondents')
        @php
            $photoUrl = fn ($member): string => $member->photo ? asset('storage/'.$member->photo) : $asset('leader');
            $topMembers = $correspondents->where('organization_level', 1)->values();
            $secondMembers = $correspondents->where('organization_level', 2)->values();
            $teamMembers = $correspondents->where('organization_level', '>=', 3)->values();
        @endphp

        <section class="overflow-hidden rounded border border-neutral-200 bg-[#0f1714] text-white shadow-sm">
            <div class="relative p-6 sm:p-10">
                <img src="{{ $asset('dhaka') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-20">
                <div class="absolute inset-0 bg-gradient-to-r from-[#0f1714] via-[#0f1714]/90 to-[#067a3b]/70"></div>
                <div class="relative max-w-3xl">
                    <p class="text-sm font-bold text-red-300">দৈনিক সময় বায়ান্ন টিম</p>
                    <h1 class="mt-2 text-4xl font-black sm:text-5xl">{{ $title }}</h1>
                    <p class="mt-4 text-lg leading-8 text-white/80">দেশজুড়ে আমাদের প্রতিনিধি, প্রতিবেদক ও ছবিসাংবাদিকদের পরিচিতি, কাজের ক্ষেত্র ও যোগাযোগের তথ্য।</p>
                </div>
            </div>
        </section>

        <section class="mt-8">
            <div class="mb-4 flex items-center justify-center gap-3">
                <span class="h-px w-16 bg-brand-red"></span>
                <h2 class="text-center text-2xl font-black">প্রধান নেতৃত্ব</h2>
                <span class="h-px w-16 bg-brand-red"></span>
            </div>

            <div class="mx-auto grid max-w-4xl gap-5">
                @forelse ($topMembers as $member)
                    <article class="group grid overflow-hidden rounded border border-neutral-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-brand-red hover:shadow-xl md:grid-cols-[320px_1fr]">
                        <div class="relative overflow-hidden">
                            <img src="{{ $photoUrl($member) }}" alt="{{ $member->name_bn }}" loading="lazy" class="h-full min-h-[360px] w-full bg-neutral-100 object-contain transition duration-500 group-hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                        </div>
                        <div class="flex flex-col justify-center p-6">
                            <p class="text-sm font-bold text-brand-red">Top Management</p>
                            <h2 class="mt-2 text-3xl font-black">{{ $member->name_bn }}</h2>
                            <p class="mt-1 text-lg font-bold text-brand-green">{{ $member->designation }}</p>
                            <p class="mt-4 leading-7 text-neutral-700">{{ $member->bio_bn }}</p>
                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                <p class="rounded bg-brand-light p-3 text-sm"><span class="block text-xs text-neutral-500">English Name</span><span class="font-bold">{{ $member->name_en }}</span></p>
                                <p class="rounded bg-brand-light p-3 text-sm"><span class="block text-xs text-neutral-500">Address</span><span class="font-bold">{{ $member->address ?: 'যোগ করা হয়নি' }}</span></p>
                                <a href="mailto:{{ $member->email }}" class="rounded bg-brand-light p-3 text-sm hover:text-brand-red"><span class="block text-xs text-neutral-500">Email</span><span class="font-bold">{{ $member->email ?: 'যোগ করা হয়নি' }}</span></a>
                                <a href="tel:{{ $member->phone }}" class="rounded bg-brand-light p-3 text-sm hover:text-brand-red"><span class="block text-xs text-neutral-500">Phone</span><span class="font-bold">{{ $member->phone ?: 'যোগ করা হয়নি' }}</span></a>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="rounded border border-dashed border-neutral-300 bg-white p-6 text-center text-neutral-600">প্রধান ব্যক্তির তথ্য admin panel থেকে যুক্ত করুন।</p>
                @endforelse
            </div>
        </section>

        <section class="mt-8">
            <div class="mb-4 flex items-center justify-center gap-3">
                <span class="h-px w-16 bg-brand-green"></span>
                <h2 class="text-center text-2xl font-black">দ্বিতীয় সারির নেতৃত্ব</h2>
                <span class="h-px w-16 bg-brand-green"></span>
            </div>

            <div class="mx-auto grid max-w-3xl gap-4 sm:grid-cols-2">
                @forelse ($secondMembers as $member)
                    <article class="group overflow-hidden rounded border border-neutral-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-brand-red hover:shadow-xl">
                        <div class="relative overflow-hidden">
                            <img src="{{ $photoUrl($member) }}" alt="{{ $member->name_bn }}" loading="lazy" class="aspect-[4/3] w-full bg-neutral-100 object-contain transition duration-500 group-hover:scale-110">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-transparent to-transparent"></div>
                            <span class="absolute left-3 top-3 rounded bg-brand-red px-3 py-1 text-xs font-bold text-white">Second in Command</span>
                            <div class="absolute bottom-3 left-3 right-3 text-white">
                                <h3 class="text-2xl font-black">{{ $member->name_bn }}</h3>
                                <p class="text-sm text-white/85">{{ $member->designation }}</p>
                            </div>
                        </div>
                        <div class="space-y-3 p-4">
                            <p class="text-sm leading-6 text-neutral-700">{{ $member->bio_bn }}</p>
                            <div class="rounded border border-neutral-200 bg-brand-light p-3 text-sm">
                                <p class="font-bold">{{ $member->address ?: 'ঠিকানা যোগ করা হয়নি' }}</p>
                                <a class="mt-1 block text-brand-green hover:text-brand-red" href="mailto:{{ $member->email }}">{{ $member->email ?: 'ইমেইল যোগ করা হয়নি' }}</a>
                                <a class="mt-1 block text-neutral-700 hover:text-brand-red" href="tel:{{ $member->phone }}">{{ $member->phone ?: 'ফোন যোগ করা হয়নি' }}</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="rounded border border-dashed border-neutral-300 bg-white p-6 text-center text-neutral-600 sm:col-span-2">দ্বিতীয় সারির নেতৃত্ব admin panel থেকে যুক্ত করুন।</p>
                @endforelse
            </div>
        </section>

        <section class="mt-8">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="border-l-4 border-brand-red pl-3 text-2xl font-black">প্রতিনিধি ও টিম</h2>
                <span class="text-sm font-semibold text-neutral-500">Admin → Authors থেকে edit/delete করা যাবে</span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($teamMembers as $member)
                    <article class="group overflow-hidden rounded border border-neutral-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-brand-red hover:shadow-xl">
                    <div class="relative overflow-hidden">
                        <img src="{{ $photoUrl($member) }}" alt="{{ $member->name_bn }}" loading="lazy" class="aspect-[4/3] w-full bg-neutral-100 object-contain transition duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-transparent to-transparent opacity-90"></div>
                        <span class="absolute left-3 top-3 rounded bg-brand-red px-3 py-1 text-xs font-bold text-white">{{ $member->organization_level === 4 ? 'Blank Slot' : 'Team' }}</span>
                        <div class="absolute bottom-3 left-3 right-3 text-white">
                            <h3 class="text-xl font-black">{{ $member->name_bn }}</h3>
                            <p class="text-sm text-white/85">{{ $member->designation }}</p>
                        </div>
                    </div>
                    <div class="space-y-3 p-4">
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <p class="rounded bg-neutral-100 p-2"><span class="block text-xs text-neutral-500">কর্মস্থল</span><span class="font-bold">{{ $member->address ?: 'খালি' }}</span></p>
                            <p class="rounded bg-neutral-100 p-2"><span class="block text-xs text-neutral-500">স্তর</span><span class="font-bold">Level {{ $member->organization_level }}</span></p>
                        </div>
                        <p class="text-sm leading-6 text-neutral-700">{{ $member->bio_bn }}</p>
                        <div class="rounded border border-neutral-200 bg-brand-light p-3 text-sm">
                            <a class="block font-semibold text-brand-green hover:text-brand-red" href="mailto:{{ $member->email }}">{{ $member->email ?: 'ইমেইল যোগ করা হয়নি' }}</a>
                            <a class="mt-1 block font-semibold text-neutral-700 hover:text-brand-red" href="tel:{{ $member->phone }}">{{ $member->phone ?: 'ফোন যোগ করা হয়নি' }}</a>
                        </div>
                    </div>
                </article>
                @empty
                    <p class="rounded border border-dashed border-neutral-300 bg-white p-6 text-center text-neutral-600 sm:col-span-2 xl:col-span-3">প্রতিনিধি তালিকা admin panel থেকে যুক্ত করুন।</p>
                @endforelse
            </div>
        </section>
    @elseif ($slug === 'videos')
        <section class="mb-5 overflow-hidden rounded border border-neutral-200 bg-white shadow-sm">
            <div class="relative min-h-[116px] bg-brand-light p-6">
                <img src="{{ $asset('padma') }}" alt="" class="absolute inset-y-0 right-0 hidden h-full w-1/2 object-cover opacity-40 sm:block">
                <div class="relative">
                    <h1 class="border-l-[6px] border-brand-red pl-4 text-4xl font-black">{{ $title }}</h1>
                    <p class="mt-2 text-neutral-700">প্রকাশিত ভিডিও সংবাদ ও বিশেষ আয়োজন</p>
                </div>
            </div>
        </section>

        <section class="mt-7">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($videos as $video)
                    <article class="overflow-hidden rounded border border-neutral-200 bg-white shadow-sm">
                        <a href="{{ $video->video_url }}" target="_blank" rel="noopener noreferrer" class="group block">
                            <div class="relative">
                                <img src="{{ $video->thumbnail ?: asset('demo-home/padma.png') }}" alt="" loading="lazy" class="aspect-video w-full bg-neutral-100 object-contain">
                                <span class="absolute left-1/2 top-1/2 grid h-12 w-12 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-brand-red shadow">▶</span>
                            </div>
                            <div class="p-4">
                                <h2 class="text-lg font-black leading-7 group-hover:text-brand-red">{{ $video->title_bn }}</h2>
                                <p class="mt-2 text-sm leading-6 text-neutral-600">{{ $video->description_bn }}</p>
                                <p class="mt-3 text-sm font-bold text-brand-red">ভিডিও দেখুন →</p>
                            </div>
                        </a>
                    </article>
                @empty
                    <p class="rounded border border-neutral-200 bg-white p-5 text-neutral-600">এখনও কোনো ভিডিও প্রকাশিত হয়নি।</p>
                @endforelse
            </div>
        </section>
    @elseif ($slug === 'photos')
        <section class="mb-5 overflow-hidden rounded border border-neutral-200 bg-white shadow-sm">
            <div class="relative min-h-[116px] bg-brand-light p-6">
                <img src="{{ $asset('cox') }}" alt="" class="absolute inset-y-0 right-0 hidden h-full w-1/2 object-cover opacity-40 sm:block">
                <div class="relative">
                    <h1 class="border-l-[6px] border-brand-red pl-4 text-4xl font-black">{{ $title }}</h1>
                    <p class="mt-2 text-neutral-700">দেশের গল্প, ছবি ও দৃশ্যপট</p>
                </div>
            </div>
        </section>

        <section class="mt-7">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($galleries as $gallery)
                    <article class="overflow-hidden rounded border border-neutral-200 bg-white shadow-sm">
                        <img src="{{ $gallery->coverImage?->url() ?? asset('demo-home/cox.png') }}" alt="" loading="lazy" class="aspect-video w-full bg-neutral-100 object-contain">
                        <div class="p-4">
                            <h2 class="text-lg font-black leading-7">{{ $gallery->title }}</h2>
                            <p class="mt-2 text-sm leading-6 text-neutral-600">{{ $gallery->description }}</p>
                        </div>
                    </article>
                @empty
                    <p class="rounded border border-neutral-200 bg-white p-5 text-neutral-600">এখনও কোনো ছবিঘর প্রকাশিত হয়নি।</p>
                @endforelse
            </div>
        </section>
    @else
        <section class="overflow-hidden rounded border border-neutral-200 bg-white shadow-sm">
            <div class="relative min-h-[116px] bg-brand-light p-6">
                <img src="{{ $asset('parliament') }}" alt="" class="absolute inset-y-0 right-0 hidden h-full w-1/2 object-cover opacity-45 sm:block">
                <div class="relative">
                    <h1 class="border-l-[6px] border-brand-red pl-4 text-4xl font-black">{{ $title }}</h1>
                    <p class="mt-2 text-neutral-700">দেশের সর্বশেষ {{ $title }} খবর, নীতি, প্রশাসন ও জনজীবনের গুরুত্বপূর্ণ সংবাদ</p>
                </div>
            </div>
        </section>

        <section class="mt-4 grid gap-6 lg:grid-cols-[minmax(0,1fr)_290px]">
            <div>
                @if ($leadArticle)
                    <article class="grid gap-4 border-b border-neutral-200 pb-5 md:grid-cols-[47%_1fr]">
                        <a href="{{ route('articles.show', $leadArticle->slug) }}" class="block overflow-hidden rounded">
                            <img src="{{ $articleImage($leadArticle, 'parliament') }}" alt="" loading="eager" class="aspect-[16/10] w-full bg-neutral-100 object-contain">
                        </a>
                        <div>
                            <p class="text-sm font-bold text-brand-red">{{ $leadArticle->primaryCategory?->name_bn ?? $title }}</p>
                            <a href="{{ route('articles.show', $leadArticle->slug) }}" class="group">
                                <h2 class="mt-2 text-2xl font-black leading-tight group-hover:text-brand-red sm:text-3xl">{{ $leadArticle->headline_bn }}</h2>
                            </a>
                            <p class="mt-3 leading-7 text-neutral-700">{{ $leadArticle->summary_bn }}</p>
                            <div class="mt-5 flex flex-wrap items-center gap-6 text-sm text-neutral-500">
                                <span>◷ {{ $leadArticle->published_at?->format('d M Y, H:i') }}</span>
                                <span>◉ ২২.৪কে</span>
                            </div>
                        </div>
                    </article>
                @endif

                <div class="divide-y divide-neutral-200">
                    @forelse ($listArticles as $article)
                        <article class="grid gap-4 py-5 sm:grid-cols-[220px_1fr]">
                            <a href="{{ route('articles.show', $article->slug) }}" class="block overflow-hidden rounded">
                                <img src="{{ $articleImage($article, 'martyrs') }}" alt="" loading="lazy" class="aspect-[16/10] w-full bg-neutral-100 object-contain">
                            </a>
                            <div>
                                <p class="text-sm font-bold text-brand-red">{{ $article->primaryCategory?->name_bn ?? $title }}</p>
                                <a href="{{ route('articles.show', $article->slug) }}" class="group">
                                    <h2 class="mt-1 text-xl font-black leading-8 group-hover:text-brand-red">{{ $article->headline_bn }}</h2>
                                </a>
                                <p class="mt-2 leading-7 text-neutral-700">{{ $article->summary_bn }}</p>
                                <div class="mt-3 flex flex-wrap items-center gap-6 text-sm text-neutral-500">
                                    <span>◷ {{ $article->published_at?->format('d M Y, H:i') }}</span>
                                    <span>◉ ৮.৫কে</span>
                                </div>
                            </div>
                        </article>
                    @empty
                        <p class="rounded border border-neutral-200 bg-white p-5 text-neutral-600">এই বিভাগে এখনও কোনো সংবাদ প্রকাশিত হয়নি।</p>
                    @endforelse
                </div>

                <nav class="mt-5 flex items-center justify-center gap-2" aria-label="পাতা নির্বাচন">
                    @foreach ([1, 2, 3, 4, 5] as $page)
                        <a href="{{ route('static.show', $slug) }}" @class([
                            'grid h-10 w-10 place-items-center rounded border text-sm font-bold',
                            'border-brand-red bg-brand-red text-white' => $page === 1,
                            'border-neutral-300 bg-white hover:border-brand-red' => $page !== 1,
                        ])>{{ $page }}</a>
                    @endforeach
                    <span class="px-2 text-neutral-500">...</span>
                    <a href="{{ route('static.show', $slug) }}" class="grid h-10 w-10 place-items-center rounded border border-neutral-300 bg-white text-sm font-bold hover:border-brand-red">24</a>
                    <a href="{{ route('static.show', $slug) }}" class="grid h-10 w-10 place-items-center rounded border border-neutral-300 bg-white text-sm font-bold hover:border-brand-red">›</a>
                </nav>
            </div>

            <aside class="space-y-6">
                <section class="rounded border border-neutral-200 bg-white shadow-sm">
                    <div class="grid grid-cols-2 border-b border-neutral-200 text-center text-sm font-bold">
                        <span class="border-b-2 border-brand-red py-3 text-brand-red">সর্বাধিক পঠিত</span>
                        <span class="py-3 text-neutral-700">সর্বশেষ</span>
                    </div>
                    <ol class="divide-y divide-neutral-100 p-3">
                        @foreach ($popularArticles as $article)
                            <li class="grid grid-cols-[42px_1fr] gap-3 py-4">
                                <span class="grid h-9 w-9 place-items-center rounded-full bg-brand-red text-lg font-black text-white">{{ $loop->iteration }}</span>
                                <div>
                                    <a href="{{ route('articles.show', $article->slug) }}" class="text-sm font-bold leading-6 hover:text-brand-red">{{ $article->headline_bn }}</a>
                                    <p class="mt-1 text-xs text-neutral-500">◷ {{ number_format(45 - ($loop->index * 6), 1) }}কে পঠিত</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>

                <a href="{{ route('static.show', 'advertise') }}" class="block overflow-hidden rounded border border-neutral-200 bg-white shadow-sm">
                    <img src="{{ $asset('ad-strip') }}" alt="বিজ্ঞাপন দিন" class="h-[250px] w-full object-cover">
                </a>

                <section>
                    <h2 class="border-l-4 border-brand-red pl-3 text-2xl font-black">সম্পর্কিত বিষয়</h2>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($topicTags as $tag)
                            <a href="{{ route('static.show', $slug) }}" class="rounded-lg bg-neutral-100 px-4 py-2 text-sm font-semibold text-neutral-700 hover:bg-brand-red hover:text-white">{{ $tag }}</a>
                        @endforeach
                    </div>
                </section>
            </aside>
        </section>
    @endif
</x-public.layout>
