(function () {
    const EMPTY_ROWS_TEXT = 'No requirement rows.';

    function groupByItem(rows) {
        const map = {};
        for (const row of rows || []) {
            const itemId = row.item_id || 'unknown';
            const total = Number(row.total_required || 0);
            if (!map[itemId]) {
                map[itemId] = { item_id: itemId, total_required: 0 };
            }
            map[itemId].total_required += total;
        }

        return Object.values(map).sort((a, b) => String(a.item_id).localeCompare(String(b.item_id)));
    }

    function renderTableRows(rows, colSpan = 2) {
        if (!Array.isArray(rows) || rows.length === 0) {
            return `<tr><td colspan="${colSpan}">${EMPTY_ROWS_TEXT}</td></tr>`;
        }

        return rows.map((item) => `
            <tr>
              <td>${item.item_id}</td>
              <td>${Number(item.total_required || 0).toFixed(2)}</td>
            </tr>
        `).join('');
    }

    window.RequirementDrilldownUtils = {
        groupByItem,
        renderTableRows,
    };

    window.RequirementDrilldown = {
        name: 'RequirementDrilldown',
        props: {
            eventId: { type: String, required: true },
            items: { type: Array, required: true },
        },
        computed: {
            groupedItems() {
                return groupByItem(this.items);
            },
            totalRequired() {
                return this.groupedItems.reduce((sum, row) => sum + Number(row.total_required || 0), 0);
            },
        },
        template: `
          <section class="panel nested">
            <h4>Requirement Drill-down (Event {{ eventId }})</h4>
            <p><strong>Total Required Quantity:</strong> {{ totalRequired.toFixed(2) }}</p>
            <table>
              <thead><tr><th>Item ID</th><th>Total Required</th></tr></thead>
              <tbody>
                <tr v-for="item in groupedItems" :key="item.item_id">
                  <td>{{ item.item_id }}</td>
                  <td>{{ Number(item.total_required).toFixed(2) }}</td>
                </tr>
                <tr v-if="groupedItems.length === 0"><td colspan="2">No requirement rows for this event.</td></tr>
              </tbody>
            </table>
          </section>
        `,
    };
})();
