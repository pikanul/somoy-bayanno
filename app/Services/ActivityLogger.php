<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivityLogger
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'password_hash',
        'current_password',
        'new_password',
        'token',
        'remember_token',
        'api_token',
        'access_token',
        'refresh_token',
        'secret',
        'api_secret',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
        'database_password',
        'db_password',
        'recovery_code',
        'recovery_codes',
        'two_factor_recovery_codes',
    ];

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $properties
     */
    public function record(
        string $action,
        ?User $user = null,
        ?Model $subject = null,
        array $oldValues = [],
        array $newValues = [],
        array $properties = [],
        ?Request $request = null,
    ): ActivityLog {
        $request ??= request();

        return ActivityLog::query()->create([
            'user_id' => $user?->getKey(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'old_values' => $this->sanitize($oldValues) ?: null,
            'new_values' => $this->sanitize($newValues) ?: null,
            'properties' => $this->sanitize($properties) ?: null,
            'occurred_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $properties */
    public function login(User $user, array $properties = []): ActivityLog
    {
        return $this->record('login', $user, $user, properties: $properties);
    }

    /** @param array<string, mixed> $properties */
    public function failedLogin(array $properties = []): ActivityLog
    {
        return $this->record('failed_login', properties: $properties);
    }

    /** @param array<string, mixed> $properties */
    public function backupAction(User $user, string $action, array $properties = []): ActivityLog
    {
        return $this->record('backup.'.$action, $user, properties: $properties);
    }

    /** @param array<string, mixed> $properties */
    public function settingsChange(User $user, array $oldValues, array $newValues, array $properties = []): ActivityLog
    {
        return $this->record('settings.change', $user, oldValues: $oldValues, newValues: $newValues, properties: $properties);
    }

    /** @param array<string, mixed> $properties */
    public function advertisementChange(User $user, ?Model $subject, string $action, array $oldValues = [], array $newValues = [], array $properties = []): ActivityLog
    {
        return $this->record('advertisement.'.$action, $user, $subject, $oldValues, $newValues, $properties);
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{old: array<string, mixed>, new: array<string, mixed>}
     */
    public function safeDiff(array $before, array $after): array
    {
        $before = $this->sanitize($before);
        $after = $this->sanitize($after);
        $oldValues = [];
        $newValues = [];

        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
            if (($before[$key] ?? null) === ($after[$key] ?? null)) {
                continue;
            }

            $oldValues[$key] = $before[$key] ?? null;
            $newValues[$key] = $after[$key] ?? null;
        }

        return ['old' => $oldValues, 'new' => $newValues];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function sanitize(array $values): array
    {
        $clean = [];

        foreach ($values as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                continue;
            }

            $clean[$key] = is_array($value) ? $this->sanitize($value) : $this->normalizeValue($value);
        }

        return $clean;
    }

    private function isSensitiveKey(string $key): bool
    {
        $key = Str::of($key)->lower()->replace(['-', ' '], '_')->toString();

        return in_array($key, self::SENSITIVE_KEYS, true)
            || Str::contains($key, ['_token', 'token_', '_secret', 'secret_', 'password']);
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        return $value;
    }
}
