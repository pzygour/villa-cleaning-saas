<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

$propertyId = (string) ($_GET['property_id'] ?? '');

ob_start();
?>
<section class="panel">
    <h3>Rooms for Property</h3>
    <p>Property ID: <code id="property-id-label"><?= htmlspecialchars($propertyId) ?></code></p>
    <form id="room-form" class="form-grid">
        <input type="hidden" name="id">
        <input type="hidden" name="property_id" value="<?= htmlspecialchars($propertyId) ?>">
        <label>Name <input type="text" name="name" required></label>
        <label>Room Type <input type="text" name="room_type" required></label>
        <label>Sort Order <input type="number" name="sort_order" value="0" required></label>
        <div class="full"><button type="submit">Save Room</button></div>
    </form>
</section>

<section class="panel">
    <table>
        <thead><tr><th>Name</th><th>Type</th><th>Beds</th><th>Bathrooms</th><th>Actions</th></tr></thead>
        <tbody id="rooms-table"></tbody>
    </table>
</section>

<script>
(async function() {
    const propertyId = <?= json_encode($propertyId, JSON_THROW_ON_ERROR) ?>;
    const form = document.getElementById('room-form');
    const table = document.getElementById('rooms-table');

    if (!propertyId) {
        table.innerHTML = '<tr><td colspan="5">Missing property_id query parameter.</td></tr>';
        return;
    }

    async function loadRooms() {
        const res = await fetch(`/rooms?property_id=${encodeURIComponent(propertyId)}`);
        const rows = await res.json();
        table.innerHTML = rows.map((r) => `
            <tr>
                <td>${r.name}</td><td>${r.room_type}</td><td>${r.beds_json || '-'}</td><td>${r.bathrooms_json || '-'}</td>
                <td><button data-edit='${JSON.stringify(r)}'>Edit</button></td>
            </tr>`).join('');

        table.querySelectorAll('button[data-edit]').forEach((b) => b.addEventListener('click', () => {
            const r = JSON.parse(b.dataset.edit);
            form.id.value = r.id;
            form.name.value = r.name;
            form.room_type.value = r.room_type;
            form.sort_order.value = r.sort_order;
        }));
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = Object.fromEntries(new FormData(form).entries());
        const id = payload.id || '';
        delete payload.id;
        const endpoint = id ? `/rooms/${id}` : '/rooms';
        const method = id ? 'PUT' : 'POST';

        await fetch(endpoint, { method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        form.reset();
        form.property_id.value = propertyId;
        await loadRooms();
    });

    await loadRooms();
})();
</script>
<?php
$html = (string) ob_get_clean();
render_admin_page('Rooms', $html);
