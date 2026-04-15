(function () {
    const { createApp } = Vue;

    createApp({
        data() {
            return {
                feedback: { type: 'ok', message: '' },
                openHandovers: [],
                detailHandoverId: '',
                detailData: null,
                detailRows: [],
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
            statusClass(status) {
                if (status === 'closed' || status === 'returned') return 'row-success';
                if (status === 'partially_returned') return 'row-warning';
                return '';
            },
            openDetail(handoverId) {
                this.detailHandoverId = handoverId;
                this.loadDetail();
                this.loadPending();
            },
            async loadOpen() {
                const res = await fetch('/laundry/handovers/open');
                this.openHandovers = await res.json();
            },
            async loadDetail() {
                if (!this.detailHandoverId) return;
                const res = await fetch(`/laundry/handovers/${encodeURIComponent(this.detailHandoverId)}`);
                this.detailData = await res.json();
                this.detailRows = this.detailData.items || [];
            },
            async loadPending() {
                if (!this.detailHandoverId) return;
                const res = await fetch(`/laundry/handovers/${encodeURIComponent(this.detailHandoverId)}/pending-returns`);
                this.pendingData = await res.json();
            },
            async quickReturnRemaining(handoverId) {
                const res = await fetch(`/laundry/handovers/${encodeURIComponent(handoverId)}/pending-returns`);
                const pending = await res.json();
                if (!Array.isArray(pending) || pending.length === 0) {
                    this.setFeedback('ok', 'No pending quantities to return.');
                    return;
                }
                this.returnForm.handover_id = handoverId;
                this.returnForm.items = pending.map((row) => ({
                    item_id: row.item_id,
                    quantity_returned: Number(row.pending_return_quantity),
                }));
                await this.processReturn();
            },
            async quickReturnHalf(handoverId) {
                const res = await fetch(`/laundry/handovers/${encodeURIComponent(handoverId)}/pending-returns`);
                const pending = await res.json();
                if (!Array.isArray(pending) || pending.length === 0) {
                    this.setFeedback('ok', 'No pending quantities to return.');
                    return;
                }
                this.returnForm.handover_id = handoverId;
                this.returnForm.items = pending.map((row) => ({
                    item_id: row.item_id,
                    quantity_returned: Math.max(0.01, Number(row.pending_return_quantity) / 2),
                }));
                await this.processReturn();
            },
        },
        mounted() {
            this.loadOpen();
        },
    }).mount('#laundry-app');
})();
