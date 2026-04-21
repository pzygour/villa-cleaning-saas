<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Http\Security\SessionSecurity;

SessionSecurity::start($_SERVER);

function base_path_for_public(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $base = rtrim((string) dirname($script), '/');

    return $base === '/' ? '' : $base;
}

SessionSecurity::clear();

header('Location: ' . base_path_for_public() . '/login.php');
exit;
