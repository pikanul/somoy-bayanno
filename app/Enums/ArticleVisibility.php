<?php

namespace App\Enums;

enum ArticleVisibility: string
{
    case Public = 'public';
    case Private = 'private';
    case Unlisted = 'unlisted';

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::Public->value => 'Public',
            self::Private->value => 'Private',
            self::Unlisted->value => 'Unlisted',
        ];
    }
}
