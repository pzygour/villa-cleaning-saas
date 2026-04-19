<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

ob_start();
?>
<section class="panel">
    <div id="laundry-app">
        <p v-if="feedback.message" :class="['banner', feedback.type]">{{ feedback.message }}</p>

        <section class="panel nested">
            <h3>Create Laundry Handover</h3>
            <div class="form-grid">
                <label>Property (optional)
                    <select v-model="createForm.property_id">
                        <option value="">-- none --</option>
                        <option v-for="p in properties" :key="p.id" :value="p.id">{{ p.name || p.id }}</option>
                    </select>
                </label>
                <label>From Location
                    <select v-model="createForm.from_location_id">
                        <option value="">-- select location --</option>
                        <option v-for="loc in locations" :key="'from-'+loc.id" :value="loc.id">{{ loc.name || loc.id }} ({{ loc.location_type }})</option>
                    </select>
                </label>
                <label>To Location
                    <select v-model="createForm.to_location_id">
                        <option value="">-- select location --</option>
                        <option v-for="loc in locations" :key="'to-'+loc.id" :value="loc.id">{{ loc.name || loc.id }} ({{ loc.location_type }})</option>
                    </select>
                </label>
                <label>Expected Return Date <input type="datetime-local" v-model="createForm.expected_return_date"></label>
                <label>Created By User ID <input type="text" v-model="createForm.created_by_user_id"></label>
                <label class="full">Note <input type="text" v-model="createForm.note"></label>
            </div>

            <h4>Handover Items</h4>
            <div v-for="(row, idx) in createForm.items" :key="'create-'+idx" class="row-editor">
                <select v-model="row.item_id">
                    <option value="">-- select item --</option>
                    <option v-for="item in items" :key="'item-'+idx+'-'+item.id" :value="item.id">{{ item.item_name || item.name || item.id }} ({{ item.item_type }})</option>
                </select>
                <input type="number" min="0.01" step="0.01" v-model.number="row.quantity_sent" placeholder="qty sent">
                <button @click="removeCreateItem(idx)">Remove</button>
            </div>
            <div class="actions-inline">
                <button @click="addCreateItem">Add Item Row</button>
                <button :disabled="loading.create" @click="createHandover">{{ loading.create ? 'Creating...' : 'Create Handover' }}</button>
            </div>
        </section>

        <section class="panel nested">
            <h3>Process Partial Return</h3>
            <div class="form-grid">
                <label>Handover ID <input type="text" v-model="returnForm.handover_id"></label>
                <div><button @click="loadPendingForReturn">Load Pending</button></div>
                <label>Created By User ID <input type="text" v-model="returnForm.created_by_user_id"></label>
                <label class="full">Note <input type="text" v-model="returnForm.note"></label>
            </div>
            <h4>Pending Return Items</h4>
            <div v-for="(row, idx) in returnForm.items" :key="'return-'+idx" class="row-editor">
                <input type="text" v-model="row.item_id" placeholder="item_id" readonly>
                <input type="number" min="0.01" step="0.01" v-model.number="row.quantity_returned" placeholder="qty returned">
                <small>pending: {{ Number(row.pending_return_quantity || 0).toFixed(2) }}</small>
                <button @click="removeReturnItem(idx)">Remove</button>
            </div>
            <div class="actions-inline">
                <button @click="addReturnItem">Add Return Row</button>
                <button :disabled="loading.return" @click="processReturn">{{ loading.return ? 'Submitting...' : 'Submit Return' }}</button>
            </div>
        </section>

        <section class="panel nested">
            <h3>Open Handovers</h3>
            <button :disabled="loading.open" @click="loadOpen">{{ loading.open ? 'Refreshing...' : 'Refresh Open Handovers' }}</button>
            <table>
                <thead><tr><th>ID</th><th>Status</th><th>Property</th><th>From</th><th>To</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                    <tr v-for="h in openHandovers" :key="h.id" :class="statusClass(h.status)">
                        <td>{{ h.id }}</td>
                        <td>{{ h.status }}</td>
                        <td>{{ h.property_name || '-' }}</td>
                        <td>{{ h.from_location_name || h.from_location_id }}</td>
                        <td>{{ h.to_location_name || h.to_location_id }}</td>
                        <td>{{ h.handover_date }}</td>
                        <td>
                            <button @click="openDetail(h.id)">Detail</button>
                            <button @click="quickReturnRemaining(h.id)">Return Remaining</button>
                        </td>
                    </tr>
                    <tr v-if="openHandovers.length === 0"><td colspan="7">No open handovers.</td></tr>
                </tbody>
            </table>
        </section>

        <section class="panel nested">
            <h3>Handover Detail + Pending Returns</h3>
            <div class="form-grid">
                <label>Handover ID <input type="text" v-model="detailHandoverId"></label>
                <div><button :disabled="loading.detail" @click="loadDetail">{{ loading.detail ? 'Loading...' : 'Load Detail' }}</button></div>
                <div><button :disabled="loading.pending" @click="loadPending">{{ loading.pending ? 'Loading...' : 'Load Pending' }}</button></div>
            </div>
            <div v-if="detailData && detailData.handover" class="form-grid">
                <div><strong>Status:</strong> {{ detailData.handover.status }}</div>
                <div><strong>Property:</strong> {{ detailData.handover.property_name || '-' }}</div>
                <div><strong>From:</strong> {{ detailData.handover.from_location_name || detailData.handover.from_location_id }}</div>
                <div><strong>To:</strong> {{ detailData.handover.to_location_name || detailData.handover.to_location_id }}</div>
                <div><strong>Date:</strong> {{ detailData.handover.handover_date }}</div>
                <div><strong>Expected Return:</strong> {{ detailData.handover.expected_return_date || '-' }}</div>
            </div>
            <table>
                <thead><tr><th>Item</th><th>Sent</th><th>Returned</th><th>Pending</th><th>Status</th></tr></thead>
                <tbody>
                    <tr v-for="row in detailRows" :key="row.item_id" :class="statusClass(row.status)">
                        <td>{{ row.item_name || row.item_id }}</td>
                        <td>{{ row.quantity_sent }}</td>
                        <td>{{ row.quantity_returned }}</td>
                        <td>{{ Number(row.pending_return_quantity || (Number(row.quantity_sent) - Number(row.quantity_returned))).toFixed(2) }}</td>
                        <td>{{ row.status }}</td>
                    </tr>
                    <tr v-if="detailRows.length === 0"><td colspan="5">No detail rows loaded.</td></tr>
                </tbody>
            </table>
            <h4>Pending Return Rows</h4>
            <table>
                <thead><tr><th>Item</th><th>Sent</th><th>Returned</th><th>Pending</th><th>Status</th></tr></thead>
                <tbody>
                    <tr v-for="row in pendingRows" :key="'pending-'+row.item_id">
                        <td>{{ row.item_name || row.item_id }}</td>
                        <td>{{ Number(row.quantity_sent).toFixed(2) }}</td>
                        <td>{{ Number(row.quantity_returned).toFixed(2) }}</td>
                        <td>{{ Number(row.pending_return_quantity).toFixed(2) }}</td>
                        <td>{{ row.status }}</td>
                    </tr>
                    <tr v-if="pendingRows.length === 0"><td colspan="5">No pending quantities.</td></tr>
                </tbody>
            </table>
        </section>
    </div>
</section>
<?php
$html = (string) ob_get_clean();
render_admin_page('Laundry', $html, [
    'https://unpkg.com/vue@3/dist/vue.global.prod.js',
    '/assets/js/components/laundry-ui-utils.js',
    '/assets/js/components/laundry-operations.js',
]);
