<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Disabled => 'Disabled',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::Active->value => self::Active->label(),
            self::Disabled->value => self::Disabled->label(),
        ];
    }
}
