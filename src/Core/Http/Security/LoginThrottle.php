<?php

declare(strict_types=1);

namespace App\Core\Http\Security;

final class LoginThrottle
{
    private const SESSION_KEY = '_login_throttle';

    public static function clientKey(array $server): string
    {
        $forwarded = (string) ($server['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($forwarded !== '') {
            $parts = array_map('trim', explode(',', $forwarded));
            if ($parts !== [] && $parts[0] !== '') {
                return $parts[0];
            }
        }

        $remote = (string) ($server['REMOTE_ADDR'] ?? 'unknown');

        return $remote !== '' ? $remote : 'unknown';
    }

    public static function status(string $clientKey): array
    {
        $row = self::state()[$clientKey] ?? [];

        return [
            'count' => (int) ($row['count'] ?? 0),
            'first_failed_at' => (int) ($row['first_failed_at'] ?? 0),
            'locked_until' => (int) ($row['locked_until'] ?? 0),
        ];
    }

    public static function isLocked(string $clientKey): bool
    {
        $lockedUntil = self::status($clientKey)['locked_until'];

        if ($lockedUntil <= time()) {
            if ($lockedUntil > 0) {
                self::clear($clientKey);
            }

            return false;
        }

        return true;
    }

    public static function lockRemainingSeconds(string $clientKey): int
    {
        $lockedUntil = self::status($clientKey)['locked_until'];

        return max(0, $lockedUntil - time());
    }

    public static function recordFailure(string $clientKey, int $maxAttempts, int $windowSeconds): void
    {
        $state = self::state();
        $now = time();
        $row = $state[$clientKey] ?? [
            'count' => 0,
            'first_failed_at' => $now,
            'locked_until' => 0,
        ];

        $firstFailedAt = (int) ($row['first_failed_at'] ?? $now);
        if (($now - $firstFailedAt) > $windowSeconds) {
            $row['count'] = 0;
            $row['first_failed_at'] = $now;
            $row['locked_until'] = 0;
        }

        $row['count'] = (int) ($row['count'] ?? 0) + 1;

        if ($row['count'] >= $maxAttempts) {
            $row['locked_until'] = $now + $windowSeconds;
            $row['count'] = 0;
            $row['first_failed_at'] = $now;
        }

        $state[$clientKey] = $row;
        $_SESSION[self::SESSION_KEY] = $state;
    }

    public static function clear(string $clientKey): void
    {
        $state = self::state();
        unset($state[$clientKey]);
        $_SESSION[self::SESSION_KEY] = $state;
    }

    private static function state(): array
    {
        $value = $_SESSION[self::SESSION_KEY] ?? [];

        return is_array($value) ? $value : [];
    }
}
