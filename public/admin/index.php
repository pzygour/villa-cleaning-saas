<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

ob_start();
?>
<section class="panel-grid">
    <article class="panel">
        <h3>Property Operations</h3>
        <p>Manage properties, rooms, bed/bath setup and bookings.</p>
        <p><a href="/admin/properties.php">Go to Properties</a> · <a href="/admin/bookings.php">Go to Bookings</a></p>
    </article>
    <article class="panel">
        <h3>Cleaning Operations</h3>
        <p>Use schedule view to assign cleaners and update assignment status.</p>
        <p><a href="/admin/schedule.php">Open Schedule</a></p>
    </article>
    <article class="panel">
        <h3>Stock Visibility</h3>
        <p>View requirement totals, inventory balances, and laundry handovers.</p>
        <p><a href="/admin/requirements.php">Requirements</a> · <a href="/admin/inventory.php">Inventory</a> · <a href="/admin/laundry.php">Laundry</a></p>
    </article>
</section>
<?php
$html = (string) ob_get_clean();
render_admin_page('Dashboard', $html);
