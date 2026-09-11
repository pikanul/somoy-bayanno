<?php

namespace App\Http\Controllers;

use App\Services\PublicArchiveService;
use App\Services\PublicContentCache;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PublicArchiveController extends Controller
{
    public function index(Request $request, PublicArchiveService $archiveService)
    {
        return $this->render($request, $archiveService, [
            'title' => 'সংবাদ আর্কাইভ',
            'label' => null,
        ]);
    }

    public function year(Request $request, PublicArchiveService $archiveService, int $year)
    {
        $this->abortUnlessValidDate($year);

        return $this->render($request, $archiveService, $archiveService->yearWindow($year));
    }

    public function month(Request $request, PublicArchiveService $archiveService, int $year, int $month)
    {
        $this->abortUnlessValidDate($year, $month);

        return $this->render($request, $archiveService, $archiveService->monthWindow($year, $month));
    }

    public function date(Request $request, PublicArchiveService $archiveService, int $year, int $month, int $day)
    {
        $this->abortUnlessValidDate($year, $month, $day);

        return $this->render($request, $archiveService, $archiveService->dateWindow($year, $month, $day));
    }

    /** @param array<string, mixed> $window */
    private function render(Request $request, PublicArchiveService $archiveService, array $window)
    {
        $filters = $request->validate([
            'category' => ['nullable', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'author' => ['nullable', 'integer', Rule::exists('authors', 'id')->whereNull('deleted_at')],
        ]);

        $filters = array_merge($filters, $window);
        $filterOptions = app(PublicContentCache::class)->publicFilterOptions();

        return view('public.archive.index', [
            'articles' => $archiveService->articles($filters),
            'filters' => $filters,
            'categories' => $filterOptions['categories'],
            'authors' => $filterOptions['authors'],
        ]);
    }

    private function abortUnlessValidDate(int $year, ?int $month = null, ?int $day = null): void
    {
        if ($year < 1971 || $year > (int) now()->addYear()->format('Y')) {
            throw new NotFoundHttpException;
        }

        if ($month !== null && ($month < 1 || $month > 12)) {
            throw new NotFoundHttpException;
        }

        if ($day !== null && ! checkdate((int) $month, $day, $year)) {
            throw new NotFoundHttpException;
        }
    }
}
