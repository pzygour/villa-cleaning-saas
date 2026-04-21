(function () {
    const { createApp } = Vue;
    const request = window.AdminRequest;

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
                    quickReturnId: null,
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
            showOk(message) {
                this.feedback = { type: 'ok', message };
            },
            showError(message) {
                this.feedback = { type: 'error', message };
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
            async api(path, options = {}) {
                try {
                    return await request.request(path, options);
                } catch (err) {
                    return {
                        ok: false,
                        data: null,
                        error: err?.message || 'request_failed',
                    };
                }
            },
            async bootstrapOptions() {
                this.loading.bootstrap = true;

                const [properties, locations, items] = await Promise.all([
                    this.api('/properties'),
                    this.api('/inventory/locations'),
                    this.api('/items'),
                ]);

                this.loading.bootstrap = false;

                if (!properties.ok || !locations.ok || !items.ok) {
                    this.showError('Failed to load laundry setup options.');
                    return;
                }

                this.properties = Array.isArray(properties.data) ? properties.data : [];
                this.locations = Array.isArray(locations.data) ? locations.data : [];
                this.items = Array.isArray(items.data) ? items.data : [];
            },
            async createHandover(event) {
                const button = event?.currentTarget || null;
                this.loading.create = true;

                await request.withButtonLoading(button, 'Submitting...', async () => {
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

                    const result = await this.api('/laundry/handovers', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                    });

                    if (!result.ok) {
                        this.showError(`Create handover failed: ${result.error}`);
                        return;
                    }

                    const handoverId = result.data?.handover_id || result.data?.id || '';
                    this.showOk(`Laundry handover created: ${handoverId || '-'}`);
                    this.createForm.note = '';
                    this.createForm.items = [{ item_id: '', quantity_sent: 1 }];
                    this.detailHandoverId = handoverId || this.detailHandoverId;
                    this.returnForm.handover_id = this.detailHandoverId;

                    await this.loadOpen();
                    if (this.detailHandoverId) {
                        await this.loadDetail();
                        await this.loadPending();
                    }
                });

                this.loading.create = false;
            },
            async processReturn(event) {
                if (!this.returnForm.handover_id) {
                    this.showError('Provide handover ID before submitting return.');
                    return;
                }

                const button = event?.currentTarget || null;
                this.loading.return = true;

                await request.withButtonLoading(button, 'Submitting...', async () => {
                    const payload = {
                        created_by_user_id: this.returnForm.created_by_user_id || null,
                        note: this.returnForm.note || null,
                        items: this.returnForm.items
                            .filter((x) => x.item_id && Number(x.quantity_returned) > 0)
                            .map((x) => ({ item_id: x.item_id, quantity_returned: Number(x.quantity_returned) })),
                    };

                    const result = await this.api(`/laundry/handovers/${encodeURIComponent(this.returnForm.handover_id)}/returns`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                    });

                    if (!result.ok) {
                        this.showError(`Return failed: ${result.error}`);
                        return;
                    }

                    this.showOk(`Return processed. Handover status: ${result.data?.status || 'updated'}`);
                    this.detailHandoverId = this.returnForm.handover_id;
                    await this.loadOpen();
                    await this.loadDetail();
                    await this.loadPending();
                });

                this.loading.return = false;
            },
            async loadOpen() {
                this.loading.open = true;
                const result = await this.api('/laundry/handovers/open');
                this.loading.open = false;

                if (!result.ok) {
                    this.showError(`Load open handovers failed: ${result.error}`);
                    return;
                }

                this.openHandovers = Array.isArray(result.data) ? result.data : [];
            },
            async loadDetail() {
                if (!this.detailHandoverId) return;

                this.loading.detail = true;
                const result = await this.api(`/laundry/handovers/${encodeURIComponent(this.detailHandoverId)}`);
                this.loading.detail = false;

                if (!result.ok) {
                    this.showError(`Load handover detail failed: ${result.error}`);
                    return;
                }

                const detail = result.data || null;
                this.detailData = detail;
                this.detailRows = Array.isArray(detail?.items) ? detail.items : [];
                this.returnForm.handover_id = this.detailHandoverId;
            },
            async loadPending() {
                if (!this.detailHandoverId) return;

                this.loading.pending = true;
                const result = await this.api(`/laundry/handovers/${encodeURIComponent(this.detailHandoverId)}/pending-returns`);
                this.loading.pending = false;

                if (!result.ok) {
                    this.showError(`Load pending returns failed: ${result.error}`);
                    return;
                }

                this.pendingRows = Array.isArray(result.data) ? result.data : [];
            },
            async loadPendingForReturn() {
                if (!this.returnForm.handover_id) {
                    this.showError('Enter handover ID to load pending quantities.');
                    return;
                }

                this.detailHandoverId = this.returnForm.handover_id;
                await this.loadDetail();
                await this.loadPending();

                if (this.pendingRows.length === 0) {
                    this.showOk('No pending quantities for this handover.');
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
            async quickReturnRemaining(handoverId, event) {
                const button = event?.currentTarget || null;
                this.loading.quickReturnId = handoverId;

                await request.withButtonLoading(button, 'Submitting...', async () => {
                    this.returnForm.handover_id = handoverId;
                    await this.loadPendingForReturn();
                    if (this.pendingRows.length > 0) {
                        await this.processReturn();
                    }
                });

                this.loading.quickReturnId = null;
            },
        },
        async mounted() {
            await this.bootstrapOptions();
            await this.loadOpen();
        },
    }).mount('#laundry-app');
})();
