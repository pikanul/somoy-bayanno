<?php

namespace App\Enums;

enum VideoProvider: string
{
    case YouTube = 'youtube';
    case Facebook = 'facebook';
    case Vimeo = 'vimeo';
    case CloudflareStream = 'cloudflare_stream';
    case Hls = 'hls';
    case CdnMp4 = 'cdn_mp4';

    public function label(): string
    {
        return match ($this) {
            self::YouTube => 'YouTube',
            self::Facebook => 'Facebook',
            self::Vimeo => 'Vimeo',
            self::CloudflareStream => 'Cloudflare Stream',
            self::Hls => 'Authorized HLS',
            self::CdnMp4 => 'Authorized CDN MP4',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::YouTube->value => self::YouTube->label(),
            self::Facebook->value => self::Facebook->label(),
            self::Vimeo->value => self::Vimeo->label(),
            self::CloudflareStream->value => self::CloudflareStream->label(),
            self::Hls->value => self::Hls->label(),
            self::CdnMp4->value => self::CdnMp4->label(),
        ];
    }
}
