<?php

declare(strict_types=1);

$debugEnv = strtolower((string) ($_ENV['APP_DEBUG'] ?? '0'));

return [
    'app_name' => 'Villa Cleaning Operations',
    'environment' => $_ENV['APP_ENV'] ?? 'production',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
    'debug' => in_array($debugEnv, ['1', 'true', 'yes', 'on'], true),
    'session_timeout_seconds' => max(0, (int) ($_ENV['SESSION_TIMEOUT_SECONDS'] ?? 3600)),
    'login_max_attempts' => max(1, (int) ($_ENV['LOGIN_MAX_ATTEMPTS'] ?? 5)),
    'login_lockout_seconds' => max(30, (int) ($_ENV['LOGIN_LOCKOUT_SECONDS'] ?? 300)),
];
