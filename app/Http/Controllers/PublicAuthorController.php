<?php

namespace App\Http\Controllers;

use App\Enums\AuthorStatus;
use App\Http\Resources\PublicAuthorResource;
use App\Models\Author;
use App\Services\SeoMetadataService;
use Illuminate\View\View;

class PublicAuthorController extends Controller
{
    public function show(string $slug, SeoMetadataService $seo): View
    {
        $author = Author::query()
            ->where('slug', $slug)
            ->where('status', AuthorStatus::Active)
            ->firstOrFail();

        return view('authors.show', [
            'author' => PublicAuthorResource::make($author)->resolve(),
            'seo' => $seo->author($author),
            'jsonLd' => [
                $seo->authorJsonLd($author),
                $seo->breadcrumbJsonLd([
                    ['name' => 'হোম', 'url' => route('home')],
                    ['name' => $author->name_bn, 'url' => route('authors.show', $author->slug)],
                ]),
            ],
        ]);
    }
}
