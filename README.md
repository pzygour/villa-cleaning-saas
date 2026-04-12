# Villa Cleaning SaaS

Framework-less PHP 8.1 + MySQL backend for rental property operations.

## Milestone 1-5 implemented scope

- Milestone 1 foundations: properties, rooms, bookings, cleaning generation, cleaning settings
- Milestone 2 foundations: item catalog, requirement rules, requirement snapshot recalculation
- Milestone 3 operations:
  - cleaning event assignments (multi-cleaner support)
  - assignment status workflow (`assigned`, `accepted`, `completed`, `cancelled`)
  - operational schedule query endpoints (property/all/cleaner/day)
  - requirement totals query endpoints (event/day/property-range)
  - cleaner-facing assigned events query
  - active cleaner listing for assignment use
- Milestone 4 inventory:
  - inventory locations CRUD
  - inventory ledger transactions and balances sync
  - event requirement reserve/unreserve
  - inventory balances, movements and event availability queries
- Milestone 5 laundry workflow:
  - laundry handover create with laundry-out inventory ledger effects
  - partial/full returns with laundry-in inventory ledger effects
  - handover/item status lifecycle management
  - laundry query endpoints (open/property range/detail/pending returns)

## Run Milestone 5 locally

1. Install dependencies:
   ```bash
   composer install
   ```
2. Apply base schema and migrations:
   ```bash
   mysql -u root -p villa_cleaning < database/sql/001_initial_schema.sql
   mysql -u root -p villa_cleaning < database/sql/003_cleaning_event_generation_guards.sql
   ```
3. Optional sample data:
   ```bash
   mysql -u root -p villa_cleaning < database/sql/002_seed_milestone1_sample_data.sql
   mysql -u root -p villa_cleaning < database/sql/004_seed_milestone2_rules_and_items.sql
   mysql -u root -p villa_cleaning < database/sql/005_seed_milestone3_assignments.sql
   ```
4. Configure DB env vars (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`).
5. Start server:
   ```bash
   php -S 0.0.0.0:8080 -t public
   ```

## API endpoints (JSON)

### Milestone 3 assignment + operations endpoints
- `GET /users/cleaners`
- `POST /cleaning-events/{eventId}/assignments` with `{ "user_ids": ["cleaner-uuid", "..."] }`
- `PATCH /cleaning-events/{eventId}/assignments/{userId}/status` with `{ "assignment_status": "accepted|completed|cancelled|assigned" }`
- `GET /schedule/property/{propertyId}?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD`
- `GET /schedule/all?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD`
- `GET /schedule/cleaner/{userId}?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD`
- `GET /schedule/day?date=YYYY-MM-DD`
- `GET /cleaners/{userId}/operations?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD`
- `GET /requirements/totals/events?event_ids=uuid1,uuid2`
- `GET /requirements/totals/day?date=YYYY-MM-DD&property_id={optionalPropertyId}`
- `GET /requirements/totals/property/{propertyId}?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD`

### Milestone 4 inventory endpoints
- `GET /inventory/locations?location_type={optional}`
- `POST /inventory/locations`
- `PUT /inventory/locations/{locationId}`
- `DELETE /inventory/locations/{locationId}`
- `POST /inventory/transactions`
- `POST /inventory/reservations/events/{eventId}/reserve`
- `POST /inventory/reservations/events/{eventId}/unreserve`
- `GET /inventory/balances/location/{locationId}`
- `GET /inventory/balances/item/{itemId}`
- `GET /inventory/movements/location/{locationId}?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD`
- `GET /inventory/movements/item/{itemId}?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD`
- `GET /inventory/availability/events/{eventId}?location_id={locationId}`

### Milestone 5 laundry endpoints
- `POST /laundry/handovers`
  - body:
    ```json
    {
      "property_id": "optional-property-uuid",
      "from_location_id": "uuid",
      "to_location_id": "uuid",
      "expected_return_date": "2026-05-01 12:00:00",
      "created_by_user_id": "optional-user-uuid",
      "note": "optional",
      "items": [
        {"item_id": "uuid", "quantity_sent": 20}
      ]
    }
    ```
- `POST /laundry/handovers/{handoverId}/returns`
  - body:
    ```json
    {
      "created_by_user_id": "optional-user-uuid",
      "note": "optional",
      "items": [
        {"item_id": "uuid", "quantity_returned": 5}
      ]
    }
    ```
- `GET /laundry/handovers/open`
- `GET /laundry/handovers/property/{propertyId}?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD`
- `GET /laundry/handovers/{handoverId}`
- `GET /laundry/handovers/{handoverId}/pending-returns`

## Remaining work after Milestone 5 backend

- Role-aware authentication and permissions
- Audit/reporting enhancements and pagination/filtering hardening
