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

$basePath = base_path_for_public();
$appUrl = static fn (string $path): string => $basePath . '/' . ltrim($path, '/');
$csrfToken = SessionSecurity::csrfToken();

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . $appUrl('/admin/index.php'));
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <title>Login - Villa Cleaning</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($appUrl('/assets/css/admin.css')) ?>">
</head>
<body>
<div class="layout" style="max-width:520px;margin:32px auto;display:block;">
    <main class="main" style="margin:0;">
        <header><h2>Sign in</h2></header>
        <p id="login-message"></p>
        <form id="login-form" class="form-grid">
            <label>Email <input type="email" name="email" required></label>
            <label>Password <input type="password" name="password" required></label>
            <div><button type="submit">Login</button></div>
        </form>
    </main>
</div>
<script>
const form = document.getElementById('login-form');
const message = document.getElementById('login-message');

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    message.textContent = 'Signing in...';

    const payload = Object.fromEntries(new FormData(form).entries());
    const basePath = <?= json_encode($basePath, JSON_THROW_ON_ERROR) ?>;
    const response = await fetch(`${basePath}/auth/login`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify(payload)
    });

    const data = await response.json();

    if (!response.ok || data.success === false) {
        message.textContent = data.message || 'Login failed';
        return;
    }

    window.location.href = `${basePath}/admin/index.php`;
});
</script>
</body>
</html>
