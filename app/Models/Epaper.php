<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Epaper extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'title_bn',
        'issue_date',
        'edition',
        'scan_path',
        'pdf_path',
        'external_url',
        'pages',
        'is_published',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'pages' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scanUrl(): ?string
    {
        return $this->scan_path ? asset('storage/'.$this->scan_path) : null;
    }

    public function pdfUrl(): ?string
    {
        return $this->pdf_path ? asset('storage/'.$this->pdf_path) : null;
    }

    public function externalPreviewUrl(): ?string
    {
        if (! $this->external_url) {
            return null;
        }

        $googleDriveFileId = $this->googleDriveFileId($this->external_url);

        if ($googleDriveFileId) {
            return "https://drive.google.com/file/d/{$googleDriveFileId}/preview";
        }

        return $this->external_url;
    }

    public function externalDownloadUrl(): ?string
    {
        if (! $this->external_url) {
            return null;
        }

        $googleDriveFileId = $this->googleDriveFileId($this->external_url);

        if ($googleDriveFileId) {
            return "https://drive.google.com/uc?export=download&id={$googleDriveFileId}";
        }

        return $this->external_url;
    }

    public function externalImageUrl(): ?string
    {
        return $this->externalImageUrlFor($this->external_url);
    }

    /** @return array<int, array{page_number: int, title: string, scan_url: ?string, pdf_url: ?string, external_url: ?string, external_preview_url: ?string, external_download_url: ?string, external_image_url: ?string}> */
    public function readerPages(): array
    {
        $pages = collect($this->pages ?? [])
            ->map(function (array $page, int $index): array {
                $externalUrl = filled($page['external_url'] ?? null) ? (string) $page['external_url'] : null;

                return [
                    'page_number' => (int) ($page['page_number'] ?? ($index + 1)),
                    'title' => filled($page['title'] ?? null) ? (string) $page['title'] : 'Page '.($index + 1),
                    'scan_url' => filled($page['scan_path'] ?? null) ? asset('storage/'.$page['scan_path']) : null,
                    'pdf_url' => filled($page['pdf_path'] ?? null) ? asset('storage/'.$page['pdf_path']) : null,
                    'external_url' => $externalUrl,
                    'external_preview_url' => $this->externalPreviewUrlFor($externalUrl),
                    'external_download_url' => $this->externalDownloadUrlFor($externalUrl),
                    'external_image_url' => $this->externalImageUrlFor($externalUrl),
                ];
            })
            ->sortBy('page_number')
            ->values()
            ->all();

        if ($pages !== []) {
            return $pages;
        }

        return [[
            'page_number' => 1,
            'title' => 'Page 1',
            'scan_url' => $this->scanUrl(),
            'pdf_url' => $this->pdfUrl(),
            'external_url' => $this->external_url,
            'external_preview_url' => $this->externalPreviewUrl(),
            'external_download_url' => $this->externalDownloadUrl(),
            'external_image_url' => $this->externalImageUrl(),
        ]];
    }

    public function readerPage(int $pageNumber): ?array
    {
        return collect($this->readerPages())->first(fn (array $page): bool => (int) $page['page_number'] === $pageNumber);
    }

    private function externalPreviewUrlFor(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $googleDriveFileId = $this->googleDriveFileId($url);

        if ($googleDriveFileId) {
            return "https://drive.google.com/file/d/{$googleDriveFileId}/preview";
        }

        return $url;
    }

    private function externalDownloadUrlFor(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $googleDriveFileId = $this->googleDriveFileId($url);

        if ($googleDriveFileId) {
            return "https://drive.google.com/uc?export=download&id={$googleDriveFileId}";
        }

        return $url;
    }

    private function externalImageUrlFor(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $googleDriveFileId = $this->googleDriveFileId($url);

        if ($googleDriveFileId) {
            return null;
        }

        return preg_match('/\.(jpe?g|png|webp)(\?.*)?$/i', $url) ? $url : null;
    }

    private function googleDriveFileId(string $url): ?string
    {
        if (! preg_match('/drive\.google\.com\/file\/d\/([^\/\?]+)/', $url, $matches)) {
            return null;
        }

        return $matches[1] ?? null;
    }
}
