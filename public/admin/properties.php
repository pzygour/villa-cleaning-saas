<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

ob_start();
?>
<section class="panel">
    <h3>Create / Edit Property</h3>
    <form id="property-form" class="form-grid">
        <input type="hidden" name="id" id="property-id">
        <label>Code <input type="text" name="code" required></label>
        <label>Name <input type="text" name="name" required></label>
        <label>Location Label <input type="text" name="location_label" required></label>
        <label>Property Type
            <select name="property_type" required>
                <option value="villa">villa</option>
                <option value="apartment">apartment</option>
                <option value="room">room</option>
                <option value="hotel_unit">hotel_unit</option>
            </select>
        </label>
        <label class="full">Operational Notes <textarea name="operational_notes" rows="2"></textarea></label>
        <div class="full">
            <button type="submit">Save Property</button>
            <button type="button" id="property-reset">Reset</button>
        </div>
    </form>
    <p id="property-message" class="message"></p>
</section>

<section class="panel">
    <h3>Property List</h3>
    <table>
        <thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Location</th><th>Actions</th></tr></thead>
        <tbody id="properties-table"></tbody>
    </table>
</section>

<script>
(async function () {
    const form = document.getElementById('property-form');
    const table = document.getElementById('properties-table');
    const message = document.getElementById('property-message');
    const resetBtn = document.getElementById('property-reset');

    async function loadProperties() {
        const res = await fetch('/properties');
        const data = await res.json();
        const rows = data.data || [];
        table.innerHTML = rows.map((p) => `
            <tr>
                <td>${p.code}</td>
                <td>${p.name}</td>
                <td>${p.property_type}</td>
                <td>${p.location_label}</td>
                <td>
                    <button data-edit='${JSON.stringify(p)}'>Edit</button>
                    <a href="/admin/rooms.php?property_id=${p.id}">Rooms</a>
                </td>
            </tr>
        `).join('');

        table.querySelectorAll('button[data-edit]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const p = JSON.parse(btn.dataset.edit);
                form.id.value = p.id;
                form.code.value = p.code;
                form.name.value = p.name;
                form.location_label.value = p.location_label;
                form.property_type.value = p.property_type;
                form.operational_notes.value = p.operational_notes ?? '';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = Object.fromEntries(new FormData(form).entries());
        const id = payload.id || '';
        delete payload.id;

        const method = id ? 'PUT' : 'POST';
        const path = id ? `/properties/${id}` : '/properties';

        const res = await fetch(path, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();

        message.textContent = res.ok ? 'Saved.' : `Error: ${data.error ?? 'request_failed'}`;
        if (res.ok) {
            form.reset();
            form.id.value = '';
            await loadProperties();
        }
    });

    resetBtn.addEventListener('click', () => {
        form.reset();
        form.id.value = '';
        message.textContent = '';
    });

    await loadProperties();
})();
</script>
<?php
$html = (string) ob_get_clean();
render_admin_page('Properties', $html);
