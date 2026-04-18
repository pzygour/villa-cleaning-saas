<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

ob_start();
?>
<section class="panel">
    <h3>Requirement Totals by Event IDs</h3>
    <div class="form-grid">
        <label class="full">Event IDs (comma separated)<input id="event-ids" type="text"></label>
        <div><button id="load-event-totals">Load</button></div>
    </div>
    <pre id="event-totals-output"></pre>
</section>

<section class="panel">
    <h3>Requirement Totals by Day</h3>
    <div class="form-grid">
        <label>Date <input id="day-date" type="date"></label>
        <label>Property ID (optional) <input id="day-property-id" type="text"></label>
        <div><button id="load-day-totals">Load</button></div>
    </div>
    <pre id="day-totals-output"></pre>
</section>

<script>
(function() {
    document.getElementById('load-event-totals').addEventListener('click', async () => {
        const ids = document.getElementById('event-ids').value;
        const res = await fetch(`/requirements/totals/events?event_ids=${encodeURIComponent(ids)}`);
        const data = await res.json();
        document.getElementById('event-totals-output').textContent = JSON.stringify(data, null, 2);
    });

    document.getElementById('load-day-totals').addEventListener('click', async () => {
        const date = document.getElementById('day-date').value;
        const propertyId = document.getElementById('day-property-id').value;
        const q = new URLSearchParams({ date });
        if (propertyId) q.set('property_id', propertyId);
        const res = await fetch(`/requirements/totals/day?${q.toString()}`);
        const data = await res.json();
        document.getElementById('day-totals-output').textContent = JSON.stringify(data, null, 2);
    });
})();
</script>
<?php
$html = (string) ob_get_clean();
render_admin_page('Requirements', $html);
