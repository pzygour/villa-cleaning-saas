window.RequirementDrilldown = {
    name: 'RequirementDrilldown',
    props: {
        eventId: { type: String, required: true },
        items: { type: Array, required: true },
    },
    computed: {
        sortedItems() {
            return [...this.items].sort((a, b) => String(a.item_id).localeCompare(String(b.item_id)));
        },
    },
    template: `
      <section class="panel nested">
        <h4>Requirement Drill-down (Event {{ eventId }})</h4>
        <table>
          <thead><tr><th>Item ID</th><th>Total Required</th></tr></thead>
          <tbody>
            <tr v-for="item in sortedItems" :key="item.item_id">
              <td>{{ item.item_id }}</td>
              <td>{{ item.total_required }}</td>
            </tr>
            <tr v-if="sortedItems.length === 0"><td colspan="2">No requirement rows for this event.</td></tr>
          </tbody>
        </table>
      </section>
    `,
};
