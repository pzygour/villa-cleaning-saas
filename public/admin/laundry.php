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
                <label>Property ID (optional) <input type="text" v-model="createForm.property_id"></label>
                <label>From Location ID <input type="text" v-model="createForm.from_location_id"></label>
                <label>To Location ID <input type="text" v-model="createForm.to_location_id"></label>
                <label>Expected Return Date <input type="datetime-local" v-model="createForm.expected_return_date"></label>
                <label>Created By User ID <input type="text" v-model="createForm.created_by_user_id"></label>
                <label class="full">Note <input type="text" v-model="createForm.note"></label>
            </div>

            <h4>Handover Items</h4>
            <div v-for="(row, idx) in createForm.items" :key="'create-'+idx" class="row-editor">
                <input type="text" v-model="row.item_id" placeholder="item_id">
                <input type="number" min="0.01" step="0.01" v-model.number="row.quantity_sent" placeholder="qty sent">
                <button @click="removeCreateItem(idx)">Remove</button>
            </div>
            <div class="actions-inline">
                <button @click="addCreateItem">Add Item Row</button>
                <button @click="createHandover">Create Handover</button>
            </div>
        </section>

        <section class="panel nested">
            <h3>Process Return</h3>
            <div class="form-grid">
                <label>Handover ID <input type="text" v-model="returnForm.handover_id"></label>
                <label>Created By User ID <input type="text" v-model="returnForm.created_by_user_id"></label>
                <label class="full">Note <input type="text" v-model="returnForm.note"></label>
            </div>
            <h4>Return Items</h4>
            <div v-for="(row, idx) in returnForm.items" :key="'return-'+idx" class="row-editor">
                <input type="text" v-model="row.item_id" placeholder="item_id">
                <input type="number" min="0.01" step="0.01" v-model.number="row.quantity_returned" placeholder="qty returned">
                <button @click="removeReturnItem(idx)">Remove</button>
            </div>
            <div class="actions-inline">
                <button @click="addReturnItem">Add Return Row</button>
                <button @click="processReturn">Submit Return</button>
            </div>
        </section>

        <section class="panel nested">
            <h3>Open Handovers</h3>
            <button @click="loadOpen">Refresh Open Handovers</button>
            <pre>{{ JSON.stringify(openHandovers, null, 2) }}</pre>
        </section>

        <section class="panel nested">
            <h3>Handover Detail + Pending Returns</h3>
            <div class="form-grid">
                <label>Handover ID <input type="text" v-model="detailHandoverId"></label>
                <div><button @click="loadDetail">Load Detail</button></div>
                <div><button @click="loadPending">Load Pending</button></div>
            </div>
            <pre>{{ JSON.stringify(detailData, null, 2) }}</pre>
            <pre>{{ JSON.stringify(pendingData, null, 2) }}</pre>
        </section>
    </div>
</section>
<?php
$html = (string) ob_get_clean();
render_admin_page('Laundry', $html, [
    'https://unpkg.com/vue@3/dist/vue.global.prod.js',
    '/assets/js/components/laundry-operations.js',
]);
