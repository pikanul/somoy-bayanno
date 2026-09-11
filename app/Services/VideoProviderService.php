<?php

namespace App\Services;

use App\Enums\VideoProvider;
use App\Models\Video;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VideoProviderService
{
    /** @return array{provider: string, video_url: string, provider_video_id: ?string} */
    public function validatedProviderPayload(string $provider, string $url): array
    {
        $providerEnum = VideoProvider::tryFrom($provider);

        if (! $providerEnum) {
            throw ValidationException::withMessages(['provider' => 'Choose a supported video provider.']);
        }

        $url = trim($url);
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'https' || $host === '') {
            throw ValidationException::withMessages(['video_url' => 'Only approved HTTPS video URLs are allowed.']);
        }

        if (Str::startsWith(strtolower($url), ['javascript:', 'data:', 'vbscript:']) || str_contains($url, '<')) {
            throw ValidationException::withMessages(['video_url' => 'Embed code and script URLs are not allowed.']);
        }

        $providerVideoId = match ($providerEnum) {
            VideoProvider::YouTube => $this->youtubeId($url, $host, $parts),
            VideoProvider::Facebook => $this->facebookId($url, $host, $parts),
            VideoProvider::Vimeo => $this->vimeoId($host, $parts),
            VideoProvider::CloudflareStream => $this->cloudflareStreamId($host, $parts),
            VideoProvider::Hls => $this->authorizedStreamPath($host, $parts, config('video.authorized_hls_hosts'), '.m3u8', 'HLS'),
            VideoProvider::CdnMp4 => $this->authorizedStreamPath($host, $parts, config('video.authorized_mp4_hosts'), '.mp4', 'MP4'),
        };

        return [
            'provider' => $providerEnum->value,
            'video_url' => $url,
            'provider_video_id' => $providerVideoId,
        ];
    }

    public function embedUrl(Video $video): string
    {
        $provider = $video->provider instanceof VideoProvider ? $video->provider : VideoProvider::from((string) $video->provider);
        $id = (string) $video->provider_video_id;

        return match ($provider) {
            VideoProvider::YouTube => 'https://www.youtube-nocookie.com/embed/'.$id,
            VideoProvider::Facebook => 'https://www.facebook.com/plugins/video.php?href='.rawurlencode($video->video_url).'&show_text=false',
            VideoProvider::Vimeo => 'https://player.vimeo.com/video/'.$id,
            VideoProvider::CloudflareStream => 'https://iframe.videodelivery.net/'.$id,
            VideoProvider::Hls, VideoProvider::CdnMp4 => $video->video_url,
        };
    }

    /** @param array<string, mixed> $parts */
    private function youtubeId(string $url, string $host, array $parts): string
    {
        if (! in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be'], true)) {
            throw ValidationException::withMessages(['video_url' => 'The URL is not an approved YouTube URL.']);
        }

        if ($host === 'youtu.be') {
            $id = trim((string) ($parts['path'] ?? ''), '/');
        } elseif (str_starts_with((string) ($parts['path'] ?? ''), '/embed/') || str_starts_with((string) ($parts['path'] ?? ''), '/shorts/')) {
            $id = explode('/', trim((string) $parts['path'], '/'))[1] ?? '';
        } else {
            parse_str((string) ($parts['query'] ?? ''), $query);
            $id = (string) ($query['v'] ?? '');
        }

        if (! preg_match('/^[A-Za-z0-9_-]{10,20}$/', $id)) {
            throw ValidationException::withMessages(['video_url' => 'The YouTube video ID could not be validated.']);
        }

        return $id;
    }

    /** @param array<string, mixed> $parts */
    private function facebookId(string $url, string $host, array $parts): string
    {
        if (! in_array($host, ['facebook.com', 'www.facebook.com', 'm.facebook.com', 'fb.watch'], true)) {
            throw ValidationException::withMessages(['video_url' => 'The URL is not an approved Facebook URL.']);
        }

        if (! str_contains($url, '/videos/') && ! str_contains($url, '/watch') && $host !== 'fb.watch') {
            throw ValidationException::withMessages(['video_url' => 'The Facebook video URL could not be validated.']);
        }

        return sha1((string) ($parts['path'] ?? '').'?'.(string) ($parts['query'] ?? ''));
    }

    /** @param array<string, mixed> $parts */
    private function vimeoId(string $host, array $parts): string
    {
        if (! in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true)) {
            throw ValidationException::withMessages(['video_url' => 'The URL is not an approved Vimeo URL.']);
        }

        preg_match('/(?:video\/)?(\d{6,12})/', (string) ($parts['path'] ?? ''), $matches);

        if (empty($matches[1])) {
            throw ValidationException::withMessages(['video_url' => 'The Vimeo video ID could not be validated.']);
        }

        return $matches[1];
    }

    /** @param array<string, mixed> $parts */
    private function cloudflareStreamId(string $host, array $parts): string
    {
        if (! str_ends_with($host, 'cloudflarestream.com') && $host !== 'iframe.videodelivery.net' && $host !== 'watch.cloudflarestream.com') {
            throw ValidationException::withMessages(['video_url' => 'The URL is not an approved Cloudflare Stream URL.']);
        }

        $segments = array_values(array_filter(explode('/', (string) ($parts['path'] ?? ''))));
        $id = $segments[0] ?? '';

        if (! preg_match('/^[A-Za-z0-9_-]{16,64}$/', $id)) {
            throw ValidationException::withMessages(['video_url' => 'The Cloudflare Stream ID could not be validated.']);
        }

        return $id;
    }

    /**
     * @param  array<string, mixed>  $parts
     * @param  array<int, string>  $authorizedHosts
     */
    private function authorizedStreamPath(string $host, array $parts, array $authorizedHosts, string $extension, string $label): ?string
    {
        if (! in_array($host, array_map('strtolower', $authorizedHosts), true)) {
            throw ValidationException::withMessages(['video_url' => "The {$label} host is not authorized."]);
        }

        $path = (string) ($parts['path'] ?? '');

        if (! str_ends_with(strtolower($path), $extension)) {
            throw ValidationException::withMessages(['video_url' => "The {$label} URL must end with {$extension}."]);
        }

        return null;
    }
}
