(function () {
    const { createApp } = Vue;

    createApp({
        components: {
            ScheduleTable: window.ScheduleTable,
            AssignmentPanel: window.AssignmentPanel,
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
                rows: [],
                selectedEventId: '',
                requirementTotals: {},
            };
        },
        methods: {
            async loadSchedule() {
                if (this.filters.view === 'day') {
                    const res = await fetch(`/schedule/day?date=${encodeURIComponent(this.filters.date)}`);
                    this.rows = await res.json();
                    return;
                }

                if (this.filters.cleaner_id) {
                    const q = new URLSearchParams({ from_date: this.filters.from_date, to_date: this.filters.to_date });
                    const res = await fetch(`/schedule/cleaner/${encodeURIComponent(this.filters.cleaner_id)}?${q.toString()}`);
                    this.rows = await res.json();
                    return;
                }

                if (this.filters.property_id) {
                    const q = new URLSearchParams({ from_date: this.filters.from_date, to_date: this.filters.to_date });
                    const res = await fetch(`/schedule/property/${encodeURIComponent(this.filters.property_id)}?${q.toString()}`);
                    this.rows = await res.json();
                    return;
                }

                const q = new URLSearchParams({ from_date: this.filters.from_date, to_date: this.filters.to_date });
                const res = await fetch(`/schedule/all?${q.toString()}`);
                this.rows = await res.json();
            },
            selectEvent(eventId) {
                this.selectedEventId = eventId;
                this.loadRequirementTotals();
            },
            async loadRequirementTotals() {
                if (!this.selectedEventId) {
                    this.requirementTotals = {};
                    return;
                }

                const res = await fetch(`/requirements/totals/events?event_ids=${encodeURIComponent(this.selectedEventId)}`);
                this.requirementTotals = await res.json();
            },
        },
        mounted() {
            this.loadSchedule();
        },
    }).mount('#schedule-app');
})();
