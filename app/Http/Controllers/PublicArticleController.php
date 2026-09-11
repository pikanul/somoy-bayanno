<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\ArticleMetricsService;
use App\Services\PublicContentCache;
use App\Services\SeoMetadataService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

class PublicArticleController extends Controller
{
    public function show(string $slug, SeoMetadataService $seo, PublicContentCache $publicCache, ArticleMetricsService $metrics): View
    {
        $article = Cache::remember($publicCache->articleKey($slug), $publicCache->ttl(), fn (): Article => Article::query()
            ->with([
                'primaryCategory',
                'authors',
                'tags',
                'topics',
                'featuredMedia',
                'socialMedia',
            ])
            ->publiclyVisible()
            ->where('slug', $slug)
            ->firstOrFail());

        $metrics->trackView($article);

        return view('public.articles.show', [
            'article' => $article,
            'seo' => $seo->article($article),
            'jsonLd' => [
                $seo->articleJsonLd($article),
                $seo->breadcrumbJsonLd(array_values(array_filter([
                    ['name' => 'হোম', 'url' => route('home')],
                    $article->primaryCategory ? ['name' => $article->primaryCategory->name_bn, 'url' => route('home')] : null,
                    ['name' => $article->headline_bn, 'url' => $seo->canonicalArticleUrl($article)],
                ]))),
            ],
        ]);
    }
}
