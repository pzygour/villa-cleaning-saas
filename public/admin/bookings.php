<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

ob_start();
?>
<section class="panel">
    <h3>Booking Filters</h3>
    <p id="booking-banner" class="banner" style="display:none"></p>
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
    <div class="form-grid">
        <label>Search <input id="booking-search" type="text" placeholder="ref/property/status"></label>
        <label>Sort
            <select id="booking-sort">
                <option value="arrival_date">arrival_date</option>
                <option value="departure_date">departure_date</option>
                <option value="booking_reference">booking_reference</option>
                <option value="status">status</option>
            </select>
        </label>
        <label>Direction
            <select id="booking-dir"><option value="asc">asc</option><option value="desc">desc</option></select>
        </label>
    </div>
    <table>
        <thead><tr><th>Ref</th><th>Property</th><th>Arrival</th><th>Departure</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody id="bookings-table"></tbody>
    </table>
    <div class="actions-inline">
        <button id="booking-prev">Prev</button>
        <span id="booking-page-meta">Page 1</span>
        <button id="booking-next">Next</button>
    </div>
</section>

<script>
(async function() {
    const filterForm = document.getElementById('booking-filter');
    const form = document.getElementById('booking-form');
    const table = document.getElementById('bookings-table');
    const banner = document.getElementById('booking-banner');

    const searchInput = document.getElementById('booking-search');
    const sortInput = document.getElementById('booking-sort');
    const dirInput = document.getElementById('booking-dir');
    const prevBtn = document.getElementById('booking-prev');
    const nextBtn = document.getElementById('booking-next');
    const pageMeta = document.getElementById('booking-page-meta');

    const state = { rows: [], page: 1, pageSize: 10 };

    function showBanner(type, message) {
        banner.style.display = 'block';
        banner.className = `banner ${type}`;
        banner.textContent = message;
    }

    function computeRows() {
        const q = searchInput.value.trim().toLowerCase();
        const sortBy = sortInput.value;
        const dir = dirInput.value === 'asc' ? 1 : -1;

        let rows = [...state.rows];
        if (q) {
            rows = rows.filter((b) => [b.booking_reference, b.property_id, b.status, b.arrival_date, b.departure_date].join(' ').toLowerCase().includes(q));
        }

        rows.sort((a, b) => {
            const av = String(a[sortBy] ?? '').toLowerCase();
            const bv = String(b[sortBy] ?? '').toLowerCase();
            if (av < bv) return -1 * dir;
            if (av > bv) return 1 * dir;
            return 0;
        });

        const totalPages = Math.max(1, Math.ceil(rows.length / state.pageSize));
        state.page = Math.min(state.page, totalPages);
        const start = (state.page - 1) * state.pageSize;
        return { rows: rows.slice(start, start + state.pageSize), totalPages };
    }

    function renderTable() {
        const { rows, totalPages } = computeRows();
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
        `).join('') || '<tr><td colspan="6">No bookings found.</td></tr>';

        pageMeta.textContent = `Page ${state.page} / ${totalPages}`;
        prevBtn.disabled = state.page <= 1;
        nextBtn.disabled = state.page >= totalPages;

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
            if (!confirm('Cancel this booking?')) return;
            await fetch(window.apiUrl(`/bookings/${btn.dataset.cancel}/cancel`), { method: 'POST' });
            await loadBookings();
        }));
    }

    async function loadBookings() {
        showBanner('ok', 'Loading bookings...');
        const q = new URLSearchParams(new FormData(filterForm));
        const res = await fetch(window.apiUrl(`/bookings?${q.toString()}`));
        const data = await res.json();
        state.rows = data.data || [];
        state.page = 1;
        renderTable();
        showBanner('ok', 'Bookings loaded.');
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

        const res = await fetch(window.apiUrl(endpoint), { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        if (res.ok) {
            showBanner('ok', 'Booking saved.');
            form.reset();
            await loadBookings();
        } else {
            const err = await res.json();
            showBanner('error', `Booking save failed: ${err.error || 'request_failed'}`);
        }
    });

    searchInput.addEventListener('input', () => { state.page = 1; renderTable(); });
    sortInput.addEventListener('change', () => { state.page = 1; renderTable(); });
    dirInput.addEventListener('change', () => { state.page = 1; renderTable(); });
    prevBtn.addEventListener('click', () => { state.page = Math.max(1, state.page - 1); renderTable(); });
    nextBtn.addEventListener('click', () => { state.page += 1; renderTable(); });

    await loadBookings();
})();
</script>
<?php
$html = (string) ob_get_clean();
render_admin_page('Bookings', $html);
