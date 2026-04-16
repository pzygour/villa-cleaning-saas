(function () {
    const banner = document.getElementById('inventory-banner');
    const movementForm = document.getElementById('inventory-movement-form');
    const reserveForm = document.getElementById('inventory-reserve-form');

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
        const res = await fetch(`/inventory/balances/location/${encodeURIComponent(id)}`);
        document.getElementById('balances-output').textContent = JSON.stringify(await readJson(res), null, 2);
    });

    document.getElementById('load-movements')?.addEventListener('click', async () => {
        const id = document.getElementById('mv-location-id').value;
        const from = document.getElementById('mv-from').value;
        const to = document.getElementById('mv-to').value;
        const q = new URLSearchParams({ from_date: from, to_date: to });
        const res = await fetch(`/inventory/movements/location/${encodeURIComponent(id)}?${q.toString()}`);
        document.getElementById('movements-output').textContent = JSON.stringify(await readJson(res), null, 2);
    });

    document.getElementById('load-availability')?.addEventListener('click', async () => {
        const eventId = document.getElementById('av-event-id').value;
        const locationId = document.getElementById('av-location-id').value;
        const res = await fetch(`/inventory/availability/events/${encodeURIComponent(eventId)}?location_id=${encodeURIComponent(locationId)}`);
        document.getElementById('availability-output').textContent = JSON.stringify(await readJson(res), null, 2);
    });
})();
