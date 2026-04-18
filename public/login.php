<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    header('Location: /admin/index.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Villa Cleaning</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
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
    const response = await fetch('/auth/login', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    });

    const data = await response.json();

    if (!response.ok || data.success === false) {
        message.textContent = data.message || 'Login failed';
        return;
    }

    window.location.href = '/admin/index.php';
});
</script>
</body>
</html>
