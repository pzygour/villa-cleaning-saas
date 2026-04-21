<?php

declare(strict_types=1);

namespace App\Core\Http\Security;

final class SessionSecurity
{
    public const CSRF_SESSION_KEY = '_csrf_token';
    public const LAST_ACTIVITY_SESSION_KEY = '_last_activity_at';

    public static function start(array $server, bool $httpsOnlyCookies = false): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = $httpsOnlyCookies || self::isHttps($server);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.cookie_secure', $isHttps ? '1' : '0');

        session_start();

        self::ensureCsrfToken();
    }

    public static function rotateAfterLogin(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        session_regenerate_id(true);
        self::regenerateCsrfToken();
        self::touchActivity();
    }

    public static function touchActivity(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION[self::LAST_ACTIVITY_SESSION_KEY] = time();
    }

    public static function isExpired(int $timeoutSeconds): bool
    {
        if ($timeoutSeconds <= 0 || session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $lastActivity = (int) ($_SESSION[self::LAST_ACTIVITY_SESSION_KEY] ?? 0);
        if ($lastActivity <= 0) {
            self::touchActivity();
            return false;
        }

        return (time() - $lastActivity) > $timeoutSeconds;
    }

    public static function clear(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => (bool) ($params['secure'] ?? false),
                'httponly' => (bool) ($params['httponly'] ?? true),
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
    }

    public static function csrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }

        self::ensureCsrfToken();

        return (string) ($_SESSION[self::CSRF_SESSION_KEY] ?? '');
    }

    public static function validateCsrfToken(array $server, array $payload = []): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $sessionToken = (string) ($_SESSION[self::CSRF_SESSION_KEY] ?? '');
        if ($sessionToken === '') {
            return false;
        }

        $headerToken = (string) ($server['HTTP_X_CSRF_TOKEN'] ?? '');
        $bodyToken = (string) ($payload['csrf_token'] ?? '');

        $provided = $headerToken !== '' ? $headerToken : $bodyToken;
        if ($provided === '') {
            return false;
        }

        return hash_equals($sessionToken, $provided);
    }

    private static function ensureCsrfToken(): void
    {
        if (empty($_SESSION[self::CSRF_SESSION_KEY])) {
            $_SESSION[self::CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
        }
    }

    private static function regenerateCsrfToken(): void
    {
        $_SESSION[self::CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }

    private static function isHttps(array $server): bool
    {
        $https = strtolower((string) ($server['HTTPS'] ?? ''));
        if ($https === 'on' || $https === '1') {
            return true;
        }

        $proto = strtolower((string) ($server['HTTP_X_FORWARDED_PROTO'] ?? ''));

        return $proto === 'https';
    }
}
