window.AssignmentPanel = {
    name: 'AssignmentPanel',
    props: {
        eventId: {
            type: String,
            required: true,
        },
    },
    emits: ['updated'],
    data() {
        return {
            userIdsCsv: '',
            statusUserId: '',
            status: 'accepted',
            message: '',
        };
    },
    methods: {
        async assign() {
            const userIds = this.userIdsCsv.split(',').map((v) => v.trim()).filter(Boolean);
            const res = await fetch(window.apiUrl(`/cleaning-events/${this.eventId}/assignments`), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_ids: userIds }),
            });
            this.message = res.ok ? 'Assigned.' : 'Assignment failed.';
            this.$emit('updated');
        },
        async updateStatus() {
            const res = await fetch(window.apiUrl(`/cleaning-events/${this.eventId}/assignments/${this.statusUserId}/status`), {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ assignment_status: this.status }),
            });
            this.message = res.ok ? 'Status updated.' : 'Status update failed.';
            this.$emit('updated');
        },
    },
    template: `
      <div>
        <div class="form-grid">
          <label class="full">Assign Cleaner IDs (comma separated)
            <input type="text" v-model="userIdsCsv" placeholder="uuid1,uuid2">
          </label>
          <div><button @click="assign">Assign</button></div>
        </div>
        <div class="form-grid">
          <label>User ID <input type="text" v-model="statusUserId"></label>
          <label>Status
            <select v-model="status">
              <option value="assigned">assigned</option>
              <option value="accepted">accepted</option>
              <option value="completed">completed</option>
              <option value="cancelled">cancelled</option>
            </select>
          </label>
          <div><button @click="updateStatus">Update Status</button></div>
        </div>
        <p class="message">{{ message }}</p>
      </div>
    `,
};
