<?php

namespace App\Enums;

enum BreakingNewsTargetType: string
{
    case Article = 'article';
    case External = 'external';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Article => 'Article',
            self::External => 'External URL',
            self::None => 'Ticker only',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::Article->value => self::Article->label(),
            self::External->value => self::External->label(),
            self::None->value => self::None->label(),
        ];
    }
}
