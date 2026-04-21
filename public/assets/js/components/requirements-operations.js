(function () {
    const request = window.AdminRequest;

    const banner = document.getElementById('requirements-banner');
    const eventOutput = document.getElementById('event-totals-output');
    const dayOutput = document.getElementById('day-totals-output');
    const rangeOutput = document.getElementById('range-totals-output');

    const dayPropertySelect = document.getElementById('day-property-id');
    const rangePropertySelect = document.getElementById('range-property-id');

    const eventTotalsButton = document.getElementById('load-event-totals');
    const dayTotalsButton = document.getElementById('load-day-totals');
    const rangeTotalsButton = document.getElementById('load-range-totals');

    const utils = window.RequirementDrilldownUtils;
    let itemMap = {};

    function showOk(message) {
        request.setBanner(banner, 'ok', message);
    }

    function showError(message) {
        request.setBanner(banner, 'error', message);
    }

    async function api(path) {
        try {
            return await request.request(path);
        } catch (err) {
            return {
                ok: false,
                data: null,
                error: err?.message || 'request_failed',
            };
        }
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
            api('/properties'),
            api('/items'),
        ]);

        if (!properties.ok || !items.ok) {
            showError('Failed to load requirements lookup metadata.');
            return false;
        }

        for (const item of (items.data || [])) {
            itemMap[item.id] = item.item_name || item.name || item.id;
        }

        for (const p of (properties.data || [])) {
            const o1 = document.createElement('option');
            o1.value = p.id;
            o1.textContent = p.name || p.id;
            dayPropertySelect.appendChild(o1);

            const o2 = document.createElement('option');
            o2.value = p.id;
            o2.textContent = p.name || p.id;
            rangePropertySelect.appendChild(o2);
        }

        return true;
    }

    async function loadEventTotals() {
        const idsRaw = document.getElementById('event-ids').value;
        const ids = idsRaw.split(',').map((x) => x.trim()).filter(Boolean);
        if (ids.length === 0) {
            showError('Enter at least one event ID.');
            eventOutput.innerHTML = '<p>Provide one or more event IDs to query totals.</p>';
            return;
        }

        showOk('Loading event requirement totals...');
        const result = await api(`/requirements/totals/events?event_ids=${encodeURIComponent(ids.join(','))}`);

        if (!result.ok) {
            showError(`Event totals failed: ${result.error}`);
            eventOutput.innerHTML = '<p>Unable to load event totals.</p>';
            return;
        }

        const groupedByEvent = {};
        for (const row of enrichRows(Array.isArray(result.data) ? result.data : [])) {
            const eventId = row.cleaning_event_id || 'unknown';
            if (!groupedByEvent[eventId]) groupedByEvent[eventId] = [];
            groupedByEvent[eventId].push(row);
        }

        const keys = Object.keys(groupedByEvent);
        if (keys.length === 0) {
            eventOutput.innerHTML = '<p>No event-level requirement totals found.</p>';
            showOk('No event totals returned.');
            return;
        }

        eventOutput.innerHTML = keys.map((eventId) => (
            groupedTableHtml(`Event ${eventId}`, groupedByEvent[eventId], 'Context: event totals grouped by item')
        )).join('<hr>');

        showOk('Event requirement totals loaded.');
    }

    async function loadDayTotals() {
        const date = document.getElementById('day-date').value;
        const propertyId = dayPropertySelect.value;
        if (!date) {
            showError('Select a day date first.');
            dayOutput.innerHTML = '<p>Select a date to run day totals.</p>';
            return;
        }

        const query = new URLSearchParams({ date });
        if (propertyId) query.set('property_id', propertyId);

        showOk('Loading day requirement totals...');
        const result = await api(`/requirements/totals/day?${query.toString()}`);

        if (!result.ok) {
            showError(`Day totals failed: ${result.error}`);
            dayOutput.innerHTML = '<p>Unable to load day totals.</p>';
            return;
        }

        const rows = enrichRows(Array.isArray(result.data) ? result.data : []);
        const propertyLabel = propertyId ? `property ${propertyId}` : 'all properties';
        dayOutput.innerHTML = groupedTableHtml('Day Totals', rows, `Context: ${date}, ${propertyLabel}`);
        showOk('Day totals loaded.');
    }

    async function loadRangeTotals() {
        const propertyId = rangePropertySelect.value;
        const from = document.getElementById('range-from-date').value;
        const to = document.getElementById('range-to-date').value;

        if (!propertyId || !from || !to) {
            showError('Select property + from + to for range totals.');
            rangeOutput.innerHTML = '<p>Select property and full date range to run totals.</p>';
            return;
        }

        const query = new URLSearchParams({ from_date: from, to_date: to });
        showOk('Loading property-range requirement totals...');
        const result = await api(`/requirements/totals/property/${encodeURIComponent(propertyId)}?${query.toString()}`);

        if (!result.ok) {
            showError(`Range totals failed: ${result.error}`);
            rangeOutput.innerHTML = '<p>Unable to load property-range totals.</p>';
            return;
        }

        const rows = enrichRows(Array.isArray(result.data) ? result.data : []);
        rangeOutput.innerHTML = groupedTableHtml('Property Range Totals', rows, `Context: property ${propertyId}, ${from} to ${to}`);
        showOk('Property-range totals loaded.');
    }

    eventTotalsButton?.addEventListener('click', async () => {
        await request.withButtonLoading(eventTotalsButton, 'Loading...', loadEventTotals);
    });

    dayTotalsButton?.addEventListener('click', async () => {
        await request.withButtonLoading(dayTotalsButton, 'Loading...', loadDayTotals);
    });

    rangeTotalsButton?.addEventListener('click', async () => {
        await request.withButtonLoading(rangeTotalsButton, 'Loading...', loadRangeTotals);
    });

    (async () => {
        const today = new Date().toISOString().slice(0, 10);
        document.getElementById('day-date').value = today;
        document.getElementById('range-from-date').value = today;
        document.getElementById('range-to-date').value = today;

        const ready = await loadMeta();
        if (ready) {
            showOk('Requirements workspace ready.');
        }
    })();
})();
