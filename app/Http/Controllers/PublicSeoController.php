<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\SeoMetadataService;
use Illuminate\Http\Response;

class PublicSeoController extends Controller
{
    public function robots(): Response
    {
        return response("User-agent: *\nDisallow: /newsroom\nSitemap: ".url('/sitemap.xml')."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function sitemap(SeoMetadataService $seo): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'lastmod' => now()->toAtomString()],
        ])->merge(
            Article::query()
                ->publiclyVisible()
                ->latest('published_at')
                ->limit(1000)
                ->get()
                ->map(fn (Article $article): array => [
                    'loc' => $seo->sitemapArticleUrl($article),
                    'lastmod' => ($article->updated_content_at ?? $article->updated_at ?? $article->published_at)?->toAtomString(),
                ])
        );

        return $this->xml('public.seo.sitemap', ['urls' => $urls]);
    }

    public function newsSitemap(): Response
    {
        return $this->xml('public.seo.sitemap-news', ['urls' => collect()]);
    }

    public function imageSitemap(): Response
    {
        return $this->xml('public.seo.sitemap-image', ['urls' => collect()]);
    }

    public function videoSitemap(): Response
    {
        return $this->xml('public.seo.sitemap-video', ['urls' => collect()]);
    }

    /** @param array<string, mixed> $data */
    private function xml(string $view, array $data): Response
    {
        return response(view($view, $data), 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
