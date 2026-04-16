window.ScheduleTable = {
    name: 'ScheduleTable',
    props: {
        rows: {
            type: Array,
            required: true,
        },
    },
    emits: ['select-event'],
    template: `
      <section class="panel nested">
        <h3>Schedule Results</h3>
        <table>
          <thead>
            <tr>
              <th>Event Date</th><th>Property</th><th>Event Type</th><th>Status</th><th>Assignments</th><th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.cleaning_event_id || row.id">
              <td>{{ row.event_date }}</td>
              <td>{{ row.property_name || row.property_id }}</td>
              <td>{{ row.event_type }}</td>
              <td>{{ row.event_status || row.status }}</td>
              <td>{{ row.assignment_statuses || '-' }}</td>
              <td><button @click="$emit('select-event', row.cleaning_event_id || row.id)">Manage</button></td>
            </tr>
            <tr v-if="rows.length === 0"><td colspan="6">No results.</td></tr>
          </tbody>
        </table>
      </section>
    `,
};
