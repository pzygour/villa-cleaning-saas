<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

ob_start();
?>
<section class="panel">
    <h3>Inventory Operations</h3>
    <p id="inventory-banner" class="banner" style="display:none"></p>
    <div id="inventory-actions" class="form-grid">
        <form id="inventory-movement-form" class="form-grid">
            <h4 class="full">Manual Stock Movement</h4>
            <label>Action Type
                <select name="transaction_type">
                    <option value="in">Stock In (+)</option>
                    <option value="out">Stock Out (-)</option>
                    <option value="adjustment">Adjustment</option>
                </select>
            </label>
            <label>Location
                <select id="movement-location" name="location_id" required></select>
            </label>
            <label>Item
                <select id="movement-item" name="item_id" required></select>
            </label>
            <label>Quantity <input name="quantity" type="number" step="0.01" min="0.01" required></label>
            <label>Reference Type <input name="reference_type" type="text" placeholder="manual_adjustment"></label>
            <label>Reference ID <input name="reference_id" type="text"></label>
            <label>Created By
                <input name="created_by_user_id" type="text" value="<?= htmlspecialchars((string) ($_SESSION['user_id'] ?? '')) ?>">
            </label>
            <label class="full">Note <input name="note" type="text"></label>
            <div><button id="movement-submit" type="submit">Post Movement</button></div>
        </form>

        <form id="inventory-reserve-form" class="form-grid">
            <h4 class="full">Reservation / Unreservation</h4>
            <label>Action
                <select name="mode">
                    <option value="reserve">Reserve for Event</option>
                    <option value="unreserve">Unreserve</option>
                </select>
            </label>
            <label>Event ID <input name="event_id" type="text" required></label>
            <label>Location
                <select id="reserve-location" name="location_id" required></select>
            </label>
            <label>Created By
                <input name="created_by_user_id" type="text" value="<?= htmlspecialchars((string) ($_SESSION['user_id'] ?? '')) ?>">
            </label>
            <div><button id="reserve-submit" type="submit">Run Action</button></div>
        </form>
    </div>
</section>

<section class="panel">
    <h3>Balances by Location</h3>
    <div class="form-grid">
        <label>Location
            <select id="location-id"></select>
        </label>
        <label>Item Filter (optional) <input id="balance-item-filter" type="text" placeholder="item id or name"></label>
        <div><button id="load-balances">Load</button></div>
    </div>
    <table>
        <thead><tr><th>Item</th><th>On Hand</th><th>Reserved</th><th>Available</th></tr></thead>
        <tbody id="balances-table"></tbody>
    </table>
</section>

<section class="panel">
    <h3>Movements by Location</h3>
    <div class="form-grid">
        <label>Location
            <select id="mv-location-id"></select>
        </label>
        <label>From <input id="mv-from" type="date"></label>
        <label>To <input id="mv-to" type="date"></label>
        <div><button id="load-movements">Load</button></div>
    </div>
    <table>
        <thead><tr><th>Date</th><th>Type</th><th>Item</th><th>Qty</th><th>Reference</th><th>Note</th></tr></thead>
        <tbody id="movements-table"></tbody>
    </table>
</section>

<section class="panel">
    <h3>Event Availability Projection</h3>
    <div class="form-grid">
        <label>Event ID <input id="av-event-id" type="text"></label>
        <label>Location
            <select id="av-location-id"></select>
        </label>
        <div><button id="load-availability">Load</button></div>
    </div>
    <table>
        <thead><tr><th>Item</th><th>Needed</th><th>Location Available</th><th>Shortage</th></tr></thead>
        <tbody id="availability-table"></tbody>
    </table>
</section>
<?php
$html = (string) ob_get_clean();
render_admin_page('Inventory', $html, [
    '/assets/js/components/inventory-ui-utils.js',
    '/assets/js/components/inventory-action-panel.js',
]);
