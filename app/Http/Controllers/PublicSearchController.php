<?php

namespace App\Http\Controllers;

use App\Services\PublicArticleSearchService;
use App\Services\PublicContentCache;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicSearchController extends Controller
{
    public function __invoke(Request $request, PublicArticleSearchService $searchService, PublicContentCache $publicCache)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'min:2', 'max:120'],
            'category' => ['nullable', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'author' => ['nullable', 'integer', Rule::exists('authors', 'id')->whereNull('deleted_at')],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'sort' => ['nullable', Rule::in(['relevance', 'latest'])],
        ]);

        $filters['q'] = trim((string) ($filters['q'] ?? ''));
        $filters['sort'] = $filters['sort'] ?? 'relevance';

        $filterOptions = $publicCache->publicFilterOptions();

        return view('public.search.index', [
            'articles' => $searchService->search($filters),
            'filters' => $filters,
            'categories' => $filterOptions['categories'],
            'authors' => $filterOptions['authors'],
        ]);
    }
}
