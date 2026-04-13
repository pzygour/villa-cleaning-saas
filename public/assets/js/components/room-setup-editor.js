(function () {
    const { createApp } = Vue;

    const RoomSetupEditor = {
        name: 'RoomSetupEditor',
        props: {
            room: { type: Object, required: true },
            bedTypes: { type: Array, required: true },
            bathroomTypes: { type: Array, required: true },
        },
        emits: ['saved', 'cancel'],
        data() {
            return {
                beds: [],
                bathrooms: [],
                saving: false,
                message: '',
                messageType: 'ok',
            };
        },
        methods: {
            parseRows(raw) {
                if (!raw) return [];
                try {
                    const parsed = JSON.parse(raw);
                    return Array.isArray(parsed) ? parsed.map((x) => ({ id: x.id || '', quantity: Number(x.quantity || 1) })) : [];
                } catch {
                    return [];
                }
            },
            addBed() { this.beds.push({ id: '', quantity: 1 }); },
            removeBed(index) { this.beds.splice(index, 1); },
            addBathroom() { this.bathrooms.push({ id: '', quantity: 1 }); },
            removeBathroom(index) { this.bathrooms.splice(index, 1); },
            async saveSetup() {
                this.saving = true;
                this.message = '';
                const beds = this.beds.filter((x) => x.id).map((x) => ({ bedTypeId: x.id, quantity: Number(x.quantity) || 1 }));
                const bathrooms = this.bathrooms.filter((x) => x.id).map((x) => ({ bathroomTypeId: x.id, quantity: Number(x.quantity) || 1 }));

                const bedRes = await fetch(`/rooms/${this.room.id}/beds`, {
                    method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ beds }),
                });
                const bathRes = await fetch(`/rooms/${this.room.id}/bathrooms`, {
                    method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ bathrooms }),
                });

                if (bedRes.ok && bathRes.ok) {
                    this.messageType = 'ok';
                    this.message = 'Room setup updated.';
                    this.$emit('saved');
                } else {
                    this.messageType = 'error';
                    this.message = 'Failed to update room setup.';
                }
                this.saving = false;
            },
        },
        watch: {
            room: {
                immediate: true,
                handler(nextRoom) {
                    this.beds = this.parseRows(nextRoom?.beds_json);
                    this.bathrooms = this.parseRows(nextRoom?.bathrooms_json);
                    if (this.beds.length === 0) this.addBed();
                    if (this.bathrooms.length === 0) this.addBathroom();
                },
            },
        },
        template: `
          <section class="panel nested">
            <h4>Setup for {{ room.name }}</h4>
            <p v-if="message" :class="['banner', messageType]">{{ message }}</p>
            <div class="split">
              <div>
                <h5>Beds</h5>
                <div v-for="(row, idx) in beds" :key="'b-'+idx" class="row-editor">
                  <select v-model="row.id">
                    <option value="">Select bed type</option>
                    <option v-for="t in bedTypes" :value="t.id">{{ t.name }}</option>
                  </select>
                  <input type="number" min="1" v-model.number="row.quantity">
                  <button @click="removeBed(idx)">Remove</button>
                </div>
                <button @click="addBed">Add Bed Row</button>
              </div>
              <div>
                <h5>Bathrooms</h5>
                <div v-for="(row, idx) in bathrooms" :key="'ba-'+idx" class="row-editor">
                  <select v-model="row.id">
                    <option value="">Select bathroom type</option>
                    <option v-for="t in bathroomTypes" :value="t.id">{{ t.name }}</option>
                  </select>
                  <input type="number" min="1" v-model.number="row.quantity">
                  <button @click="removeBathroom(idx)">Remove</button>
                </div>
                <button @click="addBathroom">Add Bathroom Row</button>
              </div>
            </div>
            <div class="actions-inline">
              <button @click="saveSetup" :disabled="saving">Save Setup</button>
              <button @click="$emit('cancel')">Close</button>
            </div>
          </section>
        `,
    };

    const root = document.getElementById('rooms-app');
    if (!root) return;

    createApp({
        components: { RoomSetupEditor },
        data() {
            return {
                propertyId: root.dataset.propertyId || '',
                rooms: [],
                bedTypes: [],
                bathroomTypes: [],
                selectedRoom: null,
                roomForm: { id: '', name: '', room_type: '', sort_order: 0 },
                feedback: { type: 'ok', message: '' },
            };
        },
        methods: {
            setFeedback(type, message) { this.feedback = { type, message }; },
            async loadCatalogs() {
                const [bedsRes, bathsRes] = await Promise.all([
                    fetch('/setup/bed-types'),
                    fetch('/setup/bathroom-types'),
                ]);
                this.bedTypes = await bedsRes.json();
                this.bathroomTypes = await bathsRes.json();
            },
            async loadRooms() {
                if (!this.propertyId) {
                    this.setFeedback('error', 'Missing property_id in URL.');
                    return;
                }
                const res = await fetch(`/rooms?property_id=${encodeURIComponent(this.propertyId)}`);
                const json = await res.json();
                this.rooms = json.data || [];
            },
            editRoom(room) {
                this.roomForm = { id: room.id, name: room.name, room_type: room.room_type, sort_order: Number(room.sort_order || 0) };
                this.setFeedback('ok', 'Editing room metadata.');
            },
            resetRoomForm() {
                this.roomForm = { id: '', name: '', room_type: '', sort_order: 0 };
            },
            selectRoomForSetup(room) {
                this.selectedRoom = room;
                this.setFeedback('ok', `Configuring bed/bath setup for ${room.name}.`);
            },
            async saveRoom() {
                const payload = {
                    property_id: this.propertyId,
                    name: this.roomForm.name,
                    room_type: this.roomForm.room_type,
                    sort_order: this.roomForm.sort_order,
                };

                const isEdit = !!this.roomForm.id;
                const path = isEdit ? `/rooms/${this.roomForm.id}` : '/rooms';
                const method = isEdit ? 'PUT' : 'POST';
                const res = await fetch(path, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                if (res.ok) {
                    this.setFeedback('ok', 'Room saved successfully.');
                    this.resetRoomForm();
                    await this.loadRooms();
                } else {
                    const err = await res.json();
                    this.setFeedback('error', `Room save failed: ${err.error || 'request_failed'}`);
                }
            },
            async onSetupSaved() {
                await this.loadRooms();
                this.setFeedback('ok', 'Room bed/bath setup saved.');
            },
        },
        async mounted() {
            await this.loadCatalogs();
            await this.loadRooms();
        },
    }).mount('#rooms-app');
})();
