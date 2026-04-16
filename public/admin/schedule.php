<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

ob_start();
?>
<section class="panel">
    <div id="schedule-app">
        <div class="form-grid">
            <label>View
                <select v-model="filters.view">
                    <option value="day">Day</option>
                    <option value="range">Date Range</option>
                </select>
            </label>
            <label v-if="filters.view === 'day'">Date <input type="date" v-model="filters.date"></label>
            <label v-if="filters.view === 'range'">From <input type="date" v-model="filters.from_date"></label>
            <label v-if="filters.view === 'range'">To <input type="date" v-model="filters.to_date"></label>
            <label>Property ID <input type="text" v-model="filters.property_id" placeholder="optional"></label>
            <label>Cleaner ID <input type="text" v-model="filters.cleaner_id" placeholder="optional"></label>
            <div><button @click="loadSchedule">Load Schedule</button></div>
        </div>

        <schedule-table :rows="rows" @select-event="selectEvent"></schedule-table>

        <section v-if="selectedEventId" class="panel nested">
            <h3>Event {{ selectedEventId }}</h3>
            <assignment-panel :event-id="selectedEventId" @updated="loadSchedule"></assignment-panel>
            <h4>Requirement Totals</h4>
            <button @click="loadRequirementTotals">Refresh Totals</button>
            <pre>{{ JSON.stringify(requirementTotals, null, 2) }}</pre>
        </section>
    </div>
</section>
<?php
$html = (string) ob_get_clean();
render_admin_page('Cleaning Schedule', $html, [
    'https://unpkg.com/vue@3/dist/vue.global.prod.js',
    '/assets/js/components/assignment-panel.js',
    '/assets/js/components/schedule-table.js',
    '/assets/js/schedule-app.js',
]);
