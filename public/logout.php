<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function base_path_for_public(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $base = rtrim((string) dirname($script), '/');

    return $base === '/' ? '' : $base;
}

$_SESSION = [];
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

header('Location: ' . base_path_for_public() . '/login.php');
exit;
