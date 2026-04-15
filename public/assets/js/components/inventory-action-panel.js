(function () {
    const banner = document.getElementById('inventory-banner');
    const movementForm = document.getElementById('inventory-movement-form');
    const reserveForm = document.getElementById('inventory-reserve-form');
    const balancesTable = document.getElementById('balances-table');
    const movementsTable = document.getElementById('movements-table');
    const availabilityTable = document.getElementById('availability-table');
    let latestBalances = [];

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
            return `<tr><td>${r.item_name || r.item_id}</td><td>${onHand}</td><td>${reserved}</td><td>${available}</td></tr>`;
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
            rendered.push(`<tr><td>${date}</td><td>${r.transaction_type}</td><td>${r.item_name || r.item_id}</td><td>${r.quantity}</td><td>${ref}</td><td>${r.note || ''}</td></tr>`);
        }
        movementsTable.innerHTML = rendered.join('') || '<tr><td colspan="6">No movement rows found.</td></tr>';
    }

    function renderAvailability(rows) {
        availabilityTable.innerHTML = rows.map((r) => {
            return `<tr><td>${r.item_name || r.item_id}</td><td>${r.still_needed}</td><td>${r.location_available}</td><td>${r.shortage_quantity}</td></tr>`;
        }).join('') || '<tr><td colspan="4">No availability rows found.</td></tr>';
    }

    if (movementForm) {
        movementForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = Object.fromEntries(new FormData(movementForm).entries());
            payload.quantity = Number(payload.quantity);

            const res = await fetch('/inventory/transactions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });

            const data = await readJson(res);
            if (res.ok) {
                showBanner('ok', `Inventory movement posted. Transaction ID: ${data.id || '-'}`);
                movementForm.reset();
            } else {
                showBanner('error', `Inventory action failed: ${data.error || 'request_failed'}`);
            }
        });
    }

    if (reserveForm) {
        reserveForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = Object.fromEntries(new FormData(reserveForm).entries());
            const mode = payload.mode;
            const eventId = payload.event_id;

            const res = await fetch(`/inventory/reservations/events/${encodeURIComponent(eventId)}/${mode}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    location_id: payload.location_id,
                    created_by_user_id: payload.created_by_user_id || null,
                }),
            });

            const data = await readJson(res);
            if (res.ok) {
                showBanner('ok', `${mode} action completed.`);
                reserveForm.reset();
            } else {
                showBanner('error', `Reservation action failed: ${data.error || 'request_failed'}`);
            }
        });
    }

    document.getElementById('load-balances')?.addEventListener('click', async () => {
        const id = document.getElementById('location-id').value;
        showBanner('ok', 'Loading balances...');
        const res = await fetch(`/inventory/balances/location/${encodeURIComponent(id)}`);
        const rows = await readJson(res);
        renderBalances(rows);
    });

    document.getElementById('balance-item-filter')?.addEventListener('input', () => {
        if (latestBalances.length === 0) return;
        renderBalances(latestBalances);
    });

    document.getElementById('load-movements')?.addEventListener('click', async () => {
        const id = document.getElementById('mv-location-id').value;
        const from = document.getElementById('mv-from').value;
        const to = document.getElementById('mv-to').value;
        const q = new URLSearchParams({ from_date: from, to_date: to });
        showBanner('ok', 'Loading movement history...');
        const res = await fetch(`/inventory/movements/location/${encodeURIComponent(id)}?${q.toString()}`);
        renderMovements(await readJson(res));
    });

    document.getElementById('load-availability')?.addEventListener('click', async () => {
        const eventId = document.getElementById('av-event-id').value;
        const locationId = document.getElementById('av-location-id').value;
        showBanner('ok', 'Loading event availability...');
        const res = await fetch(`/inventory/availability/events/${encodeURIComponent(eventId)}?location_id=${encodeURIComponent(locationId)}`);
        renderAvailability(await readJson(res));
    });
})();
