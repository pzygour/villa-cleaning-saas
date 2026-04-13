(function () {
    const { createApp } = Vue;

    createApp({
        data() {
            return {
                feedback: { type: 'ok', message: '' },
                openHandovers: [],
                detailHandoverId: '',
                detailData: null,
                pendingData: null,
                createForm: {
                    property_id: '',
                    from_location_id: '',
                    to_location_id: '',
                    expected_return_date: '',
                    created_by_user_id: '',
                    note: '',
                    items: [{ item_id: '', quantity_sent: 1 }],
                },
                returnForm: {
                    handover_id: '',
                    created_by_user_id: '',
                    note: '',
                    items: [{ item_id: '', quantity_returned: 1 }],
                },
            };
        },
        methods: {
            setFeedback(type, message) {
                this.feedback = { type, message };
            },
            normalizeDate(value) {
                if (!value) return null;
                return `${value.replace('T', ' ')}:00`;
            },
            addCreateItem() { this.createForm.items.push({ item_id: '', quantity_sent: 1 }); },
            removeCreateItem(index) { this.createForm.items.splice(index, 1); },
            addReturnItem() { this.returnForm.items.push({ item_id: '', quantity_returned: 1 }); },
            removeReturnItem(index) { this.returnForm.items.splice(index, 1); },
            async createHandover() {
                const payload = {
                    property_id: this.createForm.property_id || null,
                    from_location_id: this.createForm.from_location_id,
                    to_location_id: this.createForm.to_location_id,
                    expected_return_date: this.normalizeDate(this.createForm.expected_return_date),
                    created_by_user_id: this.createForm.created_by_user_id || null,
                    note: this.createForm.note || null,
                    items: this.createForm.items
                        .filter((x) => x.item_id)
                        .map((x) => ({ item_id: x.item_id, quantity_sent: Number(x.quantity_sent) })),
                };

                const res = await fetch('/laundry/handovers', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload),
                });
                const data = await res.json();

                if (res.ok) {
                    this.setFeedback('ok', `Laundry handover created: ${data.handover_id}`);
                    this.createForm.items = [{ item_id: '', quantity_sent: 1 }];
                    this.createForm.note = '';
                    this.detailHandoverId = data.handover_id;
                    await this.loadOpen();
                } else {
                    this.setFeedback('error', `Create handover failed: ${data.error || 'request_failed'}`);
                }
            },
            async processReturn() {
                const payload = {
                    created_by_user_id: this.returnForm.created_by_user_id || null,
                    note: this.returnForm.note || null,
                    items: this.returnForm.items
                        .filter((x) => x.item_id)
                        .map((x) => ({ item_id: x.item_id, quantity_returned: Number(x.quantity_returned) })),
                };

                const res = await fetch(`/laundry/handovers/${encodeURIComponent(this.returnForm.handover_id)}/returns`, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload),
                });
                const data = await res.json();

                if (res.ok) {
                    this.setFeedback('ok', `Return processed. Handover status: ${data.status}`);
                    this.detailHandoverId = this.returnForm.handover_id;
                    await this.loadOpen();
                    await this.loadDetail();
                    await this.loadPending();
                } else {
                    this.setFeedback('error', `Return failed: ${data.error || 'request_failed'}`);
                }
            },
            async loadOpen() {
                const res = await fetch('/laundry/handovers/open');
                this.openHandovers = await res.json();
            },
            async loadDetail() {
                if (!this.detailHandoverId) return;
                const res = await fetch(`/laundry/handovers/${encodeURIComponent(this.detailHandoverId)}`);
                this.detailData = await res.json();
            },
            async loadPending() {
                if (!this.detailHandoverId) return;
                const res = await fetch(`/laundry/handovers/${encodeURIComponent(this.detailHandoverId)}/pending-returns`);
                this.pendingData = await res.json();
            },
        },
        mounted() {
            this.loadOpen();
        },
    }).mount('#laundry-app');
})();
