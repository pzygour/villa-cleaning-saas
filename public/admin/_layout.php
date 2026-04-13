<?php

declare(strict_types=1);

/**
 * @param list<string> $scripts
 */
function render_admin_page(string $title, string $contentHtml, array $scripts = []): void
{
    $nav = [
        'Dashboard' => '/admin/index.php',
        'Properties' => '/admin/properties.php',
        'Bookings' => '/admin/bookings.php',
        'Schedule' => '/admin/schedule.php',
        'Requirements' => '/admin/requirements.php',
        'Inventory' => '/admin/inventory.php',
        'Laundry' => '/admin/laundry.php',
    ];

    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/admin/index.php', PHP_URL_PATH) ?: '/admin/index.php';

    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . htmlspecialchars($title) . ' - Villa Cleaning Admin</title>';
    echo '<link rel="stylesheet" href="/assets/css/admin.css">';
    echo '</head><body>';
    echo '<div class="layout">';
    echo '<aside class="sidebar"><h1>Villa Ops</h1><nav><ul>';

    foreach ($nav as $label => $url) {
        $isActive = $currentPath === $url;
        echo '<li><a class="' . ($isActive ? 'active' : '') . '" href="' . $url . '">' . htmlspecialchars($label) . '</a></li>';
    }

    echo '</ul></nav></aside>';
    echo '<main class="main"><header><h2>' . htmlspecialchars($title) . '</h2></header>';
    echo $contentHtml;
    echo '</main></div>';

    foreach ($scripts as $script) {
        echo '<script src="' . htmlspecialchars($script) . '"></script>';
    }

    echo '</body></html>';
}
