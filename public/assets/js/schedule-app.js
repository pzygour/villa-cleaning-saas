(function () {
    const { createApp } = Vue;
    const apiUrl = (path) => (typeof window.apiUrl === 'function' ? window.apiUrl(path) : path);

    createApp({
        components: {
            ScheduleTable: window.ScheduleTable,
            AssignmentPanel: window.AssignmentPanel,
            RequirementDrilldown: window.RequirementDrilldown,
        },
        data() {
            const today = new Date().toISOString().slice(0, 10);
            return {
                filters: {
                    view: 'day',
                    date: today,
                    from_date: today,
                    to_date: today,
                    property_id: '',
                    cleaner_id: '',
                },
                table: {
                    page: 1,
                    pageSize: 10,
                    sortBy: 'event_date',
                    sortDir: 'asc',
                    search: '',
                },
                rows: [],
                selectedEventId: '',
                requirementTotals: [],
                requirementSummaryMap: {},
                loading: false,
                error: '',
            };
        },
        methods: {
            buildRangeQuery() {
                return new URLSearchParams({
                    from_date: this.filters.from_date,
                    to_date: this.filters.to_date,
                }).toString();
            },
            async loadSchedule() {
                this.loading = true;
                this.error = '';
                this.table.page = 1;

                try {
                    let res;
                    if (this.filters.view === 'day') {
                        res = await fetch(apiUrl(`/schedule/day?date=${encodeURIComponent(this.filters.date)}`));
                    } else if (this.filters.cleaner_id) {
                        res = await fetch(apiUrl(`/schedule/cleaner/${encodeURIComponent(this.filters.cleaner_id)}?${this.buildRangeQuery()}`));
                    } else if (this.filters.property_id) {
                        res = await fetch(apiUrl(`/schedule/property/${encodeURIComponent(this.filters.property_id)}?${this.buildRangeQuery()}`));
                    } else {
                        res = await fetch(apiUrl(`/schedule/all?${this.buildRangeQuery()}`));
                    }

                    this.rows = await res.json();
                    await this.loadRequirementSummaryForRows();
                } catch (e) {
                    this.error = `Failed to load schedule: ${e.message || 'request_failed'}`;
                } finally {
                    this.loading = false;
                }
            },
            async loadRequirementSummaryForRows() {
                const eventIds = this.rows
                    .map((row) => row.cleaning_event_id || row.id)
                    .filter(Boolean);

                if (eventIds.length === 0) {
                    this.requirementSummaryMap = {};
                    return;
                }

                const res = await fetch(apiUrl(`/requirements/totals/events?event_ids=${encodeURIComponent(eventIds.join(','))}`));
                const totals = await res.json();
                const map = {};
                for (const row of totals) {
                    const eventId = row.cleaning_event_id;
                    if (!map[eventId]) map[eventId] = { total: 0, items: 0 };
                    map[eventId].total += Number(row.total_required || 0);
                    map[eventId].items += 1;
                }
                this.requirementSummaryMap = map;
            },
            async selectEvent(eventId) {
                this.selectedEventId = eventId;
                await this.loadRequirementTotals();
            },
            async loadRequirementTotals() {
                if (!this.selectedEventId) {
                    this.requirementTotals = [];
                    return;
                }

                const res = await fetch(apiUrl(`/requirements/totals/events?event_ids=${encodeURIComponent(this.selectedEventId)}`));
                this.requirementTotals = await res.json();
            },
        },
        mounted() {
            this.loadSchedule();
        },
    }).mount('#schedule-app');
})();
