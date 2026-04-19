<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function app_base_path(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $base = preg_replace('#/admin/.*$#', '', $script);
    if (!is_string($base) || $base === '') {
        return '';
    }

    $base = rtrim($base, '/');
    return $base === '/index.php' ? '' : $base;
}

function app_url(string $path = ''): string
{
    $base = app_base_path();
    if ($path === '') {
        return $base;
    }

    return $base . '/' . ltrim($path, '/');
}

function require_admin_session(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . app_url('/login.php'));
        exit;
    }

    $role = (string) ($_SESSION['user_role'] ?? '');
    if (!in_array($role, ['owner', 'manager'], true)) {
        http_response_code(403);
        echo '<h1>403 Forbidden</h1><p>You do not have access to admin pages.</p>';
        exit;
    }
}

require_admin_session();

/**
 * @param list<string> $scripts
 */
function render_admin_page(string $title, string $contentHtml, array $scripts = []): void
{
    $basePath = app_base_path();
    $nav = [
        'Dashboard' => app_url('/admin/index.php'),
        'Properties' => app_url('/admin/properties.php'),
        'Bookings' => app_url('/admin/bookings.php'),
        'Schedule' => app_url('/admin/schedule.php'),
        'Requirements' => app_url('/admin/requirements.php'),
        'Inventory' => app_url('/admin/inventory.php'),
        'Laundry' => app_url('/admin/laundry.php'),
    ];

    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? app_url('/admin/index.php'), PHP_URL_PATH) ?: app_url('/admin/index.php');

    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . htmlspecialchars($title) . ' - Villa Cleaning Admin</title>';
    echo '<link rel="stylesheet" href="' . htmlspecialchars(app_url('/assets/css/admin.css')) . '">';
    echo '</head><body>';
    echo '<div class="layout">';
    echo '<aside class="sidebar"><h1>Villa Ops</h1><nav><ul>';

    foreach ($nav as $label => $url) {
        $isActive = $currentPath === $url;
        echo '<li><a class="' . ($isActive ? 'active' : '') . '" href="' . $url . '">' . htmlspecialchars($label) . '</a></li>';
    }

    echo '</ul></nav></aside>';
    echo '<main class="main"><header><h2>' . htmlspecialchars($title) . '</h2><p><a href="' . htmlspecialchars(app_url('/logout.php')) . '">Logout</a></p></header>';
    echo $contentHtml;
    echo '</main></div>';
    echo '<script>';
    echo 'window.APP_BASE_PATH=' . json_encode($basePath, JSON_THROW_ON_ERROR) . ';';
    echo 'window.apiUrl=function(path){path=String(path||"");if(path.startsWith(window.APP_BASE_PATH+"/")){return path;}return window.APP_BASE_PATH+"/"+path.replace(/^\\/+/, "");};';
    echo '(function(){const _fetch=window.fetch.bind(window);window.fetch=function(input,init){if(typeof input==="string"&&input.startsWith("/")&&!input.startsWith(window.APP_BASE_PATH+"/")){input=window.APP_BASE_PATH+input;}return _fetch(input,init);};})();';
    echo '</script>';

    foreach ($scripts as $script) {
        $url = str_starts_with($script, 'http://') || str_starts_with($script, 'https://')
            ? $script
            : app_url($script);
        echo '<script src="' . htmlspecialchars($url) . '"></script>';
    }

    echo '</body></html>';
}
