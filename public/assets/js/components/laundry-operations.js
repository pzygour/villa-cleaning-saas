(function () {
    const { createApp } = Vue;
    const ui = window.LaundryUi;

    createApp({
        data() {
            return {
                feedback: { type: 'ok', message: '' },
                loading: {
                    bootstrap: false,
                    create: false,
                    return: false,
                    open: false,
                    detail: false,
                    pending: false,
                },
                properties: [],
                locations: [],
                items: [],
                openHandovers: [],
                detailHandoverId: '',
                detailData: null,
                detailRows: [],
                pendingRows: [],
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
                    items: [{ item_id: '', quantity_returned: 1, pending_return_quantity: 0 }],
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
            statusClass(status) {
                if (status === 'closed' || status === 'returned') return 'row-success';
                if (status === 'partially_returned') return 'row-warning';
                return '';
            },
            addCreateItem() {
                this.createForm.items.push({ item_id: '', quantity_sent: 1 });
            },
            removeCreateItem(index) {
                this.createForm.items.splice(index, 1);
                if (this.createForm.items.length === 0) this.addCreateItem();
            },
            addReturnItem() {
                this.returnForm.items.push({ item_id: '', quantity_returned: 1, pending_return_quantity: 0 });
            },
            removeReturnItem(index) {
                this.returnForm.items.splice(index, 1);
                if (this.returnForm.items.length === 0) this.addReturnItem();
            },
            async bootstrapOptions() {
                this.loading.bootstrap = true;
                try {
                    const [properties, locations, items] = await Promise.all([
                        ui.requestJson('/properties'),
                        ui.requestJson('/inventory/locations'),
                        ui.requestJson('/items'),
                    ]);
                    this.properties = Array.isArray(properties) ? properties : [];
                    this.locations = Array.isArray(locations) ? locations : [];
                    this.items = Array.isArray(items) ? items : [];
                } finally {
                    this.loading.bootstrap = false;
                }
            },
            async createHandover() {
                this.loading.create = true;
                try {
                    const payload = {
                        property_id: this.createForm.property_id || null,
                        from_location_id: this.createForm.from_location_id,
                        to_location_id: this.createForm.to_location_id,
                        expected_return_date: this.normalizeDate(this.createForm.expected_return_date),
                        created_by_user_id: this.createForm.created_by_user_id || null,
                        note: this.createForm.note || null,
                        items: this.createForm.items
                            .filter((x) => x.item_id && Number(x.quantity_sent) > 0)
                            .map((x) => ({ item_id: x.item_id, quantity_sent: Number(x.quantity_sent) })),
                    };

                    const data = await ui.requestJson('/laundry/handovers', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                    });

                    this.setFeedback('ok', `Laundry handover created: ${data.handover_id || data.id || '-'}`);
                    this.createForm.note = '';
                    this.createForm.items = [{ item_id: '', quantity_sent: 1 }];
                    this.detailHandoverId = data.handover_id || this.detailHandoverId;
                    this.returnForm.handover_id = this.detailHandoverId;
                    await this.loadOpen();
                    if (this.detailHandoverId) {
                        await this.loadDetail();
                        await this.loadPending();
                    }
                } catch (err) {
                    this.setFeedback('error', `Create handover failed: ${err.message || 'request_failed'}`);
                } finally {
                    this.loading.create = false;
                }
            },
            async processReturn() {
                if (!this.returnForm.handover_id) {
                    this.setFeedback('error', 'Provide handover ID before submitting return.');
                    return;
                }

                this.loading.return = true;
                try {
                    const payload = {
                        created_by_user_id: this.returnForm.created_by_user_id || null,
                        note: this.returnForm.note || null,
                        items: this.returnForm.items
                            .filter((x) => x.item_id && Number(x.quantity_returned) > 0)
                            .map((x) => ({ item_id: x.item_id, quantity_returned: Number(x.quantity_returned) })),
                    };

                    const data = await ui.requestJson(`/laundry/handovers/${encodeURIComponent(this.returnForm.handover_id)}/returns`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                    });

                    this.setFeedback('ok', `Return processed. Handover status: ${data.status || 'updated'}`);
                    this.detailHandoverId = this.returnForm.handover_id;
                    await this.loadOpen();
                    await this.loadDetail();
                    await this.loadPending();
                } catch (err) {
                    this.setFeedback('error', `Return failed: ${err.message || 'request_failed'}`);
                } finally {
                    this.loading.return = false;
                }
            },
            async loadOpen() {
                this.loading.open = true;
                try {
                    const rows = await ui.requestJson('/laundry/handovers/open');
                    this.openHandovers = Array.isArray(rows) ? rows : [];
                } catch (err) {
                    this.setFeedback('error', `Load open handovers failed: ${err.message || 'request_failed'}`);
                } finally {
                    this.loading.open = false;
                }
            },
            async loadDetail() {
                if (!this.detailHandoverId) return;

                this.loading.detail = true;
                try {
                    const detail = await ui.requestJson(`/laundry/handovers/${encodeURIComponent(this.detailHandoverId)}`);
                    this.detailData = detail || null;
                    this.detailRows = Array.isArray(detail?.items) ? detail.items : [];
                    this.returnForm.handover_id = this.detailHandoverId;
                } catch (err) {
                    this.setFeedback('error', `Load handover detail failed: ${err.message || 'request_failed'}`);
                } finally {
                    this.loading.detail = false;
                }
            },
            async loadPending() {
                if (!this.detailHandoverId) return;

                this.loading.pending = true;
                try {
                    const rows = await ui.requestJson(`/laundry/handovers/${encodeURIComponent(this.detailHandoverId)}/pending-returns`);
                    this.pendingRows = Array.isArray(rows) ? rows : [];
                } catch (err) {
                    this.setFeedback('error', `Load pending returns failed: ${err.message || 'request_failed'}`);
                } finally {
                    this.loading.pending = false;
                }
            },
            async loadPendingForReturn() {
                if (!this.returnForm.handover_id) {
                    this.setFeedback('error', 'Enter handover ID to load pending quantities.');
                    return;
                }

                this.detailHandoverId = this.returnForm.handover_id;
                await this.loadDetail();
                await this.loadPending();
                if (this.pendingRows.length === 0) {
                    this.setFeedback('ok', 'No pending quantities for this handover.');
                    this.returnForm.items = [{ item_id: '', quantity_returned: 1, pending_return_quantity: 0 }];
                    return;
                }

                this.returnForm.items = this.pendingRows.map((row) => ({
                    item_id: row.item_id,
                    quantity_returned: Number(row.pending_return_quantity),
                    pending_return_quantity: Number(row.pending_return_quantity),
                }));
            },
            async openDetail(handoverId) {
                this.detailHandoverId = handoverId;
                this.returnForm.handover_id = handoverId;
                await this.loadDetail();
                await this.loadPending();
            },
            async quickReturnRemaining(handoverId) {
                this.returnForm.handover_id = handoverId;
                await this.loadPendingForReturn();
                if (this.pendingRows.length > 0) {
                    await this.processReturn();
                }
            },
        },
        async mounted() {
            await this.bootstrapOptions();
            await this.loadOpen();
        },
    }).mount('#laundry-app');
})();
