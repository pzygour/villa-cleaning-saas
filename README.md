# Villa Cleaning SaaS

Framework-less PHP 8.1 + MySQL backend for rental property operations.

## Milestone 1-2 implemented scope

- Property module (CRUD-ready service/repository/controller path)
- Room module (CRUD-ready + room bed/bathroom assignments)
- Bed/Bathroom setup reads (active catalogs)
- Booking module (create/update/list/cancel + period queries)
- Property cleaning settings module (upsert/read)
- Cleaning schedule generation module with deterministic regeneration
  - event types: `arrival`, `departure`, `departure_arrival`, `mid_stay`
  - property settings override defaults
  - safe rerun strategy: delete system-generated events in range, then rebuild
- Item catalog module (CRUD-ready)
- Bed type item rule module (CRUD-ready)
- Bathroom type item rule module (CRUD-ready)
- Guest item rule module (CRUD-ready with global/property override behavior)
- Requirement calculation + snapshot persistence
  - recalculate single event
  - recalculate property/date range
  - deterministic transactional delete/rebuild of `cleaning_event_requirements`
  - MVP `departure_arrival` behavior: bed/bath rules applied, guest rules skipped when `booking_id` is `NULL`

## Run Milestone 2 locally

1. Install dependencies:
   ```bash
   composer install
   ```
2. Create database and apply schema:
   ```bash
   mysql -u root -p villa_cleaning < database/sql/001_initial_schema.sql
   ```
3. Apply safety guard migration:
   ```bash
   mysql -u root -p villa_cleaning < database/sql/003_cleaning_event_generation_guards.sql
   ```
4. Optional sample data for Milestone 1 and 2:
   ```bash
   mysql -u root -p villa_cleaning < database/sql/002_seed_milestone1_sample_data.sql
   mysql -u root -p villa_cleaning < database/sql/004_seed_milestone2_rules_and_items.sql
   ```
5. Configure DB env vars (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`).
6. Start PHP built-in server:
   ```bash
   php -S 0.0.0.0:8080 -t public
   ```

## API endpoints (JSON)

### Milestone 1
- `GET /properties`
- `POST /properties`
- `PUT /properties/{id}`
- `DELETE /properties/{id}`
- `GET /rooms?property_id={propertyId}`
- `POST /rooms`
- `PUT /rooms/{id}`
- `PUT /rooms/{id}/beds`
- `PUT /rooms/{id}/bathrooms`
- `GET /setup/bed-types`
- `GET /setup/bathroom-types`
- `GET /bookings?property_id={propertyId}&from_date=YYYY-MM-DD&to_date=YYYY-MM-DD`
- `POST /bookings`
- `PUT /bookings/{id}`
- `POST /bookings/{id}/cancel`
- `GET /properties/{propertyId}/cleaning-settings`
- `PUT /properties/{propertyId}/cleaning-settings`
- `POST /properties/{propertyId}/cleaning-events/regenerate`
- `GET /properties/{propertyId}/cleaning-events?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD`

### Milestone 2
- `GET /items?item_type=linen|towel`
- `POST /items`
- `PUT /items/{id}`
- `DELETE /items/{id}`
- `GET /rules/bed-type-items?trigger_type=arrival|departure|departure_arrival|mid_stay`
- `POST /rules/bed-type-items`
- `PUT /rules/bed-type-items/{id}`
- `DELETE /rules/bed-type-items/{id}`
- `GET /rules/bathroom-type-items?trigger_type=...`
- `POST /rules/bathroom-type-items`
- `PUT /rules/bathroom-type-items/{id}`
- `DELETE /rules/bathroom-type-items/{id}`
- `GET /rules/guest-items?trigger_type=...`
- `POST /rules/guest-items`
- `PUT /rules/guest-items/{id}`
- `DELETE /rules/guest-items/{id}`
- `POST /requirements/events/{eventId}/recalculate`
- `POST /requirements/properties/{propertyId}/recalculate` with `{ "from_date": "YYYY-MM-DD", "to_date": "YYYY-MM-DD" }`

## Remaining work for Milestone 3

- Inventory reservation/pick/adjustment workflows
- Laundry handover and returns workflows
- Role-aware authentication/authorization
- Operational dashboard/reporting read models
- API hardening (pagination, filtering standards, idempotency keys where needed)
