(function () {
    const ui = window.InventoryUi;
    const banner = document.getElementById('inventory-banner');
    const movementForm = document.getElementById('inventory-movement-form');
    const reserveForm = document.getElementById('inventory-reserve-form');
    const movementSubmit = document.getElementById('movement-submit');
    const reserveSubmit = document.getElementById('reserve-submit');

    const balancesTable = document.getElementById('balances-table');
    const movementsTable = document.getElementById('movements-table');
    const availabilityTable = document.getElementById('availability-table');

    const balanceLocation = document.getElementById('location-id');
    const movementLocation = document.getElementById('movement-location');
    const reserveLocation = document.getElementById('reserve-location');
    const movementLocationForList = document.getElementById('mv-location-id');
    const availabilityLocation = document.getElementById('av-location-id');
    const movementItem = document.getElementById('movement-item');

    let latestBalances = [];

    function showBanner(type, message) {
        if (!banner) return;
        banner.style.display = 'block';
        banner.className = `banner ${type}`;
        banner.textContent = message;
    }

    function setLoading(button, loading, textWhenLoading) {
        if (!button) return;
        if (loading) {
            button.dataset.originalText = button.textContent;
            button.textContent = textWhenLoading;
            button.disabled = true;
            return;
        }

        button.textContent = button.dataset.originalText || button.textContent;
        button.disabled = false;
    }

    function optionRow(value, label) {
        const opt = document.createElement('option');
        opt.value = value;
        opt.textContent = label;
        return opt;
    }

    function populateLocationSelect(select, locations) {
        if (!select) return;
        select.innerHTML = '';
        select.appendChild(optionRow('', '-- select location --'));
        for (const row of locations) {
            const label = `${row.name || row.location_name || row.id} (${row.location_type || 'location'})`;
            select.appendChild(optionRow(row.id, label));
        }
    }

    function populateItemSelect(select, items) {
        if (!select) return;
        select.innerHTML = '';
        select.appendChild(optionRow('', '-- select item --'));
        for (const row of items) {
            const label = `${row.item_name || row.name || row.id} (${row.item_type || 'item'})`;
            select.appendChild(optionRow(row.id, label));
        }
    }

    function renderBalances(rows) {
        latestBalances = rows;
        const itemFilter = (document.getElementById('balance-item-filter')?.value || '').trim().toLowerCase();
        const filtered = rows.filter((r) => {
            if (!itemFilter) return true;
            return [r.item_id, r.item_name, r.item_type].join(' ').toLowerCase().includes(itemFilter);
        });

        balancesTable.innerHTML = filtered.map((r) => {
            const onHand = Number(r.on_hand_quantity || 0);
            const reserved = Number(r.reserved_quantity || 0);
            const available = onHand - reserved;
            return `<tr><td>${r.item_name || r.item_id}</td><td>${onHand.toFixed(2)}</td><td>${reserved.toFixed(2)}</td><td>${available.toFixed(2)}</td></tr>`;
        }).join('') || '<tr><td colspan="4">No balances found.</td></tr>';
    }

    function renderMovements(rows) {
        let lastDate = '';
        const rendered = [];
        for (const r of rows) {
            const date = String(r.transaction_date || '').slice(0, 10);
            if (date !== lastDate) {
                rendered.push(`<tr class="row-warning"><td colspan="6">${date || 'unknown date'}</td></tr>`);
                lastDate = date;
            }
            const ref = `${r.reference_type || '-'}:${r.reference_id || '-'}`;
            rendered.push(`<tr><td>${date}</td><td>${r.transaction_type}</td><td>${r.item_name || r.item_id}</td><td>${Number(r.quantity || 0).toFixed(2)}</td><td>${ref}</td><td>${r.note || ''}</td></tr>`);
        }

        movementsTable.innerHTML = rendered.join('') || '<tr><td colspan="6">No movement rows found.</td></tr>';
    }

    function renderAvailability(rows) {
        availabilityTable.innerHTML = rows.map((r) => {
            const shortage = Number(r.shortage_quantity || 0);
            const rowClass = shortage > 0 ? 'row-warning' : 'row-success';
            return `<tr class="${rowClass}"><td>${r.item_name || r.item_id}</td><td>${Number(r.still_needed || 0).toFixed(2)}</td><td>${Number(r.location_available || 0).toFixed(2)}</td><td>${shortage.toFixed(2)}</td></tr>`;
        }).join('') || '<tr><td colspan="4">No availability rows found.</td></tr>';
    }

    async function loadBalances() {
        const id = balanceLocation?.value || '';
        if (!id) {
            showBanner('error', 'Select a location to load balances.');
            return;
        }

        showBanner('ok', 'Loading balances...');
        const rows = await ui.requestJson(`/inventory/balances/location/${encodeURIComponent(id)}`);
        renderBalances(Array.isArray(rows) ? rows : []);
        showBanner('ok', 'Balances loaded.');
    }

    async function loadMovements() {
        const id = movementLocationForList?.value || '';
        if (!id) {
            showBanner('error', 'Select a location to load movements.');
            return;
        }

        const from = document.getElementById('mv-from').value;
        const to = document.getElementById('mv-to').value;
        const q = new URLSearchParams({ from_date: from, to_date: to });

        showBanner('ok', 'Loading movement history...');
        const rows = await ui.requestJson(`/inventory/movements/location/${encodeURIComponent(id)}?${q.toString()}`);
        renderMovements(Array.isArray(rows) ? rows : []);
        showBanner('ok', 'Movement history loaded.');
    }

    async function loadAvailability() {
        const eventId = document.getElementById('av-event-id').value;
        const locationId = availabilityLocation?.value || '';
        if (!eventId || !locationId) {
            showBanner('error', 'Provide event and location for availability lookup.');
            return;
        }

        showBanner('ok', 'Loading event availability...');
        const rows = await ui.requestJson(`/inventory/availability/events/${encodeURIComponent(eventId)}?location_id=${encodeURIComponent(locationId)}`);
        renderAvailability(Array.isArray(rows) ? rows : []);
        showBanner('ok', 'Availability loaded.');
    }

    async function bootstrapSelectors() {
        const [locations, items] = await Promise.all([
            ui.requestJson('/inventory/locations'),
            ui.requestJson('/items'),
        ]);

        const locationRows = Array.isArray(locations) ? locations : [];
        populateLocationSelect(balanceLocation, locationRows);
        populateLocationSelect(movementLocation, locationRows);
        populateLocationSelect(reserveLocation, locationRows);
        populateLocationSelect(movementLocationForList, locationRows);
        populateLocationSelect(availabilityLocation, locationRows);

        const itemRows = Array.isArray(items) ? items : [];
        populateItemSelect(movementItem, itemRows);
    }

    if (movementForm) {
        movementForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            setLoading(movementSubmit, true, 'Posting...');

            try {
                const payload = Object.fromEntries(new FormData(movementForm).entries());
                payload.quantity = Number(payload.quantity);
                const data = await ui.requestJson('/inventory/transactions', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });

                showBanner('ok', `Movement posted. Transaction ID: ${data.id || '-'}`);
                await loadBalances();
                await loadMovements();
            } catch (err) {
                showBanner('error', `Inventory action failed: ${err.message || 'request_failed'}`);
            } finally {
                setLoading(movementSubmit, false, 'Post Movement');
            }
        });
    }

    if (reserveForm) {
        reserveForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            setLoading(reserveSubmit, true, 'Submitting...');

            try {
                const payload = Object.fromEntries(new FormData(reserveForm).entries());
                const mode = payload.mode;
                const eventId = payload.event_id;

                await ui.requestJson(`/inventory/reservations/events/${encodeURIComponent(eventId)}/${mode}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        location_id: payload.location_id,
                        created_by_user_id: payload.created_by_user_id || null,
                    }),
                });

                showBanner('ok', `${mode} action completed.`);
                await loadBalances();
                await loadAvailability();
            } catch (err) {
                showBanner('error', `Reservation action failed: ${err.message || 'request_failed'}`);
            } finally {
                setLoading(reserveSubmit, false, 'Run Action');
            }
        });
    }

    document.getElementById('load-balances')?.addEventListener('click', async () => {
        try {
            await loadBalances();
        } catch (err) {
            showBanner('error', `Load balances failed: ${err.message || 'request_failed'}`);
        }
    });

    document.getElementById('balance-item-filter')?.addEventListener('input', () => {
        if (latestBalances.length === 0) return;
        renderBalances(latestBalances);
    });

    document.getElementById('load-movements')?.addEventListener('click', async () => {
        try {
            await loadMovements();
        } catch (err) {
            showBanner('error', `Load movements failed: ${err.message || 'request_failed'}`);
        }
    });

    document.getElementById('load-availability')?.addEventListener('click', async () => {
        try {
            await loadAvailability();
        } catch (err) {
            showBanner('error', `Load availability failed: ${err.message || 'request_failed'}`);
        }
    });

    (async () => {
        try {
            const today = new Date();
            const from = new Date(today.getTime() - 6 * 24 * 60 * 60 * 1000);
            document.getElementById('mv-from').value = from.toISOString().slice(0, 10);
            document.getElementById('mv-to').value = today.toISOString().slice(0, 10);

            await bootstrapSelectors();
            showBanner('ok', 'Inventory tools ready.');
        } catch (err) {
            showBanner('error', `Failed to initialize inventory tools: ${err.message || 'request_failed'}`);
        }
    })();
})();
