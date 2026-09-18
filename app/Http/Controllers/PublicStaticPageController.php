<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Author;
use App\Models\Epaper;
use App\Models\Gallery;
use App\Models\LiveStream;
use App\Models\Video;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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
            'seo' => [
                'title' => "{$title} - দৈনিক সময় বায়ান্ন",
                'description' => "{$title} পাতার সংবাদ, ছবি ও ভিডিও।",
                'canonical' => route('static.show', $slug),
            ],
            'jsonLd' => [],
        ]);
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
