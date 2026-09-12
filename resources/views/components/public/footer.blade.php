@php
    use App\Enums\CategoryStatus;
    use App\Models\Category;
    use App\Models\SiteSetting;

    $settingKeys = [
        'footer_about_button_label',
        'footer_app_store_url',
        'footer_correspondent_label',
        'footer_copyright',
        'footer_description',
        'footer_google_play_url',
        'footer_logo_path',
        'footer_mobile_apps_enabled',
        'footer_newsletter_description',
        'footer_newsletter_enabled',
        'footer_newsletter_heading',
        'footer_privacy_url',
        'footer_publication_info',
        'footer_show_publication_info',
        'footer_sitemap_url',
        'footer_social_facebook_url',
        'footer_social_instagram_url',
        'footer_social_linkedin_url',
        'footer_social_whatsapp_url',
        'footer_social_x_url',
        'footer_social_youtube_url',
        'footer_story_tagline',
        'footer_tagline',
        'footer_terms_url',
    ];

    $settings = SiteSetting::publicValues($settingKeys);
    $setting = fn (string $key, string $default = ''): string => filled($settings->get($key)) ? (string) $settings->get($key) : $default;
    $internalUrl = fn (string $path): string => str_starts_with($path, 'http') ? $path : url($path);

    $showPublicationInfo = $setting('footer_show_publication_info', '1') === '1';
    $newsletterEnabled = $setting('footer_newsletter_enabled', '1') === '1';
    $mobileAppsEnabled = $setting('footer_mobile_apps_enabled', '1') === '1';

    $quickLinks = Category::query()
        ->where('status', CategoryStatus::Active)
        ->where('show_in_menu', true)
        ->orderBy('sort_order')
        ->orderBy('name_bn')
        ->take(9)
        ->get(['name_bn', 'slug']);

    if ($quickLinks->isEmpty()) {
        $quickLinks = collect([
            ['name_bn' => 'জাতীয়', 'slug' => 'national'],
            ['name_bn' => 'রাজনীতি', 'slug' => 'politics'],
            ['name_bn' => 'অর্থনীতি', 'slug' => 'economy'],
            ['name_bn' => 'আন্তর্জাতিক', 'slug' => 'international'],
            ['name_bn' => 'খেলা', 'slug' => 'sports'],
            ['name_bn' => 'বিনোদন', 'slug' => 'entertainment'],
            ['name_bn' => 'প্রযুক্তি', 'slug' => 'technology'],
            ['name_bn' => 'জীবনযাপন', 'slug' => 'lifestyle'],
            ['name_bn' => 'মতামত', 'slug' => 'opinion'],
        ])->map(fn (array $item): object => (object) $item);
    }

    $infoLinks = [
        ['আমাদের সম্পর্কে', route('static.show', 'about')],
        ['যোগাযোগ', route('static.show', 'contact')],
        ['বিজ্ঞাপন দিন', route('static.show', 'advertise')],
        [$setting('footer_correspondent_label', 'প্রতিনিধি তালিকা'), route('static.show', 'correspondents')],
        ['সম্পাদকীয় নীতি', route('static.show', 'editorial-policy')],
        ['গোপনীয়তা নীতি', $internalUrl($setting('footer_privacy_url', '/pages/privacy-policy'))],
        ['ব্যবহারের শর্তাবলি', $internalUrl($setting('footer_terms_url', '/pages/terms'))],
        ['সাংবাদিক নীতিমালা', route('static.show', 'journalism-policy')],
        ['লেখক নির্দেশিকা', route('static.show', 'writer-guidelines')],
        ['ক্যারিয়ার', route('static.show', 'career')],
    ];

    $serviceLinks = [
        ['ই-পেপার', route('static.show', 'epaper'), 'M6 4h12v16H6z M9 8h6 M9 12h6 M9 16h4'],
        ['সাবস্ক্রিপশন', route('static.show', 'subscribe'), 'M12 5v14 M5 12h14 M7 7l10 10 M17 7L7 17'],
        ['নিউজলেটার', route('static.show', 'newsletter'), 'M4 6h16v12H4z M4 7l8 6 8-6'],
        ['মোবাইল অ্যাপ', route('static.show', 'mobile-app'), 'M9 2h6a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z M11 18h2'],
        ['সাহায্য কেন্দ্র', route('static.show', 'contact'), 'M12 18h.01 M9.1 9a3 3 0 1 1 5.8 1c0 2-3 2-3 5'],
        ['মতামত দিন', route('static.show', 'opinion'), 'M5 5h14v10H8l-3 3z'],
    ];

    $socialLinks = [
        ['Facebook', 'f', $setting('footer_social_facebook_url'), '#1877f2'],
        ['X', 'X', $setting('footer_social_x_url'), '#000000'],
        ['YouTube', '▶', $setting('footer_social_youtube_url'), '#ff0000'],
        ['Instagram', '◎', $setting('footer_social_instagram_url'), '#e1306c'],
        ['LinkedIn', 'in', $setting('footer_social_linkedin_url'), '#0a66c2'],
        ['WhatsApp', '☎', $setting('footer_social_whatsapp_url'), '#25d366'],
    ];

    $copyright = $setting('footer_copyright', '© :year দৈনিক সময় বায়ান্ন | সর্বস্বত্ব সংরক্ষিত।');
    $currentYear = strtr(now()->format('Y'), ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯']);
    $copyright = str_replace([':year', '২০২৬'], [$currentYear, $currentYear], $copyright);
@endphp

<footer class="somoy-reference-footer relative mt-10 overflow-hidden bg-white text-[#222222]">
    <div class="footer-map-mark" aria-hidden="true"></div>
    <div class="footer-skyline" aria-hidden="true"></div>

    <div class="footer-shell footer-main-grid relative grid gap-5 pb-10 pt-7 sm:grid-cols-2 lg:grid-cols-[1.42fr_0.72fr_0.95fr_0.78fr_1.32fr] xl:gap-7">
        <section aria-label="দৈনিক সময় বায়ান্ন পরিচিতি" class="relative space-y-3">
            <div class="footer-monument" aria-hidden="true"></div>
            <div class="footer-birds" aria-hidden="true">⌁ ⌁ ⌁</div>
            <a href="{{ route('home') }}" class="inline-flex h-14 w-60 max-w-full items-center md:h-16 md:w-64" aria-label="দৈনিক সময় বায়ান্ন হোম">
                <img src="{{ asset($setting('footer_logo_path', 'demo-home/logo.png')) }}" alt="দৈনিক সময় বায়ান্ন" class="max-h-full max-w-full object-contain">
            </a>
            <div>
                <p class="text-lg font-bold text-[#111617] md:text-xl">{{ $setting('footer_tagline', 'সময়ের সংবাদ, সত্যের সঙ্গে') }}</p>
                <p class="mt-2 max-w-sm text-[0.95rem] leading-6 text-[#333333]">{{ $setting('footer_description', 'দেশ, সমাজ ও মানুষের গল্প নিয়ে সবসময় আপনার পাশে। নির্ভরযোগ্য, বস্তুনিষ্ঠ ও দায়িত্বশীল সংবাদ পরিবেশন আমাদের অঙ্গীকার।') }}</p>
            </div>
            <div class="flex flex-wrap gap-2.5">
                @foreach ($socialLinks as [$label, $icon, $url, $color])
                    @if (filled($url))
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $label }}" class="grid h-9 w-9 place-items-center rounded-full text-sm font-bold text-white shadow-md transition hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#e31e24] md:h-10 md:w-10" style="background-color: {{ $color }}">
                            {{ $icon }}
                        </a>
                    @else
                        <span aria-label="{{ $label }}" class="grid h-9 w-9 place-items-center rounded-full text-sm font-bold text-white shadow-md md:h-10 md:w-10" style="background-color: {{ $color }}">
                            {{ $icon }}
                        </span>
                    @endif
                @endforeach
            </div>
        </section>

        <nav aria-label="দ্রুত লিংক">
            <h2 class="footer-heading text-[#008c44]">দ্রুত লিংক</h2>
            <ul class="mt-3 space-y-1.5 text-[0.95rem]">
                @foreach ($quickLinks as $category)
                    <li>
                        <a class="footer-link" href="{{ route('static.show', $category->slug) }}">
                            <span aria-hidden="true">›</span>
                            {{ $category->name_bn }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <nav aria-label="গুরুত্বপূর্ণ তথ্য">
            <h2 class="footer-heading text-[#e31e24]">গুরুত্বপূর্ণ তথ্য</h2>
            <ul class="mt-3 space-y-1.5 text-[0.95rem]">
                @foreach ($infoLinks as [$label, $url])
                    <li>
                        <a class="footer-link" href="{{ $url }}">
                            <span aria-hidden="true">›</span>
                            {{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <nav aria-label="সেবা সমূহ">
            <h2 class="footer-heading text-[#008c44]">সেবা সমূহ</h2>
            <ul class="mt-3 space-y-2 text-[0.95rem]">
                @foreach ($serviceLinks as [$label, $url, $iconPath])
                    <li>
                        <a class="group flex items-center gap-3 text-[#333333] transition hover:text-[#008c44]" href="{{ $url }}">
                            <svg class="h-5 w-5 flex-none text-[#008c44] transition group-hover:text-[#e31e24]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" />
                            </svg>
                            <span>{{ $label }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <section aria-label="নিউজলেটার ও অ্যাপ" class="relative border-[#d7d7d7] lg:border-l lg:pl-10">
            @if ($newsletterEnabled)
                <h2 class="text-lg font-extrabold text-[#e31e24] md:text-xl">{{ $setting('footer_newsletter_heading', 'নিউজলেটার সাবস্ক্রাইব করুন') }}</h2>
                <p class="mt-1.5 text-[0.95rem] leading-6 text-[#333333]">{{ $setting('footer_newsletter_description', 'সর্বশেষ সংবাদ ও বিশেষ প্রতিবেদন সরাসরি আপনার ইমেইলে পেতে।') }}</p>
                <form class="mt-3 flex overflow-hidden rounded-full border border-[#cfcfcf] bg-white shadow-sm" onsubmit="return false;" aria-label="নিউজলেটার সাবস্ক্রাইব ফর্ম">
                    <label for="footer-newsletter-email" class="sr-only">আপনার ইমেইল লিখুন</label>
                    <input id="footer-newsletter-email" type="email" placeholder="আপনার ইমেইল লিখুন" class="min-w-0 flex-1 bg-transparent px-4 py-2.5 text-sm text-[#222222] outline-none" autocomplete="email">
                    <button type="submit" class="bg-[#e31e24] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#c9161b]">
                        সাবস্ক্রাইব →
                    </button>
                </form>
            @endif

            @if ($mobileAppsEnabled)
                <div class="mt-5">
                    <h3 class="text-lg font-extrabold text-[#008c44] md:text-xl">আমাদের অ্যাপ ডাউনলোড করুন</h3>
                    <p class="mt-1 text-[0.95rem] text-[#333333]">যেখানেই থাকুন, সময় বায়ান্ন আপনার সাথে</p>
                    <div class="mt-2.5 flex flex-wrap gap-2.5">
                        @foreach ([['GET IT ON', 'Google Play', $setting('footer_google_play_url')], ['Download on the', 'App Store', $setting('footer_app_store_url')]] as [$small, $large, $url])
                            @if (filled($url))
                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="min-w-32 rounded-lg bg-black px-4 py-2 text-white transition hover:bg-[#008c44]">
                                    <span class="block text-[10px] uppercase leading-none">{{ $small }}</span>
                                    <span class="text-base font-bold">{{ $large }}</span>
                                </a>
                            @else
                                <span class="min-w-32 rounded-lg bg-black px-4 py-2 text-white" aria-disabled="true">
                                    <span class="block text-[10px] uppercase leading-none">{{ $small }}</span>
                                    <span class="text-base font-bold">{{ $large }}</span>
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    </div>

    <div class="footer-wave-band relative bg-gradient-to-r from-[#006a34] via-[#008c44] to-[#006a34] text-white">
        <div class="footer-shell flex flex-wrap items-center justify-between gap-4 py-3 text-sm md:text-base">
            <span>{{ $copyright }}</span>
            <span class="inline-flex items-center gap-3 text-lg font-bold">
                <span class="text-xl" aria-hidden="true">♟</span>
                {{ $setting('footer_story_tagline', 'সময়ের সংবাদ, সত্যের সঙ্গে') }}
            </span>
            <nav aria-label="ফুটার আইনি লিংক" class="flex flex-wrap items-center gap-4">
                <a class="hover:text-white/75" href="{{ $internalUrl($setting('footer_privacy_url', '/pages/privacy-policy')) }}">প্রাইভেসি নীতি</a>
                <span class="text-white/30">|</span>
                <a class="hover:text-white/75" href="{{ $internalUrl($setting('footer_terms_url', '/pages/terms')) }}">ব্যবহার শর্তাবলি</a>
                <span class="text-white/30">|</span>
                <a class="hover:text-white/75" href="{{ $internalUrl($setting('footer_sitemap_url', '/sitemap.xml')) }}">সাইট ম্যাপ</a>
                <button type="button" data-back-to-top class="inline-flex items-center gap-2 rounded-full border border-white/30 px-4 py-2 font-semibold transition hover:bg-white hover:text-[#006a34]">
                    ↑ উপরের দিকে
                </button>
            </nav>
        </div>
    </div>

    @if ($showPublicationInfo)
        <div class="border-t border-[#008c44]/30 bg-gradient-to-r from-[#007a3b] via-[#008c44] to-[#006a34]">
            <div class="footer-shell py-2.5 text-center text-sm font-semibold leading-6 text-white md:text-base">
                {{ $setting('footer_publication_info', 'সম্পাদক ও প্রকাশক : শম্ভু চন্দ্র সরকার | প্রধান কার্যালয় : হোল্ডিং ৯৪, লেন ৪, ব্লক-এ, রোড ৬, বাইপাইল আশুলিয়া, সাভার ঢাকা-১৩৪৯ থেকে প্রকাশক কর্তৃক প্রকাশিত বিসমিল্লাহ প্রিন্টিং প্রেস, ১২৯ ফকিরাপুল, (১ম লেন নীচতলা), মতিঝিল ঢাকা-১০০০ থেকে মুদ্রিত। যোগাযোগ: ০৯৬১১৬৭৯৫২০, মোবাইল: ০১৫৫৪৭৩৩৩২২ | E-mail: dailysomoybayanno@gmail.com, বার্তা : somoybayanno@gmail.com | বিজ্ঞাপন: ০১৮৭০-৭০১৫২০') }}
            </div>
        </div>
        <div class="h-6 bg-[#e31e24]" aria-hidden="true"></div>
    @endif
</footer>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-back-to-top]').forEach((button) => {
                button.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
            });
        });
    </script>
@endonce
