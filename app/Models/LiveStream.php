<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'title_bn',
    'slug',
    'provider',
    'stream_url',
    'embed_url',
    'poster_url',
    'description_bn',
    'is_active',
    'autoplay',
    'muted',
    'status_text',
    'sort_order',
    'starts_at',
    'ends_at',
    'created_by',
    'updated_by',
])]
class LiveStream extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'autoplay' => 'boolean',
            'muted' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function playbackUrl(): string
    {
        $url = $this->embed_url ?: $this->stream_url;

        if (in_array($this->provider, ['youtube', 'embed'], true)) {
            return $this->youtubeEmbedUrl($url) ?? $url;
        }

        return $url;
    }

    private function youtubeEmbedUrl(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $host = is_string($host) ? strtolower($host) : '';
        $path = trim($path, '/');

        if (str_contains($host, 'youtu.be') && $path !== '') {
            $videoId = explode('/', $path)[0];
        } elseif (str_contains($host, 'youtube.com') && isset($query['v'])) {
            $videoId = (string) $query['v'];
        } elseif (str_contains($host, 'youtube.com') && str_starts_with($path, 'embed/')) {
            $videoId = substr($path, 6);
        } elseif (str_contains($host, 'youtube.com') && str_starts_with($path, 'shorts/')) {
            $videoId = substr($path, 7);
        } else {
            return null;
        }

        $videoId = preg_replace('/[^A-Za-z0-9_-]/', '', $videoId);

        return $videoId ? "https://www.youtube-nocookie.com/embed/{$videoId}" : null;
    }
}
