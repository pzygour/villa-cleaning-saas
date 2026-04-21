(function () {
    const request = window.AdminRequest;

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

    const loadBalancesButton = document.getElementById('load-balances');
    const loadMovementsButton = document.getElementById('load-movements');
    const loadAvailabilityButton = document.getElementById('load-availability');

    let latestBalances = [];

    function showOk(message) {
        request.setBanner(banner, 'ok', message);
    }

    function showError(message) {
        request.setBanner(banner, 'error', message);
    }

    function optionRow(value, label) {
        const opt = document.createElement('option');
        opt.value = value;
        opt.textContent = label;
        return opt;
    }

    function populateLocationSelect(select, locations) {
        if (!select) {
            return;
        }

        select.innerHTML = '';
        select.appendChild(optionRow('', '-- select location --'));

        for (const row of locations) {
            const label = `${row.name || row.location_name || row.id} (${row.location_type || 'location'})`;
            select.appendChild(optionRow(row.id, label));
        }
    }

    function populateItemSelect(select, items) {
        if (!select) {
            return;
        }

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
            if (!itemFilter) {
                return true;
            }

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
            showError('Select a location to load balances.');
            return false;
        }

        showOk('Loading balances...');
        const result = await request.request(`/inventory/balances/location/${encodeURIComponent(id)}`);

        if (!result.ok) {
            showError(`Load balances failed: ${result.error}`);
            return false;
        }

        renderBalances(Array.isArray(result.data) ? result.data : []);
        showOk('Balances loaded.');
        return true;
    }

    async function loadMovements() {
        const id = movementLocationForList?.value || '';
        if (!id) {
            showError('Select a location to load movements.');
            return false;
        }

        const from = document.getElementById('mv-from').value;
        const to = document.getElementById('mv-to').value;
        const query = new URLSearchParams({ from_date: from, to_date: to });

        showOk('Loading movement history...');
        const result = await request.request(`/inventory/movements/location/${encodeURIComponent(id)}?${query.toString()}`);

        if (!result.ok) {
            showError(`Load movements failed: ${result.error}`);
            return false;
        }

        renderMovements(Array.isArray(result.data) ? result.data : []);
        showOk('Movement history loaded.');
        return true;
    }

    async function loadAvailability() {
        const eventId = (document.getElementById('av-event-id').value || '').trim();
        const locationId = availabilityLocation?.value || '';

        if (!eventId || !locationId) {
            showError('Provide event and location for availability lookup.');
            return false;
        }

        showOk('Loading event availability...');
        const result = await request.request(`/inventory/availability/events/${encodeURIComponent(eventId)}?location_id=${encodeURIComponent(locationId)}`);

        if (!result.ok) {
            showError(`Load availability failed: ${result.error}`);
            return false;
        }

        renderAvailability(Array.isArray(result.data) ? result.data : []);
        showOk('Availability loaded.');
        return true;
    }

    async function bootstrapSelectors() {
        const [locations, items] = await Promise.all([
            request.request('/inventory/locations'),
            request.request('/items'),
        ]);

        if (!locations.ok) {
            showError(`Failed to load inventory locations: ${locations.error}`);
            return;
        }

        if (!items.ok) {
            showError(`Failed to load item catalog: ${items.error}`);
            return;
        }

        const locationRows = Array.isArray(locations.data) ? locations.data : [];
        populateLocationSelect(balanceLocation, locationRows);
        populateLocationSelect(movementLocation, locationRows);
        populateLocationSelect(reserveLocation, locationRows);
        populateLocationSelect(movementLocationForList, locationRows);
        populateLocationSelect(availabilityLocation, locationRows);

        const itemRows = Array.isArray(items.data) ? items.data : [];
        populateItemSelect(movementItem, itemRows);
    }

    if (movementForm) {
        movementForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            await request.withButtonLoading(movementSubmit, 'Posting...', async () => {
                const payload = Object.fromEntries(new FormData(movementForm).entries());
                payload.quantity = Number(payload.quantity);

                const result = await request.request('/inventory/transactions', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });

                if (!result.ok) {
                    showError(`Inventory action failed: ${result.error}`);
                    return;
                }

                showOk(`Movement posted. Transaction ID: ${result.data?.id || '-'}`);
                await loadBalances();
                await loadMovements();
            });
        });
    }

    if (reserveForm) {
        reserveForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            await request.withButtonLoading(reserveSubmit, 'Submitting...', async () => {
                const payload = Object.fromEntries(new FormData(reserveForm).entries());
                const mode = payload.mode;
                const eventId = (payload.event_id || '').trim();

                if (!eventId) {
                    showError('Event ID is required for reservation actions.');
                    return;
                }

                const result = await request.request(`/inventory/reservations/events/${encodeURIComponent(eventId)}/${mode}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        location_id: payload.location_id,
                        created_by_user_id: payload.created_by_user_id || null,
                    }),
                });

                if (!result.ok) {
                    showError(`Reservation action failed: ${result.error}`);
                    return;
                }

                showOk(`${mode} action completed.`);
                await loadBalances();
                await loadAvailability();
            });
        });
    }

    loadBalancesButton?.addEventListener('click', async () => {
        await request.withButtonLoading(loadBalancesButton, 'Loading...', loadBalances);
    });

    document.getElementById('balance-item-filter')?.addEventListener('input', () => {
        if (latestBalances.length > 0) {
            renderBalances(latestBalances);
        }
    });

    loadMovementsButton?.addEventListener('click', async () => {
        await request.withButtonLoading(loadMovementsButton, 'Loading...', loadMovements);
    });

    loadAvailabilityButton?.addEventListener('click', async () => {
        await request.withButtonLoading(loadAvailabilityButton, 'Loading...', loadAvailability);
    });

    (async () => {
        const today = new Date();
        const from = new Date(today.getTime() - 6 * 24 * 60 * 60 * 1000);
        document.getElementById('mv-from').value = from.toISOString().slice(0, 10);
        document.getElementById('mv-to').value = today.toISOString().slice(0, 10);

        await bootstrapSelectors();
        showOk('Inventory tools ready.');
    })();
})();
