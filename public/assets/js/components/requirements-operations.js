(function () {
    const banner = document.getElementById('requirements-banner');
    const eventOutput = document.getElementById('event-totals-output');
    const dayOutput = document.getElementById('day-totals-output');
    const rangeOutput = document.getElementById('range-totals-output');

    const dayPropertySelect = document.getElementById('day-property-id');
    const rangePropertySelect = document.getElementById('range-property-id');

    const utils = window.RequirementDrilldownUtils;
    let itemMap = {};

    function apiUrl(path) {
        if (typeof window.apiUrl === 'function') {
            return window.apiUrl(path);
        }
        return path;
    }

    function showBanner(type, message) {
        if (!banner) return;
        banner.style.display = 'block';
        banner.className = `banner ${type}`;
        banner.textContent = message;
    }

    async function readJson(response) {
        try {
            return await response.json();
        } catch {
            return {};
        }
    }

    async function request(path) {
        const response = await fetch(apiUrl(path));
        const payload = await readJson(response);

        if (!response.ok) {
            throw new Error(payload.message || payload.error || `HTTP ${response.status}`);
        }

        if (payload && typeof payload === 'object' && Object.prototype.hasOwnProperty.call(payload, 'success')) {
            if (payload.success === false) {
                throw new Error(payload.message || payload.error || 'request_failed');
            }
            return payload.data || [];
        }

        if (payload && typeof payload === 'object' && Object.prototype.hasOwnProperty.call(payload, 'data')) {
            return payload.data || [];
        }

        return payload;
    }

    function enrichRows(rows) {
        return (rows || []).map((row) => ({
            ...row,
            item_name: itemMap[row.item_id] || row.item_id,
        }));
    }

    function groupedTableHtml(title, rows, contextLabel) {
        const grouped = utils.groupByItem(rows);
        const total = grouped.reduce((sum, r) => sum + Number(r.total_required || 0), 0);

        if (grouped.length === 0) {
            return `<h4>${title}</h4><p>${contextLabel}</p><p>No requirement rows found.</p>`;
        }

        return `
            <h4>${title}</h4>
            <p>${contextLabel}</p>
            <p><strong>Total Required Quantity:</strong> ${total.toFixed(2)}</p>
            <table>
                <thead><tr><th>Item ID</th><th>Item</th><th>Total Required</th></tr></thead>
                <tbody>
                    ${grouped.map((row) => `
                        <tr>
                            <td>${row.item_id}</td>
                            <td>${itemMap[row.item_id] || row.item_id}</td>
                            <td>${Number(row.total_required || 0).toFixed(2)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }

    async function loadMeta() {
        const [properties, items] = await Promise.all([
            request('/properties'),
            request('/items'),
        ]);

        for (const item of (items || [])) {
            itemMap[item.id] = item.item_name || item.name || item.id;
        }

        for (const p of (properties || [])) {
            const o1 = document.createElement('option');
            o1.value = p.id;
            o1.textContent = p.name || p.id;
            dayPropertySelect.appendChild(o1);

            const o2 = document.createElement('option');
            o2.value = p.id;
            o2.textContent = p.name || p.id;
            rangePropertySelect.appendChild(o2);
        }
    }

    async function loadEventTotals() {
        const idsRaw = document.getElementById('event-ids').value;
        const ids = idsRaw.split(',').map((x) => x.trim()).filter(Boolean);
        if (ids.length === 0) {
            showBanner('error', 'Enter at least one event ID.');
            return;
        }

        showBanner('ok', 'Loading event requirement totals...');
        const rows = await request(`/requirements/totals/events?event_ids=${encodeURIComponent(ids.join(','))}`);
        const groupedByEvent = {};
        for (const row of enrichRows(Array.isArray(rows) ? rows : [])) {
            const eventId = row.cleaning_event_id || 'unknown';
            if (!groupedByEvent[eventId]) groupedByEvent[eventId] = [];
            groupedByEvent[eventId].push(row);
        }

        const keys = Object.keys(groupedByEvent);
        if (keys.length === 0) {
            eventOutput.innerHTML = '<p>No event-level requirement totals found.</p>';
            showBanner('ok', 'No event totals returned.');
            return;
        }

        eventOutput.innerHTML = keys.map((eventId) => (
            groupedTableHtml(`Event ${eventId}`, groupedByEvent[eventId], 'Context: event totals grouped by item')
        )).join('<hr>');
        showBanner('ok', 'Event requirement totals loaded.');
    }

    async function loadDayTotals() {
        const date = document.getElementById('day-date').value;
        const propertyId = dayPropertySelect.value;
        if (!date) {
            showBanner('error', 'Select a day date first.');
            return;
        }

        const q = new URLSearchParams({ date });
        if (propertyId) q.set('property_id', propertyId);

        showBanner('ok', 'Loading day requirement totals...');
        const rows = enrichRows(await request(`/requirements/totals/day?${q.toString()}`));
        const propertyLabel = propertyId ? `property ${propertyId}` : 'all properties';
        dayOutput.innerHTML = groupedTableHtml('Day Totals', rows, `Context: ${date}, ${propertyLabel}`);
        showBanner('ok', 'Day totals loaded.');
    }

    async function loadRangeTotals() {
        const propertyId = rangePropertySelect.value;
        const from = document.getElementById('range-from-date').value;
        const to = document.getElementById('range-to-date').value;
        if (!propertyId || !from || !to) {
            showBanner('error', 'Select property + from + to for range totals.');
            return;
        }

        const q = new URLSearchParams({ from_date: from, to_date: to });
        showBanner('ok', 'Loading property-range requirement totals...');
        const rows = enrichRows(await request(`/requirements/totals/property/${encodeURIComponent(propertyId)}?${q.toString()}`));
        rangeOutput.innerHTML = groupedTableHtml('Property Range Totals', rows, `Context: property ${propertyId}, ${from} to ${to}`);
        showBanner('ok', 'Property-range totals loaded.');
    }

    document.getElementById('load-event-totals')?.addEventListener('click', async () => {
        try {
            await loadEventTotals();
        } catch (err) {
            showBanner('error', `Event totals failed: ${err.message || 'request_failed'}`);
        }
    });

    document.getElementById('load-day-totals')?.addEventListener('click', async () => {
        try {
            await loadDayTotals();
        } catch (err) {
            showBanner('error', `Day totals failed: ${err.message || 'request_failed'}`);
        }
    });

    document.getElementById('load-range-totals')?.addEventListener('click', async () => {
        try {
            await loadRangeTotals();
        } catch (err) {
            showBanner('error', `Range totals failed: ${err.message || 'request_failed'}`);
        }
    });

    (async () => {
        try {
            const today = new Date().toISOString().slice(0, 10);
            document.getElementById('day-date').value = today;
            document.getElementById('range-from-date').value = today;
            document.getElementById('range-to-date').value = today;
            await loadMeta();
            showBanner('ok', 'Requirements workspace ready.');
        } catch (err) {
            showBanner('error', `Failed to initialize requirements page: ${err.message || 'request_failed'}`);
        }
    })();
})();
