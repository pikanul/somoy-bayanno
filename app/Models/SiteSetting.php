<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class SiteSetting extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'key',
        'label',
        'value',
        'group',
        'type',
        'is_public',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public static function publicValue(string $key, string $default = ''): string
    {
        $value = static::publicValues([$key])->get($key);

        return filled($value) ? (string) $value : $default;
    }

    /**
     * @param  array<int, string>  $keys
     * @return Collection<string, string|null>
     */
    public static function publicValues(array $keys)
    {
        if (! Schema::hasTable('site_settings')) {
            return collect();
        }

        return static::query()
            ->public()
            ->whereIn('key', $keys)
            ->pluck('value', 'key');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }
}
