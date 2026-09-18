@php
    $utilityLinks = [
        ['label' => 'ই-পেপার', 'url' => route('static.show', 'epaper')],
        ['label' => 'বিজ্ঞাপন দিন', 'url' => route('static.show', 'advertise')],
        ['label' => 'যোগাযোগ', 'url' => route('static.show', 'contact')],
        ['label' => 'ক্যারিয়ার', 'url' => route('static.show', 'career')],
        ['label' => 'সাহায্য কেন্দ্র', 'url' => route('static.show', 'contact')],
    ];
@endphp

<header class="site-header bg-white" x-data="{ menuOpen: false }" x-on:keydown.escape.window="menuOpen = false">
    <div class="header-utility bg-gradient-to-r from-[#005f2f] via-[#007a3d] to-[#005f2f] text-white">
        <div class="public-container flex min-h-11 flex-wrap items-center justify-between gap-3 py-2 text-sm font-semibold">
            <div class="flex flex-wrap items-center gap-4">
                <span class="inline-flex items-center gap-2">
                    <span class="text-[#ffc94a]" aria-hidden="true">▣</span>
                    <span id="header-local-date">{{ now('Asia/Dhaka')->translatedFormat('l, d F Y') }}</span>
                </span>
                <span class="hidden text-white/35 sm:inline">|</span>
                <span class="inline-flex items-center gap-2">
                    <span class="text-[#ffc94a]" aria-hidden="true">◷</span>
                    <span id="header-local-time">{{ now('Asia/Dhaka')->format('h:i:s A') }}</span>
                </span>
                <span class="hidden text-white/35 md:inline">|</span>
                <span class="inline-flex items-center gap-2">
                    <span class="text-[#ffc94a]" aria-hidden="true">●</span>
                    ঢাকা, বাংলাদেশ
                </span>
            </div>

            <nav class="flex flex-wrap items-center justify-end gap-3" aria-label="সহায়ক লিংক">
                @foreach ($utilityLinks as $link)
                    <a class="transition hover:text-[#ffc94a]" href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                    @if (! $loop->last)
                        <span class="hidden text-white/35 sm:inline">|</span>
                    @endif
                @endforeach
                <span class="grid h-8 w-8 place-items-center rounded-full bg-blue-600 text-xs font-bold text-white">f</span>
                <span class="grid h-8 w-8 place-items-center rounded-full bg-black text-xs font-bold text-white">x</span>
                <span class="grid h-8 w-8 place-items-center rounded-full bg-red-600 text-xs font-bold text-white">▶</span>
                <span class="grid h-8 w-8 place-items-center rounded-full bg-pink-600 text-xs font-bold text-white">◎</span>
                <button type="button" class="inline-flex h-9 items-center gap-2 rounded-full bg-white px-4 text-sm font-black text-[#007a3d] shadow-sm" aria-label="ভাষা নির্বাচন">
                    EN
                    <span aria-hidden="true">⌄</span>
                </button>
            </nav>
        </div>
    </div>

    <div class="header-masthead relative overflow-hidden bg-white">
        <div class="public-container relative grid min-h-[210px] items-center gap-4 py-7 lg:grid-cols-[1.1fr_1fr_1fr]">
            <a href="{{ route('home') }}" class="flex h-24 w-full max-w-[420px] items-center" aria-label="দৈনিক সময় বায়ান্ন হোম">
                <img src="{{ asset('brand/logo-transparent.png') }}" alt="দৈনিক সময় বায়ান্ন" class="max-h-full max-w-full object-contain">
            </a>

            <div class="hidden lg:block" aria-hidden="true"></div>

            <div class="hidden lg:block" aria-hidden="true"></div>
        </div>
    </div>

    <script>
        (() => {
            const dateNode = document.getElementById('header-local-date');
            const timeNode = document.getElementById('header-local-time');
            const timeZone = 'Asia/Dhaka';

            const render = () => {
                const now = new Date();

                if (dateNode) {
                    dateNode.textContent = new Intl.DateTimeFormat('bn-BD', {
                        timeZone,
                        weekday: 'long',
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric',
                    }).format(now);
                }

                if (timeNode) {
                    timeNode.textContent = new Intl.DateTimeFormat('bn-BD', {
                        timeZone,
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: true,
                    }).format(now);
                }
            };

            render();
            window.setInterval(render, 1000);
        })();
    </script>
</header>
