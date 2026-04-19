<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

ob_start();
?>
<section class="panel">
    <div id="schedule-app">
        <p v-if="error" class="banner error">{{ error }}</p>
        <p v-if="loading" class="banner">Loading schedule...</p>

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
            <div><button @click="loadSchedule">Apply Filters</button></div>
        </div>

        <schedule-table
            :rows="rows"
            :selected-event-id="selectedEventId"
            :requirement-summary="requirementSummaryMap"
            :page="table.page"
            :page-size="table.pageSize"
            :sort-by="table.sortBy"
            :sort-dir="table.sortDir"
            :search="table.search"
            @select-event="selectEvent"
            @update:page="table.page = $event"
            @update:sortBy="table.sortBy = $event"
            @update:sortDir="table.sortDir = $event"
            @update:search="table.search = $event"
        ></schedule-table>

        <section v-if="selectedEventId" class="panel nested">
            <h3>Event {{ selectedEventId }}</h3>
            <assignment-panel :event-id="selectedEventId" @updated="loadSchedule"></assignment-panel>
            <button @click="loadRequirementTotals">Refresh Requirement Drill-down</button>
            <requirement-drilldown :event-id="selectedEventId" :items="requirementTotals"></requirement-drilldown>
        </section>
    </div>
</section>
<?php
$html = (string) ob_get_clean();
render_admin_page('Cleaning Schedule', $html, [
    'https://unpkg.com/vue@3/dist/vue.global.prod.js',
    '/assets/js/components/assignment-panel.js',
    '/assets/js/components/schedule-table.js',
    '/assets/js/components/requirement-drilldown.js',
    '/assets/js/schedule-app.js',
]);
