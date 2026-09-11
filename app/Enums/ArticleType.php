<?php

namespace App\Enums;

enum ArticleType: string
{
    case Standard = 'standard';
    case Breaking = 'breaking';
    case Opinion = 'opinion';
    case Editorial = 'editorial';
    case Interview = 'interview';
    case Feature = 'feature';
    case PhotoStory = 'photo_story';
    case Video = 'video';
    case Live = 'live';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard',
            self::Breaking => 'Breaking',
            self::Opinion => 'Opinion',
            self::Editorial => 'Editorial',
            self::Interview => 'Interview',
            self::Feature => 'Feature',
            self::PhotoStory => 'Photo Story',
            self::Video => 'Video',
            self::Live => 'Live',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::Standard->value => self::Standard->label(),
            self::Breaking->value => self::Breaking->label(),
            self::Opinion->value => self::Opinion->label(),
            self::Editorial->value => self::Editorial->label(),
            self::Interview->value => self::Interview->label(),
            self::Feature->value => self::Feature->label(),
            self::PhotoStory->value => self::PhotoStory->label(),
            self::Video->value => self::Video->label(),
            self::Live->value => self::Live->label(),
        ];
    }
}
