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
    @elseif ($slug === 'career')
        @php
            $vacancyValue = fn ($vacancy, string $key) => $vacancy instanceof \App\Models\CareerVacancy ? $vacancy->{$key} : ($vacancy[$key] ?? null);
            $fieldClass = 'mt-1.5 min-h-12 w-full rounded-md border border-neutral-200 bg-white px-3.5 text-[15px] text-brand-dark shadow-sm outline-none transition placeholder:text-neutral-400 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20';
            $errorClass = 'mt-1.5 text-xs font-semibold leading-5 text-brand-red';
        @endphp

        <section class="overflow-hidden rounded-md border border-neutral-200 bg-white shadow-sm">
            <div class="relative grid min-h-[230px] items-center overflow-hidden bg-[linear-gradient(105deg,#ffffff_0%,#edf6f1_45%,rgba(6,122,59,.18)_100%)] px-5 py-9 sm:px-8 lg:grid-cols-[1fr_430px]">
                <div class="relative z-10">
                    <h1 class="text-[2.35rem] font-black leading-tight text-brand-green sm:text-5xl">ক্যারিয়ার</h1>
                    <span class="mt-3 block h-1 w-20 rounded-full bg-brand-red"></span>
                    <p class="mt-4 max-w-2xl text-lg font-semibold leading-8 text-neutral-800">সত্য, নিরপেক্ষতা ও মানুষের পাশে থাকার অঙ্গীকারে আমরা গড়ে তুলছি একটি শক্তিশালী টিম।</p>
                </div>
                <div class="relative hidden h-full min-h-[190px] lg:block">
                    <img src="{{ $asset('hero-metro') }}" alt="" class="absolute inset-0 h-full w-full rounded-md object-cover opacity-80">
                    <div class="absolute inset-0 rounded-md bg-gradient-to-l from-brand-green/10 via-white/20 to-transparent"></div>
                </div>
            </div>
        </section>

        <section class="mt-7 grid gap-6 lg:grid-cols-[1.55fr_0.75fr]">
            <div class="rounded-md border border-neutral-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200 bg-brand-green/10 px-5 py-4">
                    <h2 class="flex items-center gap-3 text-2xl font-black text-brand-green"><span class="grid h-9 w-9 place-items-center rounded-full bg-brand-green text-white">♙</span> বর্তমান নিয়োগ বিজ্ঞপ্তি</h2>
                    <a href="#career-apply" class="text-sm font-black text-brand-green hover:text-brand-red">সকল চাকরি দেখুন →</a>
                </div>
                <div class="divide-y divide-neutral-200 px-5">
                    @forelse ($careerVacancies as $vacancy)
                        @php
                            $vacancySlug = $vacancyValue($vacancy, 'slug');
                            $vacancyTitle = $vacancyValue($vacancy, 'title');
                            $vacancyDeadline = $vacancyValue($vacancy, 'application_deadline');
                            $vacancyId = $vacancy instanceof \App\Models\CareerVacancy ? $vacancy->id : null;
                        @endphp
                        <article class="grid gap-4 py-5 md:grid-cols-[64px_1fr_auto] md:items-center">
                            <span class="grid h-12 w-12 place-items-center rounded-full bg-brand-green text-xl text-white">▣</span>
                            <div>
                                <h3 class="text-xl font-black text-brand-green">{{ $vacancyTitle }}</h3>
                                <p class="mt-1 text-sm font-semibold text-neutral-600">{{ $vacancyValue($vacancy, 'department') }} · {{ $vacancyValue($vacancy, 'employment_type') }}</p>
                                <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm font-semibold text-neutral-600">
                                    <span>⌖ {{ $vacancyValue($vacancy, 'location') }}</span>
                                    <span>▣ আবেদনের শেষ তারিখ: {{ $vacancyDeadline?->format('d M Y') }}</span>
                                </div>
                                <p class="mt-3 max-w-2xl leading-7 text-neutral-700">{{ $vacancyValue($vacancy, 'summary') }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2 md:flex-col">
                                <a href="{{ route('career.vacancies.show', $vacancySlug) }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-brand-green px-4 py-2 text-sm font-black text-white shadow-sm hover:bg-green-800">বিজ্ঞপ্তি দেখুন →</a>
                                <a href="#career-apply" data-position="{{ $vacancyTitle }}" data-vacancy-id="{{ $vacancyId }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-brand-green px-4 py-2 text-sm font-black text-brand-green hover:border-brand-red hover:text-brand-red">আবেদন করুন</a>
                            </div>
                        </article>
                    @empty
                        <div class="p-6 text-center text-neutral-600">এই মুহূর্তে কোনো সক্রিয় নিয়োগ বিজ্ঞপ্তি নেই।</div>
                    @endforelse
                </div>
            </div>

            <aside class="space-y-4">
                <section class="rounded-md border border-neutral-200 bg-white shadow-sm">
                    <h2 class="border-b border-neutral-200 bg-brand-green/10 px-5 py-4 text-2xl font-black text-brand-green">কেন সময় বায়ান্ন?</h2>
                    <div class="divide-y divide-neutral-200 px-5">
                        @foreach ($careerBenefits as $benefit)
                            <article class="flex gap-3 py-4">
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-green text-lg text-white">{{ $benefit['icon'] }}</span>
                                <div>
                                    <h3 class="font-black text-brand-dark">{{ $benefit['title'] }}</h3>
                                    <p class="mt-1 text-sm leading-6 text-neutral-600">{{ $benefit['text'] }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
                <section class="rounded-md border border-brand-green/20 bg-brand-green/10 p-5 shadow-sm">
                    <p class="text-5xl font-black leading-none text-brand-green">“</p>
                    <p class="text-xl font-black leading-8 text-brand-green">সাংবাদিকতা শুধু পেশা নয়, এটি সমাজের প্রতি দায়বদ্ধতা।</p>
                    <p class="mt-3 font-black text-brand-dark">দৈনিক সময় বায়ান্ন</p>
                    <span class="mt-3 block h-1 w-16 rounded-full bg-brand-red"></span>
                </section>
            </aside>
        </section>

        <section class="mt-7 rounded-md border border-neutral-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="flex items-center gap-3 text-2xl font-black text-brand-green"><span class="grid h-9 w-9 place-items-center rounded-full bg-brand-green text-white">▣</span> আবেদনের প্রক্রিয়া</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-4">
                @foreach ($careerSteps as $step)
                    <article class="relative rounded-md border border-neutral-200 bg-white p-4 shadow-sm">
                        <span class="grid h-10 w-10 place-items-center rounded-full bg-brand-green text-lg font-black text-white">{{ $step['number'] }}</span>
                        <span class="mt-4 block text-3xl text-brand-green">{{ $step['icon'] }}</span>
                        <h3 class="mt-3 font-black text-brand-dark">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-neutral-600">{{ $step['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section id="career-apply" class="mt-7 grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <div class="overflow-hidden rounded-md border border-neutral-200 bg-brand-green text-white shadow-sm">
                <div class="relative min-h-full p-6">
                    <img src="{{ $asset('padma') }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover opacity-20">
                    <div class="relative">
                        <h2 class="text-3xl font-black">আমাদের টিমে যোগ দিন</h2>
                        <p class="mt-3 text-lg font-semibold leading-8 text-white/90">আপনার দক্ষতা ও অভিজ্ঞতা দিয়ে গড়ে তুলুন একটি সত্য, নিরপেক্ষ ও মানবিক সংবাদমাধ্যম।</p>
                        <a href="#career-application-form" class="mt-6 inline-flex min-h-12 items-center rounded-md bg-brand-red px-5 py-3 text-lg font-black text-white shadow-sm hover:bg-red-700">এখনই আবেদন করুন →</a>
                    </div>
                </div>
            </div>

            <section class="overflow-hidden rounded-md border border-neutral-200 bg-white shadow-sm">
                <h2 class="bg-brand-green px-5 py-4 text-2xl font-black text-white">অনলাইনে আবেদন করুন</h2>
                @if (session('career_status'))
                    <div class="mx-5 mt-5 rounded-md border border-brand-green/30 bg-brand-green/10 p-4 text-sm font-bold leading-6 text-brand-green">
                        {{ session('career_status') }}
                    </div>
                @endif
                <form id="career-application-form" method="POST" action="{{ route('static.career.apply', 'career') }}" enctype="multipart/form-data" class="grid gap-x-5 gap-y-4 p-5 md:grid-cols-2">
                    @csrf
                    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                    <input type="hidden" name="career_vacancy_id" value="{{ old('career_vacancy_id') }}">
                    <div>
                        <label for="career-full-name" class="text-sm font-bold">Full Name <span class="text-brand-red">*</span></label>
                        <input id="career-full-name" name="full_name" value="{{ old('full_name') }}" required maxlength="120" autocomplete="name" class="{{ $fieldClass }}">
                        @error('full_name') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="career-email" class="text-sm font-bold">Email <span class="text-brand-red">*</span></label>
                        <input id="career-email" type="email" name="email" value="{{ old('email') }}" required maxlength="160" autocomplete="email" class="{{ $fieldClass }}">
                        @error('email') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="career-phone" class="text-sm font-bold">Mobile Number <span class="text-brand-red">*</span></label>
                        <input id="career-phone" name="phone" value="{{ old('phone') }}" required inputmode="tel" maxlength="40" class="{{ $fieldClass }}">
                        @error('phone') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="career-position" class="text-sm font-bold">Position <span class="text-brand-red">*</span></label>
                        <input id="career-position" name="position" value="{{ old('position') }}" required maxlength="180" class="{{ $fieldClass }}">
                        @error('position') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="career-location" class="text-sm font-bold">Location</label>
                        <input id="career-location" name="location" value="{{ old('location') }}" maxlength="160" class="{{ $fieldClass }}">
                        @error('location') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="career-cv" class="text-sm font-bold">CV/Resume <span class="text-brand-red">*</span></label>
                        <input id="career-cv" type="file" name="cv" required accept=".pdf,.doc,.docx" class="{{ $fieldClass }} py-3">
                        @error('cv') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label for="career-cover-letter" class="text-sm font-bold">Cover Letter <span class="text-brand-red">*</span></label>
                        <textarea id="career-cover-letter" name="cover_letter" rows="5" required maxlength="3000" class="mt-1.5 w-full rounded-md border border-neutral-200 bg-white px-3.5 py-3 text-[15px] text-brand-dark shadow-sm outline-none transition placeholder:text-neutral-400 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20">{{ old('cover_letter') }}</textarea>
                        @error('cover_letter') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="career-portfolio" class="text-sm font-bold">Portfolio URL</label>
                        <input id="career-portfolio" type="url" name="portfolio_url" value="{{ old('portfolio_url') }}" maxlength="255" class="{{ $fieldClass }}" placeholder="https://">
                        @error('portfolio_url') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="career-linkedin" class="text-sm font-bold">LinkedIn URL</label>
                        <input id="career-linkedin" type="url" name="linkedin_url" value="{{ old('linkedin_url') }}" maxlength="255" class="{{ $fieldClass }}" placeholder="https://">
                        @error('linkedin_url') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="flex gap-3 rounded-md border border-neutral-200 bg-neutral-50 p-3 text-sm font-semibold leading-6 text-neutral-700">
                            <input type="checkbox" name="consent" value="1" required class="mt-1">
                            <span>আমি সম্মতি দিচ্ছি যে দৈনিক সময় বায়ান্ন আমার আবেদন তথ্য নিয়োগ প্রক্রিয়ার জন্য সংরক্ষণ ও পর্যালোচনা করতে পারবে।</span>
                        </label>
                        @error('consent') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-md bg-brand-green px-5 py-3 text-lg font-black text-white shadow-md shadow-green-900/20 transition hover:bg-green-800">আবেদন জমা দিন</button>
                    </div>
                </form>
            </section>
        </section>
    @elseif ($slug === 'career-detail')
        @php
            $vacancyValue = fn ($key) => $careerVacancy instanceof \App\Models\CareerVacancy ? $careerVacancy->{$key} : ($careerVacancy[$key] ?? null);
            $detailRows = [
                'Job summary' => $vacancyValue('summary'),
                'Responsibilities' => $vacancyValue('responsibilities'),
                'Requirements' => $vacancyValue('requirements'),
                'Qualifications' => $vacancyValue('qualifications'),
                'Experience' => $vacancyValue('experience'),
                'Skills' => $vacancyValue('skills'),
                'Salary/benefits' => $vacancyValue('salary_benefits'),
                'Application instructions' => $vacancyValue('application_instructions'),
            ];
        @endphp

        <section class="rounded-md border border-neutral-200 bg-white p-5 shadow-sm sm:p-7">
            <a href="{{ route('static.show', 'career') }}" class="text-sm font-black text-brand-green hover:text-brand-red">← ক্যারিয়ার পেজে ফিরুন</a>
            <h1 class="mt-4 text-4xl font-black text-brand-green">{{ $vacancyValue('title') }}</h1>
            <div class="mt-4 flex flex-wrap gap-3 text-sm font-bold text-neutral-600">
                <span class="rounded-full bg-brand-green/10 px-3 py-1 text-brand-green">{{ $vacancyValue('department') }}</span>
                <span class="rounded-full bg-neutral-100 px-3 py-1">{{ $vacancyValue('location') }}</span>
                <span class="rounded-full bg-neutral-100 px-3 py-1">{{ $vacancyValue('employment_type') }}</span>
                <span class="rounded-full bg-brand-red/10 px-3 py-1 text-brand-red">Deadline: {{ $vacancyValue('application_deadline')?->format('d M Y') }}</span>
            </div>
            <div class="mt-7 grid gap-4 lg:grid-cols-[1fr_320px]">
                <div class="space-y-4">
                    @foreach ($detailRows as $label => $body)
                        @if ($body)
                            <section class="rounded-md border border-neutral-200 bg-brand-light p-4">
                                <h2 class="text-xl font-black text-brand-green">{{ $label }}</h2>
                                <p class="mt-2 whitespace-pre-line leading-7 text-neutral-700">{{ $body }}</p>
                            </section>
                        @endif
                    @endforeach
                </div>
                <aside class="rounded-md border border-neutral-200 bg-white p-5 shadow-sm">
                    <h2 class="text-2xl font-black text-brand-green">Apply</h2>
                    <p class="mt-3 leading-7 text-neutral-700">এই পদের জন্য Career page-এর আবেদন ফর্ম ব্যবহার করুন।</p>
                    <a href="{{ route('static.show', 'career') }}#career-apply" class="mt-5 inline-flex min-h-11 w-full items-center justify-center rounded-md bg-brand-green px-4 py-2 font-black text-white hover:bg-green-800">আবেদন করুন</a>
                    <p class="mt-4 break-words text-sm font-bold text-neutral-600">Email: {{ $vacancyValue('application_email') }}</p>
                </aside>
            </div>
        </section>
    @elseif ($slug === 'contact')
        @php
            $contactCards = [
                ['title' => 'প্রধান কার্যালয়', 'value' => 'দৈনিক সময় বায়ান্ন', 'note' => $contactData['office'] ?? 'ঢাকা, বাংলাদেশ', 'icon' => '⌂'],
                ['title' => 'ফোন', 'value' => $contactData['phone'] ?? '+880 1712 345678', 'note' => 'সকাল ৯টা – সন্ধ্যা ৭টা', 'icon' => '☎'],
                ['title' => 'ই-মেইল', 'value' => $contactData['email'] ?? 'info@somoybayanno.com', 'note' => 'সাধারণ যোগাযোগ', 'icon' => '✉'],
                ['title' => 'সংবাদ পাঠান', 'value' => $contactData['news_email'] ?? 'news@somoybayanno.com', 'note' => 'নিউজরুম', 'icon' => '↗'],
            ];
            $faqs = [
                ['কীভাবে সংবাদ পাঠাব?', 'নিউজরুম ই-মেইল অথবা এই পেজের বার্তা ফর্মে “সংবাদ সংক্রান্ত” নির্বাচন করে সংবাদ পাঠাতে পারেন।'],
                ['বিজ্ঞাপনের জন্য কার সঙ্গে যোগাযোগ করব?', 'বার্তা ফর্মে “বিজ্ঞাপন” নির্বাচন করুন অথবা বিজ্ঞাপন বিভাগের ই-মেইলে আপনার প্রস্তাব পাঠান।'],
                ['ভুল সংবাদ সম্পর্কে অভিযোগ কোথায় করব?', 'বার্তার ধরন থেকে “অভিযোগ” নির্বাচন করে সংশ্লিষ্ট সংবাদ, লিংক ও সংশোধনের তথ্য পাঠান।'],
                ['সাবস্ক্রিপশন নেওয়ার পদ্ধতি কী?', 'সাবস্ক্রিপশন বিভাগে ই-মেইল করুন অথবা বার্তা ফর্মে “অন্যান্য” নির্বাচন করে আপনার আগ্রহ জানান।'],
            ];
            $fieldClass = 'mt-1.5 min-h-12 w-full rounded-md border border-neutral-200 bg-white px-3.5 text-[15px] text-brand-dark shadow-sm outline-none transition placeholder:text-neutral-400 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20';
            $errorClass = 'mt-1.5 text-xs font-semibold leading-5 text-brand-red';
        @endphp

        <section class="overflow-hidden rounded-md border border-neutral-200 bg-white shadow-sm">
            <div class="relative min-h-[260px] overflow-hidden bg-[#0f1714] px-5 py-10 text-white sm:px-8 lg:px-10">
                <img src="{{ $asset('dhaka') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-30">
                <div class="absolute inset-0 bg-gradient-to-r from-[#062d1a] via-[#064425]/92 to-[#d71920]/40"></div>
                <div class="relative max-w-3xl">
                    <p class="inline-flex rounded-full border border-white/25 bg-white/10 px-3 py-1 text-sm font-bold text-white">দৈনিক সময় বায়ান্ন</p>
                    <h1 class="mt-4 text-[2.25rem] font-black leading-tight sm:text-5xl">যোগাযোগ করুন</h1>
                    <p class="mt-4 max-w-2xl text-lg font-semibold leading-8 text-white/90">সত্যের পথে, সময়ের সাথে — আপনার মতামত, সংবাদ ও পরামর্শ আমাদের কাছে গুরুত্বপূর্ণ।</p>
                    <span class="mt-5 block h-1 w-20 rounded-full bg-brand-red"></span>
                </div>
            </div>
        </section>

        <section class="mt-7 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($contactCards as $card)
                <article class="rounded-md border border-neutral-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-brand-green hover:shadow-lg">
                    <span class="grid h-11 w-11 place-items-center rounded-full bg-brand-green/10 text-xl font-black text-brand-green">{{ $card['icon'] }}</span>
                    <h2 class="mt-4 text-xl font-black text-brand-green">{{ $card['title'] }}</h2>
                    <p class="mt-2 break-words text-lg font-bold text-brand-dark">{{ $card['value'] }}</p>
                    <p class="mt-1 text-sm font-semibold leading-6 text-neutral-600">{{ $card['note'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="mt-7 grid gap-6 lg:grid-cols-[1.25fr_0.8fr]">
            <section class="overflow-hidden rounded-md border border-neutral-200 bg-white shadow-sm">
                <h2 class="flex items-center gap-3 bg-brand-green px-5 py-4 text-2xl font-black text-white">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-white/15 text-lg">✉</span>
                    আমাদের কাছে বার্তা পাঠান
                </h2>

                @if (session('contact_status'))
                    <div class="mx-5 mt-5 rounded-md border border-brand-green/30 bg-brand-green/10 p-4 text-sm font-bold leading-6 text-brand-green">
                        {{ session('contact_status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('static.contact.store', 'contact') }}" class="grid gap-x-5 gap-y-4 p-5 md:grid-cols-2">
                    @csrf
                    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

                    <div>
                        <label for="contact-name" class="text-sm font-bold">আপনার নাম <span class="text-brand-red">*</span></label>
                        <input id="contact-name" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name" class="{{ $fieldClass }}" placeholder="আপনার নাম">
                        @error('name') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="contact-email" class="text-sm font-bold">ই-মেইল <span class="text-brand-red">*</span></label>
                        <input id="contact-email" type="email" name="email" value="{{ old('email') }}" required maxlength="160" autocomplete="email" class="{{ $fieldClass }}" placeholder="example@domain.com">
                        @error('email') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="contact-phone" class="text-sm font-bold">মোবাইল নম্বর</label>
                        <input id="contact-phone" name="phone" value="{{ old('phone') }}" inputmode="tel" autocomplete="tel" pattern="[+0-9\s().-]{7,40}" maxlength="40" class="{{ $fieldClass }}" placeholder="০১৭xxxxxxxx">
                        @error('phone') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="contact-type" class="text-sm font-bold">বার্তার ধরন <span class="text-brand-red">*</span></label>
                        <select id="contact-type" name="message_type" required class="{{ $fieldClass }}">
                            <option value="">নির্বাচন করুন</option>
                            @foreach ($contactMessageTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('message_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('message_type') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="contact-subject" class="text-sm font-bold">বিষয় <span class="text-brand-red">*</span></label>
                        <input id="contact-subject" name="subject" value="{{ old('subject') }}" required maxlength="180" class="{{ $fieldClass }}" placeholder="বার্তার বিষয় লিখুন">
                        @error('subject') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="contact-message" class="text-sm font-bold">আপনার বার্তা <span class="text-brand-red">*</span></label>
                        <textarea id="contact-message" name="message" rows="6" required maxlength="3000" class="mt-1.5 w-full rounded-md border border-neutral-200 bg-white px-3.5 py-3 text-[15px] text-brand-dark shadow-sm outline-none transition placeholder:text-neutral-400 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20" placeholder="আপনার বার্তা লিখুন...">{{ old('message') }}</textarea>
                        @error('message') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="contact-url" class="text-sm font-bold">Website / Landing Page URL <span class="text-neutral-500">(ঐচ্ছিক)</span></label>
                        <input id="contact-url" type="url" name="website_url" value="{{ old('website_url') }}" maxlength="255" class="{{ $fieldClass }}" placeholder="https://">
                        @error('website_url') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2 rounded-md border border-dashed border-neutral-300 bg-neutral-50 p-3 text-sm font-semibold leading-6 text-neutral-600">
                        CAPTCHA / anti-spam protection: এই ফর্মে CSRF, rate limiting ও hidden honeypot spam protection সক্রিয় আছে।
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-md bg-brand-green px-5 py-3 text-lg font-black text-white shadow-md shadow-green-900/20 transition hover:bg-green-800 focus-visible:outline focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-brand-red">
                            <span>➤</span>
                            বার্তা পাঠান
                        </button>
                    </div>
                </form>
            </section>

            <aside class="space-y-6">
                <section class="rounded-md border border-neutral-200 bg-white p-5 shadow-sm">
                    <h2 class="text-2xl font-black text-brand-green">যোগাযোগের বিভাগ</h2>
                    <div class="mt-4 space-y-3">
                        @foreach (($contactData['departments'] ?? []) as $department)
                            <article class="rounded-md border border-neutral-200 bg-brand-light p-4">
                                <h3 class="font-black text-brand-dark">{{ $department['title'] }}</h3>
                                <a href="mailto:{{ $department['email'] }}" class="mt-1 block break-words text-sm font-bold text-brand-green hover:text-brand-red">{{ $department['email'] }}</a>
                                <p class="mt-1 text-sm leading-6 text-neutral-600">{{ $department['note'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-md border border-neutral-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black text-brand-green">সামাজিক যোগাযোগ মাধ্যমে আমাদের সঙ্গে যুক্ত থাকুন</h2>
                    <div class="mt-4 flex flex-wrap gap-2.5">
                        @foreach (($contactData['socials'] ?? []) as $social)
                            @if ($social['url'])
                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $social['label'] }}" class="grid h-10 w-10 place-items-center rounded-full bg-brand-green text-sm font-black text-white shadow-sm transition hover:bg-brand-red">{{ $social['mark'] }}</a>
                            @endif
                        @endforeach
                    </div>
                </section>
            </aside>
        </section>

        <section class="mt-8 grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
            <div class="rounded-md border border-neutral-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-2xl font-black text-brand-green">আমাদের ঠিকানা</h2>
                <p class="mt-4 text-lg font-black">দৈনিক সময় বায়ান্ন</p>
                <p class="mt-1 leading-7 text-neutral-700">{{ $contactData['office'] ?? 'ঢাকা, বাংলাদেশ' }}</p>
                <a href="{{ $contactData['map_url'] ?? 'https://www.google.com/maps/search/?api=1&query=Dhaka%20Bangladesh' }}" target="_blank" rel="noopener noreferrer" class="mt-5 inline-flex min-h-11 items-center rounded-md bg-brand-green px-5 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-green-800">গুগল ম্যাপে দেখুন</a>
            </div>
            <div class="overflow-hidden rounded-md border border-neutral-200 bg-white shadow-sm">
                <div class="grid min-h-64 place-items-center bg-[linear-gradient(135deg,#eef7f2,#ffffff)] p-6 text-center">
                    <div>
                        <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-brand-green text-3xl text-white">⌖</span>
                        <p class="mt-4 text-2xl font-black text-brand-green">ঢাকা, বাংলাদেশ</p>
                        <p class="mt-2 max-w-md text-sm leading-6 text-neutral-600">দ্রুত লোডের জন্য এখানে ভারী map library ব্যবহার করা হয়নি। অফিস লোকেশন দেখতে Google Maps লিংক ব্যবহার করুন।</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-8 rounded-md border border-neutral-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-2xl font-black text-brand-green">সাধারণ জিজ্ঞাসা</h2>
            <div class="mt-5 divide-y divide-neutral-200 rounded-md border border-neutral-200">
                @foreach ($faqs as [$question, $answer])
                    <details class="group p-4">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-base font-black text-brand-dark focus-visible:outline focus-visible:outline-3 focus-visible:outline-offset-4 focus-visible:outline-brand-red">
                            <span>{{ $question }}</span>
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-green/10 text-brand-green transition group-open:rotate-45">+</span>
                        </summary>
                        <p class="mt-3 leading-7 text-neutral-700">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </section>
    @elseif ($slug === 'advertise')
        @php
            $benefits = [
                'নিউজ পোর্টালে প্রচার',
                'ব্যানার বিজ্ঞাপন',
                'মোবাইল বিজ্ঞাপন',
                'ভিডিও বিজ্ঞাপন',
                'Sponsored Content',
                'ব্র্যান্ড প্রচার',
                'নির্দিষ্ট সময়ের জন্য প্রচার',
                'লক্ষ্যভিত্তিক দর্শকের কাছে পৌঁছানোর সুযোগ',
            ];
            $formats = [
                ['970 × 90', 'Top Banner', 'ওয়েবসাইটের শীর্ষে', 'ad-strip'],
                ['728 × 90', 'Desktop Banner', 'ডেস্কটপ সাইটের লিডারবোর্ড', 'ad-strip'],
                ['300 × 250', 'Sidebar Banner', 'সাইডবার বিজ্ঞাপন', 'parliament'],
                ['320 × 100', 'Mobile Banner', 'মোবাইল ডিভাইসের জন্য', 'ad-strip'],
                ['Sponsored Content', 'Article Promotion', 'আর্টিকেল কনটেন্ট', 'logo'],
                ['Video Advertisement', 'Video Campaign', 'ভিডিও ক্যাম্পেইন', 'yunus-video'],
            ];
            $policies = [
                'বিজ্ঞাপনের বিষয়বস্তু প্রকাশের আগে যাচাই করা হবে।',
                'বিজ্ঞাপন অবশ্যই প্রযোজ্য আইন ও বিধি-বিধান মেনে চলতে হবে।',
                'প্রকাশনার জন্য কর্তৃপক্ষের অনুমোদন সাপেক্ষে বিজ্ঞাপন প্রকাশ করা হবে।',
                'বিজ্ঞাপনের সময়কাল ও অবস্থান চুক্তি অনুযায়ী নির্ধারিত হবে।',
                'ভ্রান্তিকর, নিষিদ্ধ বা অনৈতিক বিজ্ঞাপন গ্রহণযোগ্য নয়।',
            ];
            $fieldClass = 'mt-1.5 min-h-12 w-full rounded-md border border-neutral-200 bg-white px-3.5 text-[15px] text-brand-dark shadow-sm outline-none transition placeholder:text-neutral-400 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20';
            $errorClass = 'mt-1.5 text-xs font-semibold leading-5 text-brand-red';
        @endphp

        <section class="overflow-hidden rounded-md border border-neutral-200 bg-white shadow-sm">
            <div class="relative grid min-h-[210px] items-center gap-6 overflow-hidden bg-[linear-gradient(120deg,#ffffff_0%,#f6fbf8_48%,#e7f3ee_100%)] px-5 py-8 sm:px-8 lg:grid-cols-[minmax(0,1fr)_420px]">
                <div class="absolute inset-y-0 right-0 hidden w-[38%] bg-[linear-gradient(135deg,rgba(6,122,59,.12),rgba(215,25,32,.08))] lg:block"></div>
                <div class="relative max-w-3xl">
                    <p class="inline-flex rounded-full border border-brand-green/20 bg-brand-green/10 px-3 py-1 text-sm font-bold text-brand-green">দৈনিক সময় বায়ান্ন বিজ্ঞাপন সেবা</p>
                    <h1 class="mt-4 text-[2rem] font-black leading-tight text-brand-green sm:text-5xl">আপনার ব্র্যান্ডের বিজ্ঞাপন দিন</h1>
                    <p class="mt-3 max-w-2xl text-lg font-semibold leading-8 text-neutral-800 sm:text-xl">বাংলাদেশের পাঠকের কাছে আপনার বার্তা পৌঁছে দিন</p>
                    <span class="mt-5 block h-1 w-20 rounded-full bg-brand-red"></span>
                </div>
                <div class="relative hidden items-center justify-end lg:flex">
                    <div class="w-full max-w-sm overflow-hidden rounded-md border border-neutral-200 bg-white p-3 shadow-xl shadow-green-950/10">
                        <div class="flex items-center justify-between gap-4 border-b border-neutral-100 pb-3">
                            <img src="{{ $asset('logo') }}" alt="দৈনিক সময় বায়ান্ন" class="h-14 w-44 object-contain">
                            <span class="grid h-12 w-12 place-items-center rounded-full bg-brand-red text-xl text-white shadow-md">▶</span>
                        </div>
                        <img src="{{ $asset('ad-strip') }}" alt="বিজ্ঞাপন ব্যানার নমুনা" class="mt-3 aspect-[3.8/1] w-full rounded object-cover">
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-7 grid gap-6 lg:grid-cols-[0.92fr_1.62fr]">
            <aside class="overflow-hidden rounded-md border border-neutral-200 bg-white shadow-sm">
                <h2 class="flex items-center gap-3 border-b border-neutral-200 bg-brand-green/10 px-5 py-4 text-2xl font-black text-brand-green">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-brand-green text-lg text-white">✓</span>
                    বিজ্ঞাপনের সুবিধা
                </h2>
                <ul class="space-y-3.5 p-5 text-[17px] font-semibold leading-7 text-neutral-800">
                    @foreach ($benefits as $benefit)
                        <li class="flex items-start gap-3">
                            <span class="mt-1 grid h-7 w-7 shrink-0 place-items-center rounded-full bg-brand-green text-xs text-white">✓</span>
                            <span>{{ $benefit }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mx-5 mb-5 overflow-hidden rounded-md border border-neutral-200 bg-neutral-100">
                    <div class="relative aspect-[16/9]">
                        <img src="{{ $asset('dhaka') }}" alt="ঢাকা শহরের দৃশ্য" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-brand-green via-brand-green/90 to-transparent p-5 pt-16 text-white">
                            <p class="text-2xl font-black sm:text-3xl">আপনার বিজ্ঞাপন</p>
                            <p class="mt-1 text-base font-bold sm:text-lg">হতে পারে লক্ষ লক্ষ পাঠকের কাছে</p>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="overflow-hidden rounded-md border border-neutral-200 bg-white shadow-sm">
                <h2 class="flex items-center gap-3 bg-brand-green px-5 py-4 text-2xl font-black text-white">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-white/15 text-lg">✉</span>
                    বিজ্ঞাপনের জন্য যোগাযোগ করুন
                </h2>

                @if (session('advertisement_status'))
                    <div class="mx-5 mt-5 rounded-md border border-brand-green/30 bg-brand-green/10 p-4 text-sm font-bold leading-6 text-brand-green">
                        {{ session('advertisement_status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('static.advertise.store', 'advertise') }}" class="grid gap-x-5 gap-y-4 p-5 md:grid-cols-2">
                    @csrf
                    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

                    <div>
                        <label for="advertise-name" class="text-sm font-bold">নাম <span class="text-brand-red">*</span></label>
                        <input id="advertise-name" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name" class="{{ $fieldClass }}" placeholder="আপনার নাম">
                        @error('name') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="advertise-company" class="text-sm font-bold">প্রতিষ্ঠান/কোম্পানির নাম</label>
                        <input id="advertise-company" name="company_name" value="{{ old('company_name') }}" maxlength="160" class="{{ $fieldClass }}" placeholder="প্রতিষ্ঠানের নাম">
                        @error('company_name') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="advertise-phone" class="text-sm font-bold">মোবাইল নম্বর <span class="text-brand-red">*</span></label>
                        <input id="advertise-phone" name="phone" value="{{ old('phone') }}" required inputmode="tel" autocomplete="tel" pattern="[+0-9\s().-]{7,40}" maxlength="40" class="{{ $fieldClass }}" placeholder="০১৭xxxxxxxx">
                        @error('phone') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="advertise-email" class="text-sm font-bold">ই-মেইল <span class="text-brand-red">*</span></label>
                        <input id="advertise-email" type="email" name="email" value="{{ old('email') }}" required maxlength="160" autocomplete="email" class="{{ $fieldClass }}" placeholder="example@domain.com">
                        @error('email') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="advertise-type" class="text-sm font-bold">বিজ্ঞাপনের ধরন <span class="text-brand-red">*</span></label>
                        <select id="advertise-type" name="advertisement_type" required class="{{ $fieldClass }}">
                            <option value="">নির্বাচন করুন</option>
                            @foreach ($advertisementTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('advertisement_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('advertisement_type') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="advertise-placement" class="text-sm font-bold">বিজ্ঞাপনের অবস্থান</label>
                        <select id="advertise-placement" name="placement" class="{{ $fieldClass }}">
                            <option value="">নির্বাচন করুন</option>
                            @foreach ($advertisementPlacements as $value => $label)
                                <option value="{{ $value }}" @selected(old('placement') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('placement') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="advertise-budget" class="text-sm font-bold">সম্ভাব্য বাজেট</label>
                        <input id="advertise-budget" name="budget" value="{{ old('budget') }}" maxlength="120" class="{{ $fieldClass }}" placeholder="টাকার পরিমাণ লিখুন">
                        @error('budget') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="advertise-starts-on" class="text-sm font-bold">শুরু করার তারিখ</label>
                        <input id="advertise-starts-on" type="date" name="starts_on" value="{{ old('starts_on') }}" class="{{ $fieldClass }}">
                        @error('starts_on') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="advertise-ends-on" class="text-sm font-bold">শেষ করার তারিখ</label>
                        <input id="advertise-ends-on" type="date" name="ends_on" value="{{ old('ends_on') }}" class="{{ $fieldClass }}">
                        @error('ends_on') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="advertise-url" class="text-sm font-bold">Website / Landing Page URL</label>
                        <input id="advertise-url" type="url" name="landing_page_url" value="{{ old('landing_page_url') }}" maxlength="255" class="{{ $fieldClass }}" placeholder="https://">
                        @error('landing_page_url') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="advertise-message" class="text-sm font-bold">আপনার বার্তা</label>
                        <textarea id="advertise-message" name="message" rows="5" maxlength="2000" class="mt-1.5 w-full rounded-md border border-neutral-200 bg-white px-3.5 py-3 text-[15px] text-brand-dark shadow-sm outline-none transition placeholder:text-neutral-400 focus:border-brand-green focus:ring-2 focus:ring-brand-green/20" placeholder="আপনার বার্তা লিখুন...">{{ old('message') }}</textarea>
                        @error('message') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-md bg-brand-green px-5 py-3 text-lg font-black text-white shadow-md shadow-green-900/20 transition hover:bg-green-800 focus-visible:outline focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-brand-red">
                            <span>➤</span>
                            বিজ্ঞাপনের অনুরোধ পাঠান
                        </button>
                    </div>
                </form>
            </section>
        </section>

        <section class="mt-8">
            <h2 class="mb-5 flex items-center gap-3 text-3xl font-black"><span class="grid h-9 w-9 place-items-center rounded-full bg-brand-red text-base text-white">✹</span> বিজ্ঞাপনের ধরন</h2>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                @foreach ($formats as [$size, $formatTitle, $formatSubtitle, $image])
                    <article class="rounded-md border border-neutral-200 bg-white p-4 text-center shadow-sm transition hover:-translate-y-1 hover:border-brand-green hover:shadow-lg">
                        <div class="grid aspect-[16/9] place-items-center overflow-hidden rounded-md bg-neutral-100">
                            <img src="{{ $asset($image) }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        </div>
                        <h3 class="mt-4 text-lg font-black">{{ $formatTitle }}</h3>
                        <p class="mt-1 min-h-10 text-sm font-semibold leading-5 text-neutral-600">{{ $formatSubtitle }}</p>
                        <p class="mt-3 inline-flex rounded-full bg-brand-green/10 px-3 py-1 text-sm font-black text-brand-green">{{ $size }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="mt-8 grid gap-6 lg:grid-cols-[1.12fr_0.88fr]">
            <div class="rounded-md border border-neutral-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="flex items-center gap-3 text-2xl font-black text-brand-green">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-brand-green/10 text-base">▣</span>
                    আমাদের বিজ্ঞাপন নীতি
                </h2>
                <ul class="mt-5 space-y-3 text-base leading-7 text-neutral-700">
                    @foreach ($policies as $policy)
                        <li class="flex gap-3">
                            <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-brand-green"></span>
                            <span>{{ $policy }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="relative overflow-hidden rounded-md border border-neutral-200 bg-brand-green/10 p-5 shadow-sm sm:p-6">
                <div class="relative">
                    <h2 class="flex items-center gap-3 text-2xl font-black text-brand-green">
                        <span class="grid h-9 w-9 place-items-center rounded-full bg-white text-base shadow-sm">☎</span>
                        বিজ্ঞাপন বিভাগ
                    </h2>
                    <div class="mt-5 space-y-3 text-base leading-7 text-neutral-800">
                        <p><span class="font-black text-brand-green">ফোন:</span> {{ $advertisementContact['phone'] ?: 'ফুটার সেটিংসে যুক্ত করুন' }}</p>
                        <p><span class="font-black text-brand-green">ই-মেইল:</span> {{ $advertisementContact['email'] }}</p>
                        <p><span class="font-black text-brand-green">অফিস:</span> {{ $advertisementContact['publication_info'] }}</p>
                    </div>
                </div>
                <span class="absolute -bottom-8 -right-4 text-[9rem] font-black leading-none text-brand-green/10">☎</span>
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
