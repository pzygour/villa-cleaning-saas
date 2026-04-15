window.ScheduleTable = {
    name: 'ScheduleTable',
    props: {
        rows: { type: Array, required: true },
        selectedEventId: { type: String, default: '' },
        requirementSummary: { type: Object, default: () => ({}) },
        page: { type: Number, required: true },
        pageSize: { type: Number, required: true },
        sortBy: { type: String, required: true },
        sortDir: { type: String, required: true },
        search: { type: String, required: true },
    },
    emits: ['select-event', 'update:page', 'update:sortBy', 'update:sortDir', 'update:search'],
    computed: {
        filteredRows() {
            const q = this.search.trim().toLowerCase();
            let list = this.rows;
            if (q) {
                list = list.filter((row) => {
                    const text = [row.event_date, row.property_name, row.property_id, row.event_type, row.event_status, row.assignment_statuses].join(' ').toLowerCase();
                    return text.includes(q);
                });
            }

            const dir = this.sortDir === 'asc' ? 1 : -1;
            return [...list].sort((a, b) => {
                const av = String(a[this.sortBy] ?? '').toLowerCase();
                const bv = String(b[this.sortBy] ?? '').toLowerCase();
                if (av < bv) return -1 * dir;
                if (av > bv) return 1 * dir;
                return 0;
            });
        },
        totalPages() {
            return Math.max(1, Math.ceil(this.filteredRows.length / this.pageSize));
        },
        pagedRows() {
            const p = Math.min(this.page, this.totalPages);
            const start = (p - 1) * this.pageSize;
            return this.filteredRows.slice(start, start + this.pageSize);
        },
    },
    methods: {
        requirementLabel(eventId) {
            const summary = this.requirementSummary[eventId] || { total: 0, items: 0 };
            return `${summary.total} units / ${summary.items} items`;
        },
    },
    template: `
      <section class="panel nested">
        <h3>Schedule Results</h3>
        <div class="form-grid">
          <label>Search <input :value="search" @input="$emit('update:search', $event.target.value)" placeholder="property/date/type/status"></label>
          <label>Sort By
            <select :value="sortBy" @change="$emit('update:sortBy', $event.target.value)">
              <option value="event_date">event_date</option>
              <option value="property_name">property_name</option>
              <option value="event_type">event_type</option>
              <option value="event_status">event_status</option>
            </select>
          </label>
          <label>Direction
            <select :value="sortDir" @change="$emit('update:sortDir', $event.target.value)">
              <option value="asc">asc</option>
              <option value="desc">desc</option>
            </select>
          </label>
        </div>

        <table>
          <thead>
            <tr>
              <th>Event Date</th><th>Property</th><th>Type</th><th>Status</th><th>Assignments</th><th>Requirements</th><th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in pagedRows"
              :key="row.cleaning_event_id || row.id"
              :class="{ 'row-selected': selectedEventId === (row.cleaning_event_id || row.id) }"
            >
              <td>{{ row.event_date }}</td>
              <td>{{ row.property_name || row.property_id }}</td>
              <td>{{ row.event_type }}</td>
              <td>{{ row.event_status || row.status }}</td>
              <td>{{ row.assignment_statuses || '-' }}</td>
              <td>{{ requirementLabel(row.cleaning_event_id || row.id) }}</td>
              <td><button @click="$emit('select-event', row.cleaning_event_id || row.id)">Open</button></td>
            </tr>
            <tr v-if="pagedRows.length === 0"><td colspan="7">No results.</td></tr>
          </tbody>
        </table>

        <div class="actions-inline">
          <button :disabled="page <= 1" @click="$emit('update:page', page - 1)">Prev</button>
          <span>Page {{ page }} / {{ totalPages }}</span>
          <button :disabled="page >= totalPages" @click="$emit('update:page', page + 1)">Next</button>
        </div>
      </section>
    `,
};
