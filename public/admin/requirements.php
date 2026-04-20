<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

ob_start();
?>
<section class="panel">
    <h3>Requirements Workspace</h3>
    <p id="requirements-banner" class="banner" style="display:none"></p>
</section>

<section class="panel">
    <h3>Event-level Requirement Totals</h3>
    <div class="form-grid">
        <label class="full">Event IDs (comma separated)<input id="event-ids" type="text" placeholder="event-1,event-2"></label>
        <div><button id="load-event-totals">Load</button></div>
    </div>
    <div id="event-totals-output"></div>
</section>

<section class="panel">
    <h3>Day Requirement Totals</h3>
    <div class="form-grid">
        <label>Date <input id="day-date" type="date"></label>
        <label>Property (optional)
            <select id="day-property-id"><option value="">-- all properties --</option></select>
        </label>
        <div><button id="load-day-totals">Load</button></div>
    </div>
    <div id="day-totals-output"></div>
</section>

<section class="panel">
    <h3>Property Date-Range Requirement Totals</h3>
    <div class="form-grid">
        <label>Property
            <select id="range-property-id"><option value="">-- select property --</option></select>
        </label>
        <label>From <input id="range-from-date" type="date"></label>
        <label>To <input id="range-to-date" type="date"></label>
        <div><button id="load-range-totals">Load</button></div>
    </div>
    <div id="range-totals-output"></div>
</section>
<?php
$html = (string) ob_get_clean();
render_admin_page('Requirements', $html, [
    '/assets/js/components/requirement-drilldown.js',
    '/assets/js/components/requirements-operations.js',
]);
