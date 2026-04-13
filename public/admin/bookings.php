<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

ob_start();
?>
<section class="panel">
    <h3>Booking Filters</h3>
    <form id="booking-filter" class="form-grid">
        <label>Property ID <input type="text" name="property_id"></label>
        <label>From Date <input type="date" name="from_date"></label>
        <label>To Date <input type="date" name="to_date"></label>
        <div><button type="submit">Load</button></div>
    </form>
</section>

<section class="panel">
    <h3>Create / Edit Booking</h3>
    <form id="booking-form" class="form-grid">
        <input type="hidden" name="id">
        <label>Property ID <input type="text" name="property_id" required></label>
        <label>Reference <input type="text" name="booking_reference"></label>
        <label>Source <input type="text" name="source_system" value="manual" required></label>
        <label>Arrival <input type="date" name="arrival_date" required></label>
        <label>Departure <input type="date" name="departure_date" required></label>
        <label>Guest Count <input type="number" min="1" name="guest_count" required></label>
        <label class="full">Notes <textarea name="notes" rows="2"></textarea></label>
        <div class="full"><button type="submit">Save Booking</button></div>
    </form>
</section>

<section class="panel">
    <table>
        <thead><tr><th>Ref</th><th>Property</th><th>Arrival</th><th>Departure</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody id="bookings-table"></tbody>
    </table>
</section>

<script>
(async function() {
    const filterForm = document.getElementById('booking-filter');
    const form = document.getElementById('booking-form');
    const table = document.getElementById('bookings-table');

    async function loadBookings() {
        const q = new URLSearchParams(new FormData(filterForm));
        const res = await fetch(`/bookings?${q.toString()}`);
        const rows = await res.json();
        table.innerHTML = rows.map((b) => `
            <tr>
                <td>${b.booking_reference || '-'}</td>
                <td>${b.property_id}</td>
                <td>${b.arrival_date}</td>
                <td>${b.departure_date}</td>
                <td>${b.status}</td>
                <td>
                    <button data-edit='${JSON.stringify(b)}'>Edit</button>
                    <button data-cancel='${b.id}'>Cancel</button>
                </td>
            </tr>
        `).join('');

        table.querySelectorAll('button[data-edit]').forEach((btn) => btn.addEventListener('click', () => {
            const b = JSON.parse(btn.dataset.edit);
            form.id.value = b.id;
            form.property_id.value = b.property_id;
            form.booking_reference.value = b.booking_reference || '';
            form.source_system.value = b.source_system || 'manual';
            form.arrival_date.value = b.arrival_date;
            form.departure_date.value = b.departure_date;
            form.guest_count.value = b.guest_count;
            form.notes.value = b.notes || '';
        }));

        table.querySelectorAll('button[data-cancel]').forEach((btn) => btn.addEventListener('click', async () => {
            await fetch(`/bookings/${btn.dataset.cancel}/cancel`, { method: 'POST' });
            await loadBookings();
        }));
    }

    filterForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await loadBookings();
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = Object.fromEntries(new FormData(form).entries());
        const id = payload.id || '';
        delete payload.id;
        const endpoint = id ? `/bookings/${id}` : '/bookings';
        const method = id ? 'PUT' : 'POST';

        await fetch(endpoint, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        form.reset();
        await loadBookings();
    });

    await loadBookings();
})();
</script>
<?php
$html = (string) ob_get_clean();
render_admin_page('Bookings', $html);
