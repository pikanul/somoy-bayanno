<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaLibraryService
{
    /** @var array<string, string> */
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
    ];

    /** @param array<string, mixed> $metadata */
    public function storeUploadedImage(User $user, UploadedFile $file, array $metadata = []): MediaAsset
    {
        Gate::forUser($user)->authorize('create', MediaAsset::class);

        $bytes = $file->getContent();
        $this->assertWithinSizeLimit(strlen($bytes));
        $image = $this->inspectImage($bytes);

        return $this->storeImage($user, $bytes, $file->getClientOriginalName(), null, $image, $metadata);
    }

    /** @param array<string, mixed> $metadata */
    public function importExternalImage(User $user, string $url, array $metadata = []): MediaAsset
    {
        Gate::forUser($user)->authorize('create', MediaAsset::class);

        $this->assertSafeExternalUrl($url);

        $response = Http::timeout(config('media-library.download_timeout'))
            ->maxRedirects(config('media-library.redirect_limit'))
            ->withOptions(['stream' => false])
            ->get($url);

        if (! $response->successful()) {
            throw ValidationException::withMessages(['external_url' => 'The image could not be downloaded.']);
        }

        if (method_exists($response, 'effectiveUri') && $response->effectiveUri() !== null) {
            $this->assertSafeExternalUrl((string) $response->effectiveUri());
        }
        $this->assertAllowedContentType($response);

        $bytes = $response->body();
        $this->assertWithinSizeLimit(strlen($bytes));
        $image = $this->inspectImage($bytes);

        return $this->storeImage($user, $bytes, basename(parse_url($url, PHP_URL_PATH) ?: 'external-image'), $url, $image, $metadata);
    }

    /**
     * @param  array{mime_type: string, width: int, height: int}  $image
     * @param  array<string, mixed>  $metadata
     */
    private function storeImage(User $user, string $bytes, string $originalName, ?string $externalUrl, array $image, array $metadata): MediaAsset
    {
        $disk = config('media-library.disk');
        $extension = self::EXTENSIONS[$image['mime_type']];
        $path = sprintf('media/originals/%s/%s.%s', now()->format('Y/m'), (string) Str::uuid(), $extension);

        Storage::disk($disk)->put($path, $bytes);
        $this->createThumbnailIfSupported($disk, $path, $bytes, $image['mime_type']);

        return MediaAsset::create([
            'type' => 'image',
            'original_name' => $this->sanitizeOriginalName($originalName),
            'storage_disk' => $disk,
            'path' => $path,
            'external_url' => $externalUrl,
            'mime_type' => $image['mime_type'],
            'file_size' => strlen($bytes),
            'width' => $image['width'],
            'height' => $image['height'],
            'alt_text' => $metadata['alt_text'] ?? null,
            'caption' => $metadata['caption'] ?? null,
            'credit' => $metadata['credit'] ?? null,
            'photographer' => $metadata['photographer'] ?? null,
            'source_name' => $metadata['source_name'] ?? null,
            'source_url' => $metadata['source_url'] ?? $externalUrl,
            'copyright' => $metadata['copyright'] ?? null,
            'uploaded_by' => $user->getKey(),
        ]);
    }

    /** @return array{mime_type: string, width: int, height: int} */
    private function inspectImage(string $bytes): array
    {
        $info = @getimagesizefromstring($bytes);

        if ($info === false || ! isset($info['mime'], $info[0], $info[1])) {
            throw ValidationException::withMessages(['image' => 'The file is not a valid image.']);
        }

        $mimeType = (string) $info['mime'];

        if (! $this->isAllowedMimeType($mimeType)) {
            throw ValidationException::withMessages(['image' => 'Only JPEG, PNG, WebP, and supported AVIF images are allowed.']);
        }

        if ($mimeType === 'image/svg+xml') {
            throw ValidationException::withMessages(['image' => 'SVG uploads are not allowed.']);
        }

        return [
            'mime_type' => $mimeType,
            'width' => (int) $info[0],
            'height' => (int) $info[1],
        ];
    }

    private function isAllowedMimeType(string $mimeType): bool
    {
        if ($mimeType === 'image/avif') {
            return defined('IMG_AVIF') && ((imagetypes() & IMG_AVIF) === IMG_AVIF);
        }

        return in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true);
    }

    private function assertAllowedContentType(Response $response): void
    {
        $contentType = strtolower(trim(explode(';', $response->header('Content-Type', ''))[0]));

        if (! array_key_exists($contentType, self::EXTENSIONS)) {
            throw ValidationException::withMessages(['external_url' => 'The URL did not return an allowed image content type.']);
        }
    }

    private function assertWithinSizeLimit(int $bytes): void
    {
        if ($bytes <= 0 || $bytes > (int) config('media-library.max_bytes')) {
            throw ValidationException::withMessages(['image' => 'The image exceeds the configured maximum size.']);
        }
    }

    private function assertSafeExternalUrl(string $url): void
    {
        $parts = parse_url($url);

        if (($parts['scheme'] ?? null) !== 'https' || empty($parts['host'])) {
            throw ValidationException::withMessages(['external_url' => 'Only HTTPS image URLs are allowed.']);
        }

        $host = (string) $parts['host'];

        if (in_array(strtolower($host), ['localhost'], true)) {
            throw ValidationException::withMessages(['external_url' => 'Local network URLs are not allowed.']);
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : gethostbynamel($host);

        if ($ips === false || $ips === []) {
            throw ValidationException::withMessages(['external_url' => 'The URL host could not be resolved safely.']);
        }

        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                throw ValidationException::withMessages(['external_url' => 'Local and private network URLs are not allowed.']);
            }
        }
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false
            && $ip !== '169.254.169.254';
    }

    private function sanitizeOriginalName(string $name): string
    {
        return Str::limit(basename(str_replace('\\', '/', $name)), 255, '');
    }

    private function createThumbnailIfSupported(string $disk, string $path, string $bytes, string $mimeType): void
    {
        if (! extension_loaded('gd') || ! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return;
        }

        $source = @imagecreatefromstring($bytes);

        if ($source === false) {
            return;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $targetWidth = min((int) config('media-library.thumbnail_width'), $sourceWidth);
        $targetHeight = (int) max(1, round($sourceHeight * ($targetWidth / $sourceWidth)));
        $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);

        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        ob_start();
        imagewebp($thumbnail, null, 82);
        $thumbnailBytes = (string) ob_get_clean();

        imagedestroy($source);
        imagedestroy($thumbnail);

        Storage::disk($disk)->put('media/thumbnails/'.pathinfo($path, PATHINFO_FILENAME).'.webp', $thumbnailBytes);
    }
}
