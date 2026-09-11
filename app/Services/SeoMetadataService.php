<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Author;
use Illuminate\Support\Str;

class SeoMetadataService
{
    /** @return array<string, string|null> */
    public function home(): array
    {
        return [
            'title' => 'দৈনিক সময় বায়ান্ন - The Daily Somoy Bayanno',
            'description' => 'দৈনিক সময় বায়ান্ন - সর্বশেষ জাতীয়, রাজনীতি, অর্থনীতি, আন্তর্জাতিক, খেলা, বিনোদন, প্রযুক্তি ও মতামত সংবাদ।',
            'canonical' => route('home'),
            'image' => null,
            'type' => 'website',
        ];
    }

    /** @return array<string, string|null> */
    public function article(Article $article): array
    {
        $description = $this->firstFilled([
            $article->seo_description,
            $article->social_description,
            $article->summary_bn,
            $this->plainText($article->body_bn, 160),
        ]);

        return [
            'title' => $this->firstFilled([
                $article->seo_title,
                $article->social_title,
                $article->headline_bn,
            ]),
            'description' => $description,
            'canonical' => $this->canonicalArticleUrl($article),
            'image' => $this->absoluteUrl($article->socialMedia?->url())
                ?? $this->absoluteUrl($article->social_image)
                ?? $this->absoluteUrl($article->featuredMedia?->url())
                ?? $this->absoluteUrl($article->featured_image_url),
            'type' => 'article',
        ];
    }

    /** @return array<string, string|null> */
    public function author(Author $author): array
    {
        return [
            'title' => $this->firstFilled([$author->seo_title, $author->name_bn, $author->name_en]),
            'description' => $this->firstFilled([
                $author->seo_description,
                $author->bio_bn,
                $author->bio_en,
                $author->designation ? $author->name_bn.' - '.$author->designation : null,
            ]),
            'canonical' => route('authors.show', $author->slug),
            'image' => $this->absoluteUrl($author->photo ? asset('storage/'.$author->photo) : null),
            'type' => 'profile',
        ];
    }

    /** @return array<string, mixed> */
    public function organizationJsonLd(): array
    {
        return $this->withoutEmpty([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'The Daily Somoy Bayanno',
            'alternateName' => 'দৈনিক সময় বায়ান্ন',
            'url' => route('home'),
        ]);
    }

    /** @return array<string, mixed> */
    public function articleJsonLd(Article $article): array
    {
        $metadata = $this->article($article);
        $type = in_array($article->type?->value, ['opinion', 'editorial', 'feature'], true) ? 'Article' : 'NewsArticle';

        return $this->withoutEmpty([
            '@context' => 'https://schema.org',
            '@type' => $type,
            'headline' => $article->headline_bn,
            'description' => $metadata['description'],
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => ($article->updated_content_at ?? $article->updated_at)?->toIso8601String(),
            'mainEntityOfPage' => $metadata['canonical'],
            'image' => $metadata['image'] ? [$metadata['image']] : null,
            'author' => $this->articleAuthorsJsonLd($article),
            'publisher' => $this->organizationJsonLd(),
        ]);
    }

    /** @return array<string, mixed> */
    public function authorJsonLd(Author $author): array
    {
        return $this->withoutEmpty([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $author->name_bn,
            'alternateName' => $author->name_en,
            'description' => $author->bio_bn ?: $author->bio_en,
            'jobTitle' => $author->designation,
            'url' => route('authors.show', $author->slug),
            'image' => $this->absoluteUrl($author->photo ? asset('storage/'.$author->photo) : null),
            'sameAs' => array_values(array_filter([
                $author->website_url,
                $author->facebook_url,
                $author->x_url,
                $author->linkedin_url,
            ])),
        ]);
    }

    /** @return array<string, mixed> */
    public function breadcrumbJsonLd(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)
                ->values()
                ->map(fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ])
                ->all(),
        ];
    }

    public function sitemapArticleUrl(Article $article): string
    {
        return route('articles.show', $article->slug);
    }

    public function canonicalArticleUrl(Article $article): string
    {
        $canonicalUrl = $this->absoluteUrl($article->canonical_url);

        if ($canonicalUrl !== null && Str::startsWith($canonicalUrl, url('/'))) {
            return $canonicalUrl;
        }

        return route('articles.show', $article->slug);
    }

    public function absoluteUrl(?string $url): ?string
    {
        if (! filled($url)) {
            return null;
        }

        return Str::startsWith($url, ['http://', 'https://']) ? $url : url($url);
    }

    /** @param array<int, mixed> $values */
    private function firstFilled(array $values): ?string
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function plainText(?string $html, int $limit): ?string
    {
        if (! filled($html)) {
            return null;
        }

        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? ''), $limit, '');
    }

    /** @return array<int, array<string, mixed>> */
    private function articleAuthorsJsonLd(Article $article): array
    {
        if ($article->authors->isNotEmpty()) {
            return $article->authors
                ->map(fn (Author $author): array => $this->withoutEmpty([
                    '@type' => 'Person',
                    'name' => $author->name_bn,
                    'url' => route('authors.show', $author->slug),
                ]))
                ->values()
                ->all();
        }

        return [[
            '@type' => 'Person',
            'name' => $article->reporter_name ?: 'দৈনিক সময় বায়ান্ন',
        ]];
    }

    /** @param array<string, mixed> $data */
    private function withoutEmpty(array $data): array
    {
        return array_filter($data, fn (mixed $value): bool => is_array($value) ? $value !== [] : filled($value));
    }
}
