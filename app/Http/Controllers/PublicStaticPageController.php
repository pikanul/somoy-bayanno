<?php

namespace App\Http\Controllers;

use App\Models\AdvertisementInquiry;
use App\Models\Article;
use App\Models\Author;
use App\Models\CareerApplication;
use App\Models\CareerVacancy;
use App\Models\ContactMessage;
use App\Models\Epaper;
use App\Models\Gallery;
use App\Models\LiveStream;
use App\Models\SiteSetting;
use App\Models\Video;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublicStaticPageController extends Controller
{
    /** @var array<string, string> */
    private const PAGE_TITLES = [
        'latest' => 'সর্বশেষ',
        'live' => 'Live',
        'national' => 'জাতীয়',
        'politics' => 'রাজনীতি',
        'international' => 'আন্তর্জাতিক',
        'economy' => 'অর্থনীতি',
        'sports' => 'খেলা',
        'entertainment' => 'বিনোদন',
        'technology' => 'প্রযুক্তি',
        'lifestyle' => 'জীবনযাপন',
        'opinion' => 'মতামত',
        'videos' => 'ভিডিও',
        'photos' => 'ছবিঘর',
        'writers' => 'লেখক',
        'correspondents' => 'প্রতিনিধি তালিকা',
        'more' => 'আরও',
        'epaper' => 'ই-পেপার',
        'advertise' => 'বিজ্ঞাপন দিন',
        'contact' => 'যোগাযোগ',
        'subscribe' => 'সাবস্ক্রিপশন',
        'newsletter' => 'নিউজলেটার',
        'mobile-app' => 'মোবাইল অ্যাপ',
        'about' => 'আমাদের সম্পর্কে',
        'editorial-policy' => 'সম্পাদকীয় নীতি',
        'privacy-policy' => 'গোপনীয়তা নীতি',
        'terms' => 'ব্যবহারের নীতিমালা',
        'journalism-policy' => 'সাংবাদিক নীতিমালা',
        'writer-guidelines' => 'লেখক নির্দেশিকা',
        'career' => 'ক্যারিয়ার',
    ];

    public function show(string $slug): View
    {
        $title = self::PAGE_TITLES[$slug] ?? Str::headline($slug);
        $articles = $this->articles($slug);
        $videos = $slug === 'videos' ? $this->videos() : collect();
        $galleries = $slug === 'photos' ? $this->galleries() : collect();
        $correspondents = $slug === 'correspondents' ? $this->correspondents() : collect();
        $liveStream = $slug === 'live' ? $this->liveStream() : null;
        $epapers = $slug === 'epaper' ? $this->epapers() : collect();
        $selectedEpaper = $slug === 'epaper' ? $epapers->first() : null;

        $seo = [
            'title' => "{$title} - দৈনিক সময় বায়ান্ন",
            'description' => "{$title} পাতার সংবাদ, ছবি ও ভিডিও।",
            'canonical' => route('static.show', $slug),
        ];
        $jsonLd = [];

        if ($slug === 'advertise') {
            $seo = [
                'title' => 'বিজ্ঞাপন দিন - দৈনিক সময় বায়ান্ন',
                'description' => 'দৈনিক সময় বায়ান্নে ব্যানার, মোবাইল, ভিডিও ও Sponsored Content বিজ্ঞাপনের জন্য যোগাযোগ করুন।',
                'canonical' => route('static.show', 'advertise'),
            ];
            $jsonLd = [[
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'হোম',
                        'item' => route('home'),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => $title,
                        'item' => route('static.show', 'advertise'),
                    ],
                ],
            ]];
        }

        if ($slug === 'contact') {
            $seo = [
                'title' => 'যোগাযোগ করুন | দৈনিক সময় বায়ান্ন',
                'description' => 'দৈনিক সময় বায়ান্ন-এর সঙ্গে যোগাযোগ করুন। সংবাদ, মতামত, বিজ্ঞাপন, সাবস্ক্রিপশন ও অন্যান্য বিষয়ে আমাদের সঙ্গে যোগাযোগ করুন।',
                'canonical' => route('static.show', 'contact'),
            ];
            $jsonLd = [[
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'হোম',
                        'item' => route('home'),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => $title,
                        'item' => route('static.show', 'contact'),
                    ],
                ],
            ]];
        }

        if ($slug === 'career') {
            $seo = [
                'title' => 'ক্যারিয়ার | দৈনিক সময় বায়ান্ন',
                'description' => 'দৈনিক সময় বায়ান্ন-এর ক্যারিয়ার পেজ। বর্তমান চাকরির সুযোগ, নিয়োগ বিজ্ঞপ্তি, যোগ্যতা, আবেদন প্রক্রিয়া এবং ক্যারিয়ার সম্পর্কিত তথ্য দেখুন।',
                'canonical' => route('static.show', 'career'),
            ];
            $jsonLd = [[
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'হোম',
                        'item' => route('home'),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => $title,
                        'item' => route('static.show', 'career'),
                    ],
                ],
            ]];
        }

        return view('public.static.show', [
            'title' => $title,
            'slug' => $slug,
            'articles' => $articles,
            'videos' => $videos,
            'galleries' => $galleries,
            'correspondents' => $correspondents,
            'liveStream' => $liveStream,
            'epapers' => $epapers,
            'selectedEpaper' => $selectedEpaper,
            'advertisementContact' => $slug === 'advertise' ? $this->advertisementContact() : [],
            'advertisementTypes' => $slug === 'advertise' ? $this->advertisementTypes() : [],
            'advertisementPlacements' => $slug === 'advertise' ? $this->advertisementPlacements() : [],
            'contactData' => $slug === 'contact' ? $this->contactData() : [],
            'contactMessageTypes' => $slug === 'contact' ? $this->contactMessageTypes() : [],
            'careerVacancies' => $slug === 'career' ? $this->careerVacancies() : collect(),
            'careerBenefits' => $slug === 'career' ? $this->careerBenefits() : [],
            'careerSteps' => $slug === 'career' ? $this->careerSteps() : [],
            'seo' => $seo,
            'jsonLd' => $jsonLd,
        ]);
    }

    public function storeAdvertisementInquiry(Request $request): RedirectResponse
    {
        abort_unless($request->route('slug') === 'advertise', 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'company_name' => ['nullable', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^[+0-9\s().-]{7,40}$/'],
            'email' => ['required', 'email:rfc', 'max:160'],
            'advertisement_type' => ['required', Rule::in(array_keys($this->advertisementTypes()))],
            'placement' => ['nullable', Rule::in(array_keys($this->advertisementPlacements()))],
            'budget' => ['nullable', 'string', 'max:120'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'landing_page_url' => ['nullable', 'url', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'website' => ['prohibited'],
        ]);

        unset($validated['website']);

        AdvertisementInquiry::query()->create([
            ...$validated,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
        ]);

        return redirect()
            ->route('static.show', 'advertise')
            ->with('advertisement_status', 'আপনার বিজ্ঞাপনের অনুরোধ গ্রহণ করা হয়েছে। আমাদের বিজ্ঞাপন বিভাগ দ্রুত যোগাযোগ করবে।');
    }

    public function storeContactMessage(Request $request): RedirectResponse
    {
        abort_unless($request->route('slug') === 'contact', 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[+0-9\s().-]{7,40}$/'],
            'subject' => ['required', 'string', 'max:180'],
            'message_type' => ['required', Rule::in(array_keys($this->contactMessageTypes()))],
            'message' => ['required', 'string', 'max:3000'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'website' => ['prohibited'],
        ]);

        unset($validated['website']);

        ContactMessage::query()->create([
            ...$validated,
            'status' => 'new',
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
        ]);

        return redirect()
            ->route('static.show', 'contact')
            ->with('contact_status', 'আপনার বার্তাটি সফলভাবে পাঠানো হয়েছে। ধন্যবাদ। আমাদের সংশ্লিষ্ট বিভাগ প্রয়োজন অনুযায়ী আপনার সঙ্গে যোগাযোগ করবে।');
    }

    public function careerVacancy(string $slug): View
    {
        $vacancy = $this->findCareerVacancy($slug);

        abort_unless($vacancy, 404);

        $title = $this->careerValue($vacancy, 'title');
        $deadline = $this->careerValue($vacancy, 'application_deadline');

        return view('public.static.show', [
            'title' => $title,
            'slug' => 'career-detail',
            'articles' => collect(),
            'videos' => collect(),
            'galleries' => collect(),
            'correspondents' => collect(),
            'liveStream' => null,
            'epapers' => collect(),
            'selectedEpaper' => null,
            'careerVacancy' => $vacancy,
            'seo' => [
                'title' => "{$title} | ক্যারিয়ার | দৈনিক সময় বায়ান্ন",
                'description' => "{$title} পদে দৈনিক সময় বায়ান্ন-এর নিয়োগ বিজ্ঞপ্তি, যোগ্যতা ও আবেদন প্রক্রিয়া দেখুন।",
                'canonical' => route('career.vacancies.show', $this->careerValue($vacancy, 'slug')),
            ],
            'jsonLd' => [[
                '@context' => 'https://schema.org',
                '@type' => 'JobPosting',
                'title' => $title,
                'datePosted' => optional($this->careerValue($vacancy, 'published_at'))->toDateString() ?: now()->toDateString(),
                'validThrough' => $deadline ? $deadline->endOfDay()->toIso8601String() : null,
                'employmentType' => $this->careerValue($vacancy, 'employment_type'),
                'hiringOrganization' => [
                    '@type' => 'Organization',
                    'name' => 'দৈনিক সময় বায়ান্ন',
                ],
                'jobLocation' => [
                    '@type' => 'Place',
                    'address' => $this->careerValue($vacancy, 'location'),
                ],
            ]],
        ]);
    }

    public function storeCareerApplication(Request $request): RedirectResponse
    {
        abort_unless($request->route('slug') === 'career', 404);

        $validated = $request->validate([
            'career_vacancy_id' => ['nullable', 'integer', 'exists:career_vacancies,id'],
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:160'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^[+0-9\s().-]{7,40}$/'],
            'position' => ['required', 'string', 'max:180'],
            'location' => ['nullable', 'string', 'max:160'],
            'cover_letter' => ['required', 'string', 'max:3000'],
            'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'consent' => ['accepted'],
            'website' => ['prohibited'],
        ]);

        unset($validated['cv'], $validated['consent'], $validated['website']);

        $cv = $request->file('cv');
        $filename = now()->format('YmdHis').'-'.Str::random(20).'.'.$cv->getClientOriginalExtension();
        $cvPath = $cv->storeAs('career-applications', $filename, 'local');

        CareerApplication::query()->create([
            ...$validated,
            'cv_path' => $cvPath,
            'status' => 'new',
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
        ]);

        return redirect()
            ->route('static.show', 'career')
            ->with('career_status', 'আপনার আবেদন সফলভাবে গ্রহণ করা হয়েছে। শর্টলিস্ট হলে আমাদের টিম যোগাযোগ করবে।');
    }

    /** @return array<string, string> */
    private function advertisementTypes(): array
    {
        return [
            'top-banner' => 'Top Banner',
            'desktop-banner' => 'Desktop Banner',
            'sidebar-banner' => 'Sidebar Banner',
            'mobile-banner' => 'Mobile Banner',
            'sponsored-content' => 'Sponsored Content',
            'video-advertisement' => 'Video Advertisement',
            'brand-campaign' => 'ব্র্যান্ড প্রচার',
        ];
    }

    /** @return array<string, string> */
    private function advertisementPlacements(): array
    {
        return [
            'home' => 'হোম পেজ',
            'category' => 'ক্যাটাগরি পেজ',
            'article' => 'সংবাদ বিস্তারিত পেজ',
            'mobile' => 'মোবাইল ভিউ',
            'video' => 'ভিডিও সেকশন',
            'custom' => 'কাস্টম অবস্থান',
        ];
    }

    /** @return array<string, string> */
    private function advertisementContact(): array
    {
        $publicationInfo = SiteSetting::publicValue('footer_publication_info', 'যোগাযোগ: ০৯৬১১৬৭৯৫২০, মোবাইল: ০১৫৫৪৭৩৩৩২২ | E-mail: dailysomoybayanno@gmail.com | বিজ্ঞাপন: ০১৮৭০-৭০১৫২০');

        return [
            'publication_info' => $publicationInfo,
            'phone' => Str::match('/বিজ্ঞাপন:\s*([^|]+)/u', $publicationInfo) ?: Str::match('/মোবাইল:\s*([^|:]+)/u', $publicationInfo),
            'email' => Str::match('/E-mail:\s*([^|,]+)/u', $publicationInfo) ?: 'dailysomoybayanno@gmail.com',
        ];
    }

    /** @return array<string, string> */
    private function contactMessageTypes(): array
    {
        return [
            'general' => 'সাধারণ যোগাযোগ',
            'news' => 'সংবাদ সংক্রান্ত',
            'advertisement' => 'বিজ্ঞাপন',
            'complaint' => 'অভিযোগ',
            'suggestion' => 'পরামর্শ',
            'other' => 'অন্যান্য',
        ];
    }

    /** @return array<string, mixed> */
    private function contactData(): array
    {
        $settingKeys = [
            'footer_publication_info',
            'footer_social_facebook_url',
            'footer_social_x_url',
            'footer_social_youtube_url',
            'footer_social_instagram_url',
            'footer_social_linkedin_url',
        ];
        $settings = SiteSetting::publicValues($settingKeys);
        $publicationInfo = (string) ($settings->get('footer_publication_info') ?: 'দৈনিক সময় বায়ান্ন, ঢাকা, বাংলাদেশ');
        $primaryEmail = Str::match('/E-mail:\s*([^|,]+)/u', $publicationInfo) ?: 'info@somoybayanno.com';
        $newsEmail = Str::match('/বার্তা\s*:\s*([^|,]+)/u', $publicationInfo) ?: 'news@somoybayanno.com';
        $phone = Str::match('/যোগাযোগ:\s*([^,|:]+)/u', $publicationInfo) ?: Str::match('/মোবাইল:\s*([^|:]+)/u', $publicationInfo) ?: '+880 1712 345678';
        $office = Str::contains($publicationInfo, 'প্রধান কার্যালয়')
            ? Str::match('/প্রধান কার্যালয়\s*:\s*([^|:]+)/u', $publicationInfo)
            : 'দৈনিক সময় বায়ান্ন, ঢাকা, বাংলাদেশ';

        return [
            'office' => $office ?: 'দৈনিক সময় বায়ান্ন, ঢাকা, বাংলাদেশ',
            'phone' => $phone,
            'email' => $primaryEmail,
            'news_email' => $newsEmail,
            'map_url' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($office ?: 'Dhaka Bangladesh'),
            'departments' => [
                ['title' => 'নিউজরুম', 'email' => $newsEmail, 'note' => 'সংবাদ পাঠানো ও জরুরি সংবাদ'],
                ['title' => 'সম্পাদকীয়', 'email' => 'editorial@somoybayanno.com', 'note' => 'সম্পাদকীয় বিষয় ও মতামত'],
                ['title' => 'বিজ্ঞাপন', 'email' => 'advertise@somoybayanno.com', 'note' => 'বিজ্ঞাপন ও ব্র্যান্ড প্রচার'],
                ['title' => 'প্রযুক্তি সহায়তা', 'email' => 'support@somoybayanno.com', 'note' => 'ওয়েবসাইট ও প্রযুক্তিগত সহায়তা'],
                ['title' => 'সাবস্ক্রিপশন', 'email' => 'subscription@somoybayanno.com', 'note' => 'সাবস্ক্রিপশন ও ডেলিভারি'],
            ],
            'socials' => [
                ['label' => 'Facebook', 'mark' => 'f', 'url' => $settings->get('footer_social_facebook_url')],
                ['label' => 'X', 'mark' => 'X', 'url' => $settings->get('footer_social_x_url')],
                ['label' => 'YouTube', 'mark' => '▶', 'url' => $settings->get('footer_social_youtube_url')],
                ['label' => 'Instagram', 'mark' => '◎', 'url' => $settings->get('footer_social_instagram_url')],
                ['label' => 'LinkedIn', 'mark' => 'in', 'url' => $settings->get('footer_social_linkedin_url')],
            ],
        ];
    }

    /** @return Collection<int, CareerVacancy|array<string, mixed>> */
    private function careerVacancies(): Collection
    {
        if (Schema::hasTable('career_vacancies') && CareerVacancy::query()->exists()) {
            return CareerVacancy::query()
                ->whereIn('status', ['open', 'closing_soon'])
                ->where(function (Builder $query): void {
                    $query->whereNull('published_at')->orWhere('published_at', '<=', now());
                })
                ->where(function (Builder $query): void {
                    $query->whereNull('application_deadline')->orWhere('application_deadline', '>=', now()->toDateString());
                })
                ->orderBy('sort_order')
                ->orderBy('application_deadline')
                ->get();
        }

        return collect($this->sampleCareerVacancies());
    }

    private function findCareerVacancy(string $slug): CareerVacancy|array|null
    {
        if (Schema::hasTable('career_vacancies')) {
            $vacancy = CareerVacancy::query()
                ->where('slug', $slug)
                ->where('status', '!=', 'draft')
                ->first();

            if ($vacancy) {
                return $vacancy;
            }
        }

        return collect($this->sampleCareerVacancies())->firstWhere('slug', $slug);
    }

    private function careerValue(CareerVacancy|array $vacancy, string $key): mixed
    {
        return $vacancy instanceof CareerVacancy ? $vacancy->{$key} : ($vacancy[$key] ?? null);
    }

    /** @return array<int, array<string, mixed>> */
    private function sampleCareerVacancies(): array
    {
        $shared = [
            'published_at' => now()->subDays(3),
            'status' => 'open',
            'application_email' => 'career@somoybayanno.com',
        ];

        return [
            [
                ...$shared,
                'title' => 'নিউজ রিপোর্টার',
                'slug' => 'news-reporter',
                'department' => 'নিউজ বিভাগ',
                'location' => 'ঢাকা (অফিস)',
                'employment_type' => 'Full-time',
                'application_deadline' => now()->setDate(2026, 9, 30),
                'summary' => 'মাঠ পর্যায়ের সংবাদ সংগ্রহ, যাচাই এবং দ্রুত রিপোর্টিংয়ে আগ্রহী প্রার্থীদের জন্য।',
                'responsibilities' => 'দৈনিক সংবাদ সংগ্রহ, সূত্র যাচাই, সাক্ষাৎকার গ্রহণ এবং সম্পাদকীয় নির্দেশনা অনুযায়ী কপি জমা দেওয়া।',
                'requirements' => 'বাংলা লেখায় দক্ষতা, সংবাদমূল্য বোঝার ক্ষমতা এবং মাঠে কাজ করার মানসিকতা।',
                'qualifications' => 'সাংবাদিকতা/বাংলা/গণযোগাযোগ বিষয়ে স্নাতক অগ্রাধিকার।',
                'experience' => '১-২ বছর অভিজ্ঞতা অগ্রাধিকার।',
                'skills' => 'রিপোর্টিং, ফ্যাক্ট-চেকিং, মোবাইল জার্নালিজম।',
                'salary_benefits' => 'আলোচনা সাপেক্ষে।',
                'application_instructions' => 'CV ও সংক্ষিপ্ত cover letter পাঠান।',
            ],
            [
                ...$shared,
                'title' => 'সিনিয়র রিপোর্টার',
                'slug' => 'senior-reporter',
                'department' => 'নিউজ বিভাগ',
                'location' => 'ঢাকা (অফিস)',
                'employment_type' => 'Full-time',
                'application_deadline' => now()->setDate(2026, 10, 5),
                'summary' => 'জাতীয় ও অনুসন্ধানী প্রতিবেদনে অভিজ্ঞ সাংবাদিকদের জন্য।',
                'responsibilities' => 'বিশেষ প্রতিবেদন পরিকল্পনা, সূত্র ব্যবস্থাপনা এবং জুনিয়র রিপোর্টারদের সহায়তা।',
                'requirements' => 'দৃঢ় সংবাদবোধ, নৈতিকতা এবং deadline pressure সামলানোর দক্ষতা।',
                'qualifications' => 'সংশ্লিষ্ট বিষয়ে স্নাতক বা সমমান।',
                'experience' => '৩-৫ বছর রিপোর্টিং অভিজ্ঞতা।',
                'skills' => 'ইনভেস্টিগেটিভ রিপোর্টিং, কপি এডিটিং, সোর্স ডেভেলপমেন্ট।',
                'salary_benefits' => 'প্রতিযোগিতামূলক প্যাকেজ।',
                'application_instructions' => 'প্রকাশিত কাজের লিংকসহ আবেদন করুন।',
            ],
            [
                ...$shared,
                'title' => 'ডিজিটাল কনটেন্ট প্রডিউসার',
                'slug' => 'digital-content-producer',
                'department' => 'ডিজিটাল বিভাগ',
                'location' => 'ঢাকা (অফিস)',
                'employment_type' => 'Full-time',
                'application_deadline' => now()->setDate(2026, 10, 10),
                'summary' => 'ওয়েব, সোশ্যাল ও ভিডিও প্ল্যাটফর্মের জন্য দ্রুত কনটেন্ট তৈরির সুযোগ।',
                'responsibilities' => 'শিরোনাম, থাম্বনেইল আইডিয়া, সোশ্যাল কপি এবং ভিডিও স্ক্রিপ্ট প্রস্তুত।',
                'requirements' => 'ডিজিটাল নিউজ ট্রেন্ড ও audience behavior বোঝার ক্ষমতা।',
                'qualifications' => 'গণযোগাযোগ/মিডিয়া/সংশ্লিষ্ট বিষয়ে পড়াশোনা অগ্রাধিকার।',
                'experience' => '১-৩ বছর ডিজিটাল কনটেন্ট অভিজ্ঞতা।',
                'skills' => 'SEO, social copy, basic video workflow।',
                'salary_benefits' => 'আলোচনা সাপেক্ষে।',
                'application_instructions' => 'Portfolio URL সহ আবেদন করুন।',
            ],
            [
                ...$shared,
                'title' => 'অফিস সহকারী',
                'slug' => 'office-assistant',
                'department' => 'প্রশাসন বিভাগ',
                'location' => 'ঢাকা (অফিস)',
                'employment_type' => 'Full-time',
                'application_deadline' => now()->setDate(2026, 10, 15),
                'summary' => 'অফিস প্রশাসন, ডকুমেন্ট ও দৈনন্দিন কাজে সহায়তার দায়িত্ব।',
                'responsibilities' => 'ডকুমেন্ট সংরক্ষণ, অফিস সহায়তা এবং প্রশাসনিক কাজ সমন্বয়।',
                'requirements' => 'সময়ানুবর্তিতা, যোগাযোগ দক্ষতা এবং MS Office-এর মৌলিক জ্ঞান।',
                'qualifications' => 'এইচএসসি/সমমান বা ঊর্ধ্ব।',
                'experience' => 'অভিজ্ঞতা থাকলে অগ্রাধিকার।',
                'skills' => 'Office support, documentation, communication।',
                'salary_benefits' => 'আলোচনা সাপেক্ষে।',
                'application_instructions' => 'CV জমা দিন।',
            ],
        ];
    }

    /** @return array<int, array<string, string>> */
    private function careerBenefits(): array
    {
        return [
            ['title' => 'পেশাগত উন্নয়নের সুযোগ', 'text' => 'নিয়মিত প্রশিক্ষণ ও দক্ষতা উন্নয়ন', 'icon' => '▧'],
            ['title' => 'সৃজনশীল পরিবেশ', 'text' => 'মুক্ত চিন্তা ও কাজের স্বাধীনতা', 'icon' => '✤'],
            ['title' => 'প্রতিভাবান টিম', 'text' => 'অভিজ্ঞ ও সহায়ক সহকর্মী', 'icon' => '♙'],
            ['title' => 'আকর্ষণীয় বেতন ও সুবিধা', 'text' => 'প্রতিযোগিতামূলক প্যাকেজ', 'icon' => '◈'],
            ['title' => 'সমাজের জন্য কাজ', 'text' => 'সত্য ও দায়িত্বশীল সাংবাদিকতা', 'icon' => '✦'],
        ];
    }

    /** @return array<int, array<string, string>> */
    private function careerSteps(): array
    {
        return [
            ['number' => '১', 'title' => 'বিজ্ঞপ্তি পড়ুন', 'text' => 'পদের যোগ্যতা ও বিস্তারিত তথ্য দেখুন', 'icon' => '□'],
            ['number' => '২', 'title' => 'অনলাইনে আবেদন করুন', 'text' => 'নির্ধারিত ফর্ম পূরণ করে আবেদন জমা দিন', 'icon' => '▤'],
            ['number' => '৩', 'title' => 'শর্টলিস্ট ও সাক্ষাৎকার', 'text' => 'যোগ্য প্রার্থীদের সঙ্গে যোগাযোগ করা হবে', 'icon' => '♙'],
            ['number' => '৪', 'title' => 'চাকরিতে যোগদান', 'text' => 'নির্বাচিত প্রার্থীরা থেকে যোগ দিন আমাদের টিমে', 'icon' => '✓'],
        ];
    }

    public function epaper(?string $date = null, ?int $page = null): View
    {
        $epapers = $this->epapers();
        $selectedEpaper = $date
            ? $epapers->first(fn (Epaper $epaper): bool => $epaper->issue_date?->format('Y-m-d') === $date)
            : $epapers->first();

        abort_if($date && ! $selectedEpaper, 404);

        $issuePages = $selectedEpaper?->readerPages() ?? [];
        $selectedPageNumber = $page ?: 1;
        $selectedPage = $selectedEpaper?->readerPage($selectedPageNumber) ?? ($issuePages[0] ?? null);
        $selectedPageNumber = (int) ($selectedPage['page_number'] ?? 1);
        $title = self::PAGE_TITLES['epaper'];
        $description = $selectedEpaper
            ? $selectedEpaper->issue_date?->format('d M Y').' সংখ্যার ই-পেপার পড়ুন।'
            : 'দৈনিক সময় বায়ান্ন ই-পেপার পড়ুন।';

        return view('public.static.show', [
            'title' => $title,
            'slug' => 'epaper',
            'articles' => collect(),
            'videos' => collect(),
            'galleries' => collect(),
            'correspondents' => collect(),
            'liveStream' => null,
            'epapers' => $epapers,
            'selectedEpaper' => $selectedEpaper,
            'issuePages' => $issuePages,
            'selectedPage' => $selectedPage,
            'selectedPageNumber' => $selectedPageNumber,
            'seo' => [
                'title' => "{$title} - দৈনিক সময় বায়ান্ন",
                'description' => $description,
                'canonical' => $selectedEpaper ? route('epaper.show', [$selectedEpaper->issue_date?->format('Y-m-d'), $selectedPageNumber]) : route('static.show', 'epaper'),
            ],
            'jsonLd' => [],
        ]);
    }

    private function liveStream(): ?LiveStream
    {
        if (! Schema::hasTable('live_streams')) {
            return null;
        }

        return LiveStream::query()
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->orderBy('sort_order')
            ->latest()
            ->first();
    }

    /** @return Collection<int, Epaper> */
    private function epapers(): Collection
    {
        if (! Schema::hasTable('epapers')) {
            return collect();
        }

        return Epaper::query()
            ->published()
            ->orderByDesc('issue_date')
            ->orderBy('sort_order')
            ->limit(60)
            ->get();
    }

    /** @return Collection<int, Author> */
    private function correspondents(): Collection
    {
        if (! Schema::hasTable('authors')) {
            return collect();
        }

        return Author::query()
            ->where('status', 'active')
            ->orderBy('organization_level')
            ->orderBy('sort_order')
            ->orderBy('name_bn')
            ->get();
    }

    /** @return Collection<int, Article> */
    private function articles(string $slug): Collection
    {
        if (! Schema::hasTable('articles')) {
            return collect();
        }

        $articles = Article::query()
            ->with(['primaryCategory', 'featuredMedia'])
            ->publiclyVisible()
            ->when($slug !== 'latest', function (Builder $query) use ($slug): void {
                $query->whereHas('primaryCategory', fn (Builder $query): Builder => $query->where('slug', $slug));
            })
            ->latest('published_at')
            ->limit(18)
            ->get();

        if ($articles->count() >= 6 || $slug === 'latest') {
            return $articles;
        }

        $supplementalArticles = Article::query()
            ->with(['primaryCategory', 'featuredMedia'])
            ->publiclyVisible()
            ->whereNotIn('id', $articles->pluck('id'))
            ->latest('published_at')
            ->limit(6 - $articles->count())
            ->get();

        return $articles->merge($supplementalArticles);
    }

    /** @return Collection<int, Video> */
    private function videos(): Collection
    {
        if (! Schema::hasTable('videos')) {
            return collect();
        }

        return Video::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->limit(18)
            ->get();
    }

    /** @return Collection<int, Gallery> */
    private function galleries(): Collection
    {
        if (! Schema::hasTable('galleries')) {
            return collect();
        }

        return Gallery::query()
            ->with(['coverImage'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->limit(18)
            ->get();
    }
}
