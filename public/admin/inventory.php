<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

ob_start();
?>
<section class="panel">
    <h3>Balances by Location</h3>
    <div class="form-grid">
        <label>Location ID <input id="location-id" type="text"></label>
        <div><button id="load-balances">Load</button></div>
    </div>
    <pre id="balances-output"></pre>
</section>

<section class="panel">
    <h3>Movements by Location</h3>
    <div class="form-grid">
        <label>Location ID <input id="mv-location-id" type="text"></label>
        <label>From <input id="mv-from" type="date"></label>
        <label>To <input id="mv-to" type="date"></label>
        <div><button id="load-movements">Load</button></div>
    </div>
    <pre id="movements-output"></pre>
</section>

<section class="panel">
    <h3>Event Availability Projection</h3>
    <div class="form-grid">
        <label>Event ID <input id="av-event-id" type="text"></label>
        <label>Location ID <input id="av-location-id" type="text"></label>
        <div><button id="load-availability">Load</button></div>
    </div>
    <pre id="availability-output"></pre>
</section>

<script>
(function() {
    document.getElementById('load-balances').addEventListener('click', async () => {
        const id = document.getElementById('location-id').value;
        const res = await fetch(`/inventory/balances/location/${encodeURIComponent(id)}`);
        document.getElementById('balances-output').textContent = JSON.stringify(await res.json(), null, 2);
    });

    document.getElementById('load-movements').addEventListener('click', async () => {
        const id = document.getElementById('mv-location-id').value;
        const from = document.getElementById('mv-from').value;
        const to = document.getElementById('mv-to').value;
        const q = new URLSearchParams({ from_date: from, to_date: to });
        const res = await fetch(`/inventory/movements/location/${encodeURIComponent(id)}?${q.toString()}`);
        document.getElementById('movements-output').textContent = JSON.stringify(await res.json(), null, 2);
    });

    document.getElementById('load-availability').addEventListener('click', async () => {
        const eventId = document.getElementById('av-event-id').value;
        const locationId = document.getElementById('av-location-id').value;
        const res = await fetch(`/inventory/availability/events/${encodeURIComponent(eventId)}?location_id=${encodeURIComponent(locationId)}`);
        document.getElementById('availability-output').textContent = JSON.stringify(await res.json(), null, 2);
    });
})();
</script>
<?php
$html = (string) ob_get_clean();
render_admin_page('Inventory', $html);
