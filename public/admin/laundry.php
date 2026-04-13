<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

ob_start();
?>
<section class="panel">
    <h3>Open Handovers</h3>
    <button id="load-open-handovers">Refresh Open Handovers</button>
    <pre id="open-output"></pre>
</section>

<section class="panel">
    <h3>Handover Detail</h3>
    <div class="form-grid">
        <label>Handover ID <input id="handover-id" type="text"></label>
        <div><button id="load-handover-detail">Load Detail</button></div>
    </div>
    <pre id="detail-output"></pre>
</section>

<section class="panel">
    <h3>Pending Return Quantities</h3>
    <div class="form-grid">
        <label>Handover ID <input id="pending-handover-id" type="text"></label>
        <div><button id="load-pending">Load Pending</button></div>
    </div>
    <pre id="pending-output"></pre>
</section>

<script>
(function() {
    async function loadOpen() {
        const res = await fetch('/laundry/handovers/open');
        document.getElementById('open-output').textContent = JSON.stringify(await res.json(), null, 2);
    }

    document.getElementById('load-open-handovers').addEventListener('click', loadOpen);

    document.getElementById('load-handover-detail').addEventListener('click', async () => {
        const id = document.getElementById('handover-id').value;
        const res = await fetch(`/laundry/handovers/${encodeURIComponent(id)}`);
        document.getElementById('detail-output').textContent = JSON.stringify(await res.json(), null, 2);
    });

    document.getElementById('load-pending').addEventListener('click', async () => {
        const id = document.getElementById('pending-handover-id').value;
        const res = await fetch(`/laundry/handovers/${encodeURIComponent(id)}/pending-returns`);
        document.getElementById('pending-output').textContent = JSON.stringify(await res.json(), null, 2);
    });

    loadOpen();
})();
</script>
<?php
$html = (string) ob_get_clean();
render_admin_page('Laundry', $html);
