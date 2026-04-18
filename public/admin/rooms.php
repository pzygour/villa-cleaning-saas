<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

$propertyId = (string) ($_GET['property_id'] ?? '');

ob_start();
?>
<section class="panel">
    <h3>Room List & Setup</h3>
    <p>Property ID: <code><?= htmlspecialchars($propertyId) ?></code></p>
    <div id="rooms-app" data-property-id="<?= htmlspecialchars($propertyId) ?>">
        <p v-if="feedback.message" :class="['banner', feedback.type]">{{ feedback.message }}</p>

        <form class="form-grid" @submit.prevent="saveRoom">
            <input type="hidden" v-model="roomForm.id">
            <label>Name <input type="text" v-model="roomForm.name" required></label>
            <label>Room Type <input type="text" v-model="roomForm.room_type" required></label>
            <label>Sort Order <input type="number" v-model.number="roomForm.sort_order" required></label>
            <div><button type="submit">{{ roomForm.id ? 'Update Room' : 'Create Room' }}</button></div>
            <div><button type="button" @click="resetRoomForm">Reset</button></div>
        </form>

        <div class="form-grid">
            <label>Search <input type="text" v-model="table.search" placeholder="name/type"></label>
            <label>Sort
                <select v-model="table.sortBy">
                    <option value="name">name</option>
                    <option value="room_type">room_type</option>
                    <option value="sort_order">sort_order</option>
                </select>
            </label>
            <label>Direction
                <select v-model="table.sortDir">
                    <option value="asc">asc</option>
                    <option value="desc">desc</option>
                </select>
            </label>
        </div>

        <table>
            <thead><tr><th>Name</th><th>Type</th><th>Beds</th><th>Bathrooms</th><th>Actions</th></tr></thead>
            <tbody>
                <tr v-for="room in pagedRooms" :key="room.id">
                    <td>{{ room.name }}</td>
                    <td>{{ room.room_type }}</td>
                    <td>{{ room.beds_json || '-' }}</td>
                    <td>{{ room.bathrooms_json || '-' }}</td>
                    <td>
                        <button @click="editRoom(room)">Edit</button>
                        <button @click="selectRoomForSetup(room)">Setup Beds/Bathrooms</button>
                    </td>
                </tr>
                <tr v-if="pagedRooms.length === 0"><td colspan="5">No rooms found.</td></tr>
            </tbody>
        </table>
        <div class="actions-inline">
            <button @click="prevPage" :disabled="table.page <= 1">Prev</button>
            <span>Page {{ table.page }} / {{ totalPages }}</span>
            <button @click="nextPage" :disabled="table.page >= totalPages">Next</button>
        </div>

        <room-setup-editor
            v-if="selectedRoom"
            :room="selectedRoom"
            :bed-types="bedTypes"
            :bathroom-types="bathroomTypes"
            @saved="onSetupSaved"
            @cancel="selectedRoom = null"
        ></room-setup-editor>
    </div>
</section>
<?php
$html = (string) ob_get_clean();
render_admin_page('Rooms', $html, [
    'https://unpkg.com/vue@3/dist/vue.global.prod.js',
    '/assets/js/components/room-setup-editor.js',
]);
