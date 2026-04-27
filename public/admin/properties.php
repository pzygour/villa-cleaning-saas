<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

ob_start();
?>
<section class="panel">
    <h3>Create / Edit Property</h3>
    <p id="property-banner" class="banner" style="display:none"></p>
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
</section>

<section class="panel">
    <h3>Property List</h3>
    <div class="form-grid">
        <label>Search <input id="property-search" type="text" placeholder="code/name/location"></label>
        <label>Sort
            <select id="property-sort">
                <option value="name">name</option>
                <option value="code">code</option>
                <option value="property_type">property_type</option>
            </select>
        </label>
        <label>Direction
            <select id="property-dir"><option value="asc">asc</option><option value="desc">desc</option></select>
        </label>
    </div>
    <table>
        <thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Location</th><th>Actions</th></tr></thead>
        <tbody id="properties-table"></tbody>
    </table>
    <div class="actions-inline">
        <button id="property-prev">Prev</button>
        <span id="property-page-meta">Page 1</span>
        <button id="property-next">Next</button>
    </div>
</section>

<script>
(async function () {
    const form = document.getElementById('property-form');
    const table = document.getElementById('properties-table');
    const resetBtn = document.getElementById('property-reset');
    const banner = document.getElementById('property-banner');

    const searchInput = document.getElementById('property-search');
    const sortInput = document.getElementById('property-sort');
    const dirInput = document.getElementById('property-dir');
    const prevBtn = document.getElementById('property-prev');
    const nextBtn = document.getElementById('property-next');
    const pageMeta = document.getElementById('property-page-meta');

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
            rows = rows.filter((p) => [p.code, p.name, p.location_label].join(' ').toLowerCase().includes(q));
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
        `).join('') || '<tr><td colspan="5">No properties found.</td></tr>';

        pageMeta.textContent = `Page ${state.page} / ${totalPages}`;
        prevBtn.disabled = state.page <= 1;
        nextBtn.disabled = state.page >= totalPages;

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

    async function loadProperties() {
        showBanner('ok', 'Loading properties...');
        const res = await fetch(window.apiUrl('/properties'));
        const data = await res.json();
        state.rows = data.data || [];
        renderTable();
        showBanner('ok', 'Properties loaded.');
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = Object.fromEntries(new FormData(form).entries());
        const id = payload.id || '';
        delete payload.id;

        const method = id ? 'PUT' : 'POST';
        const path = id ? `/properties/${id}` : '/properties';

        const res = await fetch(window.apiUrl(path), {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();

        if (res.ok) {
            showBanner('ok', 'Property saved successfully.');
            form.reset();
            form.id.value = '';
            await loadProperties();
        } else {
            showBanner('error', `Save failed: ${data.error ?? 'request_failed'}`);
        }
    });

    resetBtn.addEventListener('click', () => {
        form.reset();
        form.id.value = '';
        banner.style.display = 'none';
    });

    searchInput.addEventListener('input', () => { state.page = 1; renderTable(); });
    sortInput.addEventListener('change', () => { state.page = 1; renderTable(); });
    dirInput.addEventListener('change', () => { state.page = 1; renderTable(); });
    prevBtn.addEventListener('click', () => { state.page = Math.max(1, state.page - 1); renderTable(); });
    nextBtn.addEventListener('click', () => { state.page += 1; renderTable(); });

    await loadProperties();
})();
</script>
<?php
$html = (string) ob_get_clean();
render_admin_page('Properties', $html);
